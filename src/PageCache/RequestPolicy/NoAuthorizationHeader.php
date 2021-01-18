<?php


namespace Drupal\itc_jsonapi\PageCache\RequestPolicy;


use Drupal\Core\PageCache\RequestPolicyInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * A policy allowing delivery of cached pages when there is no authorization token.
 *
 * Do not serve cached pages to authenticated users.
 */
class NoAuthorizationHeader implements RequestPolicyInterface {

  public function check(Request $request) {
    if ($request->headers->has('authorization')) {
      return NoAuthorizationHeader::DENY;
    }
  }

}
