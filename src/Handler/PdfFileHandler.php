<?php

namespace Drupal\metadata_hex\Handler;

use Exception;
use Smalot\PdfParser\Parser;

/**
 * Class PdfFileHandler
 * 
 * @MetadataHex(
 *   id = "pdf_file_handler",
 *   extensions = {"pdf"}
 * )
 *
 */
class PdfFileHandler extends FileHandler {


public function getSupportedExtentions(): array {
  return ['pdf', 'pdfx'];
}

public function getSupportedMimeTypes(): array {
  return ['application/pdf', 'application/pdfx'];
}
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
      throw new Exception("Invalid or unreadable file: {$this->fileUri}");
    }

    try {
      $parser = new Parser();
      $pdf = $parser->parseFile($this->fileUri);
      $details = $pdf->getDetails();
      return $details;
    } catch (Exception $e) {
      throw new Exception("Error parsing PDF file: " . $e->getMessage());
    }
  }
}