<?php
namespace Drupal\metadata_hex\Handler;

use Drupal\Component\Plugin\PluginInspectionInterface;

interface FileHandlerInterface extends PluginInspectionInterface {
  
  
  /**
   * Processes the file.
   *
   * @param string $file_path
   *   The path to the file.
   *
   * @return mixed
   *   The result of processing.
   */  
  public function process($file_path):mixed;
}

