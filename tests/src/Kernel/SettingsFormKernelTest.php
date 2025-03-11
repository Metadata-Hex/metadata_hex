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
     * @var \Drupal\Core\Messenger\MessengerInterface
     */
    protected $metadataExtractor;
  
    /**
     * The messenger service.
     *
     * @var \Drupal\Core\Messenger\MessengerInterface
     */
    protected $metadataBatchProcessor;
  
    protected $file_system;
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
    public function __construct(ConfigFactoryInterface $configFactory, TypedConfigManagerInterface $typedConfigManager, MessengerInterface $messenger, FileSystemInterface $fileSystem) {
      $this->configFactory = $configFactory;
      $this->typedConfigManager = $typedConfigManager;
      $this->messenger = $messenger;
      $this->file_system = $fileSystem;
      $this->metadataExtractor = new MetadataExtractor(\Drupal::service('logger.channel.default'));
      $this->metadataBatchProcessor = new MetadataBatchProcessor(\Drupal::service('logger.channel.default'), $this->metadataExtractor, $this->file_system);
  
    }
  
    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container) {
      return new static(
        $container->get('config.factory'),
        $container->get('config.typed'),
        $container->get('messenger')
      );
    }
  
    /**
     * {@inheritdoc}
     */
    // public function getFormId() {
    //   return 'metadata_hex_settings_form';
    // }
  
    /**
     * {@inheritdoc}
     */
  //   public function buildForm(array $form, FormStateInterface $form_state) {
  //     $config = $this->configFactory->get('metadata_hex.settings');
  
  //     $form['example_setting'] = [
  //       '#type' => 'textfield',
  //       '#title' => $this->t('Example Setting'),
  //       '#default_value' => $config->get('example_setting') ?? '',
  //     ];
  
  //     $form['actions']['submit'] = [
  //       '#type' => 'submit',
  //       '#value' => $this->t('Save'),
  //     ];
  
  //     return $form;
  //   }
  
  //   /**
  //    * {@inheritdoc}
  //    */
  //   public function submitForm(array &$form, FormStateInterface $form_state) {
  //     $this->configFactory->getEditable('metadata_hex.settings')
  //       ->set('example_setting', $form_state->getValue('example_setting'))
  //       ->save();
  
  //     $this->messenger->addStatus($this->t('Settings saved successfully.'));
  //   }
  // }

  protected function setMockEntities(){
    $files = [
      'metadoc.pdfx',
      'test_metadata.pdf',
      'publication_23.pdf',
      'document2.pdf',
      'document4.pdf'
    ];


    foreach ($files as $name) {
      $file = $this->createDrupalFile($name, $this->generatePdfWithMetadata(), 'application/pdf');
      $node = $this->createNode($file);
    }
  }

  protected function setMockOrphansFiles(){
    $files = [
      'orphan1.pdf',
      'orphan_test_metadata.pdf',
      'orphan_tpublication_23.pdf',
      'orphan_tdocument2.pdf',
      'orphan_tdocument4.pdf'
    ];


    foreach ($files as $name) {
      $file = $this->createDrupalFile($name, $this->generatePdfWithMetadata(), 'application/pdf', false);
    }
  }


  protected function setMockUnattachedFiles(){
    $files = [
      'unatt.pdf',
      'unatt_test_metadata.pdf',
      'unatt_tpublication_23.pdf',
      'unatt_tdocument2.pdf',
      'unatt_tdocument4.pdf'
    ];


    foreach ($files as $name) {
      $file = $this->createDrupalFile($name, $this->generatePdfWithMetadata(), 'application/pdf', true);
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
  protected function getMockFormState(array $values, $triggering_element) {
    $form_state = new FormState();
    $form_state->setValues($values);
    $form_state->setTriggeringElement(['#name' => $triggering_element]);

    return $form_state;
  }

  /**
   * Tests that the save button correctly updates configuration.
   */
  public function testBatchIngestButton() {

    $this->setMockEntities();
    // Load the form and submit a mock request.
    $form = new SettingsForm($this->configFactory,
    $this->typedConfigManager, // Required by parent
    $this->$metadataBatchProcessor,
    $this->$metadataExtractor,
    $this->$messenger);

$settings = [
  'hook_node_types' => ['article', 'page'],
  'field_mappings' => "keywords|field_topics\ntitle|title\nsubject|field_subject\nCreationDate|field_publication_date\nPages|field_pages\nDC:Format|field_file_type",
  'bundle_types' => ['article'],
  'node_process.allow_reprocess' => TRUE,
  'bundle_type_for_generation' => 'article',
  'file_attachment_field' => 'field_file_attachment',
  'ingest_directory' => '/',
];
$nids = [1, 2, 3, 4, 5];

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
   * Tests that the save button correctly updates configuration.
   */
  public function testFileIngestButton() {

    $this->setMockEntities();
    $this->setMockOrphansFiles();
    $this->setMockUnattachedFiles();
    // Load the form and submit a mock request.
    $form = new SettingsForm($this->configFactory,
    $this->typedConfigManager, // Required by parent
    $this->$metadataBatchProcessor,
    $this->$metadataExtractor,
    $this->$messenger);

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


/**     $form['file_ingest']['process_cron_nodes'] = [
  '#type' => 'submit',
  '#value' => $this->t('Ingest files'),
  '#submit' => ['::processAllFiles'], 
]; */

/**
 *     $form['node_process']['process_nodes'] = [
      '#type' => 'submit',
      '#value' => $this->t('Manually process all selected node types'),
      '#submit' => ['::processAllNodes'],
    ];
 */
    // Assert that the configuration was saved correctly.
    // $config = $this->config('metadata_hex.settings');
    // $this->assertEquals('new_value', $config->get('some_setting'), 'The setting was updated.');
  }

  // /**
  //  * Tests the reset button functionality.
  //  */
  // public function testResetButton() {
  //   // Set initial configuration.
  //   $this->config('metadata_hex.settings')->set('some_setting', 'old_value')->save();

  //   // Load the form and simulate clicking the reset button.
  //   $form = new SettingsForm();
  //   $form_state = $this->getMockFormState([], 'reset'); // Simulating reset button click

  //   $form->submitForm([], $form_state);

  //   // Assert that the configuration was reset (modify expected value as needed).
  //   $config = $this->config('metadata_hex.settings');
  //   $this->assertEquals('default_value', $config->get('some_setting'), 'The setting was reset.');
  // }

  // /**
  //  * Helper function to mock form state.
  //  *
  //  * @param array $values
  //  *   The values to simulate in the form submission.
  //  * @param string $triggering_element
  //  *   The name of the button being clicked (optional).
  //  *
  //  * @return \Drupal\Core\Form\FormState
  //  *   The mocked form state.
  //  */
  // protected function getMockFormState(array $values, $triggering_element = 'save') {
  //   $form_state = $this->createMock(\Drupal\Core\Form\FormStateInterface::class);

  //   $form_state->method('getValues')->willReturn($values);
  //   $form_state->method('getTriggeringElement')->willReturn(['#name' => $triggering_element]);

  //   return $form_state;
  // }
}
