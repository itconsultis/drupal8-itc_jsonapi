<?php

namespace Drupal\itc_jsonapi;

use Symfony\Component\HttpFoundation\Response;

/**
 *
 */
class JsonApiResponse extends Response {

  /**
   *
   */
  public function __construct($content = '', int $status = 200, array $headers = []) {
    parent::__construct($content, $status, $headers);
    $this->headers->set('Content-Type', 'application/vnd.api+json');
  }

}
