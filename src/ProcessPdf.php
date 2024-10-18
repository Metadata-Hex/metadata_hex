<?php

namespace Drupal\pdf_meta_extraction;

use Drupal\pdf_meta_extraction\Service\PDFMetadataExtractor;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\Entity\Node;
use Drupal\file\Entity\File;
use Drupal\taxonomy\Entity\Form;
use Drupal\Core\File\FileSystemInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Entity\EntityMalformedException;
class ProcessPdf
{

  /**
   * The current node being processed
   * @var Node
   */
  private $node;

  /**
   * The current node id
   * @var int
   */
  private $nid;

  /**
   * Drupal config to pull settings from
   * @var ImmutableConfig
   */
  private $config;

  /**
   * A key|value array containing pdf_field|drupal_field mapping
   * @var array
   */
  private $field_mappings;

  /**
   * Extracted pdf meta data that needs processing
   * @var array
   */
  private $data_to_process;

  /**
   * All PDF files to process for a particular node
   * @var array
   */
  private $pdf_files;

  /**
   * The content type that any pdf generated content should be created with
   * @var string
   */
  private $content_type_for_create;

  /**
   * The field name that pdf generated content should save the pdf to
   * @var string
   */
  private $field_name_for_create;

  /**
   * The body field name that pdf generated content should save the pdf to
   * @var string
   */
  private $body_field_name_for_create;

  /**
   * Decides if we should overwrite data or not
   * @var bool
   */
  private $overwrite;

  /**
   * A revision log message template 
   * @var string
   */
  private $log_message_template;

  /**
   * Decides if it's running from a cron or user
   * @var bool
   */
  private $cron;

  /**
   * Decides if it's running from an insert hook
   * @var bool
   */
  private $insert;

  /**
   * Reference to the extracter service
   * @var PDFMetadataExtractor
   */

  private $extractor;

  /**
   * Drupal logger service
   */

  private $logger;

  /**
   * @var array
   */
  private $metadata;

  /**
   * @var bool
   */
private $extract_body;

/**
 * @var bool
 */
private $reprocess;

  public function _construct()
  {
    $this->insert = false;
    $this->data_to_process = [];
    $this->metadata = [];
    $this->pdf_files = [];
    $this->field_mappings = null;
    $this->nid = null;
    $this->node = null;
    $this->overwrite = false;
    $this->extract_body = false;
    $this->cron = false;
    $this->content_type_for_create = null;
    $this->field_name_for_create = null;
    $this->init();
  }
 
  function init(){
    $this->config = \Drupal::config('pdf_meta_extraction.settings');
    $this->log_message_template = 'Metadata imported from pdf on @date by @user';
    $this->extractor = \Drupal::service('pdf_meta_extraction.pdf_metadata_extractor');
    $this->logger = \Drupal::logger('pdf_meta_extraction');
    $this->overwrite = (bool) $this->config->get('overwrite');
    $this->reprocess = (bool) $this->config->get('reprocess');
    $this->extract_body = (bool) $this->config->get('extract_body');
    $this->logger = \Drupal::logger('pdf_meta_extraction');
    $maps = $this->config->get('field_mappings');
    $this->field_mappings = $this->explodeKeyValueString($maps);
    $this->content_type_for_create = $this->config->get('content_type_for_create');
    $this->field_name_for_create = $this->config->get('field_name_for_create');
    $this->body_field_name_for_create = $this->config->get('body_field_name_for_create');

  }

  function removeTitle(){
    unset($this->field_mappings['title']);
  }

  function setInsert($insert = true){
    $this->insert = $insert;
  }
  /**
   * Get the directory path for a file field.
   *
   * @param string $entity_type
   *   The entity type (e.g., 'node').
   * @param string $bundle
   *   The bundle (content type) machine name.
   * @param string $field_name
   *   The field name (e.g., 'field_upload_a_resource').
   *
   * @return string|null
   *   The directory path for the file field, or NULL if not found.
   */
  function getFileFieldDirectory($entity_type, $bundle, $field_name)
  {
    // Load the field storage configuration.
    $field_storage = FieldStorageConfig::loadByName($entity_type, $field_name);
    if ($field_storage) {
      // Load the field configuration for the specific bundle.
      $field_config = FieldConfig::loadByName($entity_type, $bundle, $field_name);
      if ($field_config) {
        // Get the settings for the field.
        $settings = $field_config->getSettings();
        if (!empty($settings['file_directory'])) {
          return $settings['file_directory'];
        }
      }
    }
    return NULL;
  }

