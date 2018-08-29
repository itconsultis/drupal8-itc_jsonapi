<?php

namespace Drupal\itc_jsonapi\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteSubscriber.
 *
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach($collection as $route) {
      if(strpos($route->getPath(),'/jsonapi') === 0){
        $route->setOption('_auth', ['token', 'cookie']);
      }
    }
  }
}
