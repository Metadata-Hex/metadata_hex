<?php 
use Drupal\Tests\metadata_hex\Unit\Mocks\MockSettingsForm;
use Drupal\Core\Form\FormState;
use PHPUnit\Framework\TestCase;

class SettingsFormTest extends TestCase {
    
    protected $form;

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

        // Create the mocked SettingsForm with default settings
        $this->form = MockSettingsForm::create($this, $defaultSettings);
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

        // Check if values are correctly saved
        $config = $this->form->config('metadata_hex.settings');
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