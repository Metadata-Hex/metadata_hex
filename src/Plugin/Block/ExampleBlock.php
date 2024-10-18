<?php

namespace Drupal\pdf_meta_extraction\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides an example block.
 *
 * @Block(
 *   id = "pdf_meta_extraction_example",
 *   admin_label = @Translation("Example"),
 *   category = @Translation("Pdf Meta Extraction")
 * )
 */
class ExampleBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build['content'] = [
      '#markup' => $this->t('It works!'),
    ];
    return $build;
  }

}
