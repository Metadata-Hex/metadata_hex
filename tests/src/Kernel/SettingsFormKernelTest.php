<?php
namespace Drupal\Tests\metadata_hex\Kernel;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\metadata_hex\Form\SettingsForm;
use Symfony\Component\HttpFoundation\Request;
use Drupal\metadata_hex\Service\MetadataBatchProcessor;
use Drupal\metadata_hex\Service\MetadataExtractor;

/**
 * Tests backend logic triggered by settings form buttons.
 *
 * @group metadata_hex
 */
class SettingsFormKernelTest extends BaseKernelTestHex {





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
  
    protected $form;

    protected $settings = [
      'hook_node_types' => ['article', 'page'],
      'field_mappings' => "keywords|field_topics\ntitle|title\nsubject|field_subject\nCreationDate|field_publication_date\nPages|field_pages\nDC:Format|field_file_type",
      'bundle_types' => ['article'],
      'node_process.allow_reprocess' => TRUE,
      'bundle_type_for_generation' => 'article',
      'file_attachment_field' => 'field_file_attachment',
      'ingest_directory' => '/',
    ];
    /**
     * 
     */
    protected $file_system;


    private function initSettingsFormTest(){
      $this->configFactory = $this->container->get('config.factory');
      $this->typedConfigManager = $this->container->get('config.typed');
      $this->messenger = $this->container->get('messenger');
      $this->file_system = $this->container->get('file_system');
      $this->metadataExtractor = new MetadataExtractor(\Drupal::service('logger.channel.default'));
      $this->metadataBatchProcessor = new MetadataBatchProcessor(\Drupal::service('logger.channel.default'), $this->metadataExtractor, $this->file_system);

        // Manually instantiate the form with required dependencies.
        $this->form = new SettingsForm($this->configFactory,
        $this->typedConfigManager, // Required by parent
        $this->metadataBatchProcessor,
        $this->metadataExtractor,
        $this->messenger);
    }

    /**
     * Constructs a new SettingsForm.
     *
     * @param ConfigFactoryInterface $configFactory
     *   The configuration factory.
     * @param TypedConfigManagerInterface $typedConfigManager
     *   The typed configuration manager.
     * @param MessengerInterface $messenger
     *   The messenger service.
     */
    // public function __construct(ConfigFactoryInterface $configFactory, TypedConfigManagerInterface $typedConfigManager, MessengerInterface $messenger, FileSystemInterface $fileSystem) {
    //   $this->configFactory = $configFactory;
    //   $this->typedConfigManager = $typedConfigManager;
    //   $this->messenger = $messenger;
    //   $this->file_system = $fileSystem;
    //   $this->metadataExtractor = new MetadataExtractor(\Drupal::service('logger.channel.default'));
    //   $this->metadataBatchProcessor = new MetadataBatchProcessor(\Drupal::service('logger.channel.default'), $this->metadataExtractor, $this->file_system);
  
    // }
  
    // /**
    //  * {@inheritdoc}
    //  */
    // public static function create(ContainerInterface $container) {
    //   return new static(
    //     $container->get('config.factory'),
    //     $container->get('config.typed'),
    //     $container->get('messenger')
    //   );
    // }
    // protected function setUp(): void {
    //  parent::setUp();
      //$this->installConfig(['metadata_hex']);
    //   $this->configFactory = $this->container->get('config.factory');
    // $this->typedConfigManager = $this->container->get('config.typed');
    // $this->messenger = $this->container->get('messenger');
    // $this->file_system = $this->container->get('file_system');
    //   // Manually instantiate the form with required dependencies.
    //   $this->form = new SettingsForm($this->configFactory,
    //   $this->typedConfigManager, // Required by parent
    //   $this->metadataBatchProcessor,
    //   $this->metadataExtractor,
    //   $this->messenger);
  // }
 
  /**
   * Tests that the save button correctly updates configuration.
   */
  public function testBatchIngestButton() {

    $this->initSettingsformTest();
    $this->setMockEntities();
    // // Load the form and submit a mock request.
    // $form = new SettingsForm($this->configFactory,
    // $this->typedConfigManager, // Required by parent
    // $this->metadataBatchProcessor,
    // $this->metadataExtractor,
    // $this->messenger);


$nids = [1, 2, 3, 4, 5];

    $formState = new FormState();
     $this->form->buildForm($this->settings, $formState);
    $form_state = $this->getMockFormState($this->settings, 'process_cron_nodes');
    // Submit the form.
    $this->form->submitForm($this->settings, $form_state);

    foreach ($nids as $nid){
      $this->lookingForCorrectData($nid);
    }
}

  /**
   * Tests that the save button correctly updates configuration.
   */
  public function testFileIngestButton() {

    $this->initSettingsformTest();
    $this->setMockEntities();
    $this->setMockOrphansFiles();
    $this->setMockUnattachedFiles();
    // Load the form and submit a mock request.
    $form = new SettingsForm($this->configFactory,
    $this->typedConfigManager, // Required by parent
    $this->metadataBatchProcessor,
    $this->metadataExtractor,
    $this->messenger);

$settings = [
  'hook_node_types' => ['article', 'page'],
  'field_mappings' => "keywords|field_topics\ntitle|title\nsubject|field_subject\nCreationDate|field_publication_date\nPages|field_pages\nDC:Format|field_file_type",
  'bundle_types' => ['article'],
  'node_process.allow_reprocess' => TRUE,
  'bundle_type_for_generation' => 'article',
  'file_attachment_field' => 'field_file_attachment',
  'ingest_directory' => '/',
];
$nids = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15];

    $formState = new FormState();
    $builtForm = $this->form->buildForm($form, $formState);
    $formState->setValues();
    $form_state = $this->getMockFormState($settings, 'process_cron_nodes');
    // Submit the form.
    $form->submitForm($settings, $form_state);

    foreach ($nids as $nid){
      $this->lookingForCorrectData($nid);
    }
}

/**
   *
   */
  public function lookingForCorrectData($nid){
    $this->assertNotEquals('', $nid, 'Nid is empty');

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
    protected function getMockFormState(array $values, $triggering_element) {
      $form_state = new \Drupal\Core\Form\FormState();
      $form_state->setValues($values);
      $form_state->setTriggeringElement(['#name' => $triggering_element]);
  
      return $form_state;
    }
  

  }
