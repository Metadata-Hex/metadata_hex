# PdfProcess Class

## Description
This class handles high-level PDF processing operations, such as categorizing, scanning, and processing PDF files and nodes. It coordinates interactions between other components like the PDF extractor and handles batch operations during cron runs.

## Namespace
namespace Drupal\pdf_meta_extractor\Service;

---

## Dependencies
- `Psr\Log\LoggerInterface`
- `Drupal\pdf_meta_extractor\Service\PdfExtractor`
- `Drupal\Core\Entity\EntityTypeManagerInterface`

---

## Attributes

| Attribute Name         | Type                            | Visibility | Default  | Required | Description                                             |
|-------------------------|---------------------------------|------------|----------|----------|---------------------------------------------------------|
| `$logger`              | `LoggerInterface`              | `protected`|          | Yes      | Logger service for tracking errors and operations.     |
| `$bundleTypeForCreate` | `string`                       | `protected`|          | Yes      | The bundle type used for saving nodes during cron operations. |
| `$cron`                | `bool`                         | `protected`| `false`  | No       | Indicates whether the class is running during a cron operation. |
| `$reprocess`           | `bool`                         | `protected`| `false`  | No       | Indicates whether existing nodes of the given type should be reprocessed. |
| `$pdfFiles`            | `array`                        | `protected`| `[]`     | No       | List of PDF file URIs to process.                      |
| `$extractor`           | `PdfExtractor`                 | `protected`|          | Yes      | Handles metadata extraction from PDF files.            |

---

## Methods

| Method Name       | Parameters                                           | Returns   | Visibility | Dependencies          | Throws | Description                                                            |
|--------------------|------------------------------------------------------|-----------|------------|-----------------------|--------|------------------------------------------------------------------------|
| `__construct`     | `LoggerInterface $logger, PdfExtractor $extractor`   | `void`    | `public`   | `LoggerService`, `PdfExtractor` |      | Initializes the process handler with required services.               |
| `init`            | `string $bundleType, bool $cron = false, bool $reprocess = false` | `void`    | `public`   |                       |        | Initializes the processor with configuration options.                 |
| `categorizePdfs`  | `None`                                               | `array`   | `public`   |                       |        | Categorizes PDF files into specific categories.                       |
| `scanAllPdfs`     | `string $directory`                                  | `array`   | `public`   |                       |        | Scans a directory and retrieves all PDF file paths.                   |
| `processPdfFiles` | `None`                                               | `void`    | `public`   |                       |        | Processes the list of PDF files to create PdfObject instances.        |
| `processNode`     | `int $nid`                                           | `void`    | `public`   |                       |        | Processes a single node to generate and save a PDF object.            |
| `processNodes`    | `string $bundleType`                                 | `void`    | `public`   |                       |        | Processes all nodes of the given bundle type.                         |

---

## Example Implementation

### Constructor

```php
public function __construct(LoggerInterface $logger, PdfExtractor $extractor) {
    $this->logger = $logger;
    $this->extractor = $extractor;
    $this->pdfFiles = [];
}
```

### `init` Method

```php
public function init(string $bundleType, bool $cron = false, bool $reprocess = false) {
    $this->bundleTypeForCreate = $bundleType;
    $this->cron = $cron;
    $this->reprocess = $reprocess;
    $this->logger->info('PdfProcess initialized with bundle type: ' . $bundleType);
}
```

### `categorizePdfs` Method

```php
public function categorizePdfs(): array {
    $categories = [
        'processed' => [],
        'unprocessed' => []
    ];

    foreach ($this->pdfFiles as $file) {
        if ($this->isFileProcessed($file)) {
            $categories['processed'][] = $file;
        } else {
            $categories['unprocessed'][] = $file;
        }
    }

    return $categories;
}

protected function isFileProcessed(string $fileUri): bool {
    // Logic to determine if a file has already been processed
    return false; // Placeholder
}
```

### `scanAllPdfs` Method

```php
public function scanAllPdfs(string $directory): array {
    $pdfFiles = glob($directory . '/*.pdf');
    $this->pdfFiles = $pdfFiles ?: [];
    return $this->pdfFiles;
}
```

### `processPdfFiles` Method

```php
public function processPdfFiles() {
    foreach ($this->pdfFiles as $fileUri) {
        try {
            $metadata = $this->extractor->extractMetadata($fileUri);
            $this->createPdfNode($fileUri, $metadata);
        } catch (\Exception $e) {
            $this->logger->error('Error processing file: ' . $fileUri . '. ' . $e->getMessage());
        }
    }
}

protected function createPdfNode(string $fileUri, array $metadata) {
    // Logic to create a Drupal node with the given metadata
    $this->logger->info('Node created for file: ' . $fileUri);
}
```

### `processNode` Method

```php
public function processNode(int $nid) {
    $node = \Drupal\node\Entity\Node::load($nid);
    if (!$node) {
        $this->logger->error('Node with ID ' . $nid . ' not found.');
        return;
    }

    $fileUri = $node->get('field_pdf_file')->uri;
    $metadata = $this->extractor->extractMetadata($fileUri);
    $this->updateNodeMetadata($node, $metadata);
}

protected function updateNodeMetadata($node, array $metadata) {
    foreach ($metadata as $key => $value) {
        $node->set($key, $value);
    }
    $node->save();
    $this->logger->info('Node metadata updated for Node ID: ' . $node->id());
}
```

### `processNodes` Method

```php
public function processNodes(string $bundleType) {
    $query = \Drupal::entityTypeManager()->getStorage('node')->getQuery();
    $query->condition('type', $bundleType);
    $nids = $query->execute();

    foreach ($nids as $nid) {
        $this->processNode($nid);
    }
}
```

---

## Relationships
- **Implements**: None
- **Injects**: `LoggerInterface`, `PdfExtractor`
- **Depends on Services**: `logger.factory`, `entity_type.manager`

---

## Security Considerations
- Validate file paths and URIs to prevent unauthorized access or file injection.
- Ensure nodes are properly secured when updating metadata.

---

## Performance Considerations
- Batch process large directories to avoid memory exhaustion.
- Use caching for already processed files to reduce redundant operations.
- Limit cron operations to a subset of files to improve runtime efficiency.
