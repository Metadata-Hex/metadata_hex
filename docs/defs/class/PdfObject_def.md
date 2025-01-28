# PdfObject Class

## Description
This class encapsulates all functionality related to PDF objects, their corresponding nodes, and file-related operations. It handles metadata processing, file management, and interactions with Drupal nodes.

## Namespace
namespace Drupal\pdf_meta_extractor\Service;

---

## Dependencies
- `Psr\Log\LoggerInterface`
- `Smalot\PdfParser\Parser`
- `Drupal\node\Entity\Node`
- `Drupal\file\Entity\File`

---

## Attributes

| Attribute Name        | Type                          | Visibility | Default   | Required | Description                                           |
|------------------------|-------------------------------|------------|-----------|----------|-------------------------------------------------------|
| `$nid`                | `int`                        | `protected` | `null`    | No       | The node ID associated with this PDF object.         |
| `$file`               | `Drupal\file\Entity\File`    | `protected` | `null`    | Yes      | The Drupal file entity representing the PDF file.    |
| `$title`              | `string`                     | `protected` | `FileName` | Yes      | The title of the PDF (defaults to filename if no title provided). |
| `$metadataProcessed`  | `array`                      | `protected` | `[]`      | No       | Metadata that has been cleaned, processed, and matched to fields. |
| `$metadataRaw`        | `array`                      | `protected` | `[]`      | Yes      | Raw metadata extracted directly from the PDF.        |
| `$logger`             | `LoggerInterface`            | `protected` |           | Yes      | The logger service for recording events and errors.  |
| `$parser`             | `Smalot\PdfParser\Parser`    | `protected` |           | Yes      | The PDF parser for extracting raw metadata.          |
| `$dataProtected`      | `bool`                       | `protected` | `true`    | No       | A flag to prevent overwriting existing content.       |
| `$titleProtected`     | `bool`                       | `protected` | `true`    | No       | A flag to prevent overwriting the title.             |

---

## Methods

| Method                | Parameters                      | Returns   | Visibility | Dependencies      | Throws                   | Description                                                                 |
|------------------------|----------------------------------|-----------|------------|-------------------|--------------------------|-----------------------------------------------------------------------------|
| `__construct`         | `LoggerInterface $logger, Parser $parser` | `void`    | `public`    | `LoggerService`, `Parser` |                          | Initializes the object with required services.                             |
| `init`                | `File $file | Node $node`       | `void`    | `protected` |                   |                          | Initializes the object from a file or a node.                              |
| `getNode`             | `None`                         | `Node`    | `public`    |                   |                          | Returns the Drupal node associated with the PDF.                           |
| `getTitle`            | `None`                         | `string`  | `public`    |                   |                          | Returns the title from the node or file.                                   |
| `getIsNodeProcessed`  | `None`                         | `bool`    | `public`    |                   |                          | Returns the processed state of the node.                                   |
| `getPdfUri`           | `None`                         | `string`  | `public`    |                   |                          | Returns the file URI of the PDF (e.g., public://...).                       |
| `setRevision`         | `None`                         | `void`    | `public`    |                   |                          | Sets a new revision for the associated node.                               |
| `loadNode`            | `int $nid`                     | `void`    | `public`    |                   | `EntityStorageException` | Loads the PDF object from a node.                                          |
| `loadFile`            | `string $uri`                  | `void`    | `public`    |                   | `FileException`         | Loads the PDF object from a file URI.                                      |
| `createOrUpdateNode`  | `array $metadata`              | `Node`    | `public`    |                   |                          | Creates or updates the associated node, marking it processed.              |

---

## Example Implementation

### Constructor

```php
public function __construct(LoggerInterface $logger, Parser $parser) {
    $this->logger = $logger;
    $this->parser = $parser;
}
```

### `init` Method

```php
protected function init($input) {
    if ($input instanceof File) {
        $this->loadFile($input->getFileUri());
    } elseif ($input instanceof Node) {
        $this->loadNode($input->id());
    } else {
        throw new \InvalidArgumentException("Invalid input provided.");
    }
}
```

### `loadNode` Method

```php
public function loadNode(int $nid) {
    $node = Node::load($nid);
    if (!$node) {
        throw new \InvalidArgumentException("Node with ID $nid not found.");
    }
    $this->nid = $nid;
    $this->title = $node->getTitle();
}
```

### `loadFile` Method

```php
public function loadFile(string $uri) {
    $file = File::loadByUri($uri);
    if (!$file) {
        throw new \InvalidArgumentException("File with URI $uri not found.");
    }
    $this->file = $file;
    $this->title = $file->getFilename();
}
```

---

## Relationships
- **Implements**: None
- **Injects**: `LoggerInterface`, `Smalot\PdfParser\Parser`
- **Depends on Services**: `logger.factory`, `Smalot\PdfParser\Parser`

---

## Security Considerations
- Ensure the file URI or node ID is validated to prevent unauthorized access.
- Prevent overwriting metadata if `$dataProtected` is set to `true`.

---

## Performance Considerations
- Avoid reloading the same node or file multiple times; cache results if needed.
- Ensure metadata extraction processes are optimized for large files.
