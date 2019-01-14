<?php
/**
 * Created by PhpStorm.
 * User: bertrand
 * Date: 25/10/17
 * Time: 13:45
 */

namespace Drupal\itc_jsonapi\EventSubscriber;


use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ContactMessageResponseSubscriber implements EventSubscriberInterface {

  /**
   * Service config.factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  public function onKernelResponse(FilterResponseEvent $event) {
    $request = $event->getRequest();
    $response = $event->getResponse();
    $path_prefix = $this->configFactory->get('jsonapi_extras.settings')->get('path_prefix');
    if (
      strpos($request->getPathInfo(), "/${path_prefix}/contact_message") === 0
      && $request->isMethod('POST')
      && $response->getStatusCode() === 403
    ) {
      $response->setContent('');
      $response->setStatusCode(204);
    }
  }

  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['onKernelResponse', 100];
    return $events;
  }

}