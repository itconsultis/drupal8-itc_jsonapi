<?php

namespace Drupal\itc_jsonapi;

use Drupal\jsonapi\EntityToJsonApi;
use Drupal\Core\Entity\EntityInterface;

/**
 * Class EntityToJsonApi.
 *
 * @package Drupal\itc_jsonapi
 */
class EntityToJsonApi extends EntityToJsonApi {

  /**
   * Calculate the context for the serialize/normalize operation.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to generate the JSON from.
   *
   * @return array
   *   The context.
   */
  protected function calculateContext(EntityInterface $entity) {
    $context = parent::calculateContext($entity);
    $master_request = $this->requestStack->getMasterRequest();
    /** @var \Symfony\Component\HttpFoundation\Request $request */
    $request = $context['request'];
    $request->query->replace($master_request->query->all());
    return $context;
  }

}
