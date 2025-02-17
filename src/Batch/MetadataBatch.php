<?php

namespace Drupal\metadata_hex\Batch;

use Drupal\metadata_hex\Model\MetadataEntity;

use Drupal\metadata_hex\Service\FileHandlerManager;
/**
 * Handles metadata processing in batch operations.
 */
class MetadataBatch {

    /**
     * Batch operation for processing nodes.
     */

     
     public static function processNodes($bundleType, $willReprocess, &$context) {
         \Drupal::logger('metadata_hex')->notice("🔍 Batch started for {$bundleType}");
     
         // ✅ Ensure MetadataEntity class exists
         if (!class_exists('Drupal\metadata_hex\Model\MetadataEntity')) {
             require_once DRUPAL_ROOT . '/modules/custom/metadata_hex/src/Model/MetadataEntity.php';
         }  // ✅ Ensure MetadataEntity class exists
         if (!class_exists('Drupal\metadata_hex\Handler\FileHandler')) {
             require_once DRUPAL_ROOT . '/modules/custom/metadata_hex/src/Handler/FileHandler.php';
         }
     if (!class_exists('Drupal\metadata_hex\Handler\FileHandlerInterface')) {
             require_once DRUPAL_ROOT . '/modules/custom/metadata_hex/src/Handler/FileHandlerInterface.php';
         }  if (!class_exists('Drupal\metadata_hex\Handler\DocxFileHandler')) {
             require_once DRUPAL_ROOT . '/modules/custom/metadata_hex/src/Handler/DocxFileHandler.php';
         }
     if (!class_exists('Drupal\metadata_hex\Handler\PdfFileHandler')) {
             require_once DRUPAL_ROOT . '/modules/custom/metadata_hex/src/Handler/PdfFileHandler.php';
         }
     
         // ✅ Ensure FileHandlerManager service is available
         $fileHandlerManager = \Drupal::service('metadata_hex.file_handler_manager');
         $batchProcessor = \Drupal::service('metadata_hex.metadata_batch_processor');
        
     
         // ✅ Initialize and process nodes
         $batchProcessor->init($bundleType, TRUE, $willReprocess);
         $batchProcessor->processNodes();
     
         $context['message'] = t('Processing @type...', ['@type' => $bundleType]);
     }

    /**
     * Batch completion callback.
     */
    public static function batchFinished($success, $results, $operations) {
        if ($success) {
            \Drupal::messenger()->addStatus(t('Metadata processing completed successfully.'));
        } else {
            \Drupal::messenger()->addError(t('Metadata processing encountered some errors.'));
        }
    }
}