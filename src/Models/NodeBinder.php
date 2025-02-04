<?php

namespace Drupal\metadata_hex\Model;

use Drupal\node\Entity\Node;
use Drupal\file\Entity\File;
use Psr\Log\LoggerInterface;
use Exception;

/**
 * Class NodeBinder
 *
 * Encapsulates the object and functionality tied to nodes.
 */
class NodeBinder extends MetadataHexCore {

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
   * Constructs the NodeBinder class.
   *
   * @param LoggerInterface $logger
   *   The logger service.
   */
  public function __construct(LoggerInterface $logger) {
    parent::__construct($logger);
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
  protected function init($input) {
    if ($input instanceof File) {
      $this->fid = $input->id();
    } elseif ($input instanceof Node) {
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
  protected function getFileUris(): array {
    if (!$this->nid) {
      return [];
    }

    $node = Node::load($this->nid);
    if (!$node) {
      return [];
    }

    $file_uris = [];
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
  public function getIsNodeProcessed(): bool {
    if (!$this->nid) {
      return false;
    }

    $query = \Drupal::database()->select('node_processing_status', 'nps')
      ->fields('nps', ['processed'])
      ->condition('nid', $this->nid)
      ->execute()
      ->fetchField();

    return (bool) $query;
  }

  /**
   * Retrieves the node from a given NID.
   *
   * @return Node|null
   *   The loaded node or NULL if not found.
   */
  public function getNode(): ?Node {
    return $this->nid ? Node::load($this->nid) : null;
  }

  /**
   * Extracts metadata from the node's associated files.
   *
   * @return array
   *   An array of extracted metadata.
   */
  public function ingestNodeFileMeta(): array {
    $metadata = [];

    if (!$this->nid) {
      return $metadata;
    }

    $node = Node::load($this->nid);
    if (!$node) {
      return $metadata;
    }

    foreach ($node->getFields() as $field_name => $field) {
      if ($field->getFieldDefinition()->getType() === 'file') {
        foreach ($field->getValue() as $file_item) {
          $file = File::load($file_item['target_id']);
          if ($file) {
            $metadata[$file->id()] = [
              'uri' => $file->getFileUri(),
              'metadata' => [], // Placeholder for actual metadata extraction.
            ];
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
  public function initNode(string $file_uri, string $target_bundle, ?string $field_name = null): Node {
    $node = Node::create([
      'type' => $target_bundle,
      'status' => 0,
      'title' => 'Generated Node',
    ]);

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
   */
  public function save() {
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
  protected function setField(string $field_name, $value, bool $overwrite = true) {
    if (!$this->nid) {
      return;
    }

    $node = Node::load($this->nid);
    if (!$node) {
      return;
    }

    if ($overwrite || empty($node->get($field_name)->getValue())) {
      $node->set($field_name, $value);
      $node->save();
    }
  }

  /**
   * Marks the node as processed.
   */
  public function setProcessed() {
    if (!$this->nid) {
      return;
    }

    \Drupal::database()->insert('node_processing_status')
      ->fields(['nid' => $this->nid, 'processed' => 1])
      ->execute();
  }

  /**
   * Sets a revision message when updating a node.
   */
  public function setRevision() {
    if (!$this->nid) {
      return;
    }

    $node = Node::load($this->nid);
    if (!$node) {
      return;
    }

    if ($node->isNewRevision()) {
      $node->setRevisionLogMessage("Updated metadata processing.");
      $node->save();
    }
  }
}