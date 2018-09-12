<?php

namespace Drupal\itc_jsonapi\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\itc_jsonapi\JsonApiResponse;
use Drupal\itc_jsonapi\EntityToJsonApi;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class NodePreviewController.
 *
 * @package Drupal\itc_jsonapi\Controller
 */
class NodePreviewController implements ContainerInjectionInterface {

  protected $entityToJsonApi;

  /**
   *
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('itc_jsonapi.entity.to_jsonapi')
    );
  }

  /**
   *
   */
  public function __construct(EntityToJsonApi $entity_to_json_api) {
    $this->entityToJsonApi = $entity_to_json_api;
  }

  /**
   *
   */
  public function preview(EntityInterface $node_preview) {
    $res_body = $this->entityToJsonApi->serialize($node_preview);
    return new JsonApiResponse($res_body);
  }

}
