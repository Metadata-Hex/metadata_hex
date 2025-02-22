<?php

namespace Drupal\Tests\metadata_hex\Kernel;

use Drupal\Tests\metadata_hex\Kernel\BaseKernelTest;

/**
 * Kernel test for the MetadataBatchProcessor service.
 *
 * @group metadata_hex
 */
class MetadataBatchProcessorKernelTest extends BaseKernelTest {

  public function testHasMetadataProcessedTable() {
    $query = \Drupal::database()->schema()->tableExists('metadata_hex_processed');
    $this->assertEquals(true, $query, 'Database table exists');
  }


  /**
   * Tests processing a node with a valid PDF file.
   */
  public function testProcessNodeWithValidFileTypeNoMetadata() {
    $this->expectException(\Drupal\Core\Entity\EntityStorageException::class);
    $this->expectExceptionMessage("Invalid or unreadable file: vfs://root/test_document.pdf");
    // Setup a basic valid file and node
    $node = $this->createNode('/test_document.pdf');

    // Capture the original details
    $created = $node->getCreatedTime();
    $modified = $node->getChangedTime();

    // Process the node
    $this->batchProcessor->processNode($node->id());

    // Reload the node now that batch processes have occured
    $node_alt = \Drupal::entityTypeManager()->getStorage('node')->load($node->id());

    // Capture the current details
    $created_alt = $node_alt->getCreatedTime();
    $modified_alt = $node_alt->getChangedTime();

    // Assertions
    $this->assertEquals($created, $created_alt, 'Node creation date should match');
    $this->assertEquals($modified, $modified_alt, 'Node modification date should match');
  }

 /**
   * Tests processing a node with an invalid file type.
   */
  public function testProcessNodeWithInvalidFileType() {

    // Setup a basic valid file and node
    $node = $this->createNode('/test_document.txt');

    // Capture the original details
    $created = $node->getCreatedTime();
    $modified = $node->getChangedTime();

    // Process the node
    $this->batchProcessor->processNode($node->id());

    // Reload the node now that batch processes have occured
    $node_alt = \Drupal::entityTypeManager()->getStorage('node')->load($node->id());

    // Capture the current details
    $created_alt = $node_alt->getCreatedTime();
    $modified_alt = $node_alt->getChangedTime();

    // Assertions
    $this->assertEquals($created, $created_alt, 'Node creation date should match');
    $this->assertEquals($modified, $modified_alt, 'Node modification date should match');
  }

  /**
   * Tests processing a node with a valid PDF file.
   */
  public function testProcessNodeWithValidPdfWithMetadata() {

    // Setup an actual valid pdf file with metadata and node
    $file = $this->createDrupalFile('test_metadata.pdf', $this->generatePdfWithMetadata(), 'application/pdf');
    $node = $this->createNode($file);

    // Capture the original details
    $created = $node->getCreatedTime();
    $modified = $node->getChangedTime();

    // Process the node
    $this->batchProcessor->processNode($node->id());

    // Reload the node now that batch processes have occured
    $node_alt = \Drupal::entityTypeManager()->getStorage('node')->load($node->id());

    // Capture the current details
    $created_alt = $node_alt->getCreatedTime();
    $modified_alt = $node_alt->getChangedTime();
    $ff = $node_alt->get('field_subject')->getString();
    $this->assertNotEquals('', $ff, 'subject updated');
    //echo $ff . PHP_EOL;
    // Assertions
    $this->assertEquals($created, $created_alt, 'Node creation date should match');
    $this->assertNotEquals($modified, $modified_alt, 'Node modification date should differ');
  }
}
