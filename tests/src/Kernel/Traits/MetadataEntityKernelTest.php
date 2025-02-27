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
  public function testProcessNodeWithValidPdfWithMetadata() {

    $file = $this->createDrupalFile('test_metadata.pdf', $this->generatePdfWithMetadata(), 'application/pdf');
    $node = $this->createNode($file);

$me = new MetadataEntity(\Drupal::logger('info'));
$me->init($node);
$meta = $me->getMetadata();
$n = $me->getNodeBinder();

$this->assertEquals($n->getBundleType(), 'article', 'Node creation dates dont match');
$this->assertEquals($n->getNode()->id(), $node->id(), 'Node creation dates dont match');

console.log('meta', $meta);
// $this->assertContains('Drup/al', $term_names, "The expected taxonomy term name Drupal is not present.");

   
  }
}
