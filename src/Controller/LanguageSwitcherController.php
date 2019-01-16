<?php
/**
 * Created by PhpStorm.
 * User: bertrand
 * Date: 16/01/19
 * Time: 10:49
 */

namespace Drupal\itc_jsonapi\Controller;


use Drupal\Core\Cache\CacheableMetadata;
use Drupal\itc_jsonapi\CacheableJsonApiResponse;
use Drupal\itc_jsonapi\JsonApiResponse;
use Symfony\Component\HttpFoundation\Request;

class LanguageSwitcherController {

  const ALLOWED_ENTITY_TYPES = ['node', 'taxonomy_term'];

  /**
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;


  public function __construct() {
    $this->entityRepository = \Drupal::service('entity.repository');
    $this->languageManager = \Drupal::languageManager();
  }

  public function get(Request $request) {
    $entity_type = $request->query->get('entity_type');
    $uuid = $request->query->get('id');
    if (!in_array($entity_type, self::ALLOWED_ENTITY_TYPES)) {
      return new JsonApiResponse('', 400);
    }
    if (!is_string($uuid)) {
      return new JsonApiResponse('', 400);
    }
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->entityRepository->loadEntityByUuid($entity_type, $uuid);
    if (empty($entity)) {
      return new JsonApiResponse('', 404);
    }
    $links = [];
    foreach ($this->languageManager->getLanguages() as $language) {
      $langcode = $language->getId();
      if ($entity->hasTranslation($langcode)) {
        $links[$langcode] = $entity->toUrl('canonical', ['language' => $language])
          ->toString(TRUE)
          ->getGeneratedUrl();
      }
    }
    $response = new CacheableJsonApiResponse(json_encode(['links' => $links]));
    $cache_metadata = new CacheableMetadata();
    $cache_metadata->addCacheContexts(['url.query_args:entity_type', 'url.query_args:id']);
    $cache_metadata->addCacheableDependency($entity);
    $response->addCacheableDependency($cache_metadata);
    return $response;
  }
}
