<?php

namespace Drupal\Tests\metadata_hex\Unit;

use Drupal\metadata_hex\Utility\MetadataParser;
use Drupal\metadata_hex\Service\SettingsManager;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Config;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;

class MetadataParserTest extends TestCase {

    protected $parser;
    protected $loggerMock;
    protected $entityFieldManagerMock;
    protected $settingsManagerMock;
    protected $configFactoryMock;
    protected $configMock;
    protected $entityTypeManagerMock;
    protected $nodeStorageMock;

    protected function setUp(): void {
        //Mock LoggerInterface
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        //Mock EntityFieldManagerInterface
        $this->entityFieldManagerMock = $this->createMock(EntityFieldManagerInterface::class);

        //Mock ConfigFactoryInterface & Config
        $this->configFactoryMock = $this->createMock(ConfigFactoryInterface::class);
        $this->configMock = $this->createMock(Config::class);
        $this->configMock->method('get')->willReturn([]); //Ensures config retrieval does not break

        $this->configFactoryMock->method('get')->willReturn($this->configMock);
        $this->configFactoryMock->method('getEditable')->willReturn($this->configMock);

        //Use a real instance of SettingsManager with a mocked config factory
        $this->settingsManagerMock = new SettingsManager($this->configFactoryMock);

        //Mock NodeType Objects (for bundle types)
        $articleMock = new class {
            public function id() { return 'article'; }
            public function label() { return 'Article'; }
        };

        $pageMock = new class {
            public function id() { return 'page'; }
            public function label() { return 'Page'; }
        };

        //Mock NodeType Storage
        $this->nodeStorageMock = $this->createMock(EntityStorageInterface::class);
        $this->nodeStorageMock->method('loadMultiple')->willReturn([
            'article' => $articleMock,
            'page' => $pageMock,
        ]);
        $this->nodeStorageMock->method('load')->willReturnCallback(function ($bundleType) use ($articleMock, $pageMock) {
            return $bundleType === 'article' ? $articleMock : ($bundleType === 'page' ? $pageMock : null);
        });

        //Mock EntityTypeManagerInterface
        $this->entityTypeManagerMock = $this->createMock(EntityTypeManagerInterface::class);
        $this->entityTypeManagerMock->method('getStorage')->willReturnCallback(function ($entityType) {
            if ($entityType === 'node_type') {
                return $this->nodeStorageMock; //Return correct storage
            }
            throw new \InvalidArgumentException("Unexpected entity type: {$entityType}");
        });

        //Mock the Service Container
        $containerMock = new ContainerBuilder();
        $containerMock->set('config.factory', $this->configFactoryMock);
        $containerMock->set('entity_field.manager', $this->entityFieldManagerMock);
        $containerMock->set('entity_type.manager', $this->entityTypeManagerMock);
        \Drupal::setContainer($containerMock);

        //Initialize MetadataParser with the real SettingsManager mock
        $this->parser = new MetadataParser($this->loggerMock, 'article', $this->settingsManagerMock);
    }

    /** @test */
    public function it_throws_an_error_when_given_a_non_string() {
        $this->expectException(\TypeError::class);
        $this->parser->explodeKeyValueString(['invalid array input']);
    }

    /** @test */
    public function it_throws_an_error_when_given_an_empty_string() {
        $this->expectException(\Exception::class);
        $this->parser->explodeKeyValueString('');
    }

    /** @test */
    public function it_parses_a_valid_piped_string_into_an_array() {
        $input = "title|field_title\nauthor|field_author";
        $expected = [
            'field_title' => 'title',
            'field_author' => 'author',
        ];

        $result = $this->parser->explodeKeyValueString($input);

        $this->assertIsArray($result);
        $this->assertEquals($expected, $result);
    }
    /** @test */
public function it_throws_a_type_error_when_null_is_passed_without_backup() {
    $this->expectException(\TypeError::class);
    
    $this->parser->cleanMetadata(null);
}

