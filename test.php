
$node = \Drupal\node\Entity\Node::load('1');
$mdex = new Drupal\metadata_hex\Service\MetadataExtractor(\Drupal::service('logger.channel.default'));
$mdbp = new Drupal\metadata_hex\Service\MetadataBatchProcessor(\Drupal::service('logger.channel.default'), $mdex);
$mdbp->processNode($node->id());