  /**
   * Handles setting a revision message whenever the module updates content
   */

  public function setRevision()
  {
    // Enable revisions and generate the message
    $this->node->setNewRevision(TRUE);

    if (strlen($this->log_message_template) < 1) {
      $this->log_message_template = 'Metadata imported from pdf on @date by @user';
    }
    $log_message = strtr($this->log_message_template, [
      '@date' => date("Y-m-d H:i:s"),
      '@user' => $this->cron ? 'Cron' : \Drupal::currentUser()->getDisplayName(),
    ]) . print_r($this->metadata, true);

    //Set the revision message
    $this->node->setRevisionLogMessage($log_message);
  }

  /**
   * Explodes the key|value values into an array
   * @param mixed $input
   * @return string[]
   */
  function explodeKeyValueString($input)
  {
    // Split the input string by new lines
    $lines = explode("\n", $input);
    $result = [];

    foreach ($lines as $line) {
      // Split each line by the delimiter that separates the key and the value (e.g., a pipe)
      if (strpos($line, '|') !== false) {
        list($key, $value) = explode('|', $line);
        $result[trim($value)] = trim($key); //swapping these
      }
    }

    return $result;
  }


  /**
   * Check if a file is referenced by an entity and get the referencing entity IDs.
   *
   * @param string $file_path
   *   The full path of the file to check.
   *
   * @return array
   *   An array containing 'referenced' (bool) and 'entity_ids' (array).
   */
  function getFileReferences($file_path)
  {
    // Load the file entity by URI.
    $file = \Drupal::entityTypeManager()->getStorage('file')->loadByProperties(['uri' => $file_path]);
    if ($file) {
      $file = reset($file);
      // Check if the file is referenced by any entity.
      $usages = \Drupal::service('file.usage')->listUsage($file);
      if (!empty($usages)) {
        $entity_ids = [];
        foreach ($usages as $module => $usage) {
          foreach ($usage as $entity_type => $entities) {
            foreach ($entities as $entity_id => $count) {
              if ($entity_type == 'node') {
                $entity_ids[] = $entity_id;
              }
            }
          }
        }
        return [
          'referenced' => TRUE,
          'entity_ids' => $entity_ids,
        ];
      }
    }
    return [
      'referenced' => FALSE,
      'entity_ids' => [],
    ];
  }

  /**
   * Process all PDFs and categorize them based on whether they are referenced.
   *
   * @param string $directory
   *   The directory to scan for PDF files.
   *
   * @return array
   *   An associative array with two keys: 'referenced' and 'unreferenced'.
   */
  function categorizePdfs($directory)
  {
    $pdf_files = $this->scanAllPdfs($directory);
    $referenced_files = [];
    $unreferenced_files = [];

    foreach ($pdf_files as $pdf_file) {
      $references = $this->getFileReferences($pdf_file['full_path']);
      if ($references['referenced']) {
        $pdf_file['entity_ids'] = $references['entity_ids'];
        $referenced_files[] = $pdf_file;
      } else {
        $unreferenced_files[] = $pdf_file;
      }
    }

    return [
      'referenced' => $referenced_files,
      'unreferenced' => $unreferenced_files,
    ];
  }


  /**
   * Retrieve all PDFs in the specified directory.
   *
   * @param string $directory
   *   The directory to scan for PDF files.
   *
   * @return array
   *   An array of associative arrays, each containing the file name and the full path.
   */
  function scanAllPdfs($directory)
  {
    // Get the file system service.
    $file_system = \Drupal::service('file_system');
    // Ensure the directory path is absolute.
    $real_path = $file_system->realpath($directory);

    // Scan the directory for PDF files.
    $pdf_files = [];
    $files = scandir($real_path);
    foreach ($files as $file) {
      if (pathinfo($file, PATHINFO_EXTENSION) === 'pdf') {
        $pdf_files[] = [
          'file_name' => $file,
          'full_path' => $real_path . '/' . $file,
        ];
      }
    }

    return $pdf_files;
  }


