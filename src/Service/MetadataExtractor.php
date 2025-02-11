<?php

namespace Drupal\metadata_hex\Service;

use Drupal\metadata_hex\Base\MetadataHexCore;
use Drupal\metadata_hex\Utility\MetadataParser as Parser;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Class MetadataExtractor
 *
 * Extracts metadata from a  file using the Smalot\Parser service.
 */
class MetadataExtractor extends MetadataHexCore
{

  /**
   * Constructs the MetadataExtractor class.
   *
   * @param LoggerInterface $logger
   *   The logger service.
   */
  public function __construct(LoggerInterface $logger)
  {
    parent::__construct($logger);
  }

  /**
   * Initializes the extractor.
   */
  public function init()
  {
    $this->logger->info('MetadataExtractor initialized');
  }

  /**
   * Extracts metadata from a  file.
   *
   * @param string $file_uri
   *   The URI of the file.
   *
   * @return array
   *   The extracted metadata.
   *
   * @throws Exception
   *   If the file cannot be read or parsed.
   */
  protected function extractMetadata(string $file_uri): array
  {
    if (!file_exists($file_uri) || pathinfo($file_uri, PATHINFO_EXTENSION) !== '') {
      $this->logger->error("Invalid file: $file_uri");
      throw new Exception("Invalid file: $file_uri");
    }

    try {
      $parser = new Parser($this->logger);
      $file = $parser->parseFile($file_uri);
      return $file->getDetails();

    } catch (Exception $e) {
      $this->logger->error("Error parsing  metadata: " . $e->getMessage());
      throw new Exception("Error parsing  metadata: " . $e->getMessage());
    }
  }
}