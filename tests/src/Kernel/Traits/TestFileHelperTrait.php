<?php
namespace Drupal\Tests\metadata_hex\Kernel\Traits;

use Drupal\Core\File\FileSystemInterface;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use org\bovigo\vfs\vfsStream;
use TCPDF;

trait TestFileHelperTrait {

  public function setConfigSetting($field, $value){
    try {
      $this->config->set($field, $value)->save();
    } catch (\Exception $e){
      // print_r($e->getMessage(), true);
    }
  }

  /**
   * Generates a PDF with metadata using TCPDF.
   *
   * @return string The PDF content as a string.
   */
  public function generatePdfWithMetadata(): string {
      $pdf = new TCPDF();

      // Set standard metadata
      $pdf->SetCreator('Drupal Kernel Test');
      $pdf->SetAuthor('Automated Test Suite');
      $pdf->SetTitle('Test PDF Document');
      $pdf->SetSubject('Testing Metadata in PDFs');
      $pdf->SetKeywords('Drupal, TCPDF, Test, Metadata');

      $pdf->AddPage();
      $pdf->SetFont('helvetica', '', 12);
      $pdf->Cell(0, 10, 'This is a test PDF with metadata.', 0, 1, 'C');

      // Set XMP metadata for advanced metadata storage
      $xmp_metadata = '<?xpacket begin="..." id="W5M0MpCehiHzreSzNTczkc9d"?>
          <rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
              xmlns:pdf="http://ns.adobe.com/pdf/1.3/"
              xmlns:xmp="http://ns.adobe.com/xap/1.0/"
              xmlns:dc="http://purl.org/dc/elements/1.1/">
              <rdf:Description rdf:about=""
                  xmlns:xmp="http://ns.adobe.com/xap/1.0/"
                  xmp:CreatorTool="Drupal TCPDF Test"
                  xmp:CreateDate="' . date('c') . '"
                  xmp:MetadataDate="' . date('c') . '"
                  custom:Catalog="12345"
                  custom:Status="Published"
                  xmp:ModifyDate="' . date('c') . '">
              </rdf:Description>
          </rdf:RDF>
          <?xpacket end="w"?>';
      $pdf->setExtraXMP($xmp_metadata);

      // Output PDF as a string for saving in Drupal
      return $pdf->Output('', 'S'); // Return as string
  }

  /**
   * Helper function to create a file entity.
   *
   * @param string $uri
   *   The file URI.
   *
   * @return \Drupal\file\Entity\File
   *   The created file entity.
   */
  protected function createFile(string $uri) {
    $root = vfsStream::setup('root');

    $file = File::create([
      'uri' => $root->url() . $uri,
      'status' => 1,
    ]);
    $file->save();
    return $file;
  }

  /**
   * Helper function to create a node with an attached file.
   *
   * @param \Drupal\file\Entity\File|string $file
   *   The file entity.
   *
   * @return \Drupal\node\Entity\Node
   *   The created node entity.
   */
  protected function createNode(File|string $file = null) {
    if ($file !== null && is_string($file)){
      $file = $this->createFile($file);
    }
    $node = Node::create([
      'type' => 'article',
      'title' => 'Test Document Node',
      'field_subject' => '',
      'uid' => 1, // Assign to test user.
      'field_attachment' => [  // Adjust field name based on actual setup.
        'target_id' => $file?->id()??null,
      ],
      'revision' => FALSE,
    ]);
    $node->save();
    return $node;
  }

  /**
   * Helter function to create a user for node ownership.
   */
  protected function createUser() {
    $user = User::create([
      'name' => 'test_user',
    ]);
    $user->save();
    $this->container->get('current_user')->setAccount($user);
  }


  /**
   * Creates a File entity from the generated PDF.
   *
   * @param string $filename The name of the file to create.
   * @param string $pdf_content The PDF content as a string.
   * @param string $mime_type The file MIME type (default: 'application/pdf').
   *
   * @return \Drupal\file\Entity\File|null The created file entity.
   */
  public function createDrupalFile(string $filename, string $pdf_content, string $mime_type = 'application/pdf', bool $createNode = true): ?File {
    // Define Drupal's public file directory
    $directory = 'public://test-files';
    \Drupal::service('file_system')->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);

    // Define the full path
    $file_path = $directory . '/' . $filename;

    // Write content to the file
    file_put_contents(\Drupal::service('file_system')->realpath($file_path), $pdf_content);
    echo PHP_EOL.$file_path.PHP_EOL;

    if ($createNode){
    // Create a file entity
    $file = File::create([
        'uri' => $file_path,
        'filename' => $filename,
        'filemime' => $mime_type,
        'status' => 1,
    ]);
    $file->save();

    return $file;
    }
    return null;
    }

}
