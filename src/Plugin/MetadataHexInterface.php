<?php
namespace Drupal\metadata_hex\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

interface MetadataHexInterface extends PluginInspectionInterface {
  public function process();
}

