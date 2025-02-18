<?php

namespace Drupal\metadata_hex\Commands;

use Drush\Commands\DrushCommands;

/**
 * A Drush command file.
 */
class MetadataHexCommands extends DrushCommands {

  /**
   * Runs a functionality test.
   *
   * @command metadatahex:test
   * @aliases mdhext
   */
  public function testFunctionality() {
    // Load a node.
    $node = \Drupal::entityTypeManager()->getStorage('node')->load(1);

    if ($node) {
      $this->output()->writeln('Node loaded: ' . $node->label());
    }
    else {
      $this->output()->writeln('Node not found.');
    }

    $mdex = new Drupal\metadata_hex\Service\MetadataExtractor(\Drupal::service('logger.channel.default'));
    $mdbp = new Drupal\metadata_hex\Service\MetadataBatchProcessor(\Drupal::service('logger.channel.default'), $mdex);
    $result = $mdbp->processNode($node->id()); 
    if ($result === null){
      $this->output()->writeIn('Success');
    } else {
      $this->output()->writeIn('Error, please check logs');
    }
  }

}