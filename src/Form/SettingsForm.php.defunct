<?php

namespace Drupal\pdf_meta_extraction\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\node\Entity\NodeType;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\pdf_meta_extraction\ProcessPdf;

/**
 * Configure PDF Meta Extraction settings for this site.
 */
class SettingsForm extends ConfigFormBase
{

  private $mapping;

  private $debug;

  public function _construct()
  {

    $this->mapping = [];
    $this->debug = true;

  }

  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'pdf_meta_extraction_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames()
  {
    return ['pdf_meta_extraction.settings'];
  }

  function getContentTypes()
  {
    $config = \Drupal::config('pdf_meta_extraction.settings');
    $selected_types = $config->get('field_mappings');
    return $selected_types;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $config = $this->config('pdf_meta_extraction.settings');

    // Extraction settings group.
    $form['extraction_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Metadata Extraction Settings'),
      '#attributes' => [
        'class' => ['extraction-settings'],
      ],
    ];

    // Textarea for field mappings.
    $form['extraction_settings']['field_mappings'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Field Mappings'),
      '#default_value' => $config->get('field_mappings'),
      '#description' => $this->t('Enter each field mappings in the format "pdf_field | drupal_field" on a new line.'),
    ];

    $form['extraction_settings']['overwrite'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow overwriting of existing data'),
      '#default_value' => $config->get('overwrite', TRUE),
      '#description' => $this->t('Enabling this option allows existing data to be overwritten by this module. This option is enabled by default.'),
    ];

    $form['extraction_settings']['extract_body'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Extract PDF content to node body'),
      '#default_value' => $config->get('extract_body', TRUE),
      '#description' => $this->t('Enabling this option allows the module parser to grab the <strong>unformatted</strong> pdf body content and push it to the node. <span class="marker">(Not Recommended)</span>'),
    ];


