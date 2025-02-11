<?php

namespace Drupal\metadata_hex\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use \Drupal\metadata_hex\Base\MetadataHexCore;
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
   * Constructs the SettingsManager class.
   *
   * @param ConfigFactoryInterface $configFactory
   *   The configuration factory service.
   * @todo fix
   */ 
  public function __construct(?ConfigFactoryInterface $configFactory = null) {
    $this->configFactory = $configFactory ?? new ConfigFactoryInterface(); 
  }

  /**
   * Retrieves extraction settings.
   *
   * @return array
   *   The extraction settings.
   */
  protected function getExtractionSettings(): array {
    $config = $this->configFactory->get('metadata_hex.settings');
    return $config->get('extraction') ?? [];
  }

  /**
   * Retrieves field mapping settings.
   *
   * @return array
   *   The field mapping settings.
   */
  public function getFieldMappings(): array {
    $config = $this->configFactory->get('metadata_hex.settings');
    return $config->get('field_mappings') ?? [];
  }

  /**
   * Retrieves file ingestion settings.
   *
   * @return array
   *   The file ingestion settings.
   */
  protected function getFileIngestSettings(): array {
    $config = $this->configFactory->get('metadata_hex.settings');
    return $config->get('file_ingest') ?? [];
  }

  public function getAllowedNodeTypes(){
    return $this->getExtractionSettings()['hook_node_types'];
  }
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
    $config = $this->configFactory->get('metadata_hex.settings');
    return $config->get('node_processing') ?? [];
  }
}