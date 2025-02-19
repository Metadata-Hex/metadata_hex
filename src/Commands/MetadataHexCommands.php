<?php

namespace Drupal\metadata_hex\Commands;

use Drush\Commands\DrushCommands;

/**
 * A Drush command file.
 * @group metadata_hex
 */
class MetadataHexCommands extends DrushCommands {

  protected $extractor;
  protected $batchProcessor;

  /**
   * Initializes services.
   */
  protected function initServices() {
    if (!isset($this->extractor)) {
      $this->extractor = \Drupal::service('metadata_hex.metadata_extractor');
    }
    if (!isset($this->batchProcessor)) {
      $this->batchProcessor = \Drupal::service('metadata_hex.metadata_batch_processor');
    }
  }

  /**
   * Runs a functionality test.
   *
   * @command metadatahex:test
   * @aliases mdhext
   */
  public function testFunctionality() {
    $this->initServices(); // Ensure services are initialized

    $node = \Drupal::entityTypeManager()->getStorage('node')->load(1);

    if ($node) {
      $this->output()->writeln('Node loaded: ' . $node->label());
    } else {
      $this->output()->writeln('Node not found.');
      return;
    }

    $result = $this->batchProcessor->processNode($node->id()); 
    if ($result === null) {
      $this->output()->writeln('Success');
    } else {
      $this->output()->writeln('Error, please check logs');
    }
  }
}
