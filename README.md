# **Metadata Hex**

**Metadata Hex (Heap EXport) is a Drupal module that facilitates metadata extraction, processing, and association with Drupal entities, particularly nodes and files. It supports structured metadata ingestion from PDFs and other file formats, mapping extracted values to Drupal fields dynamically. 
---

## **Features**
✅ **Automated Metadata Extraction**  
- Extracts metadata from PDFs using `Smalot\PdfParser`.
- Extracts metadata from markdown using `Symphony\Yaml `
- Supports additional file types via plugin-based handlers (future enhancement).  

✅ **Node & File Processing**  
- Processes newly inserted nodes automatically via `hook_node_insert()`.  
- Ingests and categorizes files dynamically.  

✅ **Configurable Mappings**  
- Maps extracted metadata fields to Drupal entity fields.  
- Allows for **strict handling** and **data protection** settings.  

✅ **Batch Processing**  
- Supports bulk metadata extraction and node updates.  
- Processes entire directories of files and attach them to nodes.  

✅ **Admin Configuration**  
- Fully configurable via the Drupal admin panel.  
- Settings available at: `/admin/config/metadata_hex`.  

---

## **Configuration**
### **Admin Settings**
- Navigate to **Configuration → Metadata Hex Settings** (`/admin/config/metadata_hex`).
- Customize:
  - **Extraction Settings**: Node types, field mappings, strict handling.
  - **Node Processing**: Bundle types, reprocessing options.
  - **File Ingest Settings**: Directory paths, field attachments.

---

## **Usage**
### **Automatic Node Processing**
- When a node of a configured type is created, its metadata is extracted automatically:
  - **`hook_node_insert()`** checks the node bundle.
  - If eligible, it **sends the node to the batch processor**.

### ** File Ingest Processing**
Run batch processing manually using:
- process entire folders using the file batch ingest

## **Core Components**
### ** Services**
| Service Key | Class | Purpose |
|------------|-------|---------|
| `metadata_hex.file_handler_manager` | `FileHandlerManager` | Manages file handlers for different extensions |
| `metadata_hex.metadata_extractor` | `MetadataExtractor` | Extracts metadata from PDF files |
| `metadata_hex.settings_manager` | `SettingsManager` | Retrieves and manages module settings |
| `metadata_hex.metadata_batch_processor` | `MetadataBatchProcessor` | Handles batch metadata processing |

### ** Entity Classes**
| Class | Purpose |
|------------|---------|
| `NodeBinder` | Associates files and metadata with Drupal nodes |
| `MetadataEntity` | Encapsulates metadata processing for a given entity |

### ** Utilities**
| Service Key | Class | Purpose |
| `metadata_hex.metadata_parser` | `MetadataParser` | Cleans, validates, and structures metadata |


### ** Hooks**
| Hook | Description |
|------|------------|
| `hook_node_insert()` | Checks if a node should be processed and sends it to the `MetadataBatchProcessor` |

---

## **Extending the Module**
### **Adding a Custom File Handler**
1. Implement a new handler extending `FileHandler`.
2. Register it in `FileHandlerManager`.
3. Ensure it supports `getSupportedExtentions()`.

Example:
```php
class CsvFileHandler extends FileHandler {
  protected function extractMetadata(): array {
    return ['title' => 'CSV Example'];
  }

  protected function getSupportedExtentions(): array {
    return ['csv'];
  }
} 
```
## **Development & Debugging**
### **Logging**
This module logs to the `default` channel:
`\Drupal::logger('default')->info('Processing started.');`

Check logs via:
`drush ws --severity=notice`

### **Clear Cache**
After updates:
`drush cr`

---

## **Contributing**
🛠 PRs and issues welcome!  
Ensure compliance with **Drupal best practices** before submitting.

---

## **License**
📝 **MIT License**  
Free to use and modify.

---

## **Author**
🚀 Created by **David Belich**  
📧 Contact: **developer@davidbelich.com**
