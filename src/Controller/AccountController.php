<?php
/**
 * Created by PhpStorm.
 * User: bertrand
 * Date: 18/05/18
 * Time: 17:30
 */

namespace Drupal\itc_jsonapi\Controller;


use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\itc_jsonapi\JsonApiResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountProxy;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AccountController.
 *
 * @package Drupal\itc_jsonapi\Controller
 */
class AccountController implements ContainerInjectionInterface {

  /**
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $account;


  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user')
    );
  }

  public function __construct(AccountProxy $account) {
    $this->account = $account;
  }

  public function me() {
    $attributes = [
      'is_anonymous' => $this->account->isAnonymous(),
      'is_authenticated' => $this->account->isAuthenticated(),
      'preferred_langcode' => $this->account->getPreferredLangcode(),
      'role' => $this->account->getRoles(),
    ];
    $data = [
      'id' => $this->account->id(),
      'type' => 'account',
      'attributes' => $attributes,
    ];
    return new JsonApiResponse(json_encode([
      'data' => $data,
    ]));
  }

}
