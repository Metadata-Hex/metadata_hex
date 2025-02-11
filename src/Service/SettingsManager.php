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
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->configFactory = $configFactory;
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
  protected function getFieldMappings(): array {
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