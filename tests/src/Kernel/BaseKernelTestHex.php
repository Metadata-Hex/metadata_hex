<?php
namespace Drupal\Tests\metadata_hex\Kernel;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\metadata_hex\Service\MetadataBatchProcessor;
use Drupal\metadata_hex\Service\MetadataExtractor;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\metadata_hex\Kernel\Traits\TestFileHelperTrait;
use stdClass;

/**
  * @abstract
 */
abstract class BaseKernelTestHex extends KernelTestBase {

  use TestFileHelperTrait;
  /**
   * The modules required for this test.
   *
   * @var array
   */
  protected static $modules = [
    'metadata_hex',
    'node',
    'field',
    'file',
    'user',
    'taxonomy',
    'text',
    'filter',
    'system',
    'content_moderation',
    'workflows',
    'options'
  ];

  /**
   * The MetadataBatchProcessor service.
   *
   * @var \Drupal\metadata_hex\Service\MetadataBatchProcessor
   */
  protected $batchProcessor;
protected $settingsManager;
  /**
   *
   */
  protected $config;

  /**
   * Setup before running the test.
   */
  protected function setUp(): void {
    parent::setUp();

    $this->enableModules(['metadata_hex']);

    // Install required entity schemas.
    $this->installEntitySchema('node');
    $this->installEntitySchema('file');
    $this->installEntitySchema('user');
    $this->installConfig(['taxonomy']);
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('taxonomy_vocabulary');
    $this->installConfig(['text', 'filter', 'field', 'node']);

   Vocabulary::create([
        'vid' => 'tags',
        'name' => 'Tags',
    ])->save();

    Vocabulary::create([
        'vid' => 'topics',
        'name' => 'Topics',
    ])->save();

    // Ensure the "Topics" vocabulary exists.
    $vocabulary = Vocabulary::load('topics');
    if ($vocabulary) {
      // Define an array of default tags.
      $default_tags = ['Science', 'Technology', 'Drupal', 'Metadata'];

      foreach ($default_tags as $tag_name) {
        // Check if the term already exists to avoid duplicates.
        $existing_terms = \Drupal::entityTypeManager()
          ->getStorage('taxonomy_term')
          ->loadByProperties([
            'name' => $tag_name,
            'vid' => 'topics',
          ]);

        if (empty($existing_terms)) {
          // Create the taxonomy term.
          Term::create([
            'name' => $tag_name,
            'vid' => 'topics',
          ])->save();
        }
        else {
        }
      }
    }
    else {
    }

    $this->installSchema('node', ['node_access']);
    $this->installConfig(['metadata_hex']);
    $this->installSchema('metadata_hex', ['metadata_hex_processed']);
    $this->initMetadataHex();

    // Create the "article" content type. 
    NodeType::create([
        'type' => 'article',
        'name' => 'Article',
        'revision' => FALSE,
    ])->save();

    $this->disableRevisionsForContentType();
    $this->cleanContentTypeConfig('article');

    $this->createField('field_subject', 'Subject', 'string');
    $this->createField('field_pages', 'Pages', 'integer');
    $this->createField('field_publication_date', 'Publication Date', 'timestamp',  ['datetime_type' => 'datetime']);
  
    $this->createField('field_file_type', 'File Type', 'list_string',  [
      'allowed_values' => [
        'application/pdf' => 'pdf',
        'application/docx' => 'docx',
        'application/txt' => 'txt'
      ],
    ]);

    $this->createField('field_attachment', 'Attachment', 'entity_reference', ['target_type' => 'file'], ['handler' => 'default:file']);
    
    
    // $this->createField('field_topics', 'Topics', 'entity_reference', ['target_type' => 'taxonomy_term'], [
    //   'handler' => 'default:taxonomy_term',
    //   'handler_settings' => [
    //     'target_bundles' => ['topics'],
    //   ],
    // ], -1);

    // Create the field storage for the taxonomy reference field.
    FieldStorageConfig::create([
      'field_name' => 'field_topics',
      'entity_type' => 'node',
      'type' => 'entity_reference',
    'cardinality' => -1, // -1 means unlimited
      'settings' => [
        'target_type' => 'taxonomy_term',
      ],
    ])->save();

    FieldConfig::create([
      'field_name' => 'field_topics',
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => 'Topics',
      'settings' => [
        'handler' => 'default:taxonomy_term',
        'handler_settings' => [
          'target_bundles' => ['topics'],
        ],
      ],
    ])->save();// Create the field storage for the taxonomy reference field.


    // Create a user with necessary permissions.
    $this->createUser();

    // initialize the batch processor
    $mdex = new MetadataExtractor(\Drupal::service('logger.channel.default'));
    $this->batchProcessor = new MetadataBatchProcessor(\Drupal::service('logger.channel.default'), $mdex);
  }

