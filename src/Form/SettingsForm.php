<?php

namespace Drupal\metadata_hex\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

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

    $form['extraction_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Extraction Settings'),
      '#open' => true,
    ];

    $form['extraction_settings']['hook_node_types'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Node types for extraction hooks'),
      '#default_value' => implode("\n", $config->get('extraction_settings.hook_node_types') ?? []),
      '#description' => $this->t('Enter node types, one per line.'),
    ];

    $form['extraction_settings']['field_mappings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Field mappings (File to Drupal)'),
    ];

    $field_mappings = $config->get('extraction_settings.field_mappings') ?? [];
    foreach ($field_mappings as $index => $mapping) {
      $form['extraction_settings']['field_mappings'][$index] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Mapping @index', ['@index' => $index + 1]),
      ];
      $form['extraction_settings']['field_mappings'][$index]['file_field'] = [
        '#type' => 'textfield',
        '#title' => $this->t('File Field'),
        '#default_value' => $mapping['file_field'] ?? '',
      ];
      $form['extraction_settings']['field_mappings'][$index]['drupal_field'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Drupal Field'),
        '#default_value' => $mapping['drupal_field'] ?? '',
      ];
    }

    $form['extraction_settings']['strict_handling'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Strict handling for string comparison'),
      '#default_value' => $config->get('extraction_settings.strict_handling'),
    ];

    $form['extraction_settings']['data_protected'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Protect existing data from being overwritten'),
      '#default_value' => $config->get('extraction_settings.data_protected'),
    ];

    $form['extraction_settings']['title_protected'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Protect title from being overwritten'),
      '#default_value' => $config->get('extraction_settings.title_protected'),
    ];

    $form['extraction_settings']['available_extensions'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Enabled file extensions'),
      '#default_value' => implode("\n", $config->get('extraction_settings.available_extensions') ?? []),
      '#description' => $this->t('Enter file extensions, one per line.'),
    ];

    $form['node_process'] = [
      '#type' => 'details',
      '#title' => $this->t('Node Processing Settings'),
      '#open' => true,
    ];

    $form['node_process']['bundle_types'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Bundle types to process'),
      '#default_value' => implode("\n", $config->get('node_process.bundle_types') ?? []),
      '#description' => $this->t('Enter bundle types, one per line.'),
    ];

    $form['node_process']['allow_reprocess'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow reprocessing of already processed nodes'),
      '#default_value' => $config->get('node_process.allow_reprocess'),
    ];

    $form['file_ingest'] = [
      '#type' => 'details',
      '#title' => $this->t('File Ingest Settings'),
      '#open' => true,
    ];

    $form['file_ingest']['bundle_type_for_generation'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Bundle type for content generation'),
      '#default_value' => $config->get('file_ingest.bundle_type_for_generation'),
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

    return parent::buildForm($form, $form_state);
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

    $config->set('extraction_settings.hook_node_types', array_filter(array_map('trim', explode("\n", $form_state->getValue('hook_node_types')))));
    
    $config->set('extraction_settings.field_mappings', []);
    foreach ($form_state->getValue('field_mappings') as $mapping) {
      if (!empty($mapping['file_field']) && !empty($mapping['drupal_field'])) {
        $config->append('extraction_settings.field_mappings', [
          'file_field' => $mapping['file_field'],
          'drupal_field' => $mapping['drupal_field'],
        ]);
      }
    }

    $config->set('extraction_settings.strict_handling', $form_state->getValue('strict_handling'));
    $config->set('extraction_settings.data_protected', $form_state->getValue('data_protected'));
    $config->set('extraction_settings.title_protected', $form_state->getValue('title_protected'));
    $config->set('extraction_settings.available_extensions', array_filter(array_map('trim', explode("\n", $form_state->getValue('available_extensions')))));

    $config->set('node_process.bundle_types', array_filter(array_map('trim', explode("\n", $form_state->getValue('bundle_types')))));
    $config->set('node_process.allow_reprocess', $form_state->getValue('allow_reprocess'));

    $config->set('file_ingest.bundle_type_for_generation', $form_state->getValue('bundle_type_for_generation'));
    $config->set('file_ingest.file_attachment_field', $form_state->getValue('file_attachment_field'));
    $config->set('file_ingest.ingest_directory', $form_state->getValue('ingest_directory'));

    $config->save();
    parent::submitForm($form, $form_state);
  }
}