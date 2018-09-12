<?php

namespace Drupal\itc_jsonapi\EventSubscriber;

use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\itc_jsonapi\JsonApi\EntityTypeInclude;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class IncludeEntityTypeResponseSubscriber.
 *
 * @package Drupal\itc_jsonapi\EventSubscriber
 */
class IncludeEntityTypeResponseSubscriber implements EventSubscriberInterface {

  /**
   * @var \Drupal\itc_jsonapi\JsonApi\EntityTypeInclude
   */
  protected $entityTypeInclude;

  /**
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $routeMatch;

  /**
   *
   */
  public function __construct(EntityTypeInclude $entity_type_include, CurrentRouteMatch $route_match) {
    $this->entityTypeInclude = $entity_type_include;
    $this->routeMatch = $route_match;
  }

  /**
   *
   */
  public function onKernelRequest(FilterControllerEvent $event) {
    $route_name = $this->routeMatch->getRouteName();
    $is_paragraph_route = (strpos($route_name, 'jsonapi.paragraph') === 0 &&
      strpos($route_name, 'individual') > 20);
    $is_node_route = strpos($route_name, 'jsonapi.node') === 0 &&
      strpos($route_name, 'individual') > 15;
    $is_jponapi_individual_route = $is_node_route || $is_paragraph_route;

    if ($is_jponapi_individual_route || $route_name === 'itc_jsonapi.node_preview_controller.node_preview') {
      $this->entityTypeInclude->transformRequest($event->getRequest());
    }
  }

  /**
   *
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::CONTROLLER][] = ['onKernelRequest', 100];
    return $events;
  }

}
