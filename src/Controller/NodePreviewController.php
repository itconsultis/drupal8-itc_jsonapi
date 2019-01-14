<?php
/**
 * Created by PhpStorm.
 * User: bertrand
 * Date: 04/06/18
 * Time: 10:35
 */

namespace Drupal\itc_jsonapi\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\itc_jsonapi\JsonApiResponse;
use Drupal\jsonapi\Controller\EntityResource;
use Drupal\jsonapi\ResourceType\ResourceTypeRepository;
use Drupal\jsonapi\Routing\Routes;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class NodePreviewController.
 *
 * @package Drupal\itc_jsonapi\Controller
 */
class NodePreviewController implements ContainerInjectionInterface {

  protected $entityResource;

  protected $resourceTypeRepository;

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('jsonapi.entity_resource'),
      $container->get('jsonapi.resource_type.repository')
    );
  }

  public function __construct(EntityResource $entity_resource, ResourceTypeRepository $resource_type_repository) {
    $this->entityResource = $entity_resource;
    $this->resourceTypeRepository = $resource_type_repository;
  }

  public function preview(Request $request, EntityInterface $node_preview) {
    $resource_type = $this->resourceTypeRepository->get('node', $node_preview->bundle());
    $request->attributes->set(Routes::RESOURCE_TYPE_KEY, $resource_type);
    return $this->entityResource->getIndividual($node_preview, $request);
  }
}
