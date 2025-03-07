<?php

namespace Drupal\metadata_hex\Model;

use Drupal\Core\Database\Database;
use Drupal\file\Entity\File;
use Drupal\metadata_hex\Base\MetadataHexCore;
use Drupal\metadata_hex\Service\FileHandlerManager;
use Drupal\node\Entity\Node;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Class NodeBinder
 *
 * Encapsulates the object and functionality tied to nodes.
 */
class NodeBinder extends MetadataHexCore
{
  /**
   * The UUID associated with this NodeBinder object.
   *
   * @var string|null
   */
  protected $uuid = null;

  /**
   * The node ID associated with this NodeBinder object.
   *
   * @var int|null
   */
  protected $nid = null;

  /**
   * The file ID associated with this file object.
   *
   * @var int|null
   */
  protected $fid = null;

  /**
   * File manager service to ingest files
   * @var FileHandlerManager
   */
  protected $fileHandlerManager;

  /**
   * MetadataHex Settings Manager
   * @var
   */
  protected $settingsManager;
  /**
   * Constructs the NodeBinder class.
   *
   * @param LoggerInterface $logger
   *   The logger service.
   */
  public function __construct(LoggerInterface $logger)
  {
    parent::__construct($logger);
    $this->uuid = $this->generateUuid();
    $this->fileHandlerManager = \Drupal::service('metadata_hex.file_handler_manager');
    $this->settingsManager = new \Drupal\metadata_hex\Service\SettingsManager();
  }

  public function generateUuid()
  {
    return \Drupal::service('uuid')->generate();
  }

  public function getUuid()
  {
    return $this->uuid;
  }
  /**
   * Initializes the NodeBinder with a file or node input.
   *
   * @param mixed $input
   *   The input entity (either File or Node).
   *
   * @throws \InvalidArgumentException
   *   If the input is invalid.
   */
  public function init($input)
  {
    echo PHP_EOL."nodebinder:86".PHP_EOL;
    if (is_string($input)){
      $input = $this->initNode($input, $this->settingsManager->getIngestBundleType(), $this->settingsManager->getIngestField()); // TODO this needs to be dynamic
      echo "x";
    }
    elseif ($input instanceof File) {
      $this->fid = $input->id();
      $file = $input;
      $input = $this->initNode($file->getFileUri(), $this->settingsManager->getIngestBundleType(), $this->settingsManager->getIngestField()); // TODO this needs to be dynamic
    }
    if ($input instanceof Node) {
      $this->nid = $input->id();
    } else {
      throw new \InvalidArgumentException("Invalid input provided.");
    }
  }

  /**
   * Retrieves file URIs from a node.
   *
   * @return array
   *   An array of file URIs.
   */
  public function getFileUris(): array
  {
    if (!$this->nid) {
      return [];
    }

    $node = Node::load($this->nid);
    if (!$node) {
      return [];
    }

    $file_uris = [];

    //parses through all the fields on a node to find and retrieve file uris
    foreach ($node->getFields() as $field_name => $field) {
      if ($field->getFieldDefinition()->getType() === 'file') {
        foreach ($field->getValue() as $file_item) {
          if ($file_entity = File::load($file_item['target_id'])) {
            $file_uris[] = $file_entity->getFileUri();
          }
        }
      }
    }

    return $file_uris;
  }

  /**
   * Checks whether the node has been processed.
   *
   * @return bool
   *   TRUE if the node has been processed, FALSE otherwise.
   */
  public function getIsNodeProcessed(): bool
  {
    if (!$this->nid) {
      return false;
    }

    $query = (bool) \Drupal::database()->select('metadata_hex_processed', 'mhp')
      ->fields('mhp', ['processed'])
      ->condition('entity_id', $this->nid)
      ->execute()
      ->fetchField();

    return $query;
  }
 /**
 * Checks whether the node has been processed in the last 3 minutes.
 *
 * @return bool
 *   TRUE if the node's last_modified timestamp is less than 3 minutes old, FALSE otherwise.
 */
public function getWasNodeJustProcessed(): bool
{
  if (!$this->nid) {
    return false;
  }
  try {
    $query = \Drupal::database()->select('metadata_hex_processed', 'mhp')
      ->fields('mhp', ['last_modified'])
      ->condition('entity_id', $this->nid)
      ->execute()
       ->fetchField();
    } catch (\Exception $e) {
      return false;
    }

  if ($query) {
    $lastModifiedTime = strtotime($query);
    $currentTime = time();
    return ($currentTime - $lastModifiedTime) <= 180;
  } else {
    return true; // if query is null, dont let it reprocess
  }
}

  /**
   * Retrieves the node from a given NID.
   *
   * @return Node|null
   *   The loaded node or NULL if not found.
   */
  public function getNode(): ?Node
  {
    return $this->nid ? Node::load($this->nid) : null;
  }

  /**
   * Retrieves the node from a given NID.
   *
   * @return string|null
   *   The loaded node or NULL if not found.
   */
  public function getBundleType(): ?string
  {
    return $this->getNode()?->bundle();
  }

