<?php

namespace Drupal\your_module\Tests;

use Drupal\KernelTests\KernelTestBase;
use Drupal\file\Entity\File;
use Drupal\your_module\Service\PdfExtractorService;

/**
 * Tests for the PDF extraction module functionality.
 *
 * @group your_module
 */
class PdfExtractionFunctionalTest extends KernelTestBase {

  /**
   * Modules required for the test.
   *
   * @var array
   */
  protected static $modules = ['pdf_meta_extraction', 'file'];

  /**
   * A test PDF file relative to the module.
   *
   * @var string
   */
  protected $testPdfPath;

  /**
   * Setup environment for tests.
   */
  protected function setUp(): void {
    parent::setUp();

    // Define relative path to the test PDF inside the module.
    $this->testPdfPath = \Drupal::root() . '/' . drupal_get_path('module', 'pdf_meta_extraction') . '/tests/files/sample.pdf';

    // Ensure the file exists.
    $this->assertTrue(file_exists($this->testPdfPath), 'Test PDF file found.');
  }

  /**
   * Test basic PDF extraction functionality.
   */
  public function testPdfExtraction() {
    $pdfExtractor = \Drupal::service('pdf_meta_extraction.pdf_extractor');
    
    // Load the test PDF.
    $file = File::create([
      'uri' => 'public://test-sample.pdf',
    ]);
    $file->setFileContents(file_get_contents($this->testPdfPath));
    $file->save();

    // Call the extraction service.
    $text = $pdfExtractor->extractText($file->getFileUri());

    // Assertions.
    $this->assertNotEmpty($text, 'Extracted text is not empty.');
    $this->assertStringContainsString('Expected Content', $text, 'Extracted text contains expected content.');
  }

  function pdf_meta_extraction_process($entity){
    \Drupal::logger('pdf_meta_extraction')->info(__FUNCTION__.":".__LINE__);
   if ($entity instanceof Drupal\node\NodeInterface) {
     // Load the settings from the configuration.
     $config = \Drupal::config('pdf_meta_extraction.settings');
     $allowed_types = $config->get('content_types');
     \Drupal::logger('pdf_meta_extraction')->info(__FUNCTION__.":".__LINE__);
 
     // Check if the content type of the node is in the allowed types.
     if (is_array($allowed_types) && in_array($entity->getType(), $allowed_types)) {
       // Create an instance of the ProcessPdf class
       \Drupal::logger('pdf_meta_extraction')->info(__FUNCTION__.":".__LINE__);
 
       $pdfProcessor = new \Drupal\pdf_meta_extraction\ProcessPdf();
       $pdfProcessor->setInsert();
       \Drupal::logger('pdf_meta_extraction')->info(__FUNCTION__.":".__LINE__);
 
       // Call the method on the instance
       $pdfProcessor->processPdfNodeData($entity->id());
 
       \Drupal::logger('pdf_meta_extraction')->info(__FUNCTION__.":".__LINE__);
 
       \Drupal::messenger()->addMessage('Node of allowed content type is being saved.');
     }
   }
 }
}