  /**
   * Creates a blank node to populate PDF data into
   * 
   * @param mixed $target_bundle
   * @param mixed $pdf_uri
   * @param mixed $title
   * 
   * @throws \Exception
   * 
   * @return Node
   */
  function createBlankNode($target_bundle, $pdf_uri, $title = 'Temporary title')
  {
    // $maps = $this->config->get('field_mappings');
    $this->init();
    // $this->field_mappings = $this->explodeKeyValueString($maps);



    if (!file_exists($pdf_uri)) {
      throw new \Exception('The specificed PDF file does not exist at' . $pdf_uri);
    }


    //$file = File::loadByUri($pdf_uri);
    $files = \Drupal::entityTypeManager()->getStorage('file')->loadByProperties(['uri' => $pdf_uri]);
    $file = reset($files);

    if (!$file) {
      $file = File::create([
        'uri' => $pdf_uri,
        'status' => FILE_STATUS_PERMANENT,
      ]);
      $file->save();
    }

    $body = '';
    if ($this->extract_body){
      $body = nl2br($this->extractor->getBody($pdf_uri));
    } 
    // $field = 'field_upload_a_resource';
    $node = Node::create([
      'type' => $this->content_type_for_create,
      'title' => $title,
      $this->body_field_name_for_create => ['value' => $body, 'format' => 'basic_html'],
      $this->field_name_for_create => [
        [
          'target_id' => $file->id(),
          'display' => 1,
          'description' => basename($pdf_uri) ?? 'Pdf'
        ]
      ]
    ]);



    $node->set($this->field_name_for_create, [
      'target_id' => $file->id(),
      'display' => 1,
    ]);

    $this->node = $this->validate_and_save_node($node);
    // $node->save();
    // $this->node = $node;
    $this->nid = $this->node->id;

    return $node;
  }


  /**
   * Processes all pdf files in the cron
   * 
   * @return void
   */
  public function processPdfFiles($cron = true)
  {
    $this->init();
    $this->cron = $cron;
    $target_bundle = $this->config->get('content_type_for_create');

    $dir = $this->getFileFieldDirectory('node', $target_bundle, $this->field_name_for_create);

    $pdf_directory = 'public://' . $dir . '/';
    $pdf_categories = $this->categorizePdfs($pdf_directory);

    foreach ($pdf_categories['referenced'] as $item) {
      $this->processPdfNodeData(reset($item['entity_ids']));
    }

    foreach ($pdf_categories['unreferenced'] as $item) {

      $this->getPdfFieldMetadata($dir, $item['file_name']);
      $this->createBlankNode($target_bundle, $item['full_path']);
      $this->writePdfMetadata();
    }
  }


  /**
   * Processes data from a specific node.
   *
   * @param int $nodeId The ID of the node to process.
   */
  public function processPdfNodeData($nodeId)
  {
    //\Drupal::logger->info(__FUNCTION__ . ":" . __LINE__);

    $this->nid = $nodeId;
    $this->node = Node::load($this->nid);
    $this->init();
    $file_field = $this->node->get('field_upload_a_resource')->entity;

    if ($this->insert){
      $this->removeTitle();
    }

    if ($this->node && $this->validateMappings() && $file_field) {

      $this->extractPdfs();
      $this->getPdfFieldMetadata();
      $this->logger->error(print_r($this->data_to_process, true));
      $this->writePdfMetadata();

    } else {
      \Drupal::logger('pdf_meta_extraction')->error('Node with ID ' . $nodeId . ' could not be loaded.');
      // $this->logger->error('Node with ID ' . $nodeId . ' could not be loaded.');
      return false;
    }
  }


  /**
   * Validates the extracted mapping data
   * 
   * @return bool
   */
  public function validateMappings()
  {
    $invalid_fields = [];
    foreach ($this->field_mappings as $drupal_field => $pdf_field) {
      if (!$this->node->hasField($drupal_field)) {
        $invalid_fields[$pdf_field] = $drupal_field;
      }
    }

    if (!empty($invalid_fields)) {
      // Handle invalid field mappings
      $this->logger->error('Invalid field data for node: ' . $this->node->label() . '|' . print_r($invalid_fields, true));
    }

    return empty($invalid_fields);
  }

  /**
   * HNdles updating fields in regards to overwriting or not
   * 
   * @param mixed $field_name
   * @param mixed $value
   * 
   * @return void
   */
  function setField($field_name, $value)
  {

    if (($this->overwrite) || (!$this->overwrite && $this->node->get($field_name)->isEmpty())) {
      try {
        $this->node->set($field_name, $value);
      } catch (\Exception $e) {
        $this->logger->error('nid: ' . $this->node->id() . '::' . $field_name . '=' . (is_array($value) ? print_r($value, true) : $value) . '  ' . $e->getMessage());
      }
    }

  }

