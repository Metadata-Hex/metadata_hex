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

   public function testNodeBinderWithPdfNode() {

    $file = $this->createDrupalFile('test_metadata.pdf', $this->generatePdfWithMetadata(), 'application/pdf');
    $this->original = $this->createNode($file);
    $this->bind = new NodeBinder(\Drupal::logger('info'));
    $this->bind->init($this->original);

    $this->runAssertions();

  }

  /**
   * Tests processing a node with a valid PDF file.
   */
  public function testNodeBinderWithPdfFile() {

    $file = $this->createDrupalFile('test_metadata.pdf', $this->generatePdfWithMetadata(), 'application/pdf');
    $this->bind = new NodeBinder(\Drupal::logger('info'));
    $this->bind->init($file);

    $this->runAssertions();
  }

  public function testNodeBinderWithMdNode() {

    $file = $this->createDrupalFile('test_metadata.md', $this->generatePdfWithMetadata(), 'text/markdown');
    $this->original = $this->createNode($file);
    $this->bind = new NodeBinder(\Drupal::logger('info'));
    $this->bind->init($this->original);

    $this->runAssertions(1);

  }

  /**
   * Tests processing a node with a valid PDF file.
   */
  public function testNodeBinderWithMdFile() {

    $file = $this->createDrupalFile('test_metadata.md', $this->generatePdfWithMetadata(), 'text/markdown');
    $this->bind = new NodeBinder(\Drupal::logger('info'));
    $this->bind->init($file);

    $this->runAssertions(1);
  }

  /** 
   * Tests processing a node with a valid PDF file.
   */
  public function testNodeBinderWithInvalidFile() {
    $this->expectException(\TypeError::class); 
    $this->expectExceptionMessage('Argument #1 ($uri) must be of type string, null given');
    $file = $this->createFile($file);
    $this->bind = new NodeBinder(\Drupal::logger('info'));
    $this->bind->init($file);

  }


  /**
   * Tests processing a node with a valid PDF file.
   */
  public function testNodeBinderWithInvalidType() {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Invalid input provided.');

    $file = \Drupal\user\Entity\User::load(1);
    $this->bind = new NodeBinder(\Drupal::logger('info'));
    $this->bind->init($file);

  }

// maybe set settings incorrectly and confirm etc?
  /**
   * Run Assertions
   */
  public function runAssertions($matches = 5){

    $n = $this->bind->getNode();
    $meta = $this->bind->ingestNodeFileMeta();//();
    $meta_raw = [];
    array_walk_recursive($meta, function($value, $key) use (&$meta_raw) {
        $meta_raw[$key] = $value;
    });

    $this->assertEquals($n->bundle(), 'article', 'Bundle type doesnt match');
    if (!empty($this->original)){
    $this->assertEquals($n->id(), $this->original->id(), 'Nodes arent the same');
    }
    $meta = $this->bind->ingestNodeFileMeta();//();
    // Assert that meta is an array
    $this->assertIsArray($meta, "Metadata should be an array.");

    // Assert that meta has more than 5 entries
    $this->assertGreaterThan($matches, count($meta_raw), "Metadata should contain more than 5 entries.");

    // $files = $this->bind->getFileUris();

    // $this->assertTrue(
    //     (bool) array_filter($filesuris, fn($uri) => str_contains($uri, 'test_metadata.pdf')),
    //     "The array does not contain 'test_metadata.pdf' in any form."
    // );
  }
}
