<?php

namespace Drupal\metadata_hex\Handler;

use Smalot\PdfParser\Parser;
use Exception;

/**
 * Class PdfFileHandler
 *
 * Handles parsing operations for extracted metadata from PDF files.
 * Responsible for:
 * - Validating field mappings
 * - Extracting and cleaning data
 * - Ensuring compatibility with Drupal field structures
 */
class PdfFileHandler extends FileHandler {

  /**
   * Extracts metadata from a PDF file.
   *
   * @return array
   *   The extracted metadata.
   *
   * @throws \Exception
   *   If the file cannot be loaded or if an error occurs during parsing.
   */
  public function extractMetadata(): array {
    if (!$this->isValidFile()) {
      $this->logger->error("Invalid or unreadable file: {$this->fileUri}");
      throw new Exception("Invalid or unreadable file: {$this->fileUri}");
    }

    try {
      $parser = new Parser();
      $pdf = $parser->parseFile($this->fileUri);
      $details = $pdf->getDetails();

      return [
        'title' => $details['Title'] ?? '',
        'author' => $details['Author'] ?? '',
        'subject' => $details['Subject'] ?? '',
        'keywords' => $details['Keywords'] ?? '',
        'creator' => $details['Creator'] ?? '',
        'producer' => $details['Producer'] ?? '',
        'created' => $details['CreationDate'] ?? '',
        'modified' => $details['ModDate'] ?? '',
      ];
    } catch (Exception $e) {
      $this->logger->error("Error parsing PDF file: " . $e->getMessage());
      throw new Exception("Error parsing PDF file: " . $e->getMessage());
    }
  }

  /**
   * Returns an array of supported file extensions.
   *
   * @return array
   *   The supported file extensions.
   */
  public function getSupportedExtentions(): array {
    return ['pdf'];
  }
}