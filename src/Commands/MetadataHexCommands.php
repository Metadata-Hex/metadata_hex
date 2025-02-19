<?php

namespace Drupal\metadata_hex\Commands;

use Drush\Commands\DrushCommands;
use Drupal\metadata_hex\Service\MetadataExtractor;
use Drupal\metadata_hex\Service\MetadataBatchProcessor;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A Drush command file.
 */
class MetadataHexCommands extends DrushCommands {

  protected $extractor;
  protected $batchProcessor;

  public function __construct(MetadataExtractor $extractor, MetadataBatchProcessor $batchProcessor) {
    $this->extractor = $extractor;
    $this->batchProcessor = $batchProcessor;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('metadata_hex.extractor'),
      $container->get('metadata_hex.batch_processor')
    );
  }

  /**
   * Runs a functionality test.
   *
   * @command metadatahex:test
   * @aliases mdhext
   */
  public function testFunctionality() {
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