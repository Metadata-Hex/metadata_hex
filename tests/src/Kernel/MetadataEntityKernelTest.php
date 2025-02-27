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

  private $me;

  private $nb;

  /**
   * Tests processing a node with a valid node.
   */
  public function testMetadataEntityCanProcessNode() {

    $file = $this->createDrupalFile('test_metadata.pdf', $this->generatePdfWithMetadata(), 'application/pdf');
    $node = $this->createNode($file);

    $this->me = new MetadataEntity(\Drupal::logger('info'));
    $this->me->loadFromNode($node->id());
    $this->runAssertions();
  }

  /**
   * Tests processing a  file.
   */
  public function testMetadataEntityCanProcessFile() {

    $file = $this->createDrupalFile('test_metadata.pdf', $this->generatePdfWithMetadata(), 'application/pdf');

    $this->me = new MetadataEntity(\Drupal::logger('info'));
    $this->me->loadFromFile($file->getFileUri());
    $this->runAssertions();
  }
    
  
  /**
   * Run Assertions
   */
  public function runAssertions(){
    $n = $this->me->getNodeBinder();

    $this->assertEquals($n->getBundleType(), 'article', 'Bundle type doesnt match');
    $this->assertEquals($n->getNode()->id(), 1, 'Nodes arent the same');


    $meta = $this->me->getMetadata();
    $meta_raw = [];
    array_walk_recursive($meta['raw'], function($value, $key) use (&$meta_raw) {
        $meta_raw[$key] = $value;
    });
    $meta_processed = $meta['raw'];
    $meta_mapped = $meta['mapped'];

    // Assert that mapped metadata does not have more keys than processed metadata
    $this->assertLessThanOrEqual(
      count(array_keys($meta_mapped)),
      count(array_keys($meta_processed)),
      "Mapped metadata has more keys than processed metadata."
    );


    // Assert that the number of entries in raw metadata is greater than processed and mapped
    $this->assertGreaterThan(
      count(array_keys($meta_processed)),
      count(array_keys($meta_raw)),
      "Raw metadata should have more entries than processed metadata."
    );

    //due to array dimentionality
    $this->assertLessThan(
      count(array_keys($meta_mapped)),
      count(array_keys($meta_raw)),
      "Raw metadata should have more entries than mapped metadata."
    );

  
  }
}
