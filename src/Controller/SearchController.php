<?php

namespace Drupal\itc_jsonapi\Controller;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\itc_jsonapi\SearchApi\QueryBuilder;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Query\Query;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Serializer;

/**
 * Class SearchController.
 */
class SearchController extends ControllerBase {


  /**
   * Service entity_type.manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Service language_manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * @var \Drupal\itc_jsonapi\SearchApi\QueryBuilder
   */
  protected $queryBuilder;

  /**
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * Constructs a new DefaultController object.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    LanguageManagerInterface $language_manager,
    QueryBuilder $query_builder,
    Serializer $serializer
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $language_manager;
    $this->queryBuilder = $query_builder;
    $this->serializer = $serializer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('language_manager'),
      $container->get('itc_jsonapi.search_api.query_builder'),
      $container->get('serializer')
    );
  }

  /**
   * Search data in specified index.
   *
   * @param \Drupal\search_api\IndexInterface $index
   * @param \Symfony\Component\HttpFoundation\Request $request
   */
  public function search(IndexInterface $index, Request $request) {
    $query = $this->queryBuilder->buildFromRequest($request, $index);
    try {
      $result = $query->execute();
    }
    catch (\Exception $e) {
      $response = new JsonResponse([
        'errors' => [
          'Unexpected error.',
        ],
      ], 500);
      $response->headers->set('content-type', 'application/vnd.api+json');
      return $response;
    }
    $cache_metadata = new CacheableMetadata();
    $cache_metadata->addCacheContexts(['url.query_args']);
    $cache_metadata->addCacheTags(['node_list']);
    if ($result->getResultCount() === 0) {
      $cacheable_res = new CacheableResponse(json_encode(['data' => []]));
      $cacheable_res->headers->set('Content-Type', 'application/vnd.api+json');
      $cacheable_res->addCacheableDependency($cache_metadata);
      return $cacheable_res;
    }
    $meta = ['count' => $result->getResultCount(),];
    $items = [];
    /** @var \Drupal\search_api\Item\ItemInterface $item */
    foreach ($result as $item) {
      $nid = current($item->getField('nid')->getValues());
      $items[$nid] = $item;
    }
    /* Cache nodes */
    $nodes = $this->entityTypeManager
      ->getStorage('node')
      ->loadMultiple(array_keys($items));
    $data = [];
    $language = $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT);
    foreach ($items as $nid => $item) {
      $data[] = $this->serializer->normalize($item, 'api_json', [
        'language' => $language,
        'node' => $nodes[$nid],
        'request' => $request,
      ]);
    }
    $cacheable_res = new CacheableResponse();
    $cacheable_res->setContent(json_encode([
      'data' => $data,
      'meta' => $meta,
    ]));
    $cacheable_res->headers->set('Content-Type', 'application/vnd.api+json');
    $cacheable_res->addCacheableDependency($cache_metadata);
    return $cacheable_res;
  }

}
