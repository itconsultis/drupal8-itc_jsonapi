<?php

namespace Drupal\itc_jsonapi\Plugin\jsonapi\FieldEnhancer;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\jsonapi_extras\Plugin\ResourceFieldEnhancerBase;
use Shaper\Util\Context;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Transform canonical link into path alias if available.
 *
 * @ResourceFieldEnhancer(
 *   id = "file_link_enhancer",
 *   label = @Translation("Relative file path to full path"),
 *   description = @Translation("Relative file path to full path")
 * )
 */
class FileLinkEnhancer extends ResourceFieldEnhancerBase implements ContainerFactoryPluginInterface {


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
   *
   */
  protected function doUndoTransform($data, Context $context) {
    return $GLOBALS['base_url'] . $data;
  }

  /**
   *
   */
  protected function doTransform($data, Context $context) {
    throw new \TypeError();
  }

  /**
   *
   */
  public function getOutputJsonSchema() {
    return [
      'type' => 'string',
    ];
  }

}
