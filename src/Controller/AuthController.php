<?php

namespace Drupal\itc_jsonapi\Controller;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Flood\FloodInterface;
use Drupal\itc_jsonapi\JsonApiResponse;
use Drupal\itc_jsonapi\JWT;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Controller\ControllerBase;
use Drupal\user\UserAuthInterface;

/**
 * Class ConnectionController.
 */
class AuthController extends ControllerBase {

  /**
   * The user auth service.
   *
   * @var \Drupal\user\UserAuthInterface
   */
  protected $userAuth;

  /**
   * @var \Drupal\Core\Flood\FloodInterface
   */
  protected $flood;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\itc_jsonapi\JWT
   */
  protected $jwt;

  /**
   * Constructs a new DefaultController object.
   */
  public function __construct(UserAuthInterface $user_auth, FloodInterface $flood, EntityTypeManagerInterface $entity_type_manager, JWT $jwt) {
    $this->userAuth = $user_auth;
    $this->entityTypeManager = $entity_type_manager;
    $this->flood = $flood;
    $this->jwt = $jwt;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.auth'),
      $container->get('flood'),
      $container->get('entity_type.manager'),
      $container->get('itc_jsonapi.jwt')
    );
  }

  /**
   * Token.
   *
   * @return string
   *   Return tokenString
   */
  public function token(Request $request) {
    if ($request->getMethod() !== 'POST') {
      return new JsonApiResponse(json_encode([
        'errors' => [
          ['status' => 400, 'title' => 'Must be post request'],
        ],
      ]), 400);
    }
    $raw_content = $request->getContent();
    if (empty($raw_content)) {
      return new JsonApiResponse(json_encode([
        'errors' => [
          ['status' => 400, 'title' => 'Empty body.'],
        ],
      ]), 400);
    }
    $credentials = json_decode($raw_content, TRUE);
    if (json_last_error() !== JSON_ERROR_NONE) {
      return new JsonApiResponse(json_encode([
        'errors' => [
          ['status' => 400, 'title' => 'Invalid json data sent'],
        ],
      ]), 400);
    }
    $password = $credentials['password'] ?? '';
    $username = $credentials['username'] ?? '';
    if (empty($password)) {
      return new JsonApiResponse(json_encode([
        'errors' => [
          [
            'status' => 400,
            'title' => 'Password is empty',
            'source' => ['pointer' => 'password'],
          ],
        ],
      ]), 400);
    }
    if (empty($username)) {
      return new JsonApiResponse(json_encode([
        'errors' => [
          [
            'status' => 400,
            'title' => 'Username is empty',
            'source' => ['pointer' => 'username'],
          ],
        ],
      ]), 400);
    }
    $flood_config = $this->config('user.flood');
    // Do not allow any login from the current user's IP if the limit has been
    // reached. Default is 50 failed attempts allowed in one hour. This is
    // independent of the per-user limit to catch attempts from one IP to log
    // in to many different user accounts.  We have a reasonably high limit
    // since there may be only one apparent IP for all users at an institution.
    if (!$this->flood->isAllowed('user.failed_login_ip', $flood_config->get('ip_limit'), $flood_config->get('ip_window'))) {
      return new JsonApiResponse(json_encode([
        'errors' => [
          [
            'status' => 403,
            'title' => 'Too many failed attempts',
          ],
        ],
      ]), 403);
    }
    $user_storage = $this->entityTypeManager->getStorage('user');
    $accounts = $user_storage->loadByProperties([
      'name' => $username,
      'status' => 1,
    ]);
    $account = reset($accounts);
    if ($account) {
      if ($flood_config->get('uid_only')) {
        // Register flood events based on the uid only, so they apply for any
        // IP address. This is the most secure option.
        $identifier = $account->id();
      }
      else {
        // The default identifier is a combination of uid and IP address. This
        // is less secure but more resistant to denial-of-service attacks that
        // could lock out all users with public user names.
        $identifier = $account->id() . '-' . $request->getClientIP();
      }

      // Don't allow login if the limit for this user has been reached.
      // Default is to allow 5 failed attempts every 6 hours.
      if (!$this->flood->isAllowed('user.failed_login_user', $flood_config->get('user_limit'), $flood_config->get('user_window'), $identifier)) {
        return new JsonApiResponse(json_encode([
          'errors' => [
            [
              'status' => 403,
              'title' => 'Too many failed attempts. Retry in 6 hours.',
            ],
          ],
        ]), 403);
      }
    }
    // We are not limited by flood control, so try to authenticate.
    // Store $uid in form state as a flag for self::validateFinal().
    $uid = $this->userAuth->authenticate($username, $password);
    if ($uid <= 0) {
      return new JsonApiResponse(json_encode([
        'errors' => [
          [
            'status' => 400,
            'title' => 'Invalid username or password.',
          ],
        ],
      ]), 400);
    }
    user_login_finalize($account);
    \Drupal::service('session_manager')->destroy();
    [$token, $access_token] = $this->jwt->encode(['user' => $uid], ['host' => $request->getHost()]);
    if (empty($token)) {
      return new JsonApiResponse(json_encode([
        'errors' => [
          [
            'status' => 500,
            'title' => 'Unexpected error.',
          ],
        ],
      ]), 500);
    }
    return new JsonApiResponse(json_encode([
      'data' => [
        'type' => 'token',
        'attributes' => [
          'access_token' => $access_token,
          'token_type' => 'Bearer',
          'expires_in' => $token['exp'] - time(),
        ],
      ],
    ]));
  }

}
