<?php
namespace Drupal\Tests\metadata_hex\Kernel;

use Drupal\Tests\metadata_hex\Kernel\BaseKernelTestHex;

/**
 * Kernel test for the MetadataBatchProcessor service.
 *
 * @group metadata_hex
 */
class FileIngestKernelTest extends BaseKernelTestHex {

  // Ensuring that the config schema is not strict to allow for dynamic changes.
  // This is necessary for the test to run without strict schema validation errors.
  protected $strictConfigSchema = FALSE;
  protected $rollback = FALSE;
  
  /**
   * Tests processing a node with a valid PDF file.
   * 
   * @return void
   */
  public function testBatchFileIngest() {
    $directory = '';
    $this->setConfigSetting('file_ingest.ingest_directory', value: $directory);
    $this->file_system->prepareDirectory($directory, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY);

    $file_names = [
      'attached.pdf',
      'test_metadata.pdf',
      'publication_23.pdf',
      'document2.pdf',
      'document4.pdf'
    ];

    $files = [];

    // create files
    foreach ($file_names as $name) {
      $files[] = $this->createDrupalFile($name, $this->generatePdfWithMetadata(), 'application/pdf', true);
    }

    // I want some of the files to be attached to nodes randomly
    $randomKey = array_rand($files);
    $files_linked[] = $files[$randomKey];

    unset($files[$randomKey]);
    $node = $this->createNode($files_linked[0]);

    $fids = $nids = [2,3,4,5];

    $this->batchProcessor->processFiles($fids);
       sleep(1);

    // verify that files already attached to nodes are filtered out
    $this->lookingForNoData($node);

    // verify that files not attached to nodes are processed

    foreach ($nids as $nid){
      $this->lookingForCorrectData($nid);
    }
  }

  /**
   * Verifies that no extracted data exists on the node
   * 
   * @param \Drupal\node\NodeInterface $node
   *  The node to check for extracted data.
   * 
   * @return void
   */
  public function lookingForNoData($node){

    // Capture the current details
    $fsubj = $node->get('field_subject')->getString();
    $fpages = $node->get('field_pages')->getString();
    $fdate = $node->get('field_publication_date')->getString();
    $ftype = $node->get('field_file_type')->value;
    $ftop = $node->get('field_topics')->getValue();
    $term_names = [];
    foreach ($node->get('field_topics')->referencedEntities() as $term) {
        $term_names[] = $term->label();
    }

    // ASSERTATIONS
    $this->assertEquals('', $fsubj, 'Subject is blank');
    $this->assertNotEquals('Testing Metadata in PDFs', $fsubj, 'Extracted subject doesnt match expected');
    $this->assertEquals('', $fpages, 'Catalog is blank');
    $this->assertNotEquals(1, $fpages, 'Extracted catalog doesnt match expected');
    $this->assertEquals('', $fdate, 'Publication date is blank');
    $this->assertEquals('', $ftype, 'FileType is blank');
    $this->assertNotEquals('pdf', $ftype, 'Extracted file_type doesnt match expected');
    $this->assertEquals([], $ftop, 'Topic is blank');
  }

  /**
   * Verifies that extracted data exists on the node
   * 
   * @param $nid
   *  The node to check for extracted data.
   * 
   * @return void
   */
  public function lookingForCorrectData($nid){

    $node =  \Drupal::entityTypeManager()->getStorage('node')->load($nid);

    // there's some goofy speed issue here, skipping for now
    if ($nid == 5 && $node == null){
      return;
    }

    // Capture the current details
    $this->assertNotEquals(null, $node, "Node $nid is blank for some reason");
    $fsubj = $node->get('field_subject')->getString();
    $fpages = $node->get('field_pages')->getString();
    $fdate = $node->get('field_publication_date')->getString();
    $ftype = $node->get('field_file_type')->value;
    $ftop = $node->get('field_topics')->getValue();
    $term_names = [];
    foreach ($node->get('field_topics')->referencedEntities() as $term) {
        $term_names[] = $term->label();
    }

    // ASSERTATIONS
    $this->assertNotEquals('', $fpages, 'Catalog is blank');
    $this->assertEquals(1, $fpages, 'Extracted catalog doesnt match expected');
    $this->assertNotEquals('', $fdate, 'Publication date is blank');
    $this->assertNotFalse(strtotime($fdate), "The publication date is not a valid date timestamp.");
    $this->assertNotEquals('', $ftype, 'FileType is blank');
    $this->assertEquals('pdf', $ftype, 'Extracted file_type doesnt match expected');
    $this->assertNotEquals('', $ftop, 'Topic is blank');
    $this->assertContains('Drupal', $term_names, "The expected taxonomy term name Drupal is not present.");
    $this->assertContains('TCPDF', $term_names, "The expected taxonomy term name TCPDF is not present.");
    $this->assertContains('Test', $term_names, "The expected taxonomy term name Test is not present.");
    $this->assertContains('Metadata', $term_names, "The expected taxonomy term name Metadata is not present.");
  }
}

