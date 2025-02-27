<?php

namespace Drupal\Tests\metadata_hex\Kernel;

use Drupal\metadata_hex\Model\NodeBinder;
use Drupal\Tests\metadata_hex\Kernel\BaseKernelTestHex;

/**
 * Kernel test for the MetadataBatchProcessor service.
 *
 * @group metadata_hex
 */
class NodeBinderKernelTest extends BaseKernelTestHex {

  private $bind;
  private $original;
  /**
   * Tests processing a node with a valid PDF file.
   */
  
  public function testNodeBinderWithNode() {

    $file = $this->createDrupalFile('test_metadata.pdf', $this->generatePdfWithMetadata(), 'application/pdf');
    $this->original = $this->createNode($file);
    $this->bind = new NodeBinder(\Drupal::logger('info'));
    $this->bind->init($this->original);

    $this->runAssertions();

  }

  /**
   * Tests processing a node with a valid PDF file.
   */
  public function testNodeBinderWithFile() {

    $file = $this->createDrupalFile('test_metadata.pdf', $this->generatePdfWithMetadata(), 'application/pdf');
    $this->bind = new NodeBinder(\Drupal::logger('info'));
    $this->bind->init($file);

    $this->runAssertions();


  }

  /**
   * Run Assertions
   */
  public function runAssertions(){

    $n = $this->bind->getNodeBinder();

    $this->assertEquals($n->getBundleType(), 'article', 'Bundle type doesnt match');
    $this->assertEquals($n->getNode()->id(), $this->original->id(), 'Nodes arent the same');

    $meta = $n->ingestNodeFileMeta();//();
    // Assert that meta is an array
    $this->assertIsArray($meta, "Metadata should be an array.");

    // Assert that meta has more than 5 entries
    $this->assertGreaterThan(5, count($meta), "Metadata should contain more than 5 entries.");
    
    $files = $n->getFileUris();
    echo print_r($files, true);
  }
}
