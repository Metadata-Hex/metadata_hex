<?php
namespace Drupal\Tests\metadata_hex\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\metadata_hex\Batch\MetadataBatch;
use Drupal\metadata_hex\Form\SettingsForm;
use Drupal\metadata_hex\Service\MetadataBatchProcessor;
use Drupal\metadata_hex\Service\MetadataExtractor;

/**
 * Tests backend logic triggered by settings form buttons.
 *
 * @group metadata_hex
 */
class SettingsFormKernelTest extends BaseKernelTestHex
{

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The typed configuration manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfigManager;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The messenger service.
   *
   * @var \Drupal\metadata_hex\Service\MetadataExtractor
   */
  protected $metadataExtractor;

  /**
   * The messenger service.
   *
   * @var \Drupal\metadata_hex\Service\MetadataBatchProcessor
   */
  protected $metadataBatchProcessor;

  /**
   * The Settings form
   * @var 
   */
  protected $form;

  /**
   * Settings for the form.
   * @var array
   */
  protected $settings = [
    'hook_node_types' => ['article', 'page'],
    'field_mappings' => "keywords|field_topics\ntitle|title\nsubject|field_subject\nCreationDate|field_publication_date\nPages|field_pages\nDC:Format|field_file_type",
    'bundle_types' => ['article'],
    'allow_reprocess' => TRUE,
    'bundle_type_for_generation' => 'article',
    'file_attachment_field' => 'field_attachment',
    'ingest_directory' => '/',
  ];

  /**
   * File system service.
   */
  protected $file_system;

  /**
   * initializes the SettingsForm for testing.
   * @return void
   */
  private function initSettingsFormTest()
  {
    $this->configFactory = $this->container->get('config.factory');
    $this->typedConfigManager = $this->container->get('config.typed');
    $this->messenger = $this->container->get('messenger');
    $this->file_system = $this->container->get('file_system');
    $this->metadataExtractor = new MetadataExtractor(\Drupal::service('logger.channel.default'));
    $this->metadataBatchProcessor = new MetadataBatchProcessor(\Drupal::service('logger.channel.default'), $this->metadataExtractor, $this->file_system);

    // Manually instantiate the form with required dependencies.
    $this->form = new SettingsForm(
      $this->configFactory,
      $this->typedConfigManager, // Required by parent
      $this->metadataBatchProcessor,
      $this->metadataExtractor,
      $this->messenger
    );
  }

  /**
   * Tests that the save button correctly updates configuration.
   */
  public function testBatchIngestButton()
  {
    $this->initSettingsformTest();
    $this->setMockEntities();

    $nids = [1, 2, 3, 4, 5];

    $formState = new FormState();
    $builtForm = $this->form->buildForm($this->settings, $formState);
    $form_state = $this->getMockFormState($this->settings, 'node_process[process_nodes]');

    $batch = $this->form->processAllNodes($builtForm, $form_state);
    $this->runBatchAndAssert($batch);

    // Assert that the expected logic executed.
    $this->assertTrue(TRUE, 'processAllNodes executed without errors.');

    // verify that each new node has extracted data
    foreach ($nids as $nid) {
      $this->lookingForCorrectData($nid);
    }
  }

  /**
   * Tests that the save button correctly updates configuration.
   */
  public function testFileIngestButton()
  {
    $this->initSettingsformTest();
    $this->setMockEntities();
    $this->setMockOrphansFiles();
    $this->setMockUnattachedFiles();

    // Load the form and submit a mock request.
    $form = new SettingsForm(
      $this->configFactory,
      $this->typedConfigManager, // Required by parent
      $this->metadataBatchProcessor,
      $this->metadataExtractor,
      $this->messenger
    );

    $nids = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15];

    $formState = new FormState();
    $builtForm = $this->form->buildForm($this->settings, $formState);
    $form_state = $this->getMockFormState($this->settings, 'file_ingest[process_cron_nodes]');

    $batch = $this->form->processAllFiles($builtForm, $form_state);
    $this->runBatchAndAssert($batch);

    // Assert that the expected logic executed.
    $this->assertTrue(TRUE, 'processAllFiles executed without errors.');

    // verify that each new node has extracted data
    foreach ($nids as $nid) {
      $this->lookingForCorrectData($nid);
    }
  }

  /**
   * runs a batch and assertion on the results
   */
  private function runBatchAndAssert($batch)
  {
    // Prepare the batch context manually
    $context = [];
    foreach ($batch['operations'] as $operation) {
      call_user_func_array($operation[0], array_merge($operation[1], [&$context]));
    }

    // Simulate batch completion
    MetadataBatch::batchFinished(TRUE, $context['results'] ?? [], $batch['operations']);
  }

  /**
   * Verifies that the extracted data exists on the node
   * 
   * @param int $nid
   */
  public function lookingForCorrectData($nid)
  {
    $this->assertNotEquals('', $nid, 'Nid is empty');

    // Print or inspect batch structure.
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

    $subj = $ftype == 'pdf' ? 'PDFs' : 'mds';
    $ext = $ftype == 'pdf' ? $ftype : 'md';

    // ASSERTATIONS
    $this->assertNotEquals('', $fsubj, 'Subject is blank');
    $this->assertEquals('Testing Metadata in ' . $subj, $fsubj, 'Extracted subject doesnt match expected');
    $this->assertNotEquals('', $ftop, 'Topic is blank');

    switch ($ext) {
      case 'pdf':
        $this->assertNotEquals('', $ftype, 'FileType is blank');
        $this->assertContains($ftype, ['pdf', 'md'], 'Extracted file_type doesnt match expected');
        $this->assertNotEquals('', $fpages, 'Catalog is blank');
        $this->assertEquals(1, $fpages, 'Extracted catalog doesnt match expected');
        $this->assertNotEquals('', $fdate, 'Publication date is blank');
        $this->assertNotFalse(strtotime($fdate), "The publication date is not a valid date timestamp.");

        $this->assertContains('Drupal', $term_names, "The expected taxonomy term name Drupal is not present.");
        $this->assertContains('TCPDF', $term_names, "The expected taxonomy term name TCPDF is not present.");
        $this->assertContains('Metadata', $term_names, "The expected taxonomy term name Metadata is not present.");
        $this->assertContains('Test', $term_names, "The expected taxonomy term name Test is not present.");
        break;

      case 'md':
      default:
        $this->assertEquals('', $ftype, 'FileType isnt blank for non-pdf');
        $this->assertEquals('', $fpages, 'Pages isnt blank for non-pdf');
        $this->assertEquals('', $fdate, 'Publication date isnt blank for non-pdf');
        break;
    }
  }

  /**
   * Helper function to create a mock FormState object.
   *
   * @param array $values
   *   Form field values.
   * @param string $triggering_element
   *   Name of the button being clicked.
   *
   * @return \Drupal\Core\Form\FormState
   *   The mocked form state.
   */
  protected function getMockFormState(array $values, $triggering_element)
  {
    $form_state = new FormState();
    $form_state->setValues($values);
    $form_state->setTriggeringElement(['#name' => $triggering_element]);

    return $form_state;
  }
}
