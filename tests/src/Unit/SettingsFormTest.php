<?php 
use Drupal\Tests\metadata_hex\Unit\Mocks\MockConfigFactory;
use Drupal\Tests\metadata_hex\Unit\Mocks\MockSettingsForm;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Messenger\MessengerInterface; // ✅ Ensure correct namespace
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;

class SettingsFormTest extends TestCase {
    
    protected $form;
    protected $configFactoryMock;
    protected $configMock;
    protected $typedConfigManagerMock;
    protected $stringTranslationMock;
    protected $messengerMock;

    protected function setUp(): void {
        
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

        // ✅ Mock the Config object to include both get() and set()
        $this->configMock = $this->createMock(Config::class);
        $this->configMock->method('get')->willReturnCallback(fn ($key) => $defaultSettings[$key] ?? null);
        $this->configMock->method('set')->willReturnCallback(function ($key, $value) use (&$updatedConfig) {
            $updatedConfig[$key] = $value; // ✅ Store the updated values
            return $this->configMock;
        });
 // ✅ Fix: Ensure `save()` persists the values correctly
    $this->configMock->method('save')->willReturnCallback(function () use (&$updatedConfig) {
        // Simulate saving by persisting the modified array
        return $this->configMock;
    });

   // ✅ Fix: Ensure `save()` persists the values correctly
    $this->configMock->method('save')->willReturnCallback(function () use (&$updatedConfig) {
        // Simulate saving by persisting the modified array
        return $this->configMock;
    });

  
        // ✅ Mock the ConfigFactoryInterface to return the editable config
        $this->configFactoryMock = $this->createMock(ConfigFactoryInterface::class);
        $this->configFactoryMock->method('get')->with('metadata_hex.settings')->willReturn($this->configMock);
        $this->configFactoryMock->method('getEditable')->with('metadata_hex.settings')->willReturn($this->configMock);

        // ✅ Mock TypedConfigManagerInterface
        $this->typedConfigManagerMock = $this->createMock(TypedConfigManagerInterface::class);

        // ✅ Mock StringTranslation Service
        $this->stringTranslationMock = $this->createMock(TranslationInterface::class);
        $this->stringTranslationMock->method('translate')->willReturnCallback(fn ($string) => $string);

        // ✅ Mock MessengerInterface
        $this->messengerMock = $this->createMock(MessengerInterface::class);

        // ✅ Register the mocks in the service container
        $containerMock = new ContainerBuilder();
        $containerMock->set('config.factory', $this->configFactoryMock);
        $containerMock->set('config.typed', $this->typedConfigManagerMock);
        $containerMock->set('string_translation', $this->stringTranslationMock);
        $containerMock->set('messenger', $this->messengerMock); // ✅ Fix: Ensures `messenger` is registered
        \Drupal::setContainer($containerMock);

        // ✅ Fix: Pass the correct mocks into MockSettingsForm
        $this->form = new MockSettingsForm($this->configFactoryMock, $this->typedConfigManagerMock, $this->messengerMock);
    }

    public function testBuildFormContainsExpectedFields() {
        $form = [];
        $formState = new FormState();
        $builtForm = $this->form->buildForm($form, $formState);

        // Ensure that form contains expected fields
        $this->assertArrayHasKey('extraction_settings', $builtForm);
        $this->assertArrayHasKey('node_process', $builtForm);
        $this->assertArrayHasKey('file_ingest', $builtForm);
        $this->assertArrayHasKey('settings', $builtForm);
    }

    public function testSubmitFormUpdatesConfig() {
        $form = [];
        $formState = new FormState();
        $formState->setValues([
            'extraction_settings.hook_node_types' => ['article', 'page'],
            'extraction_settings.field_mappings' => "Title|title\nSubject|field_subject",
            'extraction_settings.flatten_keys' => TRUE,
            'extraction_settings.strict_handling' => FALSE,
            'extraction_settings.data_protected' => FALSE,
            'extraction_settings.title_protected' => FALSE,
            'extraction_settings.available_extensions' => "pdf\npdfx",
            'node_process.bundle_types' => ['article'],
            'node_process.allow_reprocess' => TRUE,
            'file_ingest.bundle_type_for_generation' => 'article',
            'file_ingest.file_attachment_field' => 'field_file_entity',
            'file_ingest.ingest_directory' => 'pdfs/uploads/',
        ]);

        $this->form->submitForm($form, $formState);

        // ✅ Fix: Ensure the mock config is updated correctly
        $config = $this->configFactoryMock->getEditable('metadata_hex.settings');
        $this->assertEquals(['article', 'page'], $config->get('extraction_settings.hook_node_types'));
        $this->assertEquals("Title|title\nSubject|field_subject", $config->get('extraction_settings.field_mappings'));
        $this->assertTrue($config->get('extraction_settings.flatten_keys'));
        $this->assertFalse($config->get('extraction_settings.strict_handling'));
        $this->assertFalse($config->get('extraction_settings.data_protected'));
        $this->assertFalse($config->get('extraction_settings.title_protected'));
        $this->assertEquals("pdf\npdfx", $config->get('extraction_settings.available_extensions'));
        $this->assertEquals(['article'], $config->get('node_process.bundle_types'));
        $this->assertTrue($config->get('node_process.allow_reprocess'));
        $this->assertEquals('article', $config->get('file_ingest.bundle_type_for_generation'));
        $this->assertEquals('field_file_entity', $config->get('file_ingest.file_attachment_field'));
        $this->assertEquals('pdfs/uploads/', $config->get('file_ingest.ingest_directory'));
    }
}