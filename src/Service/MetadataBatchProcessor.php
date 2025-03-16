<?php

namespace Drupal\metadata_hex\Service;

use Drupal\metadata_hex\Base\MetadataHexCore;
use Drupal\metadata_hex\Model\MetadataEntity;
use Drupal\metadata_hex\Service\MetadataExtractor;
use Drupal\metadata_hex\Service\SettingsManager;
use Psr\Log\LoggerInterface;

/**
 * Class MetadataBatchProcessor
 *
 * Handles high-level file processing operations, such as categorizing,
 * scanning, and processing files and nodes.
 */
class MetadataBatchProcessor extends MetadataHexCore
{

  /**
   * The bundle type used for updating nodes during cron operations.
   *
   * @var string
   */
  protected $bundleType;

  /**
   * Indicates whether the class is running during a cron operation.
   *
   * @var bool
   */
  protected $cron = true;

  /**
   * Indicates whether existing nodes of the given type should be reprocessed.
   *
   * @var bool
   */
  protected $reprocess = false;

  /**
   * Handles metadata extraction from files.
   *
   * @var MetadataExtractor
   */
  protected $extractor;

  /**
   * file system service
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $file_system;

  /**
   * List of file URIs to process.
   *
   * @var array
   */
  protected $files = [];

  /**
   * Summary of settingsManager
   * @var SettingsManager
   */
  protected $settingsManager;

  /**
   * Constructs the MetadataBatchProcessor class.
   *
   * @param LoggerInterface $logger
   *   The logger service.
   * @param MetadataExtractor $extractor
   *   The metadata extraction service.
   */
  public function __construct(LoggerInterface $logger, MetadataExtractor $extractor, $file_system=null)
  {
    parent::__construct($logger);
    $this->extractor = $extractor;
    $this->settingsManager = new SettingsManager();
    $this->file_system = \Drupal::service('file_system');
  }

  /**
   * Initializes the batch processor.
   *
   * @param string $bundleType
   *   The bundle type to process.
   * @param bool $cron
   *   Whether this is running via cron.
   * @param bool $reprocess
   *   Whether existing nodes should be reprocessed.
   */
  public function init(string $bundleType, bool $cron = false, bool $reprocess = false)
  {
    $this->bundleType = $bundleType;
    $this->cron = $cron;
    $this->reprocess = $reprocess;
    $this->logger->info('MetadataBatchProcessor initialized with bundle type: ' . $bundleType);
  }

  /**
   * Sorts files into processed and unprocessed categories.
   *
   * @return array
   *   Categorized files.
   */
  protected function categorizeFiles(): array
  {
    $processed = [];
    $referenced = [];
    $unreferenced = [];

    foreach ($this->files as $file_uri) {
      $file = \Drupal::entityTypeManager()->getStorage('file')->loadByProperties(['uri' => $file_uri]);

      if (!empty($file)) {
        $file = reset($file);
        $query = \Drupal::database()->select('node_field_data', 'n')
          ->fields('n', ['nid'])
          ->condition('status', 1)
          ->execute()
          ->fetchField();

        if ($query) {
          $processed[] = $file_uri;
        } else {
          $referenced[] = $file_uri;
        }
      } else {
        $unreferenced[] = $file_uri;
      }
    }

    return ['processed' => $processed, 'referenced' => $referenced, 'unreferenced' => $unreferenced];
  }


  /**
   * Processes files in a directory.
   * 
   * @var int $file
   * 
   * @return void
   */
  public static function processFile($file)
  {
    $metadataEntity = new MetadataEntity(\Drupal::logger('logger_channel.default'));
    $metadataEntity->initialize($file);
    $metadataEntity->writeMetadata();
  }

  /**
   * Processes files in a directory.
   * 
   * @var string $file_uri
   * 
   * @return void
   */
  public static function processFileUri($file_uri)
  {
    $logger = \Drupal::logger('logger_channel.default');
    if (!is_string($file_uri)) {
      $logger->error("Invalid file_url: $file_uri");
    }

    $file = \Drupal::entityTypeManager()->getStorage('file')->loadByProperties(['uri' => $file_uri]);
    $metadataEntity = new MetadataEntity($logger);
    $metadataEntity->initialize($file);
    $metadataEntity->writeMetadata();
  }

  /**
   * Process multiple files
   * 
   * @var array $fids
   * 
   * @return void
   */
  public static function processFiles(array $fids)
  {

    // Process the incoming array of fids
    foreach ($fids as $fid) {
      $file = null;

      // if the $fid is actuall a file, set file
      if ($fid instanceof File) {
        $file = $fid;
      }
      // treat it like a fid
      else if (is_string($fid) || is_int($fid)) {
        $file = \Drupal::entityTypeManager()->getStorage('file')->load($fid);
      }

      // if there's no file, then we dont proceed
      if (!empty($file)) {
        self::processFile($file);
      }
    }
  }

  /**
   * Processes a node and updates its metadata.
   *
   * @param string $nid
   *   The node ID to process.
   */
  public static function processNode(string $nid)
  {
    $logger = \Drupal::logger('logger_channel.default');

    if (!is_numeric($nid) || !$node = \Drupal\node\Entity\Node::load($nid)) {
      $logger->error("Invalid node ID: $nid");
    }

    $metadataEntity = new MetadataEntity($logger);
    $metadataEntity->initialize($node);
    $metadataEntity->writeMetadata();
  }

  /**
   * Processes all nodes of a specific bundle type.
   */
  public static function processNodes()
  {
    $config = \Drupal::configFactory()->get('metadata_hex.settings');
    $bundleType = $config->get('node_process.bundle_types');
    if (!is_array($bundleType)) {
      $bundleType = [$bundleType];
    }
    $nids = \Drupal::entityQuery('node')
      ->condition('type', $bundleType, 'IN')
      ->accessCheck(FALSE)
      ->execute();

    foreach ($nids as $nid) {
      self::processNode($nid);
    }
  }
}
