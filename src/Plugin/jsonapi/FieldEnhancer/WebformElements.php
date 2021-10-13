<?php
/**
 * Created by PhpStorm.
 * User: bertrand
 * Date: 18/10/17
 * Time: 09:08
 */

namespace Drupal\itc_jsonapi\Plugin\jsonapi\FieldEnhancer;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\jsonapi_extras\Plugin\ResourceFieldEnhancerBase;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Utility\WebformElementHelper;
use Drupal\webform\WebformTranslationManagerInterface;
use Shaper\Util\Context;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Transform canonical link into path alias if available.
 *
 * @ResourceFieldEnhancer(
 *   id = "webform_elements",
 *   label = @Translation("Webform elements"),
 *   description = @Translation("Load full elements")
 * )
 */
class WebformElements extends ResourceFieldEnhancerBase implements ContainerFactoryPluginInterface {


  /**
   * Service language_manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Service config.factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('language_manager'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LanguageManagerInterface $language_manager, ConfigFactoryInterface $config_factory) {
    $this->configuration = $configuration;
    $this->pluginId = $plugin_id;
    $this->pluginDefinition = $plugin_definition;
    $this->languageManager = $language_manager;
    $this->configFactory = $config_factory;
  }


  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * @return WebformTranslationManagerInterface
   */
  protected function getTranslationManager() {
    return \Drupal::service('webform.translation_manager');
  }


  protected function doUndoTransform($data, Context $context) {
    /** @var \Drupal\jsonapi\JsonApiResource\ResourceObject $resource_object */
    $resource_object = $context->offsetGet('field_item_object');
    $webform_id = $resource_object->getField('drupal_internal__id');
    /** @var Webform $webform */
    $webform = Webform::load($webform_id);
    $original_langcode = $this->getTranslationManager()->getOriginalLangcode($webform);
    /** @var Webform $source_webform */
    $source_elements = $this->getTranslationManager()->getElements($webform, $original_langcode);
    $elements = $webform->getElementsInitialized();
    WebformElementHelper::merge($source_elements, $elements);
    return $source_elements;
  }

  protected function doTransform($data, Context $context) {
    throw new \TypeError();
  }

  public function prepareForInput($value) {
    return $value;
  }

  public function getOutputJsonSchema() {
    return [];
  }

  public function getSettingsForm(array $resource_field_info) {
    return [];
  }

}
