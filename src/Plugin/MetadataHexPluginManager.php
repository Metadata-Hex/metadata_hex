namespace Drupal\metadata_hex\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

class MetadataHexPluginManager extends DefaultPluginManager {

  public function __construct($container, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/MetadataHex', // Namespace for plugin discovery
      $container->get('container.namespaces'),
      $module_handler,
      'Drupal\metadata_hex\Plugin\MetadataHexInterface',
      'Drupal\metadata_hex\Annotation\MetadataHex'
    );

    $this->alterInfo('metadata_hex_info');
    $this->setCacheBackend($cache_backend, 'metadata_hex_plugins');
  }
}

