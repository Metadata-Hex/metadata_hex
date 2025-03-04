<?php

namespace Drupal\Tests\metadata_hex\Kernel;

use Drupal\Tests\metadata_hex\Kernel\BaseKernelTestHex;

/**
 * Kernel test for the MetadataBatchProcessor service.
 *
 * @group metadata_hex
 */
class MetadataBatchProcessorKernelTest extends BaseKernelTestHex {

  /**
   * Tests processing a node with a valid PDF file.
   */
  public function testProcessNodeWithValidFileTypeNoMetadata() {

    $this->expectException(\Exception::class);
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
    $fsubj = $node_alt->get('field_subject')->getString();
    $fpages = $node_alt->get('field_pages')->getString();
    $fdate = $node_alt->get('field_publication_date')->getString();
    $ftype = $node_alt->get('field_file_type')->value;
    $ftop = $node_alt->get('field_topics')->getValue();
    $term_names = [];
    foreach ($node_alt->get('field_topics')->referencedEntities() as $term) {
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

 
   /**
   * Tests processing node with title not protected
   */
  public function testProcessNodeWithTitleNotProtected() {
    $this->setConfigSetting('extraction_settings.title_protected', FALSE);

    $file = $this->createDrupalFile('test_metadata.pdf', $this->generatePdfWithMetadata(), 'application/pdf');
    $node = $this->createNode($file);

    // Capture the original details
    $created = $node->getCreatedTime();
    $modified = $node->getChangedTime();
    $title = $node->get('title')->getString();

    // Process the node
    $this->batchProcessor->processNode($node->id());

    // Reload the node now that batch processes have occured
    $node_alt = \Drupal::entityTypeManager()->getStorage('node')->load($node->id());

    // Capture the current details
    $created_alt = $node_alt->getCreatedTime();
    $modified_alt = $node_alt->getChangedTime();
    $ftitle = $node_alt->get('title')->getString();
    $fsubj = $node_alt->get('field_subject')->getString();
    $fpages = $node_alt->get('field_pages')->getString();
    $fdate = $node_alt->get('field_publication_date')->getString();
    $ftype = $node_alt->get('field_file_type')->value;
    $ftop = $node_alt->get('field_topics')->getValue();
    $term_names = [];
    foreach ($node_alt->get('field_topics')->referencedEntities() as $term) {
        $term_names[] = $term->label();
    }

    // ASSERTATIONS
    $this->assertEquals($created, $created_alt, 'Node creation dates dont match');

    $this->assertNotEquals($title, $ftitle, 'Titles match on non-protected title');

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

   /**
   * Tests processing Nodes with data protected
   */
  
   public function testProcessNodeWithDataProtected() {

    /**
     * TODO: Add checks against taxonomy/string_lists
    */
    $this->setConfigSetting('extraction_settings.data_protected', TRUE);

    $file = $this->createDrupalFile('test_metadata.pdf', $this->generatePdfWithMetadata(), 'application/pdf');
    $node = $this->createNode($file);
    $timestamp = time();
    $pages = 45;
    $node->set('field_publication_date', $timestamp);
    $node->set('field_pages', $pages);
    $node->save();

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
    $fsubj = $node_alt->get('field_subject')->getString();
    $fpages = $node_alt->get('field_pages')->getString();
    $fdate = $node_alt->get('field_publication_date')->getString();
    $ftype = $node_alt->get('field_file_type')->value;
    $ftop = $node_alt->get('field_topics')->getValue();
    $term_names = [];
    foreach ($node_alt->get('field_topics')->referencedEntities() as $term) {
        $term_names[] = $term->label();
    }

    // ASSERTATIONS
    $this->assertEquals($created, $created_alt, 'Node creation dates dont match');

    $this->assertNotEquals('', $fsubj, 'Subject is blank');
    $this->assertEquals('Testing Metadata in PDFs', $fsubj, 'Extracted subject doesnt match expected');

    $this->assertNotEquals('', $fpages, 'Catalog is blank');
    $this->assertNotEquals($pages, $fpages, 'Extracted pages match when they shouldnt');

    $this->assertNotEquals('', $fdate, 'Publication date is blank');
    $this->assertNotFalse(strtotime($fdate), "The publication date is not a valid date timestamp.");
    $this->assertNotEquals($timestamp, $fdate, "The publication date hasnt changed since initial node creation");

    $this->assertNotEquals('', $ftype, 'FileType is blank');
    $this->assertEquals('pdf', $ftype, 'Extracted file_type doesnt match expected');

    $this->assertNotEquals('', $ftop, 'Topic is blank');
    $this->assertContains('Drupal', $term_names, "The expected taxonomy term name Drupal is not present.");
    $this->assertContains('TCPDF', $term_names, "The expected taxonomy term name TCPDF is not present.");
    $this->assertContains('Test', $term_names, "The expected taxonomy term name Test is not present.");
    $this->assertContains('Metadata', $term_names, "The expected taxonomy term name Metadata is not present.");
    }

/**
   * Tests processing with strict handling
   */
  public function testProcessNodeWithStrictHandling() { //not working
echo PHP_EOL."STRICTHANDLING".PHP_EOL;
    $this->setConfigSetting('extraction_settings.strict_handling', TRUE);
    $this->setConfigSetting('extraction_settings.field_mappings', 'keyWoRds|field_topics\ntiTlE|title\nsuBjEct|field_subject\nCReationDaTE|field_publication_date\nPAGES|field_pages\nDC:FormAt|field_file_type');
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
    $fsubj = $node_alt->get('field_subject')->getString();
    $fpages = $node_alt->get('field_pages')->getString();
    $fdate = $node_alt->get('field_publication_date')->getString();
    $ftype = $node_alt->get('field_file_type')->value;
    $ftop = $node_alt->get('field_topics')->getValue();
    $term_names = [];
    foreach ($node_alt->get('field_topics')->referencedEntities() as $term) {
        $term_names[] = $term->label();
    }

    // ASSERTATIONS
    $this->assertEquals($created, $created_alt, 'Node creation dates dont match');

    $this->assertNotEquals('', $fsubj, 'Subject is blank');
    $this->assertNotEquals('Testing Metadata in PDFs', $fsubj, 'Extracted subject doesnt match expected'); //fails, it equals

    $this->assertNotEquals('', $fpages, 'Catalog is blank');
    $this->assertNotEquals(1, $fpages, 'Extracted catalog doesnt match expected');

    $this->assertNotEquals('', $fdate, 'Publication date is blank');
    $this->assertNotFalse(strtotime($fdate), "The publication date is not a valid date timestamp.");

    $this->assertNotEquals('', $ftype, 'FileType is blank');
    $this->assertNotEquals('pdf', $ftype, 'Extracted file_type doesnt match expected');

    $this->assertNotEquals('', $ftop, 'Topic is blank');
    $this->assertNotContains('Drupal', $term_names, "The expected taxonomy term name Drupal is not present.");
    $this->assertNotContains('TCPDF', $term_names, "The expected taxonomy term name TCPDF is not present.");
    $this->assertNotContains('Test', $term_names, "The expected taxonomy term name Test is not present.");
    $this->assertNotContains('Metadata', $term_names, "The expected taxonomy term name Metadata is not present.");
    }

  /**
   * Tests processing with flatten keys 
   */
  public function testProcessNodeWithFlattenKeys() { //not working
    echo PHP_EOL."FLATTEN".PHP_EOL;

    $this->setConfigSetting('extraction_settings.flatten_keys', TRUE);
    $this->setConfigSetting('extraction_settings.field_mappings', "keywords|field_topics\ntitle|title\nsubject|field_subject\nCreationDate|field_publication_date\nPages|field_pages\nformat|field_file_type");
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
    $fsubj = $node_alt->get('field_subject')->getString();
    $fpages = $node_alt->get('field_pages')->getString();
    $fdate = $node_alt->get('field_publication_date')->getString();
    $ftype = $node_alt->get('field_file_type')->value;
    $ftop = $node_alt->get('field_topics')->getValue();
    $term_names = [];
    foreach ($node_alt->get('field_topics')->referencedEntities() as $term) {
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
    $this->assertNotEquals('pdf', $ftype, 'Extracted file_type matches when it shouldnt'); // it shouldnt be pdf == pdf 

    $this->assertNotEquals('', $ftop, 'Topic is blank');
    $this->assertContains('Drupal', $term_names, "The expected taxonomy term name Drupal is not present.");
    $this->assertContains('TCPDF', $term_names, "The expected taxonomy term name TCPDF is not present.");
    $this->assertContains('Test', $term_names, "The expected taxonomy term name Test is not present.");
    $this->assertContains('Metadata', $term_names, "The expected taxonomy term name Metadata is not present.");
    }

  /**
   * Tests processing .
   */
  public function testProcessNodeWithBundleNotSelected() {

  // Setup an actual valid pdf file with metadata and node
  $this->setConfigSetting('extraction_settings.hook_node_types', ['page']);
    $file = $this->createDrupalFile('test_metadata.pdf', $this->generatePdfWithMetadata(), 'application/pdf');
    $node = $this->createNode($file);

    // Capture the original details
    $created = $node->getCreatedTime();
    $modified = $node->getChangedTime();

    // Process the node
    $this->batchProcessor->processNode($node->id());
    // Reload the node now that batch processes have occured
    $node_alt = \Drupal::entityTypeManager()->getStorage('node')->load($node->id());

    // ASSERTATIONS
    $this->assertNotEquals($created, $created_alt, 'Node creation dates dont match');
  }

/**
   * Tests processing with incorrect mappings
   * 
   */
  public function testProcessNodeWithFieldMapping() { //not working
    echo PHP_EOL."FIELD".PHP_EOL;

    $this->setConfigSetting('extraction_settings.field_mappings', "keywords|field_topics\ntitle|title\ndx:subjcts|field_subject\nCreationDate|field_pub_date\nDC:Format|field_file_type");

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
    $fsubj = $node_alt->get('field_subject')->getString();
    $fpages = $node_alt->get('field_pages')->getString();
    $fdate = $node_alt->get('field_publication_date')->getString();
    $ftype = $node_alt->get('field_file_type')->value;
    $ftop = $node_alt->get('field_topics')->getValue();
    $term_names = [];
    foreach ($node_alt->get('field_topics')->referencedEntities() as $term) {
        $term_names[] = $term->label();
    }

    // ASSERTATIONS
    $this->assertEquals($created, $created_alt, 'Node creation dates dont match');
    $this->assertEquals($node->id(), $node_alt->id(), 'Node ids dont match');

    $this->assertEquals('', $fsubj, 'Subject is blank');
    $this->assertEquals('Testing Metadata in PDFs', $fsubj, 'Extracted subject doesnt match expected');

    $this->assertNotEquals('', $fpages, 'Catalog is blank');
    $this->assertNotEquals(1, $fpages, 'Extracted catalog doesnt match expected'); // this should = 1 = 

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
