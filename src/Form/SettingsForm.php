<?php

namespace Drupal\metadata_hex\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\metadata_hex\Validator\FormValidator;
use Drupal\node\Entity\NodeType;

/**
* Class SettingsForm
*
* Provides a settings form for the Metadata Hex module.
*/
class SettingsForm extends ConfigFormBase {
  
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
    $config = $this->config('metadata_hex.settings');
    $form['settings'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Configuration Sections'),
    ];
    
    $content_types = NodeType::loadMultiple();
    $options = [];
    foreach ($content_types as $content_type) {
      $options[$content_type->id()] = $content_type->label();
    }
  
    $fileHandlerManager = \Drupal::service('metadata_hex.file_handler_manager');
    $extensions = $fileHandlerManager->getAvailableExtentions();

    $form['extraction_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Extraction Settings'),
      '#open' => true,
      '#group' => 'settings',
    ];
    
    $form['extraction_settings']['hook_node_types'] = [
      '#type' => 'select',
      '#title' => $this->t('Bundle types to hook'),
      '#multiple' => TRUE,
      '#options' => $options,
      '#default_value' => implode("\n", $config->get('extraction_settings.hook_node_types') ?? []),
      '#description' => $this->t('Select multiple node bundle types to indicate which types will be preprocessed on update or create.'),
    ];
    
    $form['extraction_settings']['field_mappings'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Field mappings (File to Drupal)'),
      '#default_value' => $config->get('field_mappings'),
      '#description' => $this->t('Enter each field mappings in the format "file_field | drupal_field" on a new line.'),
    ];
    
    $form['extraction_settings']['flatten_keys'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Flattens metadata keys that have : in them'),
      '#default_value' => $config->get('extraction_settings.flatten_keys')||false,
    ];    

    $form['extraction_settings']['strict_handling'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Strict handling for string comparison'),
      '#default_value' => $config->get('extraction_settings.strict_handling')||false,
    ];
    
    $form['extraction_settings']['data_protected'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Protect existing data from being overwritten'),
      '#default_value' => $config->get('extraction_settings.data_protected')||true,
    ];
    
    $form['extraction_settings']['title_protected'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Protect title from being overwritten'),
      '#default_value' => $config->get('extraction_settings.title_protected')||true,
    ];
    
    $form['extraction_settings']['available_extensions'] = [
      '#type' => 'textarea',
      '#access' => false,
      '#title' => $this->t('Enabled file extensions'),
      '#default_value' => $config->get('extraction_settings.available_extensions') ?? $extensions,
      '#description' => $this->t('Enter file extensions, one per line.'),
    ];
    $form['extraction_settings']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Configuration'),
    ];

    $form['node_process'] = [
      '#type' => 'details',
      '#title' => $this->t('Node Bulk Processing Settings'),
      '#open' => false,
      '#group' => 'settings',
    ];
    
    $form['node_process']['bundle_types'] = [
      '#type' => 'select',
      '#title' => $this->t('Bundle types to process'),
      '#multiple' => TRUE,
      '#options' => $options,
      '#default_value' => implode("\n", $config->get('node_process.bundle_types') ?? []),
      '#description' => $this->t('Select multiple node bundle types to indicate which types will be preprocessed for file metadata extraction.'),
    ];
    
    $form['node_process']['allow_reprocess'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow reprocessing of already processed nodes'),
      '#default_value' => $config->get('node_process.allow_reprocess'),
    ];
    
    $form['node_process']['process_nodes'] = [
      '#type' => 'submit',
      '#value' => $this->t('Manually process all selected node types'),
      '#submit' => ['::processAllNodes'],
    ];
    
    $form['file_ingest'] = [
      '#type' => 'details',
      '#title' => $this->t('File Ingest Settings'),
      '#open' => false,
      '#group' => 'settings',
    ];
    
    $form['file_ingest']['bundle_type_for_generation'] = [
      '#type' => 'select',
      '#title' => $this->t('Bundle type for content generation'),
      '#options' => $options,
      '#multiple' => false,
      '#default_value' => implode("\n", $config->get('node_process.bundle_types') ?? []),
      '#description' => $this->t('Select  node bundle types to indicate which types will be created from extracted metadata.'),
    ];
    
    $form['file_ingest']['file_attachment_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Field for attaching files'),
      '#default_value' => $config->get('file_ingest.file_attachment_field'),
    ];
    
    $form['file_ingest']['ingest_directory'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Directory for file ingestion'),
      '#default_value' => $config->get('file_ingest.ingest_directory'),
      '#description' => $this->t('Enter the directory path where files should be ingested.'),
    ];
    
    $form['file_ingest']['process_cron_nodes'] = [
      '#type' => 'submit',
      '#value' => $this->t('Manually trigger file ingest'),
      '#submit' => ['::processAllPdfs'], // @todo fix this
    ];
    
    
    return parent::buildForm($form, $form_state);
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
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('metadata_hex.settings');
    
    $config->set('extraction_settings.hook_node_types', $form_state->getValue('hook_node_types'));
    
    $config->set('extraction_settings.field_mappings', $form_state->getValue('field_mappings'));
    $config->set('extraction_settings.strict_handling', $form_state->getValue('strict_handling'));
    $config->set('extraction_settings.flatten_keys', $form_state->getValue('flatten_keys'));
    $config->set('extraction_settings.data_protected', $form_state->getValue('data_protected'));
    $config->set('extraction_settings.title_protected', $form_state->getValue('title_protected'));
    //$config->set('extraction_settings.available_extensions', $form_state->getValue('available_extensions'));
    
    $config->set('node_process.bundle_types', $form_state->getValue('bundle_types'));
    $config->set('node_process.allow_reprocess', $form_state->getValue('allow_reprocess'));
    
    $config->set('file_ingest.bundle_type_for_generation', $form_state->getValue('bundle_type_for_generation'));
    $config->set('file_ingest.file_attachment_field', $form_state->getValue('file_attachment_field'));
    $config->set('file_ingest.ingest_directory', $form_state->getValue('ingest_directory'));
    
    $config->save();
    parent::submitForm($form, $form_state);
  }
}
