<?php

namespace Drupal\Tests\metadata_hex\Kernel;

use Drupal\Tests\metadata_hex\Kernel\BaseKernelTestHex;

/**
 * Kernel test for the MetadataBatchProcessor service.
 *
 * @group metadata_hex
 */
class BatchFileIngestKernelTest extends BaseKernelTestHex {

  

  /**
   * Tests processing a node with a valid PDF file.
   */
  public function testProcessNodeWithValidPdfWithMetadata() {

    $files = [
      'attached.pdf',
      'metadoc.pdfx',
      'banner.doc',
      'test_metadata.pdf',
      'publication_23.pdf',
      'document2.pdf',
      'document4.pdf'
    ];

    $fids = [];
    $popped = [];

    // create files
    foreach ($files as $name) {
      $file = $this->createDrupalFile($name, $this->generatePdfWithMetadata(), 'application/pdf');
      $fids[] = $file->id();
    }

    // pop off the first and attach it to a node
    $popped[] = array_shift($fids); 

    foreach ($popped as $pop){
      $node = $this->createNode($pop);
    }

    // Process the files and ingest
    $this->batchProcessor->processFiles();

    // Ensure that each file is attached to a node and has extracted metadata
    foreach ($fids as $fid){
      $this->lookingForCorrectData($fid);
    }

    // Ensure that files already attached to nodes aren't messed with
    foreach ($popped as $pop){
      $this->lookingForNoData($fid);
    }

  }

  /**
   * 
   */
  public function lookingForNoData($fid){ 

    $query = \Drupal::entityQuery('node')
    ->condition('field_attachment', $fid)
    ->accessCheck(false)
    ->execute();

$nids = array_values($query); // Get node IDs
$nid = reset($nids); // Get the first node ID (if any)
    $node =  \Drupal::entityTypeManager()->getStorage('node')->load($nid);
  
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

    $this->assertNotEquals('', $fsubj, 'Subject is blank');
    $this->assertNotEquals('Testing Metadata in PDFs', $fsubj, 'Extracted subject doesnt match expected');

    $this->assertNotEquals('', $fpages, 'Catalog is blank');
    $this->assertNotEquals(1, $fpages, 'Extracted catalog doesnt match expected');

    $this->assertNotEquals('', $fdate, 'Publication date is blank');

    $this->assertNotEquals('', $ftype, 'FileType is blank');
    $this->assertNotEquals('pdf', $ftype, 'Extracted file_type doesnt match expected');

    $this->assertEquals([], $ftop, 'Topic is blank');
  }

  /**
   * 
   */
  public function lookingForCorrectData($nid){ 

    $node =  \Drupal::entityTypeManager()->getStorage('node')->load($nid);
  
    // Capture the current details
    $created_alt = $node->getCreatedTime();
    $modified_alt = $node->getChangedTime();
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
    $this->assertEquals($created, $created_alt, 'Node creation dates dont match');

    $this->assertNotEquals('', $fsubj, 'Subject is blank');
    $this->assertEquals('Testing Metadata in PDFs', $fsubj, 'Extracted subject doesnt match expected');

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