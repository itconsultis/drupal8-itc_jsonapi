<?php

namespace Drupal\itc_jsonapi\Plugin\jsonapi\FieldEnhancer;

use Drupal\jsonapi_extras\Plugin\ResourceFieldEnhancerBase;
use Shaper\Util\Context;

/**
 * Tablefield enhancer.
 *
 * @ResourceFieldEnhancer(
 *   id = "tablefield",
 *   label = @Translation("Tablefield"),
 *   description = @Translation("Tablefield")
 * )
 */
class TablefieldEnhancer extends ResourceFieldEnhancerBase {

  /**
   *
   */
  protected function doTransform($data, Context $context) {
    throw new \TypeError();
  }

  /**
   *
   */
  protected function doUndoTransform($data, Context $context) {
    /** @var \Drupal\tablefield\Plugin\Field\FieldType\TablefieldItem $field */
    $field = $context['object'];
    return $field->getValue();
  }

  /**
   *
   */
  public function getOutputJsonSchema() {
    return [];
  }

}
