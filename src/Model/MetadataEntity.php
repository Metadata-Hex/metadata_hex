<?php

namespace Drupal\metadata_hex\Model;

use Drupal\node\Entity\Node;
use Drupal\file\Entity\File;
use Psr\Log\LoggerInterface;
use Exception;
use \Drupal\metadata_hex\Base\MetadataHexCore;
use \Drupal\metadata_hex\Model\NodeBinder;
use Drupal\metadata_hex\Utility\MetadataParser;

/**
 * Class MetadataEntity
 *
 * Encapsulates the node, metadata, and file connection.
 */
class MetadataEntity extends MetadataHexCore
{

  /**
   * Flag to prevent overwriting existing data.
   *
   * @var bool
   */
  protected $dataProtected = true;

  /**
   * Metadata that has been cleaned, processed, and matched to fields.
   *
   * @var array
   */
  protected $metadataMapped = [];
  /**
   * Metadata that has been cleaned, processed, and matched to fields.
   *
   * @var array
   */
  protected $metadataProcessed = [];

  /**
   * Raw metadata extracted directly from the file.
   *
   * @var array
   */
  protected $metadataRaw = [];

  /**
   * The NodeBinder associated with this MetadataEntity object.
   *
   * @var NodeBinder
   */
  protected $nodeBinder;

  /**
   * Summary of metadataParser
   * @var MetadataParser
   */
  protected $metadataParser = null;
  /**
   * Flag to prevent overwriting the title.
   *
   * @var bool
   */
  protected $titleProtected = true;

  /**
   * Constructs the MetadataEntity class.
   *
   * @param LoggerInterface $logger
   *   The logger service.
   */
  public function __construct(LoggerInterface $logger)
  {
    parent::__construct($logger);
  }

  /**
   * Initializes the MetadataEntity with a file or node input.
   *
   * @param mixed $input
   *   The input entity (either File or Node).
   *
   * @throws \InvalidArgumentException
   *   If the input is invalid.
   */
  protected function init($input)
  {
    if ($input instanceof File) {
      $this->loadFromFile($input->getFileUri());
    } elseif ($input instanceof Node) {
      $this->loadFromNode($input->id());
    } else {
      throw new \InvalidArgumentException("Invalid input provided.");
    }

    $this->metadataParser = new MetadataParser($this->logger, $this->getNodeBinder()->getBundleType());

  }
  /**
   * public initialize
   * @param mixed $input
   * @return void
   */
  public function initialize($input)
  {
    $this->init($input);
  }
  /**
   * Summary of getParser
   * @return MetadataParser
   */
  public function getParser(): MetadataParser
  {
    if ($this->metadataParser === null) {
      $this->metadataParser = new MetadataParser($this->logger, $this->getNodeBinder()->getBundleType());

    }
    return $this->metadataParser;
  }
  /**
   * Matches a passed-in string to available bundle taxonomy.
   *
   * @param string $term_to_find
   *   The term to find.
   *
   * @return array
   *   The matching taxonomy IDs.
   *
   * @throws Exception
   *   If the input is not a string.
   */
  protected function findMatchingTaxonomy(string $term_to_find): array
  {
    if (!is_string($term_to_find)) {
      throw new Exception("Invalid input. Expected a string.");
    }

    $term_to_find = strtolower($term_to_find);
    $matching_terms = [];

    $vocabulary = 'taxonomy_vocabulary'; // Replace with actual vocabulary.
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vocabulary);

    foreach ($terms as $term) {
      if (strtolower($term->name) === $term_to_find) {
        $matching_terms[] = $term->tid;
      }
    }

