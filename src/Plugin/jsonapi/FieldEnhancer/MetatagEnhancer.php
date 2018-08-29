<?php
/**
 * Created by PhpStorm.
 * User: bertrand
 * Date: 02/03/18
 * Time: 17:13
 */

namespace Drupal\itc_jsonapi\Plugin\jsonapi\FieldEnhancer;


use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Shaper\Util\Context;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\jsonapi_extras\Plugin\ResourceFieldEnhancerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Metatag normalizer.
 *
 * @ResourceFieldEnhancer(
 *   id = "metatag",
 *   label = @Translation("Metatag"),
 *   description = @Translation("Metatag")
 * )
 */
class MetatagEnhancer extends ResourceFieldEnhancerBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\metatag\MetatagManager
   */
  protected $metatagManager;

  /**
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $routeMatch;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $metatag_manager = NULL;
    if ($container->has('metatag.manager')) {
      $metatag_manager = $container->get('metatag.manager');
    }
    $route_match = $container->get('current_route_match');
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $metatag_manager,
      $route_match
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $metatag_manager, CurrentRouteMatch $route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->metatagManager = $metatag_manager;
    $this->routeMatch = $route_match;
  }

  protected function doUndoTransform($value, Context $context) {
    if ($this->metatagManager === NULL) {
      return $value;
    }
    /** @var \Drupal\metatag\Plugin\Field\FieldType\MetatagFieldItem $metatag_field_item */
    $metatag_field_item = $context['object'];
    $entity = $metatag_field_item->getEntity();
    $tags = $this->metatagManager->tagsFromEntityWithDefaults($entity);
    $elements = $this->metatagManager->generateRawElements($tags, $entity);
    return array_values($elements);
  }

  protected function doTransform($value, Context $context) {
    throw new \TypeError();
  }

  public function getOutputJsonSchema() {
    return [];
  }


}