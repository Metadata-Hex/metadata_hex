<?php
namespace Drupal\Tests\metadata_hex\Kernel;

/**
 * Tests backend logic triggered by settings form buttons.
 *
 * @group metadata_hex
 */
class UpsertKernelTest extends BaseKernelTestHex
{

  private function setupTest()
  {

    // Simulate creating a new Article node via user form submission.
    $file = $this->createDrupalFile('document.pdf', $this->generatePdfWithMetadata());
    $node = \Drupal\node\Entity\Node::create([
      'type' => 'article',
      'title' => 'Test Document Node',
      'field_subject' => '',
      'uid' => 1, // Assign to test user.
      'field_attachment' => [
        'target_id' => $file?->id() ?? null,
      ],
    ]);

    $node->save();
    return $node;
  }

  /**
   * test upsert hook with correct bundle
   * @return void
   */
  public function testUpsertCorrectBundle()
  {

    $node = $this->setupTest();
    sleep(seconds: 1); // Wait for the node to be saved and processed.

    $this->lookingForCorrectData($node->id());
  }

  /**
   * test upsert hook with different bundle selected
   * @return void
   */

  public function testUpsertIncorrectBundle()
  {
    $this->setConfigSetting('extraction_settings.hook_node_types', ['document']);

    $node = $this->setupTest();
    sleep(seconds: 1); // Wait for the node to be saved and processed.

    $this->lookingForNoData($node->id());
  }

  /**
   * Looking for correct data on the node
   * @param int $nid
   *  The node id to check for extracted data.
   */
  public function lookingForCorrectData($nid)
  {
    $this->assertNotEquals('', $nid, 'Nid is empty');

    $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);

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

  /**
   * Verifies that no extracted data exists on the node
   * 
   * @param int $nid
   *  The node to check for extracted data.
   * 
   * @return void
   */
  public function lookingForNoData($nid)
  {
    $this->assertNotEquals('', $nid, 'Nid is empty');

    $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);

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