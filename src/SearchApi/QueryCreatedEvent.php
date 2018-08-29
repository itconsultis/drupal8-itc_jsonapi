<?php
/**
 * Created by PhpStorm.
 * User: bertrand
 * Date: 28/11/17
 * Time: 16:15
 */

namespace Drupal\itc_jsonapi\SearchApi;


use Drupal\search_api\Query\QueryInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class QueryCreatedEvent
 *
 * @package Drupal\itc_jsonapi\SearchApi
 */
class QueryCreatedEvent extends Event {

  /**
   * @var \Drupal\search_api\Query\QueryInterface
   */
  public $query;

  /**
   * QueryCreatedEvent constructor.
   *
   * @param \Drupal\search_api\Query\QueryInterface $query
   */
  public function __construct(QueryInterface $query) {
    $this->query = $query;
  }
}