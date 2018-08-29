<?php
/**
 * Created by PhpStorm.
 * User: bertrand
 * Date: 11/12/17
 * Time: 14:33
 */

namespace Drupal\itc_jsonapi\Query;


class QueryParser {
  const SORT_ASC = 'ASC';

  const SORT_DESC = 'DESC';

  const MAX_LIMIT = 50;

  const DEFAULT_LIMIT = 10;


  public function getFilter($filter_name, $filters) {
    if (!isset($filters[$filter_name])) {
      return NULL;
    }
    $filter = $filters[$filter_name];
    if (is_scalar($filter)) {
      $filter = [
        'value' => $filter,
        'operator' => '=',
      ];
    }
    if (is_array($filter)) {
      $value = $filter['value'] ?? '';
      $operator = strtoupper($filter['operator'] ?? '=');
      switch ($operator) {
        case '=':
        case '<':
        case '>':
        case '<>':
        case '!=':
          return [
            'value' => $value,
            'operator' => $operator,
          ];

        case 'IN':
        case 'NOT IN':
          return [
            'value' => explode(',', $value),
            'operator' => $operator,
          ];

        case 'BETWEEN':
        case 'NOT BETWEEN':
          $values = explode(',', $value);
          if (count($values) !== 2) {
            break;
          }
          foreach ($values as $val) {
            $is_scalar = is_scalar($val);
            if (!$is_scalar) {
              return NULL;
            }
          }
          return [
            'value' => $values,
            'operator' => $operator,
          ];
      }
    }
    return NULL;
  }

  public function getSort($raw_sort) {
    $sort = [];
    $sort_fields = explode(',', $raw_sort);
    foreach ($sort_fields as $sort_field) {
      if (strpos($sort_field, '-') === 0) {
        $sort_order = self::SORT_DESC;
        $sort_field = substr($sort_field, 1);
      }
      else {
        $sort_order = self::SORT_ASC;
      }
      if (!empty($sort_field)) {
        $sort[] = [
          'field' => $sort_field,
          'order' => $sort_order,
        ];
      }
    }
    return $sort;
  }

  public function getPager($page) {
    $limit = 10;
    $offset = 0;
    if (is_array($page)) {
      $limit = (int) ($page['limit'] ?? self::DEFAULT_LIMIT);
      $offset = (int) ($page['offset'] ?? 0);
    }
    if ($limit < 1 || $limit > self::MAX_LIMIT) {
      $limit = 10;
    }
    if ($offset < 0) {
      $offset = 0;
    }
    return [
      'limit' => $limit,
      'offset' => $offset,
    ];
  }

}