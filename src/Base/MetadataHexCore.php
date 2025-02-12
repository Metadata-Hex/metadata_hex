<?php

namespace Drupal\metadata_hex\Base;

use Drupal\Core\Config\ConfigFactoryInterface;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Class MetadataHexCore
 * 
 * Initializes several services available for all code flows.
 */
abstract class MetadataHexCore {
  
  /**
   * Logger Service
   *
   * @var LoggerInterface
   */
  protected $logger;

  /**
   * Constructs the MetadataHexCore class.
   *
   * @param LoggerInterface $logger
   *   The logger service.
   */
  public function __construct(LoggerInterface $logger) {
    $this->logger = $logger;
  }

  /**
   * Executes a function with automatic error handling.
   *
   * @param callable $callback
   *   The callback function to execute.
   * @param mixed $default
   *   Default value to return if an exception occurs.
   * @param string|null $context
   *   Optional context message for error logging.
   *
   * @return mixed
   *   The result of the callback or the default value in case of an error.
   */
  protected function guard(callable $callback, $default = null, ?string $context = null) {
    try {
      return $callback();
    } catch (Exception $e) {
      $message = $context ? "{$context}: {$e->getMessage()}" : $e->getMessage();
      $this->logger->error($message);
      return $default;
    }
  }
}