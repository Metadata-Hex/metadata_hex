<?php

namespace Drupal\Tests\pdf_meta_extraction\Unit\Service;

use Drupal\pdf_meta_extraction\Service\PDFMetadataExtractor;
use PHPUnit\Framework\TestCase;
use Smalot\PdfParser\Parser;
use Psr\Log\LoggerInterface;
use Prophecy\PhpUnit\ProphecyTrait;

class PDFMetadataExtractorTest extends TestCase {
    use ProphecyTrait;

    protected $parser;
    protected $logger;
    protected $pdfMetadataExtractor;

    protected function setUp(): void {
        $this->parser = $this->prophesize(Parser::class);
        $this->logger = $this->prophesize(LoggerInterface::class);

        $this->pdfMetadataExtractor = new PDFMetadataExtractor();
        $this->pdfMetadataExtractor->parser = $this->parser->reveal();
        $this->pdfMetadataExtractor->logger = $this->logger->reveal();
    }

    public function testGetMetadataSuccess() {
        $filePath = 'path/to/pdf';
        $pdf = $this->prophesize(\Smalot\PdfParser\Document::class);
        $details = ['Title' => 'Test PDF'];

        $this->parser->parseFile($filePath)->willReturn($pdf->reveal());
        $pdf->getDetails()->willReturn($details);

        $result = $this->pdfMetadataExtractor->getMetadata($filePath);

        $this->assertSame($details, $result);
    }

    public function testGetMetadataFileNotAccessible() {
        $filePath = 'path/to/pdf';

        $this->parser->parseFile($filePath)->willThrow(new \Exception('File not accessible'));

        $this->logger->error('Error parsing PDF file: @message', ['@message' => 'File not accessible'])->shouldBeCalled();

        $result = $this->pdfMetadataExtractor->getMetadata($filePath);

        $this->assertNull($result);
    }

    public function testGetBodySuccess() {
        $filePath = 'path/to/pdf';
        $pdf = $this->prophesize(\Smalot\PdfParser\Document::class);
        $text = 'This is a test PDF text';

        $this->parser->parseFile($filePath)->willReturn($pdf->reveal());
        $pdf->getText()->willReturn($text);

        $result = $this->pdfMetadataExtractor->getBody($filePath);

        $this->assertSame($text, $result);
    }

    public function testGetBodyFileNotAccessible() {
        $filePath = 'path/to/pdf';

        $this->parser->parseFile($filePath)->willThrow(new \Exception('File not accessible'));

        $this->logger->error('Body not readable : @filePath', ['@filePath' => $filePath])->shouldBeCalled();

        $result = $this->pdfMetadataExtractor->getBody($filePath);

        $this->assertNull($result);
    }
}
