# PdfExtractor Class

## Description
This class is responsible for extracting data (metadata) from a PDF file using a PdfParser service. It provides mechanisms for loading, parsing, and extracting metadata in compliance with Drupal’s standards.

## Namespace
namespace Drupal\pdf_meta_extractor\Service;

---

## Dependencies
- `Psr\Log\LoggerInterface`
- `Drupal\Core\Config\ImmutableConfig`
- `Drupal\pdf_meta_extractor\Service\PdfParser`

---

## Attributes

| Attribute Name   | Type                   | Visibility  | Default  | Required | Description                                                      |
|-------------------|------------------------|-------------|----------|----------|------------------------------------------------------------------|
| `$parser`        | `PdfParser`           | `protected` |          | Yes      | The service responsible for parsing PDF content.                |
| `$logger`        | `LoggerInterface`     | `protected` |          | Yes      | Logger service for tracking errors and operations.              |
| `$config`        | `ImmutableConfig`     | `private`   |          | Yes      | Stores configuration settings loaded from the Drupal admin form. |
| `$allowOverwrite`| `bool`                | `protected` | `false`  | No       | Determines if existing metadata can be overwritten.             |

---

## Methods

| Method Name       | Parameters                                      | Returns   | Visibility | Dependencies              | Throws       | Description                                                        |
|--------------------|------------------------------------------------|-----------|------------|---------------------------|--------------|--------------------------------------------------------------------|
| `__construct`     | `PdfParser $parser, LoggerInterface $logger, ImmutableConfig $config` | `void`    | `public`   | `LoggerService`, `Config` |              | Initializes the extractor with required services and configuration. |
| `init`            | `None`                                         | `void`    | `public`   |                           |              | Initializes the extractor based on configuration settings.          |
| `extractMetadata` | `string $fileUri`                              | `array`   | `public`   |                           |              | Loads a file, parses it, and returns extracted metadata as an array. |
| `filterMetadata`  | `array $metadata`                              | `array`   | `protected`|                           |              | Filters out empty or invalid metadata entries.                     |

---

## Example Implementation

### Constructor

```php
public function __construct(PdfParser $parser, LoggerInterface $logger, ImmutableConfig $config) {
    $this->parser = $parser;
    $this->logger = $logger;
    $this->config = $config;
    $this->allowOverwrite = $config->get('allow_overwrite') ?? false;
}
```

### `init` Method

```php
public function init() {
    // Additional initialization logic if needed
    $this->logger->info('PdfExtractor initialized with allowOverwrite: ' . ($this->allowOverwrite ? 'true' : 'false'));
}
```

### `extractMetadata` Method

```php
public function extractMetadata(string $fileUri): array {
    try {
        // Ensure the file exists
        if (!file_exists($fileUri)) {
            throw new \InvalidArgumentException("File not found at URI: $fileUri");
        }

        // Parse the file using the PdfParser
        $metadata = $this->parser->parseFile($fileUri);

        // Perform additional processing or filtering if needed
        if (!$this->allowOverwrite) {
            $metadata = $this->filterMetadata($metadata);
        }

        return $metadata;
    } catch (\Exception $e) {
        $this->logger->error('Failed to extract metadata: ' . $e->getMessage());
        return [];
    }
}
```

### `filterMetadata` Method

```php
protected function filterMetadata(array $metadata): array {
    // Example filtering logic
    return array_filter($metadata, function ($value) {
        return !empty($value); // Remove empty metadata entries
    });
}
```

---

## Relationships
- **Implements**: None
- **Injects**: `LoggerInterface`, `PdfParser`, `ImmutableConfig`
- **Depends on Services**: `logger.factory`, `config.factory`

---

## Security Considerations
- Validate the file URI to ensure no unauthorized access.
- Ensure metadata is sanitized to prevent injection or corruption.
- Respect the `$allowOverwrite` flag to avoid overwriting protected data.

---

## Performance Considerations
- Avoid re-parsing the same file multiple times; cache metadata when possible.
- Ensure file I/O operations are optimized, especially for large PDF files.
