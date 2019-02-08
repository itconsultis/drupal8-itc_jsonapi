<?php

namespace Drupal\itc_jsonapi\Normalizer;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Url;
use Drupal\search_api\Item\ItemInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Class SearchApiItemNodeNormalizer.
 *
 * @package Drupal\itc_jsonapi\Normalizer
 */
class SearchApiItemNodeNormalizer implements NormalizerInterface {

  /**
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * SearchApiItemNodeNormalizer constructor.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(LanguageManagerInterface $language_manager, EntityTypeManagerInterface $entity_type_manager) {
    $this->languageManager = $language_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    /** @var \Drupal\Core\Language\LanguageInterface $current_language */
    $current_language = $context['language'];
    /** @var \Drupal\search_api\Item\ItemInterface $item */
    $item = $object;
    /** @var \Drupal\search_api\Item\Item $item */
    $nid = current($item->getField('nid')->getValues());
    /** @var \Drupal\node\NodeInterface $node */
    $node = $context['node'] ?? NULL;
    if (empty($node)) {
      $node_storage = $this->entityTypeManager->getStorage('node');
      $node = $node_storage->load($nid);
    }
    $type = $node->bundle();
    $uuid = $node->uuid();
    $url = Url::fromRoute("entity.node.canonical", [
      'node' => $node->id(),
    ], [
      'language' => $current_language,
    ])->toString(TRUE)->getGeneratedUrl();
    $path = [
      'alias' => $url,
      'langcode' => $current_language->getId(),
    ];
    if ($node->hasTranslation($current_language->getId())) {
      $t_node = $node->getTranslation($current_language->getId());
    }
    else {
      $t_node = $node;
    }
    $data = [
      'type' => 'search_api--result_item',
      'id' => $uuid,
      'attributes' => [
        'nid' => $nid,
        'uuid' => $uuid,
        'type' => $type,
        'path' => $path,
        'title' => $t_node->getTitle(),
        'language' => $t_node->language()->getId(),
      ],
      'meta' => [
        'score' => $item->getScore(),
      ],
    ];
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsNormalization($data, $format = NULL) {
    if ($format !== 'api_json') {
      return FALSE;
    }
    return $data instanceof ItemInterface;
  }

}