  /**
   * Extracts metadata from the node's associated files.
   *
   * @return array
   *   An array of extracted metadata.
   */
  public function ingestNodeFileMeta(): array
  {
    $metadata = [];

    if (!$this->nid) {
      return $metadata;
    }

    $node = Node::load($this->nid);
    if (!$node) {
      return $metadata;
    }

    // Iterate over all node fields for files
    foreach ($node->getFields() as $field_name => $field) {
      $field_definition = $field->getFieldDefinition();
      $field_type = $field_definition->getType();
      $target_type = $field_definition->getSetting('target_type') ?? '';

      if ($field_type === 'file' || ($field_type === 'entity_reference' && $target_type === 'file')) {
        foreach ($field->getValue() as $file_item) {
          if (!empty($file_item['target_id'])) {
          // loads the file into a drupal model
          $file = File::load($file_item['target_id']);

          // assuming it exists, retrieve information
          if ($file) {
            $file_uri = $file->getFileUri();
            $file_extension = pathinfo($file_uri, PATHINFO_EXTENSION);
            $handler = $this->fileHandlerManager->getHandlerForExtension($file_extension);

            // setup the handler and extract the metadata
            if ($handler !== null) {
              $handler->setFileUri($file_uri);
              $exmd = $handler->extractMetadata();

              $data = [
                'uri' => $file->getFileUri(),
                'metadata' => $exmd,
              ];

              // appends the data in a non-destructive way
              if (!isset($metadata[$file->id()])) {
                $metadata[$file->id()] = $data;
              } else {
                $metadata[$file->id()] = array_merge($metadata[$file->id()], $data);
              }
            } else {
              $this->logger->error("No extraction handler found for $file_uri");
            }

          }
        }
        }
      }
    }
    return $metadata;
  }

  /**
   * Initializes a blank node if no reference is found.
   *
   * @param string $file_uri
   *   The file URI.
   * @param string $target_bundle
   *   The target bundle type.
   * @param string|null $field_name
   *   Optional field name.
   *
   * @return Node
   *   The initialized node.
   */
  public function initNode(string $file_uri, string $target_bundle, ?string $field_name = null): Node
  {
    // initiate a new node
    $node = Node::create([
      'type' => $target_bundle,
      'status' => 0,
      'title' => 'Generated Node',
    ]);

    // attach the file to the specified field and save
    if ($field_name) {
      $file = File::create(['uri' => $file_uri]);
      $file->save();
      $node->set($field_name, ['target_id' => $file->id()]);
    }

    $node->save();
    return $node;
  }

  /**
   * Saves the node after validation.
   *
   * @return void
   *
   */
  public function save()
  {
    if (!$this->nid) {
      return;
    }

    $node = Node::load($this->nid);
    if (!$node) {
      return;
    }

    // Validate and save the node.
    $violations = $node->validate();
    if (count($violations) === 0) {
      $node->save();
    } else {
      foreach ($violations as $violation) {
        $this->logger->error($violation->getMessage());
      }
    }
  }

  /**
   * Sets a field on the node.
   *
   * @param string $field_name
   *   The field name.
   * @param mixed $value
   *   The value to set.
   * @param bool $overwrite
   *   Whether to overwrite existing values.
   */
  public function setField(string $field_name, $value, bool $overwrite = true)
  {

    if (!$this->nid) {
      return;
    }

    $node = Node::load($this->nid);
    if (!$node) {
      return;
    }

    // Lets set the field to the new value, ONLY if the target is empty or overwrite is true
    if ($overwrite || empty($node->get($field_name)->getValue())) {
      $node->set($field_name, $value);
      $node->save();
    }
  }

  /**
   * Marks the node as processed.
   *
   * @return void
   *
   */
public function setProcessed()
{
  if (!$this->nid) {
    return;
  }
  $entity_id = (int) $this->nid;
  $entity_type = (string) $this->getBundleType();
  $processed = 1;
  $ts = (string) date('Y-m-d H:i:s');

  try {
    $db = Database::getConnection();
    $query = $db->select('metadata_hex_processed', 'mhp')
      ->fields('mhp', ['entity_id'])
      ->condition('entity_id', $entity_id)
      ->execute()
      ->fetchField();

    if ($query) {
      // Update the existing record
      $db->update('metadata_hex_processed')
        ->fields([
          'last_modified' => $ts,
          'processed' => $processed,
        ])
    // Assert that all mapped keys exist in processed metadata
        ->condition('entity_id', $entity_id)
        ->execute();
    } else {
      // Insert a new record
      $db->insert('metadata_hex_processed')
        ->fields([
          'entity_id' => $entity_id,
          'last_modified' => $ts,
          'processed' => $processed,
        ])
        ->execute();
    }
  } catch (\Exception $e) {
    // Handle the exception as needed
  }
}

  /**
   * Sets a revision message when updating a node.
   *
   * @return void
   *
   */
  public function setRevision()
  {
    if (!$this->nid) {
      return;
    }

    $node = Node::load($this->nid);
    if (!$node) {
      return;
    }
    $node_type = $this->getBundleType();
    $revisions_enabled = \Drupal::config("node.type.$node_type")->get('enable_revisions');

    if ($revisions_enabled && $node->isNewRevision()) {
      $node->setRevisionLogMessage("Updated metadata processing.");
      $node->save();
    }
  }
}
