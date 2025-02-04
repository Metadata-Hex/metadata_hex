<?php

namespace Drupal\metadata_hex\Service;

use Psr\Log\LoggerInterface;
use Exception;
use FileHandlerManager;

/**
 * Class MetadataExtractor
 *
 * Extracts metadata from a PDF file using the Smalot\PdfParser service.
 */
class MetadataExtractor extends MetadataHexCore {


  protected $fileHandlerManager;
  /**
   * Constructs the MetadataExtractor class.
   *
   * @param LoggerInterface $logger
   *   The logger service.
   */
  public function __construct(LoggerInterface $logger) {
    parent::__construct($logger);
    $this->fileHandlerManager = new FileHandlerManager();
  }

  /**
   * Initializes the extractor.
   */
  public function init() {
    $this->logger->info('MetadataExtractor initialized');
  }

  /**
   * Extracts metadata from a PDF file.
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
  protected function extractMetadata(string $file_uri): array {
    if (!file_exists($file_uri)) {
      $this->logger->error("Invalid file: $file_uri");
      throw new Exception("Invalid file: $file_uri");
    }

    try {
      $handler = $this->fileHandlerManager->getHandlerForExtension( pathinfo($file_uri, PATHINFO_EXTENSION));
      $metadata = $handler->extractMetadata($file_uri);

      return $this->sanitizeArray($metadata);
    } catch (Exception $e) {
      $this->logger->error("Error parsing file metadata: " . $e->getMessage());
      throw new Exception("Error parsing file metadata: " . $e->getMessage());
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
   */
  private function sanitizeArray(array $metadata): array {
    return array_map(function ($value) {
      return is_array($value) ? array_map('trim', $value) : trim(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
    }, $metadata);
  }
}