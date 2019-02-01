<?php

namespace Drupal\itc_jsonapi\Authentication\Provider;

use Drupal\Core\Authentication\AuthenticationProviderInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Firebase\JWT\JWT;

/**
 * Class PreviewAuthenticationProvider.
 */
class Token implements AuthenticationProviderInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a HTTP basic authentication provider object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Checks whether suitable authentication credentials are on the request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return bool
   *   TRUE if authentication credentials suitable for this provider are on the
   *   request, FALSE otherwise.
   */
  public function applies(Request $request) {
    $pathInfo = $request->getpathInfo();
    if (strpos($pathInfo, '/jsonapi') === 0) {
      return $request->headers->has('Authorization');
    }
    // If you return TRUE and the method Authentication logic fails,
    // you will get out from Drupal navigation if you are logged in.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate(Request $request) {
    $authorization = $request->get('Authorization');
    $bearer = json_decode($this->getBearerToken($authorization), TRUE);
    $key = $this->configFactory->get('itc_jsonapi')->get('encryption_key');
    $data = $this->jwtDecode($bearer, $key);
    if (!empty($data)) {
      return $this->entityTypeManager->getStorage('user')
        ->load($data['data']->user);
    }
  }

  /**
   * Get access token from header.
   * */
  public function getBearerToken($bearer) {
    if (!empty($bearer)) {
      if (preg_match('/Bearer\s((.*)\.(.*)\.(.*))/', $bearer, $matches)) {
        return $matches[1];
      }
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function cleanup(Request $request) {
  }

  /**
   * {@inheritdoc}
   */
  public function handleException(GetResponseForExceptionEvent $event) {
    $exception = $event->getException();
    if ($exception instanceof AccessDeniedHttpException) {
      $event->setException(
        new UnauthorizedHttpException('Invalid consumer origin.', $exception)
      );
      return TRUE;
    }
    return FALSE;
  }

  /**
   * FirebaseJWT handle hmac tampering test himself.
   */
  private function jwtDecode($jwt, $key) {
    try {
      $decoded = JWT::decode($jwt, $key, ['HS256']);
      $decoded_array = (array) $decoded;
      return $decoded_array;
    }
    catch (\Exception $e) {
      // Token is not valid, nothing to log.
      return NULL;
    }
  }

}
