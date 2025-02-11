<?php

namespace Drupal\metadata_hex\Utility;

use \Drupal\metadata_hex\Base\MetadataHexCore;
use Psr\Log\LoggerInterface;
use Exception;
use Drupal\node\Entity\NodeType;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\metadata_hex\Service\SettingsManager;

/**
 * Class MetadataParser
 *
 * Handles parsing operations for extracted metadata.
 * Responsible for:
 * - Validating field mappings
 * - Extracting and cleaning data
 * - Ensuring compatibility with Drupal field structures
 */
class MetadataParser extends MetadataHexCore
{

  /**
   * The fields available.
   *
   * @var array
   */
  protected $availableFields = [];

  /**
   * The field mappings.
   *
   * @var array
   */
  protected $fieldMapping = null;

  /**
   * Summary of bundleType
   * 
   * @var NodeType|string|null 
   *
   */
  protected $bundleType;
  /**
   * Determines if we are strictly handling string comparisons.
   *
   * @var bool
   */
  protected $strictHandling = false;

  /**
   * Summary of flattenKeys
   * @var bool
   */
  protected $flattenKeys = false;
  /**
   * The metadata array.
   *
   * @var array
   */
  protected $metaArray = [];

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Summary of settingsManager
   * @var 
   */
  protected $settingsManager = null;
  /**
   * Constructs the MetadataParser class.
   *
   * @param LoggerInterface $logger
   *   The logger service.
   * @param EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager service.
   * @param string $bundleType
   *   The bundle type object.
   */
  public function __construct(LoggerInterface $logger, $bundleType = null)
  {
    parent::__construct($logger);
    $this->settingsManager = new SettingsManager();
    $this->entityFieldManager = self::getEntityFieldManager();
    if ($bundleType !== null) {
      $this->setBundleType($bundleType);
    }
  }

  /**
   * Initializes the parser.
   */
  public function init()
  {

    if ($this->bundleType !== null) {
self::initAvailableFields();    }
  }

protected function getAvailableFields(){
  if ($this->availableFields == null || empty($this->availableFields)){
    self::initAvailableFields();
  }
  return $this->availableFields;
}  

protected function initAvailableFields(){
      $this->availableFields = $this->entityFieldManager->getFieldDefinitions('node', $this->bundleType->id());

}
  /**
   * Initiates the settings manager
   */
  protected function initSettingsManager(){
    if ($this->settingsManager == null){
      $this->settingsManager = new SettingsManager();
      self::initFieldMaps();
    }
  }

  /**
   * Returns the settings manager, includes an init check
   */
  protected function getSettingsManager(){
    self::initSettingsManager();
    return $this->settingsManager;
  }

  /**
   * initiates and populates field mapping
   */
  protected function initFieldMaps(){
    
    $extractedFieldMaps =  self::explodeKeyValueString($this->getSettingsManager()->getFieldMappings());
    
    $this->fieldMapping = $this->cleanFieldMapping($extractedFieldMaps);
  }

  /**
   * Returns the entityFieldManager service
   */
  protected function getEntityFieldManager()
  {
    return \Drupal::service('entity_field.manager');
  }

  /**
   * Removes any field mappings that do not match available fields.
   *
   * @param array|null $dirty_fieldmapping
   *   The uncleaned field mappings.
   *
   * @return array
   *   The cleaned field mappings.
   *
   * @throws Exception
   *   If input is invalid.
   */
  protected function cleanFieldMapping(array $dirty_fieldmapping = null): array
  {
    if ($dirty_fieldmapping === null) {
      $dirty_fieldmapping = $this->getFieldMappings();
    } 
    echo print_r($this->availableFields, true);
echo print_r($dirty_fieldmapping, true);
    if (!is_array($dirty_fieldmapping) || empty($dirty_fieldmapping)) {
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
  public function cleanMetadata(array $dirty_metadata): array
  {
    if ($dirty_metadata === null && !empty($this->metaArray)) {
      $dirty_metadata = $this->metaArray;
    } else if (!is_array($dirty_metadata)) {
      throw new Exception("Invalid metadata input. Expected an associative array.");
    }
    // Flatten the array.
    $flattenedArray = $this->flattenArray($dirty_metadata);

    // Sanitize the array.
    $sanitizedArray = $this->sanitizeArray($flattenedArray);
    $clean_metadata = [];
    foreach ($sanitizedArray as $key => $value) {
      if (empty($key) || empty($value)) {
        continue;
      }

      // if we arent strict handling, normalize all keys
      if (!$this->strictHandling) {
        $key = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $key));
      }

      $clean_metadata[$key] = is_array($value) ? array_map('trim', $value) : trim($value);
    }

    if (empty($clean_metadata)) {
      $this->logger->error("Cleaned metadata array is empty.");
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
  protected function explodeKeyValueString(string $fieldMappings): array
  {
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
  private function sanitizeArray(array $unsanitized_array): array
  {
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

      if ($this->flattenKeys){
        $cleanKey = substr(strrchr($cleanKey, ':'), 1);
      }

      $sanitized[$cleanKey] = $cleanValue;
    }

    if (empty($sanitized)) {
      throw new Exception("Sanitized array is empty.");
    }

    return $sanitized;
  }

  /**
   * Flattens a multi-dimensional array into a single-dimensional array with dot-separated keys.
   *
   * @param array $array
   *   The multi-dimensional array to flatten.
   * @param string $prefix
   *   The prefix for the keys (used for recursion).
   *
   * @return array
   *   The flattened array.
   */
  private function flattenArray(array $array): array
  {
    $result = [];

    array_walk_recursive($array, function ($value, $key) use (&$result) {
      $result[$key] = $value;
    });

    return $result;
  }

  /**
   * Loads the correct bundle type from a bundle type string.
   *
   * @param string $bundleType
   *   The bundle type string.
   *
   * @return object
   *   The loaded bundle type object.
   *
   * @throws Exception
   *   If the bundle type cannot be loaded.
   */
  protected function loadBundleType(string $bundleType)
  {
    $entity_type_manager = \Drupal::entityTypeManager();
    $bundle_info = $entity_type_manager->getStorage('node_type')->load($bundleType);
    
    if (!$bundle_info) {
      throw new Exception("Bundle type not found: $bundleType");
    }

    return $bundle_info;
  }

  /**
   * Sets the bundle type.
   *
   * @param string|NodeType $bundleType
   *   The bundle type string or NodeType object.
   *
   * @throws Exception
   *   If the bundle type cannot be loaded.
   */
  public function setBundleType($bundleType)
  {
    if (is_string($bundleType)) {
      $this->bundleType = $this->loadBundleType($bundleType);
    } elseif ($bundleType instanceof NodeType) {
      $this->bundleType = $bundleType;
    } else {
      throw new Exception("Invalid bundle type. Expected a string or NodeType object.");
    }
self::initAvailableFields();
  }

  /**
   * Summary of getFieldMappings
   * @return array
   */
  public function getFieldMappings()
  {
    if ($this->fieldMapping == null){
      self::initFieldMaps();
    }
      echo print_r($dirty_fieldmapping, true);
    return $this->fieldMapping;
  }
}