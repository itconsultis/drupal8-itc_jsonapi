<?php

namespace Drupal\itc_jsonapi;

use Drupal\Core\Cache\CacheableResponse;

/**
 * Class CacheableJsonApiResponse.
 *
 * @package Drupal\itc_jsonapi
 */
class CacheableJsonApiResponse extends CacheableResponse {

  /**
   *
   */
  public function __construct($content = '', int $status = 200, array $headers = []) {
    parent::__construct($content, $status, $headers);
    $this->headers->set('Content-Type', 'application/vnd.api+json');
  }

}
