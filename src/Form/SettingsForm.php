<?php

namespace Drupal\metadata_hex\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\file\Entity\File;
use Drupal\metadata_hex\Handler\FileHandlerInterface;
use Drupal\metadata_hex\Service\MetadataBatchProcessor;
use Drupal\metadata_hex\Service\MetadataExtractor;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SettingsForm extends ConfigFormBase {

  /**
   * Metadata Batch PRocessor
   * @var MetadataBatchProcessor
   */
  protected $batchProcessor;

  /**
   * Metadata Extractor
   * @var MetadataExtractor
   */
  protected $metadataExtractor;

  /**
   * Messenger interface
   * @var MessengerInterface
   */
  protected $messenger;

  /**
   * Get all supported extensions from registered FileHandler plugins.
   *
   * @return array
   *   A unique list of all supported file extensions.
   */
  private function getAllSupportedExtensions(): array {
      $extensions = [];
      $mime_types = [];
  
      // Get the FileHandler plugin manager.
      $plugin_manager = \Drupal::service('plugin.manager.metadata_hex');
  
      // Load all plugin definitions (registered handlers).
      $definitions = $plugin_manager->getDefinitions();
  
      // Loop through each plugin, instantiate it, and get its supported extensions.
      foreach ($definitions as $plugin_id => $definition) {
          /** @var FileHandlerInterface $plugin */
          $plugin = $plugin_manager->createInstance($plugin_id);
  
          // Merge extensions from this handler.
          $extensions = array_merge($extensions, $plugin->getSupportedExtentions());
          $mime_types = array_merge($mime_types, $plugin->getSupportedMimeTypes());
      }
  
      return ['extensions'=> array_unique($extensions),'mime_types'=> array_unique($mime_types)];
  }
  
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),  // Inject config.factory
      $container->get('config.typed'),  // Inject typed config manager
      $container->get('metadata_hex.metadata_batch_processor'),
      $container->get('metadata_hex.metadata_extractor'),
      $container->get('messenger')
    );
  }

  /**
   * Constructs the settings form.
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    TypedConfigManagerInterface $typedConfigManager, // Required by parent
    MetadataBatchProcessor $batchProcessor,
    MetadataExtractor $metadataExtractor,
    MessengerInterface $messenger
  ) {
    parent::__construct($configFactory, $typedConfigManager);
    $this->batchProcessor = $batchProcessor;
    $this->metadataExtractor = $metadataExtractor;
    $this->messenger = $messenger;
  }

  /**
   * Submit handler for processing all selected node types.
   */
  public function processAllNodes(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('metadata_hex.settings');
    $config->set('node_process.bundle_types', $form_state->getValue('bundle_types'));
    $config->set('node_process.allow_reprocess', $form_state->getValue('allow_reprocess'));
    $config->save();
  
    $selectedNodeTypes = $form_state->getValue('bundle_types');
    $willReprocess = $form_state->getValue('allow_reprocess') ?? FALSE;

    if (!empty($selectedNodeTypes)) {
        // Define batch operations
        $operations = [];
        foreach ($selectedNodeTypes as $bundleType) {
            $operations[] = [
                ['Drupal\metadata_hex\Service\MetadataBatchProcessor', 'processNodes'],
                [$bundleType, $willReprocess],
            ];
        }

        // Define batch
        $batch = [
            'title' => $this->t('Processing Nodes for Metadata'),
            'operations' => $operations,
            'init_message' => $this->t('Starting metadata processing...'),
            'progress_message' => $this->t('Processing...'),
            'error_message' => $this->t('Metadata processing encountered an error.'),
            'finished' => ['MetadataBatch', 'batchFinished'],
          ];

        batch_set($batch);
    } else {
        $this->messenger->addWarning($this->t('No node types selected for processing.'));
    }

    batch_process();

    return $batch;
  }

  /**
   * Scans a directory for files.
   *
   * @param string $dir_to_scan
   *   Directory to scan.
   */
  public function ingestFiles(string $dir_to_scan)
  {  
    $file_system = \Drupal::service('file_system');
    $files = [];
    $directory = "public://$dir_to_scan"; 
    $real_path = $file_system->realpath($directory);
    $root_files = scandir($real_path);
    $extensions = $this->getAllSupportedExtensions()['extensions'] ?? [];
    
    foreach ($root_files as $file) {
      $file_extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

      if (in_array($file_extension, $extensions, true)) {
                $files[] = "$dir_to_scan$file";
      }
    }
    return $files;
  }

 /**
   * Retrieve file IDs of orphaned files with specified extensions.
   *
   * @return array
   *   Array of orphaned file IDs.
   */
  protected function getOrphanedFiles() {
    $connection = Database::getConnection();
    $mime_types = $this->getAllSupportedExtensions()['mime_types'] ?? [];

    // Subquery to get all used file IDs
$subquery = $connection->select('file_usage', 'fu')
    ->fields('fu', ['fid']); // This retrieves all file IDs that are referenced

// Main query to get orphaned files
$query = $connection->select('file_managed', 'f')
    ->fields('f', ['fid'])
    ->condition('f.fid', $subquery, 'NOT IN')  // Exclude files that are in use
    ->condition('f.filemime', $mime_types, 'IN'); // Filter by MIME type

return $query->execute()->fetchCol();
  }

  /**
   * Submit handler for processing all selected node types.
   */
  public function processAllFiles(array &$form, FormStateInterface $form_state) {

    $ingest_dir = $form_state->getValue('ingest_directory');
    $config = $this->configFactory->getEditable('metadata_hex.settings');
    $config->set('file_ingest.bundle_type_for_generation', $form_state->getValue('bundle_type_for_generation'));
    $config->set('file_ingest.file_attachment_field', $form_state->getValue('file_attachment_field'));
    $config->set('file_ingest.ingest_directory', $ingest_dir);
    $config->save();

    $files = $this->ingestFiles($ingest_dir);
    $fids = [];

    // Iterate over file URIs and ensure a Drupal file entity exists.
    foreach ($files as $uri) {
      $fid = $this->getFidFromUri($uri);

      if ($fid) {
        $fids[] = $fid;
      }
      else {
        $new_file = File::create([
          'uri' => $uri,
          'status' => 1,
        ]);
        $new_file->save();
        $fids[] = $new_file->id();
      }
    }

    // Find orphaned files of allowed extensions.
    $orphaned_fids = $this->getOrphanedFiles();

    // Merge, deduplicate, and return unique file IDs.
    $batch_fids = array_unique(array_merge($fids, $orphaned_fids));

    $operations[] = [
      ['Drupal\metadata_hex\Service\MetadataBatchProcessor', 'processFiles'],
      [$batch_fids, true],
    ];

    // Define batch
    $batch = [
      'title' => $this->t('Ingesting Files for metadata processing'),
      'operations' => $operations,
      'init_message' => $this->t('Starting file ingestion...'),
      'progress_message' => $this->t('Processing...'),
      'error_message' => $this->t('File ingest or Metadata processing encountered an error.'),
      'finished' => ['MetadataBatch', 'batchFinished'],
    ]; 

    batch_set($batch);
    batch_process();

    return $batch;
  } 

  /**
   * Get the fid from a file uri
   * 
   * @param mixed $uri
   * @return int|null
   */
  public function getFidFromUri($uri) {
    $connection = Database::getConnection();
    $query = $connection->select('file_managed', 'fm')
      ->fields('fm', ['fid'])
      ->condition('uri', $uri)
      ->execute()
      ->fetchField();
  
    return $query ? (int) $query : NULL;
  }

  /**
  * {@inheritdoc}
  */
  protected function getEditableConfigNames() {
    return ['metadata_hex.settings'];
  }
  
  /**
  * {@inheritdoc}
  */
  public function getFormId() {
    return 'metadata_hex_settings_form';
  }
  
  /**
  * Builds the settings form.
  *
  * @param array $form
  *   The form structure.
  * @param FormStateInterface $form_state
  *   The current form state.
  *
  * @return array
  *   The form structure.
  */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $config = $this->config('metadata_hex.settings');
    $form['settings'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Configuration Sections'),
    ];
    
    $node_storage = \Drupal::entityTypeManager()->getStorage('node_type');
    $content_types = $node_storage ? $node_storage->loadMultiple() : [];

    // Ensure it's an array
    if (!is_array($content_types)) {
      $content_types = [];
    }

    $options = [];
    foreach ($content_types as $content_type) {
      $options[$content_type->id()] = $content_type->label();
    }

    $form['extraction_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Extraction'),
      '#open' => true,
      '#group' => 'settings',
    ];
    
    $form['extraction_settings']['hook_node_types'] = [
      '#type' => 'select',
      '#title' => $this->t('Bundle types to hook'),
      '#multiple' => TRUE,
      '#options' => $options,
      '#default_value' => $config->get('extraction_settings.hook_node_types') ?? [],
      '#description' => $this->t('Select the node bundle types to preprocess on update or create.'),
    ];

    $form['extraction_settings']['field_mappings'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Field mappings (File to Drupal)'),
      '#default_value' => $config->get('extraction_settings.field_mappings'),
      '#description' => $this->t('Enter each field mapping in the format "file_field | drupal_field" on a new line.'),
    ];

    $form['extraction_settings']['flatten_keys'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Flatten metadata keys containing colons'),
      '#default_value' => $config->get('extraction_settings.flatten_keys') ?? FALSE,
      '#description' => $this->t('Enable this option to flatten keys containing colons: key(pdfx:title) becomes key(title).'),
    ];

    $form['extraction_settings']['strict_handling'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable strict handling'),
      '#default_value' => $config->get('extraction_settings.strict_handling') ?? FALSE,
      '#description' => $this->t('When enabled, handling will bypass case/syntax transforms'),
    ];

    $form['extraction_settings']['data_protected'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Protect existing data from being overwritten'),
      '#default_value' => $config->get('extraction_settings.data_protected') ?? TRUE,
      '#description' => $this->t('Prevent the system from overwriting existing data during processing.'),
    ];

    $form['extraction_settings']['title_protected'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Protect title from being overwritten'),
      '#default_value' => $config->get('extraction_settings.title_protected') ?? TRUE,
      '#description' => $this->t('Ensure that the node title remains unchanged during processing.'),
    ];

    $form['node_process'] = [
      '#type' => 'details',
      '#title' => $this->t('Node Bulk Processing'),
      '#open' => false,
      '#group' => 'settings',
    ];
    
    $form['node_process']['bundle_types'] = [
      '#type' => 'select',
      '#title' => $this->t('Bundle types to process'),
      '#multiple' => TRUE,
      '#options' => $options,
      '#default_value' => $config->get('node_process.bundle_types') ?? [],
      '#description' => $this->t('Select multiple bundle types to indicate which types will be preprocessed for file metadata extraction.'),
    ];
    
    $form['node_process']['allow_reprocess'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow reprocessing of content'),
      '#default_value' => $config->get('node_process.allow_reprocess'),
    ];
    
    $form['node_process']['process_nodes'] = [
      '#type' => 'submit',
      '#value' => $this->t('Manually process all selected node types'),
      '#submit' => ['::processAllNodes'],
    ];
    
    $form['file_ingest'] = [
      '#type' => 'details',
      '#title' => $this->t('File Ingest'),
      '#open' => false,
      '#group' => 'settings',
    ];
    
    $form['file_ingest']['bundle_type_for_generation'] = [
      '#type' => 'select',
      '#title' => $this->t('Bundle type for content generation'),
      '#options' => $options,
      '#multiple' => false,
      '#default_value' => implode("\n", $config->get('node_process.bundle_types') ?? []),
      '#description' => $this->t('The node bundle type that will be created on ingest.'),
    ];
    
    $form['file_ingest']['file_attachment_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Field to attaching files to'),
      '#default_value' => $config->get('file_ingest.file_attachment_field'),
    ];
    
    $form['file_ingest']['ingest_directory'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Directory for file ingestion'),
      '#default_value' => $config->get('file_ingest.ingest_directory'),
      '#description' => $this->t('Enter the directory path where files should be ingested from.'),
    ];
    
    $form['file_ingest']['process_cron_nodes'] = [
      '#type' => 'submit',
      '#value' => $this->t('Ingest files'),
      '#submit' => ['::processAllFiles'], 
    ];
    
    return $form; 
  }
  
  /**
  * {@inheritdoc}
  */
  public function validateForm(array &$form, FormStateInterface $form_state)
  {

    $fieldMappings = $form_state->getValue('field_mappings');
    $validator = new FormValidator($fieldMappings);
    $result = $validator->validateForm();
    
    if ($result !== true) {
      $form_state->setErrorByName('field_mappings', $result);
    }
  }
  
  /**
  * Submits the settings form.
  *
  * @param array $form
  *   The form structure.
  * @param FormStateInterface $form_state
  *   The form state.
  */
