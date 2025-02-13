<?php
namespace Drupal\metadata_hex\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a MetadataHex annotation object.
 *
 * @Annotation
 */
class MetadataHex extends Plugin {
  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name.
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;
  
  /**
   * The file extensions this plugin handles.
   *
   * @var array
   */
  public $extensions = [];
}