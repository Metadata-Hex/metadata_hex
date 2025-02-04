<?php

namespace Drupal\metadata_hex\Utility;

use Psr\Log\LoggerInterface;
use Exception;

/**
 * Class MetadataParser
 *
 * Handles parsing operations for extracted metadata.
 * Responsible for:
 * - Validating field mappings
 * - Extracting and cleaning data
 * - Ensuring compatibility with Drupal field structures
 */
class MetadataParser extends MetadataHexCore {

  /**
   * The fields available.
   *
   * @var array
   */
  protected $availableFields;

  /**
   * Initialized MetadataExtractor.
   *
   * @var MetadataExtractor
   */
  protected $extractor;

  /**
   * The field mappings.
   *
   * @var array
   */
  protected $fieldMapping;

  /**
   * Determines if we are strictly handling string comparisons.
   *
   * @var bool
   */
  protected $strictHandling = false;

  /**
   * Constructs the MetadataParser class.
   *
   * @param LoggerInterface $logger
   *   The logger service.
   * @param object $bundleType
   *   The bundle type object.
   */
  public function __construct(LoggerInterface $logger, $bundleType) {
    parent::__construct($logger);
    $this->bundleType = $bundleType;
  }

  /**
   * Initializes the parser.
   */
  public function init() {
    $this->availableFields = $this->bundleType
      ->extractFields()
      ->extractFieldNameValues()
      ->toArray();

    // Grab the module configuration.
    $config = \Drupal::config('metadata_hex.settings');

    // Extract the entered field mappings from config.
    $extractedFieldMaps = $config->get('field_mappings');
    $this->fieldMapping = $this->cleanFieldMapping($extractedFieldMaps);

    // Initialize the extractor.
    $this->extractor = new MetadataExtractor();
  }

  /**
   * Removes any field mappings that do not match available fields.
   *
   * @param array $dirty_fieldmapping
   *   The uncleaned field mappings.
   *
   * @return array
   *   The cleaned field mappings.
   *
   * @throws Exception
   *   If input is invalid.
   */
  protected function cleanFieldMapping(array $dirty_fieldmapping): array {
    if (!is_array($dirty_fieldmapping)) {
      throw new Exception("Invalid input for field mapping. Expected an array.");
    }

    $cleaned = array_filter($dirty_fieldmapping, function ($key) {
      return in_array($key, $this->availableFields, true);
    }, ARRAY_FILTER_USE_KEY);

    if (empty($cleaned)) {
      throw new Exception("All field mappings were removed. No valid mappings found.");
    }

    return $cleaned;
  }

  /**
   * Cleans and standardizes the metadata associative array.
   *
   * @param array $dirty_metadata
   *   The uncleaned metadata.
   *
   * @return array
   *   The cleaned metadata.
   *
   * @throws Exception
   *   If input is invalid.
   */
  protected function cleanMetadata(array $dirty_metadata): array {
    if (!is_array($dirty_metadata)) {
      throw new Exception("Invalid metadata input. Expected an associative array.");
    }

    $clean_metadata = [];
    foreach ($dirty_metadata as $key => $value) {
      if (empty($key) || empty($value)) {
        continue;
      }

      $normalizedKey = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $key));
      if (!$this->strictHandling) {
        $key = $normalizedKey;
      }

      $clean_metadata[$key] = is_array($value) ? array_map('trim', $value) : trim($value);
    }

    if (empty($clean_metadata)) {
      throw new Exception("Cleaned metadata array is empty.");
    }

    return $clean_metadata;
  }

  /**
   * Explodes key|value strings into an associative array.
   *
   * @param string $fieldMappings
   *   The string of field mappings.
   *
   * @return array
   *   The parsed field mappings.
   *
   * @throws Exception
   *   If input is invalid.
   */
  protected function explodeKeyValueString(string $fieldMappings): array {
    if (!is_string($fieldMappings) || empty($fieldMappings)) {
      throw new Exception("Invalid field mapping string.");
    }

    $lines = explode("\n", $fieldMappings);
    $result = [];

    foreach ($lines as $line) {
      if (strpos($line, '|') !== false) {
        list($key, $value) = explode('|', $line);
        $result[trim($value)] = trim($key);
      }
    }

    if (empty($result)) {
      throw new Exception("Parsed field mapping array is empty.");
    }

    return $result;
  }

  /**
   * Sanitizes an array’s keys and values to prevent injection.
   *
   * @param array $unsanitized_array
   *   The unclean array.
   *
   * @return array
   *   The sanitized array.
   *
   * @throws Exception
   *   If input is invalid.
   */
  protected function sanitizeArray(array $unsanitized_array): array {
    if (!is_array($unsanitized_array)) {
      throw new Exception("Invalid input for sanitization. Expected an array.");
    }

    $sanitized = [];
    foreach ($unsanitized_array as $key => $value) {
      $cleanKey = htmlspecialchars(trim($key), ENT_QUOTES, 'UTF-8');
      $cleanValue = is_array($value)
        ? array_map(fn($v) => htmlspecialchars(trim($v), ENT_QUOTES, 'UTF-8'), $value)
        : htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');

      if ($this->strictHandling) {
        $cleanKey = preg_replace('/[^a-zA-Z0-9_]/', '', $cleanKey);
        $cleanValue = is_array($cleanValue)
          ? array_map(fn($v) => preg_replace('/[^a-zA-Z0-9_ ]/', '', $v), $cleanValue)
          : preg_replace('/[^a-zA-Z0-9_ ]/', '', $cleanValue);
      }

      $sanitized[$cleanKey] = $cleanValue;
    }

    if (empty($sanitized)) {
      throw new Exception("Sanitized array is empty.");
    }

    return $sanitized;
  }
}