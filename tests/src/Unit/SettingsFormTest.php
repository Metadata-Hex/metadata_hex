<?php

require_once __DIR__ . '/Mocks/NodeTypeStub.php';

use Drupal\node\Entity\NodeTypeStub as NodeType;
use Drupal\Tests\metadata_hex\Unit\Mocks\MockSettingsForm;
use Drupal\metadata_hex\Service\MetadataBatchProcessor;
use Drupal\metadata_hex\Service\MetadataExtractor;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;

class SettingsFormTest extends TestCase
{

  protected $form;
  protected $configFactoryMock;
  protected $configMock;
  protected $typedConfigManagerMock;
  protected $stringTranslationMock;
  protected $messengerMock;
  protected $entityTypeManagerMock;
  protected $entityTypeRepositoryMock;
  protected $entityStorageMock;
  protected $batchProcessorMock;
  protected $metadataExtractorMock;

  protected function setUp(): void
  {
    $defaultSettings = [
      'extraction_settings.hook_node_types' => ['article', 'page'],
      'extraction_settings.field_mappings' => "title|field_title\nauthor|field_author",
      'extraction_settings.flatten_keys' => TRUE,
      'extraction_settings.strict_handling' => FALSE,
      'extraction_settings.data_protected' => TRUE,
      'extraction_settings.title_protected' => TRUE,
      'extraction_settings.available_extensions' => "pdf\npdfx",
      'node_process.bundle_types' => ['article'],
      'node_process.allow_reprocess' => TRUE,
      'file_ingest.bundle_type_for_generation' => 'article',
      'file_ingest.file_attachment_field' => 'field_file',
      'file_ingest.ingest_directory' => 'pdfs/',
    ];

    // Store updated values in a reference array
    $updatedConfig = $defaultSettings;

    // Mock Config to store and return updated values
    $this->configMock = $this->createMock(Config::class);
    $this->configMock->method('get')->willReturnCallback(function ($key) use (&$updatedConfig) {
      return $updatedConfig[$key] ?? null;
    });

    $this->configMock->method('set')->willReturnCallback(function ($key, $value) use (&$updatedConfig) {
      $updatedConfig[$key] = $value;
      return $this->configMock;
    });

    $this->configMock->method('save')->willReturnCallback(function () {
      return true; // Simulates a successful save
    });

    // Mock ConfigFactoryInterface
    $this->configFactoryMock = $this->createMock(ConfigFactoryInterface::class);
    $this->configFactoryMock->method('getEditable')->with('metadata_hex.settings')->willReturn($this->configMock);

    // Mock TypedConfigManagerInterface
    $this->typedConfigManagerMock = $this->createMock(TypedConfigManagerInterface::class);

    // Mock StringTranslationInterface
    $this->stringTranslationMock = $this->createMock(TranslationInterface::class);
    $this->stringTranslationMock->method('translate')->willReturnCallback(fn($string) => $string);

    // Mock MessengerInterface
    $this->messengerMock = $this->createMock(MessengerInterface::class);

    // Properly mock NodeType instances
    $this->articleMock = $this->createMock(\Drupal\Core\Config\Entity\ConfigEntityInterface::class);
    $this->articleMock->method('id')->willReturn('article');
    $this->articleMock->method('label')->willReturn('Article');

    $this->pageMock = $this->createMock(\Drupal\Core\Config\Entity\ConfigEntityInterface::class);
    $this->pageMock->method('id')->willReturn('page');
    $this->pageMock->method('label')->willReturn('Page');

    // Mock NodeType Storage
    $this->entityStorageMock = $this->createMock(EntityStorageInterface::class);
    $this->entityStorageMock->method('loadMultiple')->willReturn([
      'article' => $this->articleMock,
      'page' => $this->pageMock,
    ]);

    $this->entityStorageMock->method('load')->willReturnCallback(function ($bundleType) {
      return $bundleType === 'article' ? $this->articleMock : ($bundleType === 'page' ? $this->pageMock : null);
    });

    // Mock EntityTypeManagerInterface
    $this->entityTypeManagerMock = $this->createMock(EntityTypeManagerInterface::class);
    $this->entityTypeManagerMock->method('getStorage')->willReturnCallback(function ($entityType) {
      if ($entityType === 'node_type') {
        return $this->entityStorageMock;
      }
      throw new \InvalidArgumentException("Unexpected entity type: {$entityType}");
    });

    // Mock EntityTypeRepositoryInterface
    $this->entityTypeRepositoryMock = $this->createMock(EntityTypeRepositoryInterface::class);

    // Mock MetadataBatchProcessor & MetadataExtractor
    $this->batchProcessorMock = $this->createMock(MetadataBatchProcessor::class);
    $this->metadataExtractorMock = $this->createMock(MetadataExtractor::class);

    // Register mocks in service container
    $containerMock = new ContainerBuilder();
    $containerMock->set('config.factory', $this->configFactoryMock);
    $containerMock->set('config.typed', $this->typedConfigManagerMock);
    $containerMock->set('string_translation', $this->stringTranslationMock);
    $containerMock->set('messenger', $this->messengerMock);
    $containerMock->set('entity_type.manager', $this->entityTypeManagerMock);
    $containerMock->set('entity_type.repository', $this->entityTypeRepositoryMock);
    $containerMock->set('metadata_hex.metadata_batch_processor', $this->batchProcessorMock);
    $containerMock->set('metadata_hex.metadata_extractor', $this->metadataExtractorMock);
    \Drupal::setContainer($containerMock);

    // Pass mocks into MockSettingsForm
    $this->form = new MockSettingsForm(
      $this->configFactoryMock,
      $this->typedConfigManagerMock,
      $this->batchProcessorMock,
      $this->metadataExtractorMock,
      $this->messengerMock
    );
  }

  /** @test */
  public function testBuildFormContainsExpectedFields()
  {
    $form = [];
    $formState = new FormStateInterface();
    $builtForm = $this->form->buildForm($form, $formState);

    // Ensure form contains expected fields
    $this->assertArrayHasKey('extraction_settings', $builtForm);
    $this->assertArrayHasKey('node_process', $builtForm);
    $this->assertArrayHasKey('file_ingest', $builtForm);
    $this->assertArrayHasKey('settings', $builtForm);
  }

  /** @test */
  public function testSubmitFormUpdatesConfig()
  {
    $form = [];
    $formState = new FormStateInterface();
    $formState->setValues([
      'hook_node_types' => ['article', 'page'],
      'field_mappings' => "Title|title\nSubject|field_subject",
      'bundle_types' => ['article'],
      'file_attachment_field' => 'field_file_entity',
      'ingest_directory' => 'pdfs/uploads/',
    ]);

    $this->form->submitForm($form, $formState);
    $config = $this->configFactoryMock->getEditable('metadata_hex.settings');

    $this->assertEquals(['article', 'page'], $config->get('extraction_settings.hook_node_types'));
    $this->assertEquals("Title|title\nSubject|field_subject", $config->get('extraction_settings.field_mappings'));
    $this->assertEquals(['article'], $config->get('node_process.bundle_types'));
    $this->assertEquals('field_file_entity', $config->get('file_ingest.file_attachment_field'));
    $this->assertEquals('pdfs/uploads/', $config->get('file_ingest.ingest_directory'));
  }
}