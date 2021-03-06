<?php

namespace Drupal\itc_jsonapi;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Firebase\JWT\JWT as JWTencoder;

/**
 * Class JWT.
 *
 * @package Drupal\itc_jsonapi
 */
class JWT {

  /**
   * Request.
   *
   * @var null|\Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Key.
   *
   * @var string
   */
  protected $key;

  /**
   * Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * AccountProxyInterface.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * JWT constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   Request stack service.
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   Request stack service.
   * @param \Psr\Log\LoggerInterface $logger
   *   Request stack service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RequestStack $request_stack, AccountProxyInterface $account, LoggerInterface $logger) {
    $this->key = $config_factory->get('itc_jsonapi')->get('encryption_key');
    $this->request = $request_stack->getMasterRequest();
    $this->account = $account;
    $this->logger = $logger;
  }

  /**
   * Return token && jwt token.
   *
   * @param mixed $data
   *   Data to encode.
   * @param array $opts
   *   Host: set token iss and aud.
   */
  public function encode($data, array $opts = []) {
    if (empty($this->key)) {
      $this->logger->critical("Empty config['itc_jsonapi']['encryption_key'] in settings.php");
      return [NULL, NULL];
    }
    $iat = new \DateTime('now', new \DateTimeZone('UTC'));
    $exp = new \DateTime();
    $host = $opts['host'] ?? $this->request->getHost();
    // P mandatory for specifying period
    // T required if time interval include time element
    // H hours
    // Here session works for 1 hours.
    $exp->add(new \DateInterval('PT1H'));
    $exp->setTimezone(new \DateTimeZone('UTC'));
    $token = [
      "iss" => $host,
      "aud" => $host,
      "iat" => $iat->getTimestamp(),
      "nbf" => $iat->getTimestamp(),
      "exp" => $exp->getTimestamp(),
      "data" => $data,
    ];
    $this->payload = $token;
    return [$token, JWTencoder::encode($token, $this->key, 'HS256')];
  }

  /**
   * Return current User Token.
   *
   * @return array
   *   Return User Token.
   */
  public function getCurrentUserToken() {
    if ($this->account->isAuthenticated()) {
      return $this->encode(['user' => $this->account->id()]);
    }
  }

}
