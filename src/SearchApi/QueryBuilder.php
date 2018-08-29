<?php
/**
 * Created by PhpStorm.
 * User: bertrand
 * Date: 27/11/17
 * Time: 11:36
 */

namespace Drupal\itc_jsonapi\SearchApi;


use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\itc_jsonapi\Query\QueryParser;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Query\Query;
use Drupal\search_api\Query\QueryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

class QueryBuilder {

  const SORT_ASC = 'ASC';

  const SORT_DESC = 'DESC';

  /**
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * @var \Drupal\itc_jsonapi\Query\QueryParser
   */
  protected $queryParser;

  public function __construct(LanguageManagerInterface $language_manager, EventDispatcherInterface $event_dispatcher, QueryParser $query_parser) {
    $this->languageManager = $language_manager;
    $this->eventDispatcher = $event_dispatcher;
    $this->queryParser = $query_parser;
  }


  public function applyFilters($filters, QueryInterface $query, $allowed_fields = []) {
    $and_condition_group = $query->createConditionGroup('AND');
    if (is_array($filters)) {
      foreach ($filters as $field_name => $filter) {
        if (!empty($allowed_fields) && !in_array($field_name, $allowed_fields)) {
          continue;
        }
        $normalized_filter = $this->queryParser->getFilter($field_name, $filters);
        if (!empty($normalized_filter)) {
          $and_condition_group->addCondition($field_name, $normalized_filter['value'], $normalized_filter['operator']);
        }
      }
    }
    if (!empty($and_condition_group->getConditions())) {
      $query->addConditionGroup($and_condition_group);
    }
  }

  public function applyLanguageFilter($langcode = '', QueryInterface $query) {
    $index = $query->getIndex();
    $field_names = array_keys($index->getFields());
    if (!in_array('language', $field_names)) {
      return;
    }
    if (empty($langcode)) {
      $langcode = $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
    }
    $language = $this->languageManager->getLanguage($langcode);
    $query->addCondition('language', $language->getId());
  }

  public function applySort($sort, QueryInterface $query, $allowed_fields = []) {
    $sort = $this->queryParser->getSort($sort);
    foreach ($sort as $sort_item) {
      if (in_array($sort_item['field'], $allowed_fields)) {
        $query->sort($sort_item['field'], $sort_item['order']);
      }
    }
  }

  public function applyPager($page, QueryInterface $query) {
    $pager = $this->queryParser->getPager($page);
    $query->range($pager['offset'], $pager['limit']);
  }

  public function buildFromRequest(Request $request, IndexInterface $index = NULL) {
    if (empty($index)) {
      $index = $request->get('index');
      if (empty($index)) {
        return NULL;
      }
    }
    $query = $index->query();
    $filters = $request->query->get('filter', []);
    $langcode = $request->query->get('langcode', '');
    $sort = $request->query->get('sort', '');
    $q = $request->query->get('query', '');
    $page = $request->query->get('page');

    $allowed_fields = array_keys($index->getFields());

    $this->applyFilters($filters, $query, $allowed_fields);
    $this->applyLanguageFilter($langcode, $query);
    $this->applySort($sort, $query, $allowed_fields);
    if (!empty($q)) {
      $query->keys($q);
    }
    $this->applyPager($page, $query);
    $this->eventDispatcher->dispatch(QueryBuilderEvents::QUERY_CREATED, new QueryCreatedEvent($query));
    return $query;
  }
}