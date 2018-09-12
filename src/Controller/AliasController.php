<?php

namespace Drupal\itc_jsonapi\Controller;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableResponse;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\itc_jsonapi\AliasResolver;
use Drupal\itc_jsonapi\CacheableJsonApiResponse;
use Drupal\itc_jsonapi\JsonApiResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Class AliasController.
 *
 * @package Drupal\itc_jsonapi\Controller
 */
class AliasController implements ContainerInjectionInterface {
  use StringTranslationTrait;

  /**
   * Service http_kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * Service config.factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Drupal\itc_jsonapi\AliasResolver
   */
  protected $aliasResolver;

  /**
   * Constructs a new DefaultController object.
   */
  public function __construct(
    HttpKernelInterface $http_kernel,
    ConfigFactoryInterface $config_factory,
    AliasResolver $alias_resolver
  ) {
    $this->httpKernel = $http_kernel;
    $this->configFactory = $config_factory;
    $this->aliasResolver = $alias_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_kernel'),
      $container->get('config.factory'),
      $container->get('itc_jsonapi.alias_resolver')
    );
  }

  /**
   * Alias resolver.
   *
   * @return string
   *   Return resource matching the given alias.
   */
  public function resolve(Request $request) {
    $alias = $request->get('alias');
    if (strlen($alias) === 0 || !is_string($alias)) {
      return new JsonApiResponse(json_encode([
        'errors' => [
          [
            'status' => Response::HTTP_BAD_REQUEST,
            'title' => $this->t('Missing alias parameter in query string'),
          ],
        ],
      ]), Response::HTTP_BAD_REQUEST);
    }
    $entity = $this->aliasResolver->resolve($alias);
    if (empty($entity)) {
      $destination = $this->aliasResolver->getRedirect($alias, $request->getHost());
      if (!empty($destination)) {
        return new JsonApiResponse(json_encode([
          'meta' => [
            'redirect' => [
              'status' => Response::HTTP_MOVED_PERMANENTLY,
              'location'  => $destination,
            ],
          ],
        ]));
      }
      return new JsonApiResponse(json_encode([
        'errors' => [
          [
            'status' => Response::HTTP_NOT_FOUND,
            'title'  => $this->t('No content matching this path found.'),
          ],
        ],
      ]), Response::HTTP_NOT_FOUND);
    }
    $language = $this->aliasResolver->getLanguage($alias);
    $entity_type = $entity->getEntityTypeId();
    $bundle = $entity->bundle();
    $uuid = $entity->uuid();
    $path_prefix = $this->configFactory->get('jsonapi_extras.settings')->get('path_prefix');
    $sub_request_query = $request->query->all();
    $sub_request_query['langcode'] = $language->getId();
    unset($sub_request_query['alias']);
    $sub_request = Request::create(
      "/${path_prefix}/${entity_type}/${bundle}/${uuid}",
      'GET',
      $sub_request_query,
      $request->cookies->all()
    );
    $response = $this->httpKernel->handle($sub_request);
    if ($response instanceof CacheableResponse) {
      $cache_metadata = $response->getCacheableMetadata();
    }
    else {
      $cache_metadata = new CacheableMetadata();
    }
    $cache_metadata->addCacheContexts(['url.query_args:alias', 'url.query_args:langcode']);
    $cacheable_res = new CacheableJsonApiResponse($response->getContent(), $response->getStatusCode());
    $cacheable_res->addCacheableDependency($cache_metadata);
    $cacheable_res->addCacheableDependency($entity);
    return $cacheable_res;
  }

}
