<?php
namespace Drupal\Tests\metadata_hex\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\Tests\metadata_hex\Kernel\Traits\TestFileHelperTrait;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\metadata_hex\Service\MetadataBatchProcessor;
use Drupal\metadata_hex\Service\MetadataExtractor;

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
  ];

  /**
   * The MetadataBatchProcessor service.
   *
   * @var \Drupal\metadata_hex\Service\MetadataBatchProcessor
   */
  protected $batchProcessor;

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
          echo "Created term: $tag_name\n";
        }
        else {
          echo "Term '$tag_name' already exists.\n";
        }
      }
    }
    else {
      echo "Topics vocabulary not found.\n";
    }

    $this->installConfig(['metadata_hex']);
    $this->installSchema('metadata_hex', ['metadata_hex_processed']);
    $this->initMetadataHex();

    // Create the "article" content type.
    NodeType::create([
        'type' => 'article',
        'name' => 'Article',
    ])->save();

    // Create **STRING** field_subject (text field)
    FieldStorageConfig::create([
        'field_name' => 'field_subject',
        'entity_type' => 'node',
        'type' => 'string',
    ])->save();

    FieldConfig::create([
        'field_name' => 'field_subject',
        'entity_type' => 'node',
        'bundle' => 'article',
        'label' => 'Subject',
    ])->save();

    // Create INTEGER field_catalog_number (integer field)
    FieldStorageConfig::create([
      'field_name' => 'field_catalog_number',
      'entity_type' => 'node',
      'type' => 'integer',
    ])->save();

    FieldConfig::create([
      'field_name' => 'field_catalog_number',
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => 'Catalog Number',
    ])->save();

    // Create DATETIME field_publication_date (datetime field)
    FieldStorageConfig::create([
      'field_name' => 'field_publication_date',
      'entity_type' => 'node',
      'type' => 'datetime',
      'settings' => [
        'datetime_type' => 'datetime',  // Defines that this field stores both date and time.
      ],
    ])->save();

    FieldConfig::create([
      'field_name' => 'field_publication_date',
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => 'Publication Date',
    ])->save();

    // Create the field storage for the taxonomy reference field.
    FieldStorageConfig::create([
      'field_name' => 'field_topics',
      'entity_type' => 'node',
      'type' => 'entity_reference',
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

    // Create LIST_STRING field_publication_status (list_string field)
    FieldStorageConfig::create([
      'field_name' => 'field_publication_status',
      'entity_type' => 'node',
      'type' => 'list_string',
      'settings' => [
        'allowed_values' => [
          'draft' => 'Draft',
          'in_review' => 'In Review',
          'published' => 'Published',
        ],
      ],
    ])->save();

    FieldConfig::create([
      'field_name' => 'field_publication_status',
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => 'Publication Status',
    ])->save();

    // Create field_attachment (entity reference to file)
    FieldStorageConfig::create([
        'field_name' => 'field_attachment',
        'entity_type' => 'node',
        'type' => 'entity_reference',
        'settings' => ['target_type' => 'file'],
    ])->save();

    FieldConfig::create([
        'field_name' => 'field_attachment',
        'entity_type' => 'node',
        'bundle' => 'article',
        'label' => 'Attachment',
        'settings' => ['handler' => 'default:file'],
    ])->save();

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
        'extraction_settings.field_mappings' => "title|label\nsubject|field_subject\nCreateDate|field_publication_date\nCatalog|field_catalog_number\nStatus|field_publication_status",
        'extraction_settings.flatten_keys' => TRUE,
        'extraction_settings.strict_handling' => FALSE,
        'extraction_settings.data_protected' => FALSE,
        'extraction_settings.title_protected' => TRUE,
        'node_process.bundle_types' => ['article'],
        'node_process.allow_reprocess' => TRUE,
        'file_ingest.bundle_type_for_generation' => 'article',
        'file_ingest.file_attachment_field' => 'field_file',
        'file_ingest.ingest_directory' => 'pdfs/',
    ];

    // Dynamically set
    foreach ($settings as $field => $value) {
      $this->setConfigSetting($field, $value);
    }

    // Save the configuration
    $this->config->save();
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
        echo "Field: {$result->name}\n";
      }
    } else {
      echo "Table metadata_hex_processed does not exist.\n";
    }
  
    //rebgrab to test
    $table_exists_now = \Drupal::database()->schema()->tableExists('metadata_hex_processed');
    $this->assertEquals(true, $table_exists_now, 'Database table exists');
    
    return;
  }
}
