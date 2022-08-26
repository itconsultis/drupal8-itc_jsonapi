<?php
/**
 * Created by PhpStorm.
 * User: bertrand
 * Date: 16/01/19
 * Time: 10:49
 */

namespace Drupal\itc_jsonapi\Controller;


use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Language\Language;
use Drupal\Core\Url;
use Drupal\itc_jsonapi\CacheableJsonApiResponse;
use Drupal\itc_jsonapi\JsonApiResponse;
use Symfony\Component\HttpFoundation\Request;

class LanguageSwitcherController {

  const ALLOWED_ENTITY_TYPES = ['node', 'taxonomy_term'];

  /**
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * @var \Drupal\Core\Path\AliasManager
   */
  protected $aliasManager;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;


  public function __construct() {
    $this->entityRepository = \Drupal::service('entity.repository');
    $this->languageManager = \Drupal::languageManager();
    $this->aliasManager = \Drupal::hasService('path_alias.manager')
      ? \Drupal:service('path_alias.manager') // Drupal >=9.2
      : \Drupal:service('path.alias_manager') //Drupal 8
    ;
    $this->configFactory = \Drupal::configFactory();
  }

  protected function resolvedPathIsHomePath($resolved_path, Language $language) {
    $home_path = $this->configFactory->get('system.site')->get('page.front');
    if($resolved_path === $home_path) {
      return TRUE;
    }
    $home_url = Url::fromUserInput($home_path, ['language' => $language])
      ->toString(TRUE)
      ->getGeneratedUrl();
    return $home_url === $resolved_path;
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
    $is_homepage = false;
    foreach ($this->languageManager->getLanguages() as $language) {
      $langcode = $language->getId();
      if ($entity->hasTranslation($langcode)) {
        $url = $entity->toUrl('canonical', ['language' => $language])
          ->toString(TRUE)
          ->getGeneratedUrl();
        if (!$is_homepage) {
          $is_homepage = $this->resolvedPathIsHomePath($url, $language);
        }
        if ($is_homepage) {
          $url = Url::fromRoute('<front>', [], ['language' => $language])
            ->toString(TRUE)
            ->getGeneratedUrl();
        }
        $links[$langcode] = $url;
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
