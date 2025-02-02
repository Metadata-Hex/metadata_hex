<?php

namespace Drupal\pdf_meta_extraction\Batch;

use Drupal\node\Entity\Node;

class NodeProcessor
{

  /**
   * Processes a single node.
   *
   * @param int $nid Node ID to process.
   * @param  $context Batch context for logging and tracking progress.
   */
  public static function processNode($nid, &$context)
  {

    \Drupal::logger('pdf_meta_extraction')->info(__FUNCTION__ . ":" . __LINE__);

    $node = Node::load($nid);
    if ($node) {

      
      // Create an instance of the ProcessPdf class
      $pdfProcessor = new \Drupal\pdf_meta_extraction\ProcessPdf();

      // Call the method on the instance
      $pdfProcessor->processPdfNodeData($nid);

      $context['message'] = t('Processing node @nid', ['@nid' => $nid]);
      $context['results'][] = $nid;
    } else {
      $context['message'] = t('Failed to load node @nid', ['@nid' => $nid]);
    }
  }

  /**
   * Callback function to be called when the batch operation finishes.
   *
   * @param bool $success A boolean indicating whether the operation was successful.
   * @param array $results Contains all operations results.
   * @param array $operations If $success is FALSE, contains the operations that remained unprocessed.
   */
  public static function batchFinished($success, $results, $operations)
  {
    if ($success) {
      $message = t('All nodes have been processed. Nodes processed: @count', ['@count' => count($results)]);
    } else {
      $message = t('Finished with an error.');
    }
    \Drupal::messenger()->addMessage($message);
  }
}
