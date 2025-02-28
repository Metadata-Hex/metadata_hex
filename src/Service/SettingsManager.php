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
   * Constructs the SettingsManager class.
   *
   * @param ConfigFactoryInterface $configFactory
   *   The configuration factory service.
   * 
   */ 
  public function __construct(?ConfigFactoryInterface $configFactory = null) {
    $this->configFactory = $configFactory ?? \Drupal::service('config.factory'); 
    $this->config = $this->configFactory->get('metadata_hex.settings');
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
 

  public function getIngestField(): string {
    return $this->config->get('file_ingest.file_attachment_field')??'';
  }


  public function getIngestDirectory(): string {
    return $this->config->get('file_ingest.ingest_directory')??'';
  }

  public function getIngestBundleType(): string {
    return $this->config->get('file_ingest.bundle_type_for_generation')??'';
  }
  /**
   * Retrieves file ingestion settings.
   *
   * @return array
   *   The file ingestion settings.
   */
  protected function getFileIngestSettings(): array {
    return $this->config->get('file_ingest') ?? [];
  }

  /**
   * Retrieves if we are strict handling
   * 
   * @return bool
   *  The node types
   */
  public function getStrictHandling(){
    return $this->config->get('extraction_settings.strict_handling') ?? false;
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