<?php
namespace Drupal\Tests\metadata_hex\Unit\Mocks;

use Drupal\metadata_hex\Form\SettingsForm;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a reusable instance of SettingsForm with mocked services.
 */
class MockSettingsForm extends SettingsForm {

    /**
     * Constructor for MockSettingsForm.
     *
     * @param ConfigFactoryInterface $configFactory
     *   The mocked config factory service.
     * @param TypedConfigManagerInterface $typedConfigManager
     *   The mocked typed config manager service.
     */
    public function __construct(
        ConfigFactoryInterface $configFactory,
        TypedConfigManagerInterface $typedConfigManager
    ) {
        parent::__construct($configFactory, $typedConfigManager);
    }

    /**
     * Static create() method that matches ConfigFormBase::create().
     *
     * @param ContainerInterface $container
     *   The service container.
     *
     * @return static
     *   A new instance of MockSettingsForm.
     */
    public static function create(ContainerInterface $container): MockSettingsForm {
        return new static(
            $container->get('config.factory'),
            $container->get('config.typed')
        );
    }
}