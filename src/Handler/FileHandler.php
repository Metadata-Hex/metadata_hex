<?php
namespace Drupal\metadata_hex\Handler;

require_once __DIR__ . '/../../vendor/autoload.php';


use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class FileHandler
 *
 * Handles parsing operations for extracted metadata. Responsible for:
 * - Validating field mappings
 * - Extracting and cleaning data
 * - Ensuring compatibility with Drupal field structures
 */
abstract class FileHandler extends PluginBase implements FileHandlerInterface, ContainerFactoryPluginInterface
{

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
   * The file system service
   * 
   * @var \Drupal\Core\File\FileSystemInterface $fileSystem
   * 
   */
  protected $fileSystem;

  /**
   * Constructs a FileHandler object.
   *
   * @param array $configuration
   *   The plugin configuration array.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $file_system)
  {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->fileSystem = $file_system;
  }

  /**
   * Summary of setFileUri
   * @param string $fileUri
   * @return void
   */
  public function setFileUri(string $fileUri)
  {
    if (strpos($fileUri, '://') === false) {
      $fileUri = 'public://' . ltrim($fileUri, '/');
    }
    $this->fileUri = $fileUri;
    $this->fileType = pathinfo($this->fileUri, PATHINFO_EXTENSION);
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
   * Returns an array of supported file extensions.
   *
   * @return array
   *   The supported file extensions.
   */
  abstract public function getSupportedMimeTypes(): array;

  /**
   * Returns an array of supported file extensions.
   *
   * @return array
   *   The supported file extensions.
   */
  abstract public function getSupportedExtentions(): array;

  /**
   * Returns the file extension.
   *
   * @return string
   *   The file extension.
   */
  protected function getFileType(): string
  {
    return $this->fileType;
  }

  /**
   * Returns the plugin ID.
   */
  public function getPluginId()
  {
    return $this->pluginId;
  }

  /**
   * Returns the plugin definition.
   */
  public function getPluginDefinition()
  {
    return $this->pluginDefinition;
  }

  /**
   * Finds all entity references to a file.
   *
   * @return array
   *   Array containing file reference information.
   */
  protected function getFileReferences(): array
  {
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
  protected function isValidFile(): bool
  {
    return file_exists($this->fileUri) && is_readable($this->fileUri);
  }

  /**
   * Factory method for dependency injection.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
  {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      null
    );
  }

  /**
   * Processes the file.
   *
   * @param string $file_path
   *   The path to the file.
   *
   * @return mixed
   *   The result of processing.
   */
  public function process($file_path): mixed
  {
    return $file_path;
  }
}