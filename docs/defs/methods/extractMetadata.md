### `extractMetadata($fileUri)`
**Description**: Extracts metadata from a PDF file.

#### Steps:
1. Validate that `$fileUri` is not empty.
   - If `$fileUri` is empty or invalid:
     - Log an error.
     - Throw an `InvalidArgumentException`.
2. Use the `PdfParser` to open the file.
   - Pass the `$fileUri` to the parser.
   - Retrieve raw metadata from the PDF.
3. Filter out invalid metadata.
   - Check for empty fields or invalid characters.
   - Sanitize fields like "title" or "author".
4. Return the cleaned metadata.
   - Example output:
     ```json
     {
       "title": "Sample Document",
       "author": "John Doe",
       "created_date": "2025-01-01",
       "keywords": ["sample", "document", "pdf"]
     }

## Parameters
- `$fileUri` (`string`): The URI of the file to parse.

## Returns
- `array`: An associative array of extracted metadata.

## Exceptions
- `InvalidArgumentException`: If the file does not exist.

## Example
```php
$metadata = $extractor->extractMetadata('public://sample.pdf');
