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
    $this->configFactory = $configFactory ?? new ConfigFactoryInterface(); 
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
   * @return array
   *   The field mapping settings.
   */
  public function getFieldMappings(): array {
    return $this->config->get('field_mappings') ?? [];
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
   * Retrieves the allowed node types setup for parsing
   * 
   * @return array
   *  The node types
   */
  public function getAllowedNodeTypes(){
    return $this->getExtractionSettings()['hook_node_types'];
  }

 /**
   * Retrieves the user set directory to ingest
   * 
   * @return string
   *  The directory to ingest
   */
  public function getIngestDir(){
    return $this->getFileIngestSettings()['ingest_directory'];
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