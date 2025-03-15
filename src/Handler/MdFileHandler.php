<?php

namespace Drupal\metadata_hex\Handler;

use Exception;
use Symfony\Component\Yaml\Yaml;

/**
 * Class MdFileHandler
 *
 * @MetadataHex(
 *   id = "md_file_handler",
 *   extensions = {"md"}
 * )
 * Handles ingestion and extraction of metadata from DOCX files.
 */
class MdFileHandler extends FileHandler
{

  public function getSupportedExtentions(): array
  {
    return ['md'];
  }

  public function getSupportedMimeTypes(): array
  {
    return ['text/markdown'];
  }


  /**
   * Extracts metadata from a DOCX file.
   *
   * @return array
   *   The extracted metadata.
   *
   * @throws \Exception
   *   If the file cannot be loaded or if an error occurs during parsing.
   */
  public function extractMetadata(): array
  {

    if (!$this->isValidFile()) {
      throw new Exception("Invalid or unreadable file: {$this->fileUri}");
    }

    // Read the file
    $content = file_get_contents($this->fileUri);

    // Match frontmatter using regex (YAML-style --- at the start)
    if (preg_match('/^---\n(.*?)\n---\n/s', $content, $matches)) {
      $yamlContent = $matches[1];
      $markdownBody = preg_replace('/^---\n.*?\n---\n/s', '', $content, 1);

      // Parse YAML into an array
      $frontmatter = yaml::parse($yamlContent) ?: [];
    } else {
      // No frontmatter found, assume only Markdown content
      $frontmatter = [];
      $markdownBody = $content;
    }

    return [
      'frontmatter' => $frontmatter,
      'markdown' => trim($markdownBody),
    ];
  }
}
