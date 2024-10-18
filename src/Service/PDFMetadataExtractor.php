<?php
namespace Drupal\pdf_meta_extraction\Service;

use Smalot\PdfParser\Parser;
use Psr\Log\LoggerInterface;

class PDFMetadataExtractor {
  
  protected $parser;
  protected $logger;

  public function __construct() {
    $this->parser = new Parser();
    $this->logger = \Drupal::logger('pdf_meta_extraction');
  }

/**
 * Check if an external file is accessible.
 *
 * @param string $url
 * @return bool
 */
private function isExternalFileAccessible($url) {
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_NOBODY, true);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_TIMEOUT, 10);
  curl_exec($ch);
  $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  return $httpCode >= 200 && $httpCode < 400;
}

  /**
   * Summary of getMetadata
   * @param mixed $filePath
   * @return array|null
   */
  public function getMetadata($filePath) {
    // if (!file_exists($filePath)) {
    //   $this->logger->error('File not found : @filePath', ['@filePath' => $filePath]);
    //   return NULL;
    // }    
    // if (!is_readable($filePath)) {
    //   $this->logger->error('File not readable : @filePath', ['@filePath' => $filePath]);
    //   return NULL;
    // }
    // if (!$this->isExternalFileAccessible($filePath)) {
    //   $this->logger->error('File not accessible : @filePath', ['@filePath' => $filePath]);
    //   return NULL;
    // }

    try {

      $pdf = $this->parser->parseFile($filePath);

      $details = ($pdf->getDetails());
      $sanatizedDetails = $this->sanitize_array_values($details);

      return $sanatizedDetails;
    } catch (\Exception $e) {
      $this->logger->error('Error parsing PDF file: @message', ['@message' => $e->getMessage()]);
      return NULL;
    }
  }

  /**
 * Sanitizes an array by removing illegal characters from its values.
 *
 * @param  $details
 *   The array to sanitize.
 *
 * @return array
 *   The sanitized array.
 */
function sanitize_array_values( $details) {
  $sanitized_details = [];

  foreach ($details as $key => $value) {
 

    if (is_string($value)){
    // Trim whitespace from the beginning and end of the string.
    $value = trim($value);
    
    // Remove any HTML tags.
    $value = strip_tags($value);
    
    // Convert special characters to HTML entities to prevent XSS.
    $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
    // Optionally, remove any characters that are not alphanumeric or basic punctuation.
    // Uncomment the next line if you want to apply this stricter sanitization.
    // $value = preg_replace('/[^a-zA-Z0-9\s\.\,\-]/', '', $value);
    
    // Assign the sanitized value back to the array.
    $sanitized_details[$key] = $value;
  }

  return $sanitized_details;
}



  public function getBody($filePath){
    // if (!file_exists($filePath)) {
    //   $this->logger->error('File not found : @filePath', ['@filePath' => $filePath]);
    //   return NULL;
    // }    
    // if (!is_readable($filePath)) {
    //   $this->logger->error('File not readable : @filePath', ['@filePath' => $filePath]);
    //   return NULL;
    // }
    
    // if (!$this->isExternalFileAccessible($filePath)) {
    //   $this->logger->error('File not accessible : @filePath', ['@filePath' => $filePath]);
    //   return  null;
    // }

    try {

      $pdf = $this->parser->parseFile($filePath);
      return $pdf->getText();
    } catch (\Exception $e) {
      $this->logger->error('Body not readable : @filePath', ['@filePath' => $filePath]);

    }
    return null;
  }
}
