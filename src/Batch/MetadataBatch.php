<?php 
namespace Drupal\metadata_hex\Batch;

/**
 * Handles metadata processing in batch operations.
 */
class MetadataBatch {
    
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