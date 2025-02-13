<?php

namespace Drupal\metadata_hex\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Traversable;

/**
 * PluginManager for the module
 */
class MetadataHexPluginManager extends DefaultPluginManager {

  public function __construct(Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {

parent::__construct(
      'Handler',
      $namespaces, 
      $module_handler,
      'Drupal\metadata_hex\Handler\FileHandlerInterface', 
      'Drupal\metadata_hex\Annotation\MetadataHex' 
    );

    $this->alterInfo('metadata_hex_info');
    $this->setCacheBackend($cache_backend, 'metadata_hex_plugins');
  }
}
