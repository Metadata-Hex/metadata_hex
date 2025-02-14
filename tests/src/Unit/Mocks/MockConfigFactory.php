<?php
namespace Drupal\Tests\metadata_hex\Unit\Mocks;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Config;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Provides a reusable mock for ConfigFactoryInterface.
 */
class MockConfigFactory {

    /**
     * Creates a mock ConfigFactoryInterface with predefined settings.
     *
     * @param TestCase $test
     *   The PHPUnit test case.
     * @param array $mockSettings
     *   An associative array of settings to return.
     *
     * @return ConfigFactoryInterface|MockObject
     *   A mocked ConfigFactoryInterface.
     */
    public static function create(TestCase $test, array $mockSettings = []): ConfigFactoryInterface {
        // Mock the Config object
        $configMock = $test->createMock(Config::class);
        
        // Set up expected return values for each config key
        $configMock->method('get')->willReturnCallback(function ($key) use ($mockSettings) {
            return $mockSettings[$key] ?? null;
        });

        // Mock the ConfigFactoryInterface
        $configFactoryMock = $test->createMock(ConfigFactoryInterface::class);
        $configFactoryMock->method('get')->with('metadata_hex.settings')->willReturn($configMock);

        return $configFactoryMock;
    }
}