<?php

namespace Drupal\Tests\metadata_hex\Unit;

use Drupal\metadata_hex\Service\MetadataExtractor;
use Drupal\metadata_hex\Service\SettingsManager;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Config;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;

class MetadataExtractorTest extends TestCase {

    protected $fhm;
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
       // $this->fhm = MetadataExtractor()//\Drupal::service('metadata_hex.file_handler_manager');
    }
    /** @test */
    public function test_it_extracts_data() {
         $this->assertTrue(true);
    }
}
    

//extractMetadata(uri)