public function submitForm(array &$form, FormStateInterface $formState) {
    $config = $this->config('metadata_hex.settings');

    $config->set('extraction_settings.hook_node_types', $formState->getValue('hook_node_types', []));
    $config->set('extraction_settings.field_mappings', $formState->getValue('field_mappings', ''));
    $config->set('extraction_settings.strict_handling', $formState->getValue('strict_handling', FALSE));
    $config->set('extraction_settings.flatten_keys', $formState->getValue('flatten_keys', FALSE));
    $config->set('extraction_settings.data_protected', $formState->getValue('data_protected', FALSE));
    $config->set('extraction_settings.title_protected', $formState->getValue('title_protected', FALSE));

    $config->set('node_process.bundle_types', $formState->getValue('bundle_types', []));
    $config->set('node_process.allow_reprocess', $formState->getValue('allow_reprocess', FALSE));

    $config->set('file_ingest.bundle_type_for_generation', $formState->getValue('bundle_type_for_generation', ''));
    $config->set('file_ingest.file_attachment_field', $formState->getValue('file_attachment_field', ''));
    $config->set('file_ingest.ingest_directory', $formState->getValue('ingest_directory', ''));

    $config->save();
    parent::submitForm($form, $formState); // This ensures default form handling works.
  }
  
}

class FormValidator
{
  /**
   * @var string
   */
  private $fieldMappings;

  /**
   * 
   */
  public function __construct($fieldMappings)
  {
    $this->fieldMappings = $fieldMappings;
  }

  /**
   * Validates the format of field mappings.
   * 
   * Each mapping must either be empty or in the format 'key|value'.
   * Returns true if valid, or an error message if invalid.
   */
  public function validateForm()
  {
    // Split input into lines
    $lines = explode("\n", $this->fieldMappings);
    $lineNumber = 0;

    foreach ($lines as $line) {
      $lineNumber++;
      $line = trim($line);
      if (!empty($line)) {
        // Check if line contains exactly one '|'
        if (substr_count($line, '|') != 1) {
          return "Error on line $lineNumber: Each entry must be in the format 'key|value'.";
        }
        // Further split the line to check for non-empty key and value
        list($key, $value) = explode('|', $line, 2);
        if (trim($key) === '' || trim($value) === '') {
          return "Error on line $lineNumber: Neither key nor value can be empty.";
        }
      }
    }

    return true;
  }
}