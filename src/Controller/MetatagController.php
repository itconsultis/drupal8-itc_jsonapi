<?php
/**
 * Created by PhpStorm.
 * User: bertrand
 * Date: 03/12/18
 * Time: 10:10
 */

namespace Drupal\itc_jsonapi\Controller;


use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Url;
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
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  public function __construct() {
    $this->entityRepository = \Drupal::service('entity.repository');
    $this->languageManager = \Drupal::languageManager();
    $this->metatagManager = \Drupal::service('metatag.manager');
    $this->renderer = \Drupal::service('renderer');
    $this->configFactory = \Drupal::configFactory();
  }

  protected function isHomePath($path, LanguageInterface $language) {
    $home_path = $this->configFactory->get('system.site')->get('page.front');
    if($path === $home_path) {
      return TRUE;
    }
    $home_url = Url::fromUserInput($home_path, ['language' => $language])
      ->toString(TRUE)
      ->getGeneratedUrl();
    return $home_url === $path;
  }

  public function metatag(Request $request) {
    $entity_type = $request->query->get('entity_type');
    $uuid = $request->query->get('id');
    $language = $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT);
    $langcode = $language->getId();
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
    if ($entity->hasTranslation($langcode)) {
      $entity = $entity->getTranslation($langcode);
    }
    $tags = $this->metatagManager->tagsFromEntityWithDefaults($entity);
    $context = new RenderContext();
    $metatags = $this->renderer->executeInRenderContext($context, function () use ($tags, $entity) {
      return $this->metatagManager->generateElements($tags, $entity)['#attached']['html_head'];
    });
    $data = [
      'data' => [],
    ];
    foreach ($metatags as $tag) {
      [$tag_data, $tag_name] = $tag;
      switch ($tag_name) {
        case 'canonical_url':
          $path = parse_url($tag_data['#attributes']['href'], PHP_URL_PATH);
          $tag_data['#attributes']['href'] = $path;
          if ($this->isHomePath($path, $language)) {
            $home_url = Url::fromRoute('<front>', [], ['language' => $language])
              ->setAbsolute()
              ->toString(TRUE)
              ->getGeneratedUrl();
            $tag_data['#attributes']['href'] = $home_url;
          }
          $data['data'][] = $tag_data;
          break;

        default:
          $data['data'][] = $tag_data;
          break;
      }
    }
    $response = new CacheableJsonApiResponse(json_encode($data));
    if (!empty($context->isEmpty())) {
      $response->addCacheableDependency($context->pop());
    }
    $metadata = $response->getCacheableMetadata();
    $metadata->addCacheContexts(['languages', 'url.query_args:entity_type', 'url.query_args:id']);
    $metadata->addCacheableDependency($entity);
    return $response;
  }
}
