<?php

namespace Drupal\metadata_hex\Handler;

use Drupal\metadata_hex\Base\MetadataHexCore;
use Drupal\file\Entity\File;

/**
 * Class FileHandler
 *
 * Handles parsing operations for extracted metadata. Responsible for:
 * - Validating field mappings
 * - Extracting and cleaning data
 * - Ensuring compatibility with Drupal field structures
 */
abstract class FileHandler extends MetadataHexCore {

  /**
   * Array of IDs from nodes that reference the file.
   *
   * @var string[]
   */
  protected $associatedEntityIds;

  /**
   * The ID of the file.
   *
   * @var string
   */
  protected $fileId;

  /**
   * The file type of the file.
   *
   * @var string
   */
  protected $fileType;

  /**
   * The URI of the file.
   *
   * @var string
   */
  protected $fileUri;

  /**
   * Constructs the FileHandler class.
   *
   * @param string $filePath
   *   The path to the file.
   */
  public function __construct(string $filePath) {
    parent::__construct(\Drupal::service('logger.factory')->get('metadata_hex'));
    $this->fileUri = $filePath;
    $this->fileType = pathinfo($filePath, PATHINFO_EXTENSION);
  }

  /**
   * Extract metadata from the file.
   * Must be implemented in child classes.
   *
   * @return array
   *   The extracted metadata.
   */
  abstract public function extractMetadata(): array;

  /**
   * Returns the extensions supported.
   * Must be implemented in child classes.
   *
   * @return array
   *   The supported extensions.
   */
  abstract public function getSupportedExtentions(): array;

  /**
   * Returns the file extension.
   *
   * @return string
   *   The file extension.
   */
  protected function getFileType(): string {
    return $this->fileType;
  }

  /**
   * Finds all entity references to a file.
   *
   * @return array
   *   Array containing file reference information.
   */
  protected function getFileReferences(): array {
    $file = \Drupal::entityTypeManager()
      ->getStorage('file')
      ->loadByProperties(['uri' => $this->fileUri]);

    if (!empty($file)) {
      $file = reset($file);
      $file_usage = \Drupal::service('file.usage')->listUsage($file);

      $entity_ids = [];
      foreach ($file_usage as $module => $usage) {
        foreach ($usage as $entity_type => $entities) {
          foreach ($entities as $entity_id => $count) {
            if ($entity_type === 'node') {
              $entity_ids[] = $entity_id;
            }
          }
        }
      }

      $this->associatedEntityIds = [
        'referenced' => !empty($entity_ids),
        'entity_ids' => $entity_ids,
      ];
    } else {
      $this->associatedEntityIds = [
        'referenced' => FALSE,
        'entity_ids' => [],
      ];
    }

    return $this->associatedEntityIds;
  }

  /**
   * Determines if the file exists and is readable.
   *
   * @return bool
   *   TRUE if the file exists and is readable, FALSE otherwise.
   */
  protected function isValidFile(): bool {
    return file_exists($this->fileUri) && is_readable($this->fileUri);
  }
}