  /**
   * Load taxonomy terms by name and vocabulary ID, case-insensitive.
   *
   * @param string $term_name
   *   The term name to search for.
   * @param string $target_bundle
   *   The vocabulary ID to limit the search.
   *
   * @return \Drupal\taxonomy\Entity\Term[]
   *   An array of matching taxonomy terms.
   */
  function findMatchingTerms($term_name, $target_bundle)
  {


    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties([
      'vid' => $target_bundle,
    ]);

    // Filter terms by name, case-insensitive.
    $matching_terms = [];
    $term_name_lower = mb_strtolower($term_name);

    foreach ($terms as $term) {
      if (mb_strtolower($term->getName()) === $term_name_lower) {
        $matching_terms[] = $term;
      }
    }

    return $matching_terms;
  }




  /**
   * Write the pdf metadata to a drupal node
   * 
   * @return void
   */
  
    function writePdfMetadata()
  {
    try {
      if ($this->data_to_process != null){
      foreach ($this->data_to_process as $field_name => $value) {
        if ($this->node->hasField($field_name)) {
          $field_definition = $this->node->getFieldDefinition($field_name);
          $field_type = $field_definition->getType();
          \Drupal::logger('pdf_meta_extraction')->info('Field type: ' . $field_type);
          \Drupal::logger('pdf_meta_extraction')->info('Field name: ' . $field_name);
          switch ($field_type) {
            case 'string':
            case 'string_long':
            case 'text':
            case 'text_long':
            case 'text_with_summary':

              // Set text-based fields.
              $this->setField($field_name, $value);
              break;

            case 'entity_reference':
              // Check if the field is configured to reference taxonomy terms
              if ($field_definition->getSetting('target_type') === 'taxonomy_term') {
                $termsCaptured = explode(',', $value);
                $term_ids = [];
                foreach ($termsCaptured as $term_name) {
                  $term_name = trim($term_name);

                  // Check if the term exists
                  $target_bundle = $field_definition->getSetting('handler_settings')['target_bundles'];
                  $target_bundle = reset($target_bundle);

                  $terms = $this->findMatchingTerms($term_name, $target_bundle);

                  if (empty($terms) && !empty($term_name)) {
                    // Create term if it doesn't exist
                    $term = Term::create([
                      'name' => $term_name,
                      'vid' => $target_bundle
                    ]);
                    $term->save();
                    $term_ids[] = $term->id();
                  } else if (!is_bool(reset($terms))) {
                    $term_ids[] = reset($terms)->id();
                  } else {
                    // Use existing term
                    $term_ids[] = ($terms);
                  }
                }

                $formatted_term_ids = [];

                foreach ($term_ids as $term_id) {
                  $formatted_term_ids[] = ['target_id' => $term_id];
                }

                $this->setField($field_name, $formatted_term_ids);
              } else {

                // Normal entity reference handling
                $this->setField($field_name, ['target_id' => $value]);
              }

              break;

            case 'boolean':
              // Set boolean fields, ensure $value is a boolean.
              $this->setField($field_name, (bool) $value);
              break;

            case 'integer':
              // Set integer fields, ensure $value is an integer.
              $this->setField($field_name, (int) $value);
              break;

            case 'datetime':
              $datetime = new \DateTime($value);
              $formatted_value = $datetime->format('Y-m-d\TH:i:s');
              $this->setField($field_name, $formatted_value);
              break;

            case 'list_string':
              /** @var \Drupal\field\Entity\FieldConfig $field */
              $field = FieldConfig::loadByName('node', $this->content_type_for_create, $field_name);
              // $this->logger->error($this->content_type_for_create . '::' .$field_name);

              try {
              $allowed_values = $field->getSetting('allowed_values');
              } catch (\Exception $e){
                $this->logger->error($this->content_type_for_create . '::' .$field_name . $e->getMessage());
              }
              if (in_array($value, $allowed_values)) {
                $this->setField($field_name, $value);
              } else {
                $this->logger->error('Field ' . $field_name . ' has a value ' . $value . ' that isnt in the allowed values');
              }
              break;


            default:
              \Drupal::messenger()->addWarning("Field type '{$field_type}' of '{$field_name}' is not explicitly handled.");
              break;
          }

        } else {
          $this->logger->error('The field @field does not exist on the node type.', ['@field' => $field_name]);
        }
      }
      $this->setRevision();

      \Drupal::logger('pdf_meta_extraction')->info('Node ID ' . print_r($this->node->toArray(), true) . ' updated with PDF metadata.');
      \Drupal::logger('pdf_meta_extraction')->info('reprocess: '.$this->reprocess);
      \Drupal::logger('pdf_meta_extraction')->info('isnot process: '.(!$this->getIsNodeProcessed($this->node)));
      
      if ($this->reprocess || (!$this->getIsNodeProcessed($this->node) || $this->reprocess)){
        \Drupal::logger('pdf_meta_extraction')->info('About to save');

        try {
        $this->node->save();
        $this->setNodeProcessed($this->node);
        } catch(\Exception $e){
          \Drupal::logger('pdf_meta_extraction')->info(__LINE__ . $e->getMessage());
          $this->logger->error($e->getMessage());
        }
      } 
    }
    } catch (\Exception $e) {
    \Drupal::logger('pdf_meta_extraction')->info(__LINE__ . $e->getMessage());
      \Drupal::messenger()->addError('An error as occured');
    }
  }

