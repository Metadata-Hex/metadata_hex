<?php 
namespace Drupal\Tests\metadata_hex\Unit\Mocks;

use Drupal\metadata_hex\Form\SettingsForm;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Config;
use Drupal\Core\Form\FormStateInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Provides a reusable mock for the MetadataHex SettingsForm.
 */
class MockSettingsForm {

    /**
     * Creates a mock instance of SettingsForm with predefined settings.
     *
     * @param TestCase $test
     *   The PHPUnit test case.
     * @param array $mockSettings
     *   An associative array of default settings to return.
     *
     * @return SettingsForm|MockObject
     *   A mocked SettingsForm instance.
     */
    public static function create(TestCase $test, array $mockSettings = []): SettingsForm {
        // Mock the Config object
        $configMock = $test->createMock(Config::class);
        $configMock->method('get')->willReturnCallback(function ($key) use ($mockSettings) {
            return $mockSettings[$key] ?? null;
        });

        // Mock the ConfigFactoryInterface
        $configFactoryMock = $test->createMock(ConfigFactoryInterface::class);
        $configFactoryMock->method('get')->with('metadata_hex.settings')->willReturn($configMock);

        // Return a partially mocked form instance with the mocked config factory
        return new class($configFactoryMock) extends SettingsForm {
            protected $configFactory;

            public function __construct(ConfigFactoryInterface $configFactory) {
                $this->configFactory = $configFactory;
            }
        };
    }
}