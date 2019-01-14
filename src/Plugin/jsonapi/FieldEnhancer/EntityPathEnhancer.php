<?php
/**
 * Created by PhpStorm.
 * User: bertrand
 * Date: 18/10/17
 * Time: 09:08
 */

namespace Drupal\itc_jsonapi\Plugin\jsonapi\FieldEnhancer;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\Core\Url;
use Drupal\jsonapi_extras\Plugin\ResourceFieldEnhancerBase;
use Shaper\Util\Context;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Transform canonical link into path alias if available.
 *
 * @ResourceFieldEnhancer(
 *   id = "entity_path",
 *   label = @Translation("Entity path"),
 *   description = @Translation("Add translations to path")
 * )
 */
class EntityPathEnhancer extends ResourceFieldEnhancerBase implements ContainerFactoryPluginInterface {

  /**
   * Service entity_type.manager.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $entityTypeManager;

  /**
   * Service path.alias_manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * @var array
   */
  protected $prefixes;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('path.alias_manager'),
      $container->get('language_manager'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, AliasManagerInterface $alias_manager, LanguageManagerInterface $language_manager, ConfigFactoryInterface $config_factory) {
    $this->configuration = $configuration;
    $this->pluginId = $plugin_id;
    $this->pluginDefinition = $plugin_definition;
    $this->entityTypeManager = $entity_type_manager;
    $this->aliasManager = $alias_manager;
    $this->languageManager = $language_manager;
    $this->prefixes = $config_factory->get('language.negotiation')->get('url.prefixes');
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  protected function doUndoTransform($value, Context $context) {
    $path = $this->aliasManager->getPathByAlias($value['alias'], $value['langcode']);
    $params = Url::fromUserInput($path)->getRouteParameters();
    $entity_type = key($params);
    $entity_id = current($params);
    $storage = $this->entityTypeManager->getStorage($entity_type);
    $entity = $storage->load($entity_id);
    if ($entity instanceof TranslatableInterface) {
      $paths = [];
      foreach ($entity->getTranslationLanguages() as $language) {
        $url = Url::fromRoute("entity.${entity_type}.canonical",
          [$entity_type => $entity_id],
          [
            'language' => $language,
          ]
        );
        $paths[$language->getId()] = [
          'alias' => $url->toString(TRUE)->getGeneratedUrl(),
          'langcode' => $language->getId(),
        ];
      }
      return $paths;
    }
    return $value;
  }

  protected function doTransform($value, Context $context) {
    throw new \TypeError();
  }

  public function getOutputJsonSchema() {
    $properties = [];
    foreach ($this->languageManager->getLanguages() as $language) {
      $properties[$language->getId()] = [
        'type' => 'object',
        'properties' => [
          'alias' => [
            'type' => 'string',
            'langcode' => 'string',
            'pid' => 'string',
          ],
        ],
      ];
    }
    return [
      'type' => 'object',
      'properties' => $properties,
    ];
  }

  public function getSettingsForm(array $resource_field_info) {
    return [];
  }

}
