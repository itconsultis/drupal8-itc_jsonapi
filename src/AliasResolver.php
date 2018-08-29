<?php
/**
 * Created by PhpStorm.
 * User: bertrand
 * Date: 02/07/18
 * Time: 17:23
 */

namespace Drupal\itc_jsonapi;


use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Path\AliasManagerInterface;

class AliasResolver {

  /**
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $urlPrefixes;

  /**
   * @var string
   */
  protected $frontPath;

  /**
   * @var AliasManagerInterface
   */
  protected $aliasManager;

  protected $pathMatcher;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  protected $domainRedirects;


  public function __construct(LanguageManagerInterface $language_manager, ConfigFactoryInterface $config_factory, AliasManagerInterface $alias_manager, EntityTypeManagerInterface $entity_type_manager) {
    $this->languageManager = $language_manager;
    $this->urlPrefixes = $config_factory->get('language.negotiation')->get('url.prefixes');
    $this->frontPath = $config_factory->get('system.site')->get('page.front');
    $this->domainRedirects = $config_factory->get('redirect_domain.domains')->get('domain_redirects');
    $this->aliasManager = $alias_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->pathMatcher = \Drupal::service('path.matcher');

  }


  public function getLanguage($alias) {
    $enabled_languages = $this->languageManager->getLanguages();
    foreach ($enabled_languages as $l) {
      $language_prefix = '/' . $this->urlPrefixes[$l->getId()];
      if (strlen($language_prefix) > 0 && strpos($alias, $language_prefix . '/') === 0) {
        $language = $l;
        return $language;
      }
    }
    return $this->languageManager->getCurrentLanguage();
  }

  public function stripLanguagePrefix($alias, LanguageInterface $language) {
    $prefix = $this->urlPrefixes[$language->getId()];
    if (empty($prefix)) {
      return $alias;
    }
    $language_prefix = '/' . $prefix;
    return substr($alias, strlen($language_prefix));
  }

  public function stripTrailingSlash($alias) {
    if ($alias[-1] === '/' && strlen($alias) > 1) {
      return substr($alias, 0, -1);
    }
    return $alias;
  }

  public function resolve($raw_alias) {
    $language = $this->getLanguage($raw_alias);
    $alias = $this->stripTrailingSlash($this->stripLanguagePrefix($raw_alias, $language));
    $system_path = $this->aliasManager->getPathByAlias($alias, $language->getId());
    if ($alias === $system_path) {
      return NULL;
    }
    [, $entity_type, $entity_id] = explode('/', $system_path);
    $storage = $this->entityTypeManager->getStorage($entity_type);
    $entity = $storage->load($entity_id);
    if ($entity->access('view')) {
      return $entity;
    }
    return NULL;
  }

  public function getRedirect($alias, $host) {
    $path = $alias;
    $key = str_replace('.', ':', $host);
    if (isset($this->domainRedirects[$key])) {
      foreach ($this->domainRedirects[$key] as $item) {
        if ($this->pathMatcher->matchPath($path, $item['sub_path'])) {
          $destination = $item['destination'];
          return $destination;
        }
      }
    }
    return NULL;
  }
}