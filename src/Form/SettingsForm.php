<?php

namespace Drupal\metadata_hex\Form;

use Drupal\metadata_hex\Service\MetadataBatchProcessor;
use Drupal\metadata_hex\Service\MetadataExtractor;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;

class SettingsForm extends ConfigFormBase {

  protected $batchProcessor;
  protected $metadataExtractor;
  protected $messenger;
//protected $typedConfigManager;// must be Drupal\Core\Config\TypedConfigManagerInterface
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),  // ✅ Inject config.factory
      $container->get('config.typed'),  // ✅ Inject typed config manager
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
    TypedConfigManagerInterface $typedConfigManager, // ✅ Required by parent
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
    $config = $this->configFactory->getEditable('metadata_hex.settings'); // ✅ This makes it writable.
    $config->set('node_process.bundle_types', $form_state->getValue('node_process.bundle_types'));
    $config->set('node_process.allow_reprocess', $form_state->getValue('node_process.allow_reprocess'));
    $config->save();
    
    $selectedNodeTypes = $form_state->getValue('node_process.bundle_types');
    $willReprocess = $form_state->getValue('node_process.allow_reprocess') ?? FALSE;

    if (!empty($selectedNodeTypes)) {
      foreach ($selectedNodeTypes as $bundleType) {
        $this->batchProcessor->init($bundleType, TRUE, $willReprocess)->processNodes();
      }
      $this->messenger->addStatus($this->t('Metadata processing started for selected node types.'));
    } else {
      $this->messenger->addWarning($this->t('No node types selected for processing.'));
    }
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

    // Unset the default submit button.
    //unset($form['actions']['submit']);
    $config = $this->config('metadata_hex.settings');
    $form['settings'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Configuration Sections'),
    ];
    
    $node_storage = \Drupal::entityTypeManager()->getStorage('node_type');
    $content_types = $node_storage ? $node_storage->loadMultiple() : null;
    $options = [];
    foreach ($content_types as $content_type) {
      $options[$content_type->id()] = $content_type->label();
    }
  
    //$fileHandlerManager = \Drupal::service('metadata_hex.file_handler_manager');
    //$extensions = $fileHandlerManager->getAvailableExtentions();

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

    $form['extraction_settings']['available_extensions'] = [
      '#type' => 'textarea',
      '#access' => FALSE,
      '#title' => $this->t('Enabled file extensions'),
      '#default_value' => $config->get('extraction_settings.available_extensions') ?? $extensions,
      '#description' => $this->t('Enter allowed file extensions, one per line.'),
    ];

    // $form['extraction_settings']['actions']['submit'] = [
    //   '#type' => 'submit',
    //   '#value' => $this->t('Save configuration'),
    //   '#attributes' => [
    //     'class' => ['button', 'button--primary'],
    //   ],
    // ];

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
      '#submit' => ['::processAllPdfs'], // @todo fix this
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
      \Drupal::logger('metadata_hex')->notice('🔍 submitForm() triggered.');
    error_log("🔍 submitForm() triggered.");
    parent::submitForm($form, $formState); // This ensures default form handling works.
    $config = $this->config('metadata_hex.settings');

    $config->set('extraction_settings.hook_node_types', $formState->getValue('extraction_settings.hook_node_types', []));
    $config->set('extraction_settings.field_mappings', $formState->getValue('extraction_settings.field_mappings', ''));
    $config->set('extraction_settings.strict_handling', $formState->getValue('extraction_settings.strict_handling', FALSE));
    $config->set('extraction_settings.flatten_keys', $formState->getValue('extraction_settings.flatten_keys', FALSE));
    $config->set('extraction_settings.data_protected', $formState->getValue('extraction_settings.data_protected', FALSE));
    $config->set('extraction_settings.title_protected', $formState->getValue('extraction_settings.title_protected', FALSE));
    $config->set('extraction_settings.available_extensions', $formState->getValue('extraction_settings.available_extensions', ''));

    $config->set('node_process.bundle_types', $formState->getValue('node_process.bundle_types', []));
    $config->set('node_process.allow_reprocess', $formState->getValue('node_process.allow_reprocess', FALSE));

    $config->set('file_ingest.bundle_type_for_generation', $formState->getValue('file_ingest.bundle_type_for_generation', ''));
    $config->set('file_ingest.file_attachment_field', $formState->getValue('file_ingest.file_attachment_field', ''));
    $config->set('file_ingest.ingest_directory', $formState->getValue('file_ingest.ingest_directory', ''));

    $config->save();
        \Drupal::logger('metadata_hex')->notice('💾 Config saved successfully.');
    error_log("💾 Config saved successfully.");

  }
  /**
   * Submit handler for processing all selected node types.
   */
  // public function processAllNodes(array &$form, FormStateInterface $form_state) {
  //   $selectedNodeTypes = $form_state->getValue('node_process.bundle_types');
  //   $willReprocess = $form_state->getValue('node_process.allow_reprocess')??FALSE;
  //   if (!empty($selectedNodeTypes)) {
  //     foreach ($selectedNodeTypes as $bundleType) {
  //       $me = new MetadataExtractor();
  //       $np = new MetadataBatchProcessor($this->logger, $me)->init($bundleType, TRUE, $willReprocess)->processNodes();
  //     }
  //     $this->messenger->addStatus($this->t('Metadata processing started for selected node types.'));
  //   }
  //   else {
  //     $this->messenger->addWarning($this->t('No node types selected for processing.'));
  //   }
  // }
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
