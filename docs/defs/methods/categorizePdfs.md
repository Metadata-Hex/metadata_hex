### `categorizePdfs()`
**Description**: Categorizes PDF files into "processed" and "unprocessed."

#### Steps:
1. Initialize two categories: `processed` and `unprocessed`.
2. Iterate through `$this->pdfFiles`:
   - For each file:
     - Check if the file is marked as processed in the system.
       - If yes: Add to the `processed` array.
       - If no: Add to the `unprocessed` array.
3. Return an array:
   - Example output:
     ```json
     {
       "processed": ["file1.pdf", "file2.pdf"],
       "unprocessed": ["file3.pdf", "file4.pdf"]
     }
     ```- `$fileUri` (`string`): The URI of the file to parse.

## Returns
- `array`: An associative array of extracted metadata.

## Exceptions
- `InvalidArgumentException`: If the file does not exist.

## Example
```php
$metadata = $extractor->extractMetadata('public://sample.pdf');