  /**
   * Check if PDF fields exist in a PDF file's metadata.
   *
   * @param int $file_id The file entity ID of the PDF.
   * @return array An array of fields that exist in the PDF metadata.
   */
  function getPdfFieldMetadata($dir = null, $name = null)
  {

    $this->logger->info(__FUNCTION__ . ":" . __LINE__);

    $file_system = \Drupal::service('file_system');
    $drupal_root = $file_system->realpath('public://');

    if (!empty($dir) && !empty($name)) {
      $this->pdf_files = ['/' . $dir . '/' . $name];
    }

    foreach ($this->pdf_files as $uri) {
      $file_path = $drupal_root . $uri;
      $this->logger->info('Cron processing ' . $file_path);


      $metadata = $this->extractor->getMetadata($file_path);
      $this->metadata = $metadata;//for logging

      $existing_fields = [];
      foreach ($this->field_mappings as $drupal_field => $pdf_field) {

        if (isset($metadata[$pdf_field]) && !empty($metadata[$pdf_field])) {
          $this->data_to_process[$drupal_field] = $metadata[$pdf_field];
        }
      }
    }

    return [];
  }

  /**
   * Extracts PDF files from a node's fields
   * 
   * @return void
   */
  private function extractPdfs()
  {

    $fields = $this->node->getFieldDefinitions();

    // Loop over all node fields and find any pdfs
    foreach ($fields as $field_name => $field_definition) {
      if ($field_definition->getType() === 'file' || $field_definition->getType() === 'entity_reference') {
        // Process each file field
        $file_field = $this->node->get($field_name);
        foreach ($file_field->referencedEntities() as $file) {
          try {
            if (method_exists($file, 'getMimeType') && $file->getMimeType() === 'application/pdf') {
              $this->pdf_files[] = str_replace('sites/default/files/', '', $file->createFileUrl());
            }
          } catch (\Exception $e) {
            $this->logger->error('errors abound @node and @e.', ['@node' => $file, '@e' => $e]);

          }
        }
      }
    }
  }

  /**
   * Tracks the processed node
   * @param mixed $node
   * @return void
   */
  function setNodeProcessed($node){
    $entity_type = 'node';
$entity_id = $node->id();

// Insert a record into the custom table to mark the entity as processed.
\Drupal::database()->insert('pdf_meta_extraction_processed')
  ->fields([
    'entity_type' => $entity_type,
    'entity_id' => $entity_id,
    'processed' => 1,
  ])
  ->execute();


// \Drupal::logger('pdf_meta_extraction')->notice('Node ID ' . $entity_id . ' marked as processed.');

  }

  /**
   * Decides if the node has been processed
   * @param mixed $node
   * @return bool
   */
  function getIsNodeProcessed($node){
    $entity_type = 'node';
    $entity_id = $node->id();

// Check if the node has already been processed.
$database = \Drupal::database();
$processed = $database->select('pdf_meta_extraction_processed', 'p')
  ->fields('p', ['id'])
  ->condition('entity_type', $entity_type)
  ->condition('entity_id', $entity_id)
  ->execute()
  ->fetchField();

return $processed;

  }

  /**
 * Validates a node before saving.
 *
 * @param \Drupal\node\NodeInterface $node
 *   The node entity to validate.
 *
 * @throws \Drupal\Core\Entity\EntityMalformedException
 *   Thrown when the entity is malformed.
 */
function validate_and_save_node(\Drupal\node\NodeInterface $node) {
  // Validate the node.
  $violations = $node->validate();

  // Check if there are any violations.
  if ($violations->count() > 0) {
    // Handle the validation errors.
    foreach ($violations as $violation) {
      \Drupal::logger('node_validation')->error($violation->getMessage());
    }

  }
  else {
    // No violations, proceed to save the node.
    try {
      $node->save();
    }
    catch (EntityStorageException $e) {
      \Drupal::logger('node_save')->error('Failed to save the node: ' . $e->getMessage());
      throw $e;
    }
  }
  return $node;
}
}