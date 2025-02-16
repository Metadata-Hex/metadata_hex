<?php

namespace Drupal\Tests\metadata_hex\Unit;

use Drupal\metadata_hex\Service\FileHandlerManager;
use Drupal\metadata_hex\Service\SettingsManager;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Config;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\metadata_hex\Handler\PdfFileHandler;
use Drupal\metadata_hex\Handler\DocxFileHandler;

class FileHandlerManagerTest extends TestCase {

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

        $this->pluginManagerMock = $this->createMock(\Drupal\metadata_hex\Plugin\MetadataHexPluginManager::class);
        $this->fhm = new FileHandlerManager($this->pluginManagerMock, $this->settingsManagerMock);
    }

  /** @test */
  public function test_it_returns_a_handler_for_pdf_extension() {
      // Mock the handler object
      $mockHandler = $this->createMock(PdfFileHandler::class);

      // Assuming FileHandlerManager has a method getHandlerForExtension()
      $this->fhm = $this->createMock(FileHandlerManager::class);
      $this->fhm->method('getHandlerForExtension')->with('pdf')->willReturn($mockHandler);

      $result = $this->fhm->getHandlerForExtension('pdf');

      $this->assertInstanceOf(PdfFileHandler::class, $result);
  }

  /** @test */
  public function test_it_returns_a_handler_for_doc_extension() {
      // Mock the handler object
      $mockHandler = $this->createMock(DocxFileHandler::class);

      // Assuming FileHandlerManager has a method getHandlerForExtension()
      $this->fhm = $this->createMock(FileHandlerManager::class);
      $this->fhm->method('getHandlerForExtension')->with('docx')->willReturn($mockHandler);

      $result = $this->fhm->getHandlerForExtension('docx');

      $this->assertInstanceOf(DocxFileHandler::class, $result);
  }

  /** @test */
  public function test_it_returns_null_for_an_invalid_extension() {
      // Assuming FileHandlerManager has a method getHandlerForExtension()
      $this->fhm = $this->createMock(FileHandlerManager::class);
      $this->fhm->method('getHandlerForExtension')->with('invalidext')->willReturn(null);

      $result = $this->fhm->getHandlerForExtension('invalidext');

      $this->assertNull($result);
  } 
}
  /*
setup filehandlerManager
get available extentions, recieve an array
get handler for extention('pdf'), handler::typeOF = 
get handler for extention('doc'), handler::tyopOF = 
".    " for a bad ext, verify is null */