    return $matching_terms;
  }

  /**
   * Loads and initializes a MetadataEntity via a file URI.
   *
   * @param string $file_uri
   *   The file URI.
   *
   * @throws Exception
   *   If the file is invalid.
   */
  public function loadFromFile(string $file_uri)
  {
    $file = File::load(\Drupal::entityQuery('file')
      ->condition('uri', $file_uri)
      ->execute());

    if (!$file) {
      throw new Exception("File not found: $file_uri");
    }


    $this->setLocalMetadata($this->getNodeBinder($file)->ingestNodeFileMeta());

    // cleans up and parses the metadata and sets
    $mtdt = $this->getParser()->cleanMetadata($this->metadataRaw);
    $this->mapMetadata($mtdt);
    $this->setLocalMetadata($this->metadataMapped, false);
    //$this->metadataParser = new MetadataParser($this->logger, $this->getNodeBinder()->getBundleType());

  }

  public function getNodeBinder($nodefile = null)
  {

    if ($this->nodeBinder === null) {
      $this->nodeBinder = new NodeBinder($this->logger);
    }

    if ($nodefile === null) {
      return $this->nodeBinder;
    }

    $this->nodeBinder->init($nodefile);
    return $this->nodeBinder;
  }
  /**
   * Loads and initializes a MetadataEntity via a node ID.
   *
   * @param string $nid
   *   The node ID.
   *
   * @throws Exception
   *   If the node is invalid.
   */
  public function loadFromNode(string $nid)
  {
    $node = Node::load($nid);

    if (!$node) {
      throw new Exception("Node not found: $nid");
    }

    // Ingests the metadata and sets
    $tmd = $this->getNodeBinder($node)->ingestNodeFileMeta();
    $this->setLocalMetadata($tmd);

    // cleans up and parses the metadata and sets
    $mtdt = $this->getParser()->cleanMetadata($this->metadataRaw);
    $this->mapMetadata($mtdt);
    $this->setLocalMetadata($this->metadataMapped, false);
  }

  /**
   * Sets the local metadata array. 
   *
   * @param array $metadata
   * @param mixed $raw
   * @return void
   */
  public function setLocalMetadata(array $metadata, $raw = true)
  {
    if ($raw) {
      $this->metadataRaw = array_merge($this->metadataRaw, $metadata);
    } else {
      $this->metadataProcessed = array_merge($this->metadataProcessed, $metadata);
    }
  }

  /**
   * Writes the processed metadata to a node.
   */
  public function writeMetadata()
  {
    if (empty($this->metadataProcessed)) {
      return;
    }

    $node = $this->getNodeBinder()->getNode();
    if (!$node) {
      throw new Exception("No valid node found for metadata writing.");
    }

    foreach ($this->metadataProcessed as $field_name => $value) {

      // These should already be filtered out by the parser.
      if (!$node->hasField($field_name)) {
        continue;
      }

      $field_definition = $node->getFieldDefinition($field_name);
      $field_type = $field_definition->getType();

      switch ($field_type) {
        case 'string':
        case 'string_long':
        case 'text':
        case 'text_long':
        case 'text_with_summary':
          $node->set($field_name, $value);
          break;

        case 'entity_reference':
          $target_type = $field_definition->getSetting('target_type');
          if ($target_type === 'taxonomy_term') {
            $term_ids = [];
            foreach (explode(',', $value) as $term_name) {
              $term_name = trim($term_name);
              $matching_terms = $this->findMatchingTaxonomy($term_name);
              if (empty($matching_terms)) {
                $term = \Drupal\taxonomy\Entity\Term::create([
                  'vid' => $field_definition->getSetting('target_bundle'),
                  'name' => $term_name,
                ]);
                $term->save();
                $term_ids[] = $term->id();
              } else {
                $term_ids = array_merge($term_ids, $matching_terms);
              }
            }
            $node->set($field_name, $term_ids);
          } else {
            $node->set($field_name, ['target_id' => $value]);
          }
          break;

        case 'boolean':
        case 'integer':
          $node->set($field_name, (int) $value);
          break;

        case 'datetime':
          $node->set($field_name, date('Y-m-d\TH:i:s', strtotime($value)));
          break;

        case 'list_string':
          $allowed_values = $field_definition->getSetting('allowed_values');
          if (in_array($value, $allowed_values, true)) {
            $node->set($field_name, $value);
          } else {
            $this->logger->error("Invalid value '{$value}' for field {$field_name}.");
          }
          break;

        default:
          $this->logger->error("Unhandled field type: {$field_type} for field {$field_name}.");
          break;
      }
    }

    $this->getNodeBinder()->setRevision();
    $node->save();
    $this->getNodeBinder()->setProcessed();
  }

  /**
   * Summary of mapMetadata
   * @param array $metadata
   * @return void
   */
  public function mapMetadata(array $metadata)
  {

    $field_mappings = $this->metadataParser->getFieldMappings();
    foreach ($field_mappings as $drupal_field => $pdf_field) {

      if (isset($metadata[$pdf_field]) && !empty($metadata[$pdf_field])) {
        $this->metadataMapped[$drupal_field] = $metadata[$pdf_field];
      }
    }
  }
}