    /** @test */
    public function it_logs_a_message_when_an_empty_array_is_passed() {
        $result = $this->parser->cleanMetadata([]);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /** @test */
    public function it_returns_cleaned_metadata_when_structured_array_is_passed() {
        $input = ['key1' => 'value', 'key2' => 'value'];
        $expected = ['key1' => 'value', 'key2' => 'value'];

        $result = $this->parser->cleanMetadata($input);
        $this->assertEquals($expected, $result);
    }

    /** @test */
    public function it_cleans_utf8_encoded_strings() {
        $input = ['key1' => 'value', 'key2' => "valueé😀"];
        $expected = ['key1' => 'value', 'key2' => 'value'];

        $result = $this->parser->cleanMetadata($input);
        $this->assertEquals($expected, $result);
    }

    /** @test */
    public function it_flattens_nested_structures_into_a_cleaned_array() {
        $input = [['key1' => 'value', 'key2' => 'value'], ['key3' => 'value']];
        $expected = ['key1' => 'value', 'key2' => 'value', 'key3' => 'value'];

        $result = $this->parser->cleanMetadata($input);
        $this->assertEquals($expected, $result);
    }

    /** @test */
    public function it_removes_empty_keys_and_values() {
        $input = ['key1' => '', 'key2' => 'value', '' => 'value'];
        $expected = ['key2' => 'value'];

        $result = $this->parser->cleanMetadata($input);
        $this->assertEquals($expected, $result);
    }

    /** @test */
    public function it_normalizes_key_case_by_default() {
        $input = ['Key1' => 'value', 'kEy2' => 'value'];
        $expected = ['key1' => 'value', 'key2' => 'value'];

        $result = $this->parser->cleanMetadata($input);
        $this->assertEquals($expected, $result);
    }

    /** @test */
    public function it_retains_original_key_case_when_strict_handling_is_enabled() {
        $input = ['Key1' => 'value', 'keY2' => 'value'];
        $expected = ['Key1' => 'value', 'keY2' => 'value'];

        // Enable strict handling in the parser
        $this->parser->setStrictHandling(true);
        $result = $this->parser->cleanMetadata($input);
        $this->assertEquals($expected, $result);
    }
  /** @test */
    public function it_retains_unflattened_keys_when_flatten_keys_is_disabled() {
        $input = ['key1' => 'value', 'pdfx:key2' => 'value'];
        $expected = ['key1' => 'value', 'pdfx:key2' => 'value'];

        // Enable strict handling in the parser
        $this->parser->setFlattenKeys(false);
        $result = $this->parser->cleanMetadata($input);
        $this->assertEquals($expected, $result);
    }

  /** @test */
    public function it_flattens_keys_when_flatten_keys_is_enabled() {
        $input = ['key1' => 'value', 'pdfx:key2' => 'value'];
        $expected = ['key1' => 'value', 'key2' => 'value'];

        // Enable strict handling in the parser
        $this->parser->setFlattenKeys(true);
        $result = $this->parser->cleanMetadata($input);
        $this->assertEquals($expected, $result);
    }

/** @test */
public function it_throws_an_exception_when_null_is_passed() {
    $this->expectException(\TypeError::class);
    $this->parser->cleanFieldMapping(null);
}

/** @test */
public function it_returns_expected_fields_when_available_fields_are_set_and_input_matches() { 
    $this->parser->setAvailableFields(['title', 'author', 'date']);
    $input = ['title' => 'field_title', 'author' => 'field_author', 'date' => 'field_date'];
    $expected = ['title' => 'field_title', 'author' => 'field_author', 'date' => 'field_date'];

    $result = $this->parser->cleanFieldMapping($input);
    $this->assertEquals($expected, $result);
}

/** @test */
public function it_filters_out_fields_not_in_available_fields() { /*** */
    $this->parser->setAvailableFields(['title', 'author']);

    $input = ['title' => 'field_title', 'author' => 'field_author', 'extra_field' => 'field_extra'];
    $expected = ['title' => 'field_title', 'author' => 'field_author'];

    $result = $this->parser->cleanFieldMapping($input);
    $this->assertEquals($expected, $result);
}

/** @test */
public function it_returns_an_empty_array_when_no_fields_match_available_fields() {
    $this->parser->setAvailableFields(['title', 'author']);

    $input = ['extra_field' => 'field_extra', 'another_field' => 'field_another'];
    $expected = [];

    $result = $this->parser->cleanFieldMapping($input);
    $this->assertEquals($expected, $result);
}
   
}
  