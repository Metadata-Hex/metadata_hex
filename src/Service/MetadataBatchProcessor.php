<?php

namespace Drupal\metadata_hex\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;
use Exception;
use Drupal\metadata_hex\Base\MetadataHexCore;
use Drupal\metadata_hex\Model\MetadataEntity;
use Drupal\metadata_hex\Service\MetadataExtractor;
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
   * List of file URIs to process.
   *
   * @var array
   */
  protected $files = [];

  /**
   * Constructs the MetadataBatchProcessor class.
   *
   * @param LoggerInterface $logger
   *   The logger service.
   * @param MetadataExtractor $extractor
   *   The metadata extraction service.
   */
  public function __construct(LoggerInterface $logger, MetadataExtractor $extractor)
  {
    parent::__construct($logger);
    $this->extractor = $extractor;
    $this->files = [];
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
   * Callback function to print batch success messages.
   *
   * @param bool $success
   *   Whether the batch process was successful.
   * @param array $results
   *   Processed results.
   * @param array $failed_operations
   *   Failed operations.
   */
  protected function BatchFinished(bool $success, array $results, array $failed_operations)
  {
    if ($success) {
      $this->logger->info('Batch process completed successfully with ' . count($results) . ' processed files.');
    } else {
      $this->logger->error('Batch process failed with errors in ' . count($failed_operations) . ' operations.');
    }
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
   * Scans a directory for files.
   *
   * @param string $dir_to_scan
   *   Directory to scan.
   */
  protected function ingestFiles(string $dir_to_scan)
  {
    if (!is_dir($dir_to_scan)) {
      $this->logger->warning("Invalid directory: $dir_to_scan");
      return;
    }
    // todo this needs to pull compatible extentions automatically
    $files = scandir($dir_to_scan);
    foreach ($files as $file) {
      if (pathinfo($file, PATHINFO_EXTENSION) === 'pdf') {
        $this->files[] = "$dir_to_scan/$file";
      }
    }
  }

  /**
   * Processes files in a directory.
   */
  protected function processFiles()
  {
    $this->ingestFiles('public://');// @TODO make this dynamic
    $categorized = $this->categorizeFiles();

    foreach ($categorized['referenced'] as $file_uri) {
      $metadataEntity = new MetadataEntity($this->logger);
      $metadataEntity->loadFromFile($file_uri);
      $metadataEntity->writeMetadata();
    }

    foreach ($categorized['unreferenced'] as $file_uri) {
      $metadataEntity = new MetadataEntity($this->logger);
      $metadataEntity->loadFromFile($file_uri);
      $metadataEntity->writeMetadata();
    }

    $this->logger->info('File processing completed.');
  }

  /**
   * Processes a node and updates its metadata.
   *
   * @param string $nid
   *   The node ID to process.
   */
  public function processNode(string $nid)
  {

    if (!is_numeric($nid) || !$node = \Drupal\node\Entity\Node::load($nid)) {
      $this->logger->error("Invalid node ID: $nid");
    }

    $metadataEntity = new MetadataEntity($this->logger);
    $metadataEntity->initialize($node);
    $metadataEntity->writeMetadata();
  }

  /**
   * Processes all nodes of a specific bundle type.
   */
  protected function processNodes()
  {
    $nids = \Drupal::entityQuery('node')
      ->condition('type', $this->bundleType)
      ->execute();

    foreach ($nids as $nid) {
      $this->processNode($nid);
    }
  }
}