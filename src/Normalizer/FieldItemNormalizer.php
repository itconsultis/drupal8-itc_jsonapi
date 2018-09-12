<?php

namespace Drupal\itc_jsonapi\Normalizer;

use Drupal\jsonapi_extras\Normalizer\FieldItemNormalizer as FieldItemNormalizerExtra;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\jsonapi\Normalizer\Value\FieldItemNormalizerValue;
use Shaper\Util\Context;

/**
 *
 */
class FieldItemNormalizer extends FieldItemNormalizerExtra {

  /**
   *
   */
  public function normalize($object, $format = NULL, array $context = []) {
    switch ($object->getPluginId()) {
      case 'field_item:path':
      case 'field_item:link':
      case 'field_item:tablefield':
      case 'field_item:metatag':
        return $this->enhance(new FieldItemNormalizerValue($object->getValue(), new CacheableMetadata()), $object, $format, $context);

      default:
        return parent::normalize($object, $format, $context);
    }
  }

  /**
   *
   */
  protected function enhance($normalized_output, $object, $format = NULL, array $context = []) {
    $resource_type = $context['resource_type'];
    $enhancer = $resource_type->getFieldEnhancer($object->getParent()->getName());
    if (!$enhancer) {
      return $normalized_output;
    }
    $enhancer_context = new Context([
      'object' => $object,
      'format' => $format,
    ]);
    // Apply any enhancements necessary.
    $processed = $enhancer->undoTransform($normalized_output->rasterizeValue(), $enhancer_context);
    $normalized_output = new FieldItemNormalizerValue([$processed], new CacheableMetadata());

    return $normalized_output;
  }

}
