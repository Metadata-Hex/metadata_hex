<?php
namespace Drupal\Tests\metadata_hex\Unit\Mocks;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Config;

/**
 * Provides a reusable mock for ConfigFactoryInterface.
 */
class MockConfigFactory {

    protected ConfigFactoryInterface $configFactoryMock;
    protected Config $configMock;

    /**
     * Constructor for MockConfigFactory.
     *
     * @param ConfigFactoryInterface $configFactoryMock
     *   The mocked ConfigFactoryInterface.
     * @param Config $configMock
     *   The mocked Config object.
     */
    public function __construct(ConfigFactoryInterface $configFactoryMock, Config $configMock) {
        $this->configFactoryMock = $configFactoryMock;
        $this->configMock = $configMock;
    }

    /**
     * Returns the mocked ConfigFactoryInterface.
     *
     * @return ConfigFactoryInterface
     *   The mocked ConfigFactoryInterface.
     */
    public function getMock(): ConfigFactoryInterface {
        return $this->configFactoryMock;
    }

    /**
     * Returns the mocked Config object.
     *
     * @return Config
     *   The mocked Config object.
     */
    public function getConfigMock(): Config {
        return $this->configMock;
    }
}