<?php

namespace Drupal\Tests\metadata_hex\Kernel;

use Drupal\metadata_hex\Model\MetadataEntity;
use Drupal\Tests\metadata_hex\Kernel\BaseKernelTestHex;

/**
 * Kernel test for the MetadataBatchProcessor service.
 *
 * @group metadata_hex
 */
class MetadataEntityKernelTest extends BaseKernelTestHex {

  /**
   * Tests processing a node with a valid PDF file.
   */
  public function testMetadataEntityCanProcessNodes() {

    $file = $this->createDrupalFile('test_metadata.pdf', $this->generatePdfWithMetadata(), 'application/pdf');
    $node = $this->createNode($file);

    $me = new MetadataEntity(\Drupal::logger('info'));
    $me->loadFromNode($node->id());
    $n = $me->getNodeBinder();

    $this->assertEquals($n->getBundleType(), 'article', 'Bundle type doesnt match');
    $this->assertEquals($n->getNode()->id(), $node->id(), 'Nodes arent the same');


    $meta = $me->getMetadata();
    $meta_raw = $meta['raw'];
    $meta_processed = $meta['raw'];
    $meta_mapped = $meta['mapped'];
    $this->assertContains('title', array_keys($meta_raw), "The expected term is not present.");
    $this->assertContains('title', array_keys($meta_processed), "The expected term is not present.");
    $this->assertContains('title', array_keys($meta_mapped), "The expected term is not present.");

    // Assert that mapped metadata does not have more keys than processed metadata
    $this->assertLessThanOrEqual(
      count(array_keys($meta_mapped)),
      count(array_keys($meta_processed)),
      "Mapped metadata has more keys than processed metadata."
    );

    // Assert that all mapped keys exist in processed metadata
    foreach (array_keys($meta_mapped) as $key) {
      $this->assertContains(
          $key,
          array_keys($meta_processed),
          "Mapped key '$key' is missing from processed metadata."
      );
    }

    // Assert that the number of entries in raw metadata is greater than processed and mapped
    $this->assertGreaterThan(
      count(array_keys($meta_processed)),
      count(array_keys($meta_raw)),
      "Raw metadata should have more entries than processed metadata."
    );

    $this->assertGreaterThan(
      count(array_keys($meta_mapped)),
      count(array_keys($meta_raw)),
      "Raw metadata should have more entries than mapped metadata."
    );

  }
}
