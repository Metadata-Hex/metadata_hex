<?php

namespace Drupal\metadata_hex\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\metadata_hex\Base\MetadataHexCore;

/**
 * Class SettingsManager
 *
 * Handles retrieving admin settings and returns them in a readable format.
 */
class SettingsManager extends MetadataHexCore {

  /**
   * The configuration factory to fetch settings.
   *
   * @var ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The config object used to fetch specific settings
   */
  protected $config;

  /**
   * Defaults
   */
  const DEFAULT_STRICT = false;
  const DEFAULT_FLATTEN = false;
  const DEFAULT_PROTECT_TITLE = true;
  const DEFAULT_PROTECT_DATA = false;
  const DEFAULT_ALLOW_REPROCESS = false;

  /**
   * Constructs the SettingsManager class.
   *
   * @param ConfigFactoryInterface $configFactory
   *   The configuration factory service.
   * 
   */ 
  public function __construct(?ConfigFactoryInterface $configFactory = null) {
    $this->configFactory = $configFactory ?? \Drupal::service('config.factory'); 
    $this->config = $this->configFactory->getEditable('metadata_hex.settings');
  }

  /**
   * Retrieves extraction settings.
   *
   * @return array
   *   The extraction settings.
   */
  protected function getExtractionSettings(): array {
    return $this->config->get('extraction') ?? [];
  }

  /**
   * Retrieves field mapping settings.
   *
   * @return string
   *   The field mapping settings.
   */
  public function getFieldMappings(): string {
   return $this->config->get('extraction_settings.field_mappings') ?? '';
  }
 
/** 
 * Retrieves the file attachment field
 * @return array
*/
  public function getIngestField(): string {
    return $this->config->get('file_ingest.file_attachment_field')??'';
  }

/** 
 * Retrieves the file ingest directory
 * @return array
*/
  public function getIngestDirectory(): string {
    return $this->config->get('file_ingest.ingest_directory')??'';
  }

  /** 
 * Retrieves the ingest bundle type
 * @return array
*/
  public function getIngestBundleType(): string {
    return $this->config->get('file_ingest.bundle_type_for_generation')??'';
  }

  /**
   * Retrieves file ingestion settings.
   * @return array
   */
  protected function getFileIngestSettings(): array {
    return $this->config->get('file_ingest') ?? [];
  }

  /**
   * Retrieves if we are strict handling
   * @return bool
   */
  public function getStrictHandling(){
    return $this->config->get('extraction_settings.strict_handling') ?? self::DEFAULT_STRICT; 
  }

  /**
   * Retrieves if we are strict handling
   * @return bool
   */
  public function getProtectedData(){
    return $this->config->get('extraction_settings.data_protected') ?? self::DEFAULT_PROTECT_DATA;
  }

  /**
   * Retrieves if we are strict handling
   * @return bool
   */
  public function getProtectedTitle(){
    return $this->config->get('extraction_settings.title_protected') ?? self::DEFAULT_PROTECT_TITLE;
  }


  /**
   * Retrieves if we are strict handling
   * 
   * @return bool
   *  The node types
   */
  public function getFlattenKeys(){
    return $this->config->get('extraction_settings.flatten_keys') ??  self::DEFAULT_FLATTEN;
  }

  /**
   * Retrieves if we are strict handling
   * 
   * @return bool
   *  The node types
   */
  public function getReprocess(){
    return $this->config->get('node_process.allow_reprocess') ??  self::DEFAULT_FLATTEN;
  }

  /**
   * Retrieves the allowed node types setup for parsing
   * 
   * @return array
   *  The node types
   */
  public function getAllowedNodeTypes(){
    return $this->config->get('extraction_settings.hook_node_types') ?? '';
  }


  /**
   * Retrieves node processing settings.
   *
   * @return array
   *   The node processing settings.
   */
  protected function getNodeProcessingSettings(): array {
    return $this->config->get('node_processing') ?? [];
  }
}