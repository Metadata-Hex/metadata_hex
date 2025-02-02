<?php

namespace Drupal\metadata_hex\Service;

use Drupal\Component\Plugin\PluginManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class FileHandlerManager
 *
 * Manages file handlers for different extensions.
 */
class FileHandlerManager {

  /**
   * The plugin manager.
   *
   * @var PluginManagerInterface
   */
  protected $pluginManager;

  /**
   * Constructs the FileHandlerManager class.
   *
   * @param PluginManagerInterface $pluginManager
   *   The plugin manager service.
   */
  public function __construct(PluginManagerInterface $pluginManager) {
    $this->pluginManager = $pluginManager;
  }

  /**
   * Retrieves the handler for a given file extension.
   *
   * @param string $extension
   *   The file extension.
   *
   * @return mixed|null
   *   The handler plugin or null if not found.
   */
  protected function getHandlerForExtension(string $extension) {
    foreach ($this->pluginManager->getDefinitions() as $plugin_id => $definition) {
      if (isset($definition['extensions']) && in_array($extension, $definition['extensions'], true)) {
        return $this->pluginManager->createInstance($plugin_id);
      }
    }

    return null;
  }

  /**
   * Retrieves the available file extensions.
   *
   * @return array
   *   An array of available file extensions.
   */
  protected function getAvailableExtentions(): array {
    $extensions = [];

    foreach ($this->pluginManager->getDefinitions() as $definition) {
      if (isset($definition['extensions'])) {
        $extensions = array_merge($extensions, $definition['extensions']);
      }
    }

    return array_unique($extensions);
  }
}