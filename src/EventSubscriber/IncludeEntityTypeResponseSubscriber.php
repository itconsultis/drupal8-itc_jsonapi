<?php
/**
 * Created by PhpStorm.
 * User: bertrand
 * Date: 21/06/18
 * Time: 10:31
 */

namespace Drupal\itc_jsonapi\EventSubscriber;

use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\itc_jsonapi\JsonApi\EntityTypeInclude;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
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

  public function __construct(EntityTypeInclude $entity_type_include, CurrentRouteMatch $route_match) {
    $this->entityTypeInclude = $entity_type_include;
    $this->routeMatch = $route_match;
  }

  public function onKernelRequest(GetResponseEvent $event) {
    $request = $event->getRequest();
    $path_info = $request->getPathInfo();
    $path_parts = explode('/', $path_info);
    if (count($path_parts) < 4) {
      return;
    }
    $is_paragraph_route = strpos($path_info, '/jsonapi/paragraph') === 0;
    $is_node_route = strpos($path_info, '/jsonapi/node') === 0;
    $is_node_preview = strpos($path_info, '/jsonapi/node_preview') === 0;
    $is_jponapi_individual_route = $is_node_route || $is_paragraph_route || $is_node_preview;
    if ($is_jponapi_individual_route) {
      $this->entityTypeInclude->transformRequest($event->getRequest());
    }
  }

  public static function getSubscribedEvents() {
    // Run before drupal dynamic cache
    $events[KernelEvents::REQUEST][] = ['onKernelRequest', 100];
    return $events;
  }
}