    // Hook settings group.
    $form['hook_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Processing Options'),
      '#attributes' => [
        'class' => ['hook-settings'],
      ],
      '#description' => 'Title will not be replaced when creating a blank node through the insert hook.'
    ];

    // Multiselect for selecting content types.
    $content_types = NodeType::loadMultiple();
    $options = [];
    foreach ($content_types as $content_type) {
      $options[$content_type->id()] = $content_type->label();
    }

    $form['hook_settings']['content_types'] = [
      '#type' => 'select',
      '#title' => $this->t('Node Type for Processing'),
      '#multiple' => TRUE,
      '#options' => $options,
      '#default_value' => $config->get('content_types'),
      '#description' => $this->t('Select multiple node bundle types to indicate which types will be preprocessed for PDF metadata extraction.'),
    ];

    $form['hook_settings']['reprocess'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow reprocessing'),
      '#default_value' => $config->get('reprocess', false),
      '#description' => $this->t('By default, each content type will be processed once. Enabling this option allows the module parser to reprocess previously processed nodes. <span class="marker">(Existing data will revert to initial Pdf extracted values)</span>'),
    ];

    $form['hook_settings']['process_nodes'] = [
      '#type' => 'submit',
      '#value' => $this->t('Manually process all selected node types'),
      '#submit' => ['::processAllNodes'],
    ];

    // Cron settings group.
    $form['cron_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Cron Settings'),
      '#attributes' => [
        'class' => ['cron-settings'],
      ],
    ];

    $form['cron_settings']['content_type_for_create'] = [
      '#type' => 'select',
      '#title' => $this->t('Node type for PDF Content Generation'),
      '#multiple' => FALSE,
      '#options' => $options,
      '#default_value' => $config->get('content_type_for_create'),
      '#description' => $this->t('Select a single node type to indicate which type new content should be generated from PDF data during cron runs.'),
    ];

    $form['cron_settings']['field_name_for_create'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Field to attach PDFs to on cron create'),
      '#default_value' => $config->get('field_name_for_create'),
      '#description' => $this->t('Enter the field name where PDFs should be saved when new content is generated during cron runs.'),
    ];
    $form['cron_settings']['body_field_name_for_create'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Field to insert PDF body to on cron create'),
      '#default_value' => $config->get('body_field_name_for_create'),
      '#description' => $this->t('Enter the body field name where PDFs content should be saved when new content is generated during cron runs.'),
    ];

    $form['cron_settings']['process_cron_nodes'] = [
      '#type' => 'submit',
      '#value' => $this->t('Manually trigger cron'),
      '#submit' => ['::processAllPdfs'],
    ];

    return parent::buildForm($form, $form_state);
  }

  public function processAllNodes(array &$form, FormStateInterface $form_state)
  {
    $config = $this->config('pdf_meta_extraction.settings');
    $processPdfService = new ProcessPdf(); //\Drupal::service('pdf_meta_extraction.process_pdf');
    $batch = [
      'title' => t('Processing All Nodes'),
      'operations' => [],
      'finished' => '::batchFinished',
    ];
    $allowed_types = $config->get('content_types');
    $nids = [];

    if (!empty($allowed_types) && is_array($allowed_types)){
      $query = \Drupal::entityQuery('node')
        ->condition('type', $allowed_types, 'IN');
        $nids = $query->execute();
    }
    \Drupal::logger('pdf_meta_extraction')->notice('Processing nodes: ' . print_r($nids, true));
    foreach($nids as $nid){
      if (!is_numeric($nid) || $nid <= 0) {
        \Drupal::logger('pdf_meta_extraction')->error('Invalid Node ID: ' . $nid);
        continue;
      }  
      $batch['operations'][] = [[$processPdfService, 'processPdfNodeData'], [$nid]];
    }
    batch_set($batch);

  }


  public function processAllPdfs(array &$form, FormStateInterface $form_state)
  {
    $config = $this->config('pdf_meta_extraction.settings');
    $processPdfService = new ProcessPdf();//\Drupal::service('pdf_meta_extraction.process_pdf');
    
    $batch = [
      'title' => t('Processing All Pdfs'),
      'operations' => [
        [[$processPdfService, 'processPdfFiles'], [true]]
      ],
      'finished' => [$this, 'batchFinished'],
    ];

if ($this->debug || true){
    $connection = \Drupal::database();
    $connection->truncate('watchdog')->execute();
}
    batch_set($batch);

  }




  /**
   * Finished callback for the batch process.
   *
   * @param bool $success
   *   A boolean indicating whether the batch completed successfully.
   * @param array $results
   *   The results of the batch process.
   * @param array $operations
   *   Any remaining operations in the batch process.
   */
  public static function batchFinished($success, array $results, array $operations)
  {
    if ($success) {
      // Success message.
      \Drupal::messenger()->addMessage(t('All PDFs processed successfully.'));
    } else {
      // Error message.
      $error_operation = reset($operations);
      \Drupal::messenger()->addMessage(t('An error occurred while processing PDFs at @operation.', ['@operation' => $error_operation[0]]), MessengerInterface::TYPE_ERROR);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state)
  {

    $fieldMappings = $form_state->getValue('field_mappings');
    $validator = new FormValidator($this->mapping);
    $result = $validator->validateForm();

    if ($result !== true) {
      $form_state->setErrorByName('field_mappings', $result);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $raw_mappings = $form_state->getValue('field_mappings');
    $this->mapping = $this->parseMappings($raw_mappings);

    $this->config('pdf_meta_extraction.settings')
      ->set('field_mappings', $form_state->getValue('field_mappings'))
      ->set('content_types', $form_state->getValue('content_types'))
      ->set('overwrite', $form_state->getValue('overwrite'))
      ->set('reprocess', $form_state->getValue('reprocess'))
      ->set('extract_body', $form_state->getValue('extract_body'))
      ->set('content_type_for_create', $form_state->getValue('content_type_for_create'))
      ->set('field_name_for_create', $form_state->getValue('field_name_for_create'))
      ->set('body_field_name_for_create', $form_state->getValue('body_field_name_for_create'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  private function parseMappings($raw_mappings)
  {
    $mappings = [];
    $lines = explode("\n", $raw_mappings);
    foreach ($lines as $line) {
      if (strpos($line, '|') !== FALSE) {
        list($pdf_field, $drupal_field) = explode('|', trim($line));
        $mappings[trim($pdf_field)] = trim($drupal_field);
      }
    }
    return $mappings;
  }
}


class FormValidator
{
  private $fieldMappings;

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
