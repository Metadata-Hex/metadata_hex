<?php

namespace Drupal\Tests\metadata_hex\Kernel;
use Drupal\file\Entity\File;
use Drupal\Core\Database\Database;

use Drupal\Tests\metadata_hex\Kernel\BaseKernelTestHex;
use Drupal\Core\File\FileSystemInterface;

/**
 * Kernel test for the MetadataBatchProcessor service.
 *
 * @group metadata_hex
 */
class BatchFileIngestKernelTest extends BaseKernelTestHex {

  /**
   * Tests processing a node with a valid PDF file.
   */
  public function testBatchFileIngest() {

    $this->setConfigSetting('file_ingest.ingest_directory', 'test-files/');

    $file_names = [
      'attached.pdf', //1
      // 'metadoc.pdfx', //2
      // 'banner.doc', // 
      'test_metadata.pdf', //3
      'publication_23.pdf', //4
      'document2.pdf', //5
      'document4.pdf' //6
    ];

    // $files_unattached_valid = [4,5,6,7];
    // $files_on_node = [1];
    // $files_unattached_invalid = [1,2,3];
    $files = [];

    // create files
    foreach ($file_names as $name) {
      $files[] = $this->createDrupalFile($name, $this->generatePdfWithMetadata(), 'application/pdf', false);
    }

    // My mistake here is in forgetting that im explicitly creating files in the system without File entries in Drupal
    // Step 1: Select a random key from the $files array
    $randomKey = array_rand($files);

    // Step 2: Transfer the File object to $files_linked
    $files_linked[] = $files[$randomKey];

    // Step 3: Remove the File object from $files
    unset($files[$randomKey]);
    foreach ($files_linked as $file){
      $this->createNode($file);
    }

    $directory = 'public://test-files'; // Change this to 'public://' for all public files.
    $real_path = $this->container->get('file_system')->realpath($directory);

    $root_files = scandir($real_path);

foreach ($root_files as $rf){
  $this->assertContains($rf, $root_files, "File $rf is missing from root files."); // $file->id().$file->getFileUri();
}

    // Process the files and ingest
    $this->batchProcessor->processFiles();

    // Ensure that files already attached to nodes aren't messed with
    foreach ($files_linked as $furi){
      
      $this->lookingForNoData($furi);
    }


    // Ensure that each file is attached to a node and has extracted metadata
    foreach ($files as $furi){
      $this->lookingForCorrectData($furi);
    }

  }

  /**
   *
   */
  public function lookingForNoData($furi){
    $this->assertNotEquals('', $furi, 'Furi is empty');
    $files = \Drupal::entityTypeManager()
    ->getStorage('file')
    ->loadByProperties(['uri' => $file_uri]);

  // Retrieve the first file entity from the result.
  $file = reset($files);

$storage = \Drupal::entityTypeManager()->getStorage('node');

$nodes = $storage->loadByProperties(['field_attachment' => $file->id()]);

    //$nid = $fid;
   // $node =  \Drupal::entityTypeManager()->getStorage('node')->load($nid);
foreach($nodes as $node){
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
  }

  /**
   *
   */
  public function lookingForCorrectData($furi){

    $this->assertNotEquals('', $furi, 'Nid is empty');

    $files = \Drupal::entityTypeManager()
    ->getStorage('file')
    ->loadByProperties(['uri' => $file_uri]);

  // Retrieve the first file entity from the result.
  $file = reset($files);
  $nodes = $storage->loadByProperties(['field_attachment' => $file->id()]);
  foreach($nodes as $node){

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
}

