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

    $this->setConfigSetting('file_ingest.ingest_directory', 'test-files');

    $files = [
      'attached.pdf',
      'metadoc.pdfx',
      'banner.doc',
      'test_metadata.pdf',
      'publication_23.pdf',
      'document2.pdf',
      'document4.pdf'
    ];

    $fidsGood = [4,5,6,7];
    $popped = [1];
    $fidsBad = [1,2,3];

    // create files
    foreach ($files as $name) {
      $file = $this->createDrupalFile($name, $this->generatePdfWithMetadata(), 'application/pdf', false);
    }

    foreach ($popped as $pop){
      $this->createNode($pop);
    }

    $directory = 'public://test-files'; // Change this to 'public://' for all public files.
    $real_path = $this->container->get('file_system')->realpath($directory);

    $files = scandir($real_path);

    // Filter out . and .. from scandir result.
    //:$files = array_diff($files, ['.', '..']);


    foreach ($fidsGood as $fid) {
//     $this->deleteFileEntity($fid);
      print File::load($fid)->uri . "\n"; // Prints the full URI like "public://subdir/file.jpg"
    }


    // Process the files and ingest
    $this->batchProcessor->processFiles();

    // Ensure that files already attached to nodes aren't messed with
    foreach ($fidsBad as $pop){
      $this->lookingForNoData($pop);
    }


    // Ensure that each file is attached to a node and has extracted metadata
    foreach ($fidsGood as $fid){
      $this->lookingForCorrectData($fid);
    }

  }

  /**
   *
   */
  public function lookingForNoData($fid){
    $this->assertNotEquals('', $fid, 'Fid is empty');

    $nid = $fid;
    $node =  \Drupal::entityTypeManager()->getStorage('node')->load($nid);
echo print_r($node->toArray(), true);
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
   *
   */
  public function lookingForCorrectData($fid){

    $this->assertNotEquals('', $fid, 'Nid is empty');

    $nid = $fid;

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
public function deleteFileEntity($file_id){
// Load the file entity.
$file = File::load($file_id);

if ($file) {
    // Remove any references in file_usage.
    \Drupal::service('file.usage')->delete($file, 'custom_module', 'entity_name', $entity_id);

    // Manually delete the file entry from the database.
    $connection = Database::getConnection();
    $connection->delete('file_managed')
        ->condition('fid', $file->id())
        ->execute();

    \Drupal::logger('custom_module')->notice('File entry deleted from database: @fid', ['@fid' => $file->id()]);
} else {
    \Drupal::logger('custom_module')->error('File not found.');
}
}
}
