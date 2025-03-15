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
   * Metadata Extractor
   * @var 
   */
  private $me;

  /**
   * Node Binder
   * 
   */
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
   * Tests processing a node with a valid file.
   */
  public function testMetadataEntityCanProcessFile() {

    $file = $this->createDrupalFile('test_metadata.pdf', $this->generatePdfWithMetadata(), 'application/pdf');

    $this->me = new MetadataEntity(\Drupal::logger('info'));
    $this->me->loadFromFile($file->getFileUri());
    $this->runAssertions();
  }


  /**
   * Tests processing a node with a valid node.
   */
  public function testMetadataEntityCanProcessMdNode() {

    $file = $this->createDrupalFile('test_metadata.md', $this->generateMdWithMetadata(), 'text/markdown');
    $node = $this->createNode($file);

    $this->me = new MetadataEntity(\Drupal::logger('info'));
    $this->me->loadFromNode($node->id());
    $this->runAssertions();
  }

  /**
   * Tests processing a node with a valid file.
   */
  public function testMetadataEntityCanProcessMdFile() {

    $file = $this->createDrupalFile('test_metadata.md', $this->generateMdWithMetadata(), 'text/markdown');

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

    if (!empty($n->getNode())){
      $this->assertEquals($n->getNode()->id(), 1, 'Nodes arent the same');
    }

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
    $this->assertGreaterThan(
      count(array_keys($meta_mapped)),
      count(array_keys($meta_raw)),
      "Raw metadata should have more entries than mapped metadata."
    );
  }


  /**
   * Tests processing a node with an invalid file.
   */
  public function testMetadataEntityWithInvalidFile() {
    $this->expectException(\TypeError::class);
    $this->expectExceptionMessage('Argument #1 ($uri) must be of type string, null given');
    $file = $this->createFile($file);
    $this->me = new MetadataEntity(\Drupal::logger('info'));
    $this->me->loadFromFile($file);

  }


  /**
   * Tests processing a file with an invalid class type.
   */
  public function testMetadataEntityWithInvalidType() {
    $this->expectException(\TypeError::class);
    $this->expectExceptionMessage('Argument #1 ($file_uri) must be of type string, Drupal\user\Entity\User given');

    $file = \Drupal\user\Entity\User::load(1);
    $this->me = new MetadataEntity(\Drupal::logger('info'));
    $this->me->loadFromFile($file);


  }

  /**
   * Tests processing a node with an invalid file.
   */
  public function testMetadataEntityNodeWithInvalidFile() {
    $this->expectException(\TypeError::class);
    $this->expectExceptionMessage('Argument #1 ($uri) must be of type string, null given');
    $file = $this->createFile($file);
    $this->me = new MetadataEntity(\Drupal::logger('info'));
    $this->me->loadFromNode($file);

  }


  /**
   * Tests processing a node with an invalid class type.
   */
  public function testMetadataEntityNodeWithInvalidType() {
    $this->expectException(\TypeError::class);
    $this->expectExceptionMessage('Argument #1 ($nid) must be of type string, Drupal\user\Entity\User given');

    $file = \Drupal\user\Entity\User::load(1);
    $this->me = new MetadataEntity(\Drupal::logger('info'));
    $this->me->loadFromNode($file);


  }
    /**
   * Tests processing terms with invalid variable types.
   */
  public function testMetadataEntityWithInvalidTermType() {
    $this->expectException(\Error::class);
    $this->expectExceptionMessage("Call to protected method Drupal\metadata_hex\Model\MetadataEntity::findMatchingTaxonomy() from scope Drupal\Tests\metadata_hex\Kernel\MetadataEntityKernelTest");

    $file = \Drupal\user\Entity\User::load(1);
    $this->me = new MetadataEntity(\Drupal::logger('info'));
    $this->me->findMatchingTaxonomy(['term1', 'term2'], 'taxonomy');
  }

 /**
   * Tests writing metadata to empty node.
   */
  public function testMetadataEntityCannotWriteMetadataToEmptyNode() {
    $this->expectException(\Error::class);

    $file = $this->createFile($file);

    $this->me = new MetadataEntity(\Drupal::logger('info'));
    $this->me->loadFromFile($file->getFileUri());
    $this->me->writeMetadata();
  }


}
