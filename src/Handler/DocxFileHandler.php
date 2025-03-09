<?php

namespace Drupal\metadata_hex\Handler;

use Exception;
use PhpOffice\PhpWord\IOFactory;

/**
 * Class DocxFileHandler
 *
 * @MetadataHex(
 *   id = "docx_file_handler",
 *   extensions = {"docx"}
 * )
 * Handles ingestion and extraction of metadata from DOCX files.
 */
class DocxFileHandler extends FileHandler {

  /**
   * Extracts metadata from a DOCX file.
   *
   * @return 
   *   The extracted metadata.
   *
   * @throws \Exception
   *   If the file cannot be loaded or if an error occurs during parsing.
   */
  public function extractMetadata(): array {
    if (!$this->isValidFile()) {
      throw new Exception("Invalid or unreadable file: {$this->fileUri}");
    }
    if (!file_exists($this->fileUri)) {
      throw new \Exception("Error: DOCX file does not exist at $this->fileUri");
  }
  
  $file_system = \Drupal::service('file_system');
  $real_path = $file_system->realpath($this->fileUri);

//   $zip = new \ZipArchive();
//   $zip_status = $zip->open($real_path);
//   //$zip->close();
// //   $handle = fopen($this->fileUri, "rb");
// //   $first_bytes = fread($handle, 8);
// //   fclose($handle);
  
// //   echo "First 8 bytes: " . bin2hex($first_bytes) . "\n";
// //   $filesize = filesize($this->fileUri);
// // $handle = fopen($this->fileUri, "rb");
// // fseek($handle, -16, SEEK_END);
// // $last_bytes = fread($handle, 16);
// // fclose($handle);

// // echo "Last 16 bytes: " . bin2hex($last_bytes) . "\n";
// $content = file_get_contents($this->fileUri);
// $pos = strpos($content, "\x50\x4B\x05\x06"); // Look for ZIP EOCD signature

// if ($pos !== false) {
//     echo "✅ EOCD Signature found at position: $pos\n";
// } else {
//     echo "❌ No EOCD Signature found. The DOCX may be corrupt.\n";
// }


//   $eocd_pos = $pos;//.7459; // EOCD position you found

//   // Load the full file content
//   $content = file_get_contents($this->fileUri);
  
//   // Trim the file at the EOCD signature + 18 bytes (standard footer length)
//   $fixed_content = substr($content, 0, $eocd_pos + 18);
  
//   // Save the corrected file
//   file_put_contents($this->fileUri, $fixed_content);
  
//   echo "✅ File truncated at correct EOCD location.\n";
//   $real_path = $file_system->realpath($this->fileUri);
//   $zip = new \ZipArchive();
//   $zip_status = $zip->open($real_path);

try {
  $docxText = $this->readDocxWithoutZipArchive($this->fileUri);
  echo PHP_EOL.'--'.$docxText.'---'.PHP_EOL;
} catch (\Exception $e) {
  echo "Error: " . $e->getMessage();
}
return $docxText;
//     try {
//      // echo filesize($this->fileUri);
// //echo PHP_EOL.$real_path.': '.$zip_status.PHP_EOL;
// $phpWord = IOFactory::load($real_path);
//       $docInfo = $phpWord->getDocInfo();
      
//       return $docInfo;
//     } catch (Exception $e) {
//       throw new Exception("Error parsing DOCX file: " . $e->getMessage());
//     }
  }
/**
 * Reads DOCX file contents without relying on ZipArchive.
 */
public function readDocxWithoutZipArchive($fileUri) {
  if (!file_exists($fileUri)) {
      throw new \Exception("DOCX file not found: $fileUri");
  }

  $phpWord = IOFactory::load($fileUri, 'Word2007');
  $text = '';

  foreach ($phpWord->getSections() as $section) {
      foreach ($section->getElements() as $element) {
          if (method_exists($element, 'getText')) {
              $text .= $element->getText() . "\n";
          }
      }
  }

  return trim($text);
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
