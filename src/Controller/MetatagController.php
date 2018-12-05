<?php
/**
 * Created by PhpStorm.
 * User: bertrand
 * Date: 03/12/18
 * Time: 10:10
 */

namespace Drupal\itc_jsonapi\Controller;


use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Language\LanguageInterface;
use Drupal\itc_jsonapi\CacheableJsonApiResponse;
use Drupal\itc_jsonapi\JsonApiResponse;
use Symfony\Component\HttpFoundation\Request;

class MetatagController {

  const ALLOWED_ENTITY_TYPES = ['node', 'taxonomy_term'];

  /**
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * @var \Drupal\metatag\MetatagManagerInterface
   */
  protected $metatagManager;

  /**
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  public function __construct() {
    $this->entityRepository = \Drupal::service('entity.repository');
    $this->languageManager = \Drupal::languageManager();
    $this->metatagManager = \Drupal::service('metatag.manager');
    $this->cache = \Drupal::cache();
  }

  public function metatag(Request $request) {
    $entity_type = $request->query->get('entity_type');
    $uuid = $request->query->get('id');
    $language = $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT);
    $langcode = $language->getId();
    $cache_key = "${entity_type}:${uuid}:${langcode}";
    $cache_item = $this->cache->get($cache_key);
    if (empty($cache_item)) {
      if (!in_array($entity_type, self::ALLOWED_ENTITY_TYPES)) {
        return new JsonApiResponse();
      }
      if (!is_string($uuid)) {
        return new JsonApiResponse();
      }
      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      $entity = $this->entityRepository->loadEntityByUuid($entity_type, $uuid);
      if (empty($entity)) {
        return new JsonApiResponse();
      }
      if ($entity->hasTranslation($langcode)) {
        $entity = $entity->getTranslation($langcode);
      }
      $tags = $this->metatagManager->tagsFromEntityWithDefaults($entity);
      $metatags = $this->metatagManager->generateElements($tags, $entity)['#attached']['html_head'];
      $data = [
        'data' => [],
      ];
      foreach ($metatags as $tag) {
        $data['data'][] = $tag[0];
      }
      $this->cache->set($cache_key, $data, Cache::PERMANENT, $entity->getCacheTags());
      $cache_item = $this->cache->get($cache_key);
    }
    $response = new JsonApiResponse(json_encode($cache_item->data));
    return $response;
  }
}
