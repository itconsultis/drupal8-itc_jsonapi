<?php

namespace Drupal\itc_jsonapi;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\itc_jsonapi\Normalizer\FieldItemNormalizer;

/**
 *
 */
class ItcJsonapiServiceProvider implements ServiceModifierInterface {

  /**
   *
   */
  public function alter(ContainerBuilder $container) {
    $container->getDefinition('serializer.normalizer.field_item.jsonapi_extras')
      ->setClass(FieldItemNormalizer::class);
  }

}
