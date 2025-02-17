<?php

namespace Drupal\Tests\metadata_hex\Unit\Mocks;

use Drupal\metadata_hex\Form\SettingsForm;
use Drupal\metadata_hex\Service\MetadataBatchProcessor;
use Drupal\metadata_hex\Service\MetadataExtractor;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
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
     * @param MetadataBatchProcessor $batchProcessor
     *   The mocked batch processor service.
     * @param MetadataExtractor $metadataExtractor
     *   The mocked metadata extractor service.
     * @param MessengerInterface $messenger
     *   The mocked messenger service.
     */
    public function __construct(
        ConfigFactoryInterface $configFactory,
        TypedConfigManagerInterface $typedConfigManager,
        MetadataBatchProcessor $batchProcessor,
        MetadataExtractor $metadataExtractor,
        MessengerInterface $messenger
    ) {
        parent::__construct($configFactory, $typedConfigManager, $batchProcessor, $metadataExtractor, $messenger);
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
            $container->get('config.typed'),
            $container->get('metadata_hex.metadata_batch_processor'),
            $container->get('metadata_hex.metadata_extractor'),
            $container->get('messenger')
        );
    }
}