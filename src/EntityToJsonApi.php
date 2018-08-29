<?php
/**
 * Created by PhpStorm.
 * User: bertrand
 * Date: 04/06/18
 * Time: 10:51
 */

namespace Drupal\itc_jsonapi;

use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\HttpFoundation\Request;


/**
 * Class EntityToJsonApi
 *
 * @package Drupal\itc_jsonapi
 */
class EntityToJsonApi extends \Drupal\jsonapi\EntityToJsonApi {

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
    /** @var Request $request */
    $request = $context['request'];
    $request->query->replace($master_request->query->all());
    return $context;
  }
}