  /**
   * Initialize the metadata tables
   */
  public function initMetadataHex(){
    $database = \Drupal::database();
    $schema = $database->schema();
    $this->enableModules(['metadata_hex']);
    sleep(1);

    \Drupal::service('kernel')->rebuildContainer();
    \Drupal::service('cache.bootstrap')->deleteAll();
    \Drupal::service('cache.config')->deleteAll();
    $this->hasMetadataProcessedTable();
    $this->config = \Drupal::configFactory()->getEditable('metadata_hex.settings');

    // Default settings
    $settings = [
        'extraction_settings.hook_node_types' => ['article', 'page'],
        'extraction_settings.field_mappings' => "keywords|field_topics\ntitle|title\nsubject|field_subject\nCreationDate|field_publication_date\nPages|field_pages\nDC:Format|field_file_type",
        'extraction_settings.flatten_keys' => FALSE,
        'extraction_settings.strict_handling' => FALSE,
        'extraction_settings.data_protected' => FALSE,
        'extraction_settings.title_protected' => TRUE,
        'node_process.bundle_types' => ['article'],
        'node_process.allow_reprocess' => TRUE,
        'file_ingest.bundle_type_for_generation' => 'article',
        'file_ingest.file_attachment_field' => 'field_attachment',
        'file_ingest.ingest_directory' => 'pdfs/',
    ];

    // Dynamically set
    foreach ($settings as $field => $value) {
      $this->setConfigSetting($field, $value);
    }

    // Save the configuration
    $this->config->save();
    $this->settingsManager = new \Drupal\metadata_hex\Service\SettingsManager();

    $this->assertEquals(false, $this->settingsManager->getStrictHandling(), 'Strict handling should be false');
    $this->assertEquals(FALSE, $this->settingsManager->getFlattenKeys(), 'flatten keys should be true');
    $this->assertEquals(false, $this->settingsManager->getProtectedData(), 'data protection should be false');
    $this->assertEquals(true, $this->settingsManager->getProtectedTitle(), 'title protection should be true');
  }

  /**
   * Teardown
   *
   * Custom override to allow sqlite to bypass cleanup (and those pesky executedDdlStatement errors)
   * @return void
   */
  protected function tearDown(): void {
    if (\Drupal::database()->driver() === 'sqlite') {
        return;
    }

    // Let the default cleanup run for MySQL/PostgreSQL.
    parent::tearDown();
  }

  /**
   * Checks to see if the required table exists
   */
  public function hasMetadataProcessedTable() {

    $table_exists = \Drupal::database()->schema()->tableExists('metadata_hex_processed');
    if ($table_exists) {
      // Use raw query to list field names.
      $results = \Drupal::database()->query("PRAGMA table_info(metadata_hex_processed)")->fetchAll();
      foreach ($results as $result) {
      }
    } else {
    }

    //regrab to test
    $table_exists_now = \Drupal::database()->schema()->tableExists('metadata_hex_processed');
    $this->assertEquals(true, $table_exists_now, 'Database table exists');

    return;
  }

  /**
   * Disable content type revisions
   *
   * @var string $content_type_id
   * @return void
   */
  protected function disableRevisionsForContentType($content_type_id = 'article') {
    $content_type = \Drupal::entityTypeManager()
      ->getStorage('node_type')
      ->load($content_type_id);

    if ($content_type) {
      $content_type->setNewRevision(FALSE);
      $content_type->set('preview_mode', 0);
      $content_type->save();
    }
  }

  /**
   * Clean up any odd config schema
   *
   * @var string $content_type
   * @return void
   */
  protected function cleanContentTypeConfig(string $content_type) {
    $config = \Drupal::configFactory()->getEditable("node.type.$content_type");

    if ($config->get('third_party_settings.node.default_revision') !== NULL) {
      $config->clear('third_party_settings.node.default_revision')->save();
    }
  }

/**
 * Creates a drupal field for testing
 * 
 * @var string $field_name
 * @var string $label
 * @var string $type
 * @var array $fsc_settings
 * @var array $fc_settings
 * @var string $bundle
 * 
 * @return void
 */
  protected function createField($field_name, $label, $type, $fsc_settings=[], $fc_settings = [], $bundle = 'article'){
       FieldStorageConfig::create([
        'field_name' => $field_name,
        'entity_type' => 'node',
        'type' => $type,
        'settings' => $fsc_settings
      ])->save();
  
      FieldConfig::create([
        'field_name' => $field_name,
        'entity_type' => 'node',
        'bundle' => $bundle,
        'label' => $label,
        'settings' => $fc_settings,
      ])->save();
  }
}

