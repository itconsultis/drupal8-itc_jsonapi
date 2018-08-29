<?php
/**
 * Created by PhpStorm.
 * User: bertrand
 * Date: 02/04/18
 * Time: 13:50
 */

namespace Drupal\itc_jsonapi\Plugin\jsonapi\FieldEnhancer;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\jsonapi_extras\Plugin\ResourceFieldEnhancerBase;
use Drupal\language\ConfigurableLanguageManager;
use Shaper\Util\Context;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Transform menu name into menu label.
 *
 * @ResourceFieldEnhancer(
 *   id = "menu_name_to_menu_label",
 *   label = @Translation("Menu name to label"),
 *   description = @Translation("Transform menu name to menu label")
 * )
 */
class MenuNameToMenuLabel extends ResourceFieldEnhancerBase implements ContainerFactoryPluginInterface {

  /**
   * Service language_manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Service entity_type.manager.
   *
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('language_manager'),
      $container->get('entity_type.manager'),
      $container->get('module_handler')
    );
  }

  public function __construct(array $configuration, $plugin_id, $plugin_definition, LanguageManager $language_manager, EntityTypeManagerInterface $entity_type_manager, ModuleHandler $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->languageManager = $language_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
  }

  protected function doTransform($data, Context $context) {
    throw new \TypeError();
  }

  protected function doUndoTransform($data, Context $context) {
    $menu_storage = $this->entityTypeManager->getStorage('menu');
    $menu = $menu_storage->load($data);
    if (!$this->moduleHandler->moduleExists('language')) {
      return [
        'id' => $data,
        'label' => $menu->label(),
      ];
    }
    $language = $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT);
    /** @var \Drupal\language\Config\LanguageConfigOverride $config_translation */
    $config_translation = $this->languageManager->getLanguageConfigOverride($language->getId(), $menu->getConfigDependencyName());
    $label = $config_translation->get('description');
    if (!empty($label)) {
      return [
        'id' => $data,
        'label' => $menu->label(),
      ];
    }
    return [
      'id' => $data,
      'label' => $menu->label(),
    ];
  }

  public function getOutputJsonSchema() {
    return [
      'type' => 'object',
      'properties' => [
        'label' => [
          'type' => 'string',
        ],
        'id' => [
          'type' => 'string',
        ],
      ],
    ];
  }


}