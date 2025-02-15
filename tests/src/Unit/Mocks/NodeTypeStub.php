<?php
namespace Drupal\node\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Stub class for NodeType replacement in unit tests.
 */
class NodeTypeStub extends ConfigEntityBase {

  protected $id;
  protected $label;

  public function __construct($id, $label) {
    $this->id = $id;
    $this->label = $label;
  }

  public function id() {
    return $this->id;
  }

  public function label() {
    return $this->label;
  }

  /**
   * Override NodeType::loadMultiple() for unit tests.
   */
  public static function loadMultiple(?array $ids = null) {
    return [
        'article' => new self('article', 'Article'),
        'page' => new self('page', 'Page'),
    ];
}
}