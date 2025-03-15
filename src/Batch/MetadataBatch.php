<?php 
namespace Drupal\metadata_hex\Batch;

use Drupal\metadata_hex\Service\FileHandlerManager;
use Drupal\metadata_hex\Service\MetadataBatchProcessor;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Handles metadata processing in batch operations.
 */
class MetadataBatch {
    
    protected static $batchProcessor;
    protected static $fileHandlerManager;
    protected static $logger;

    /**
     * Initializes the required services.
     */
    protected static function initializeServices() {
        if (!isset(self::$batchProcessor)) {
            self::$batchProcessor = \Drupal::service('metadata_hex.metadata_batch_processor');
        }
        if (!isset(self::$fileHandlerManager)) {
            self::$fileHandlerManager = \Drupal::service('metadata_hex.file_handler_manager');
        }
        if (!isset(self::$logger)) {
            self::$logger = \Drupal::logger('logger_channel.default');
        }
    }

    /**
     * Batch operation for processing files.
     */
    // public static function processFiles(&$context) {
    //     self::initializeServices();
    //     self::$batchProcessor->processFiles();
    //     $context['message'] = t('Processing files...');
    // }

    /**
     * Batch operation for processing nodes.
     */
    // public static function processNodes($bundleType, $willReprocess, &$context) {
    //     self::initializeServices();
    //     self::$logger->notice("🔍 Batch started for {$bundleType}");

    //     // Ensure dependencies are available
    //     self::requireClasses();

    //     // Initialize and process nodes
    //     self::$batchProcessor->init($bundleType, TRUE, $willReprocess);
    //     self::$batchProcessor->processNodes();

    //     $context['message'] = t('Processing @type...', ['@type' => $bundleType]);
    // }

  /**
   * Callback function to print batch success messages.
   *
   * @param bool $success
   *   Whether the batch process was successful.
   * @param array $results
   *   Processed results.
   * @param array $failed_operations
   *   Failed operations.
   */
    public static function batchFinished($success, $results, $failed_operations) {
       
       \Drupal::state()->set('metadata_hex.batch_results', $results);
       \Drupal::state()->set('metadata_hex.batch_failed', $failed_operations);
        if ($success) {
            \Drupal::messenger()->addStatus(t('Metadata processing completed successfully.'));
        } else {
            \Drupal::messenger()->addError(t('Metadata processing encountered some errors.'));
        }
    }

    /**
     * Ensure necessary classes are loaded.
     */
    protected static function requireClasses() {
        $required_classes = [
            'Drupal\metadata_hex\Model\MetadataEntity' => '/modules/custom/metadata_hex/src/Model/MetadataEntity.php',
            'Drupal\metadata_hex\Handler\FileHandler' => '/modules/custom/metadata_hex/src/Handler/FileHandler.php',
            'Drupal\metadata_hex\Handler\FileHandlerInterface' => '/modules/custom/metadata_hex/src/Handler/FileHandlerInterface.php',
            'Drupal\metadata_hex\Handler\MdFileHandler' => '/modules/custom/metadata_hex/src/Handler/MdFileHandler.php',
            'Drupal\metadata_hex\Handler\PdfFileHandler' => '/modules/custom/metadata_hex/src/Handler/PdfFileHandler.php',
        ];

        foreach ($required_classes as $class => $file) {
            if (!class_exists($class)) {
                require_once DRUPAL_ROOT . $file;
            }
        }
    }
}