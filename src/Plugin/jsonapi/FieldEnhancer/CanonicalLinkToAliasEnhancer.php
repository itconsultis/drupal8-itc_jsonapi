<?php
/**
 * Created by PhpStorm.
 * User: bertrand
 * Date: 18/10/17
 * Time: 09:08
 */

namespace Drupal\itc_jsonapi\Plugin\jsonapi\FieldEnhancer;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\jsonapi_extras\Plugin\ResourceFieldEnhancerBase;
use Shaper\Util\Context;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Transform canonical link into path alias if available.
 *
 * @ResourceFieldEnhancer(
 *   id = "canonical_link_to_alias",
 *   label = @Translation("Canonical link to alias"),
 *   description = @Translation("Transform canonical link into path alias if available")
 * )
 */
class CanonicalLinkToAliasEnhancer extends ResourceFieldEnhancerBase implements ContainerFactoryPluginInterface {


  /**
   * Service language_manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LanguageManagerInterface $language_manager) {
    $this->configuration = $configuration;
    $this->pluginId = $plugin_id;
    $this->pluginDefinition = $plugin_definition;
    $this->languageManager = $language_manager;
  }


  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  protected function doUndoTransform($data, Context $context) {
    $language = $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT);
    $uri = $data['uri'];
    $url = Url::fromUri($uri, [
      'language' => $language,
    ]);
    $next_value = $data;
    $next_value['uri'] = $url->toString(TRUE)->getGeneratedUrl();;

    return $next_value;
  }

  protected function doTransform($data, Context $context) {
    throw new \TypeError();
  }

  public function prepareForInput($value) {
    return $value;
  }

  public function getOutputJsonSchema() {
    return [
      'type' => 'object',
      'properties' => [
        'uri' => 'string',
        'title' => 'string',
      ],
    ];
  }

  public function getSettingsForm(array $resource_field_info) {
    return [];
  }

}