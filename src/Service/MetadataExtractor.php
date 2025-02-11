<?php

namespace Drupal\metadata_hex\Service;

use Psr\Log\LoggerInterface;
//use Smalot\Pa/rser\Parser;
use Exception;
use Drupal\metadata_hex\Base\MetadataHexCore;
use Drupal\metadata_hex\Utility\MetadataParser as Parser;
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
      $parser = new Parser();
      $file = $parser->parseFile($file_uri);
      $details = $file->getDetails();

      // TODO what the hell is this about?!
      $metadata = [
        'title' => $details['Title'] ?? '',
        'author' => $details['Author'] ?? '',
        'created_date' => $details['CreationDate'] ?? '',
        'keywords' => isset($details['Keywords']) ? explode(',', $details['Keywords']) : [],
      ];

      return $this->sanitizeArray($metadata);
    } catch (Exception $e) {
      $this->logger->error("Error parsing  metadata: " . $e->getMessage());
      throw new Exception("Error parsing  metadata: " . $e->getMessage());
    }
  }

  /**
   * Sanitizes an array's keys and values.
   *
   * @param array $metadata
   *   The metadata array.
   *
   * @return array
   *   The sanitized metadata.
   * 
   */
  // TODO is this a duplicate method from parser?
  private function sanitizeArray(array $metadata): array
  {
    return array_map(function ($value) {
      return is_array($value) ? array_map('trim', $value) : trim(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
    }, $metadata);
  }
}