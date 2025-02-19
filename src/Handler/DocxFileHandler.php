<?php

namespace Drupal\metadata_hex\Handler;

use Exception;
use PhpOffice\PhpWord\IOFactory;

/**
 * Class DocxFileHandler
 *
 * Handles ingestion and extraction of metadata from DOCX files.
 */
class DocxFileHandler extends FileHandler {

  /**
   * Extracts metadata from a DOCX file.
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
      $phpWord = IOFactory::load($this->fileUri);
      $docInfo = $phpWord->getDocInfo();

      return $docInfo;
      // @todo test this
      // return [
      //   'title' => $docInfo->getTitle(),
      //   'creator' => $docInfo->getCreator(),
      //   'company' => $docInfo->getCompany(),
      //   'description' => $docInfo->getDescription(),
      //   'keywords' => $docInfo->getKeywords(),
      //   'last_modified_by' => $docInfo->getLastModifiedBy(),
      //   'created' => $docInfo->getCreated(),
      //   'modified' => $docInfo->getModified(),
      // ];
    } catch (Exception $e) {
      throw new Exception("Error parsing DOCX file: " . $e->getMessage());
    }
  }

  /**
   * Returns an array of supported file extensions.
   *
   * @return array
   *   The supported file extensions.
   */
  public function getSupportedExtentions(): array {
    return ['doc', 'docx'];
  }
}
