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

    \Drupal::service('kernel')->rebuildContainer();
\Drupal::service('cache.bootstrap')->deleteAll();
\Drupal::service('cache.config')->deleteAll();
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

    $this->installConfig(['metadata_hex']);
    $this->initMetadataHex();

    // Create the "article" content type.
    NodeType::create([
        'type' => 'article',
        'name' => 'Article',
    ])->save();

    // Create field_subject (text field)
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
   * 
   */
  public function initMetadataHex(){

    
  $database = \Drupal::database();
  $schema = $database->schema();

  // Drop table using raw SQL if it exists.
  if ($schema->tableExists('metadata_hex_processed')) {
    $database->query('DROP TABLE IF EXISTS metadata_hex_processed');
  }

    $this->installSchema('metadata_hex', ['metadata_hex_processed']);
    $this->hasMetadataProcessedTable();
    
    $this->config = \Drupal::configFactory()->getEditable('metadata_hex.settings');

    // $table_exists = \Drupal::database()->schema()->tableExists('metadata_hex_processed');
    // if ($table_exists) {
    //   $results = \Drupal::database()->query("PRAGMA table_info(metadata_hex_processed)")->fetchAll();
    //   foreach ($results as $result) {
    //     echo "Field: {$result->name}\n";
    //   }
   
    // } else {
    //   echo "Table metadata_hex_processed does not exist.\n";
    // }

    
    // Default settings
    $settings = [
        'extraction_settings.hook_node_types' => ['article', 'page'],
        'extraction_settings.field_mappings' => "title|field_title\nsubject|field_subject",
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
   * 
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

   // $this->initMetadataHex();
    // $table_exists = \Drupal::database()->schema()->tableExists('metadata_hex_processed');
    $this->assertEquals(true, $table_exists, 'Database table exists');
    if ($table_exists) {
      // Define expected fields.
      $expected_fields = [
        'entity_type',
        'entity_id',
        'last_modified',
        'processed',
      ];
      
  // In your test class setup method
// \Drupal::service('module_installer')->uninstall(['your_module_name']);
// \Drupal::service('module_installer')->install(['your_module_name']);
      foreach ($expected_fields as $field) {
        try {
        $field_exists = \Drupal::database()->schema()->fieldExists('metadata_hex_processed', $field);
        } catch (\Exception $e){
        echo $e->getMessage();
        }
        $this->assertEquals(true, $field_exists, "Field '$field' doesnt exist in the table.");
      }
  
    }
  

  }
}
