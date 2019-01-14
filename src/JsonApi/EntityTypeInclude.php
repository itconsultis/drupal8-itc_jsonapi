<?php
/**
 * Created by PhpStorm.
 * User: bertrand
 * Date: 04/07/18
 * Time: 21:14
 */

namespace Drupal\itc_jsonapi\JsonApi;


use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Symfony\Component\HttpFoundation\Request;

class EntityTypeInclude {

  /**
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $routeMatch;

  /**
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  protected $includedEntityTypes;

  /**
   * @var array
   */
  protected $fields;

  protected $exclude = [];

  protected $cacheTags = [];

  public function __construct(CurrentRouteMatch $route_match, CacheBackendInterface $cache) {
    $this->routeMatch = $route_match;
    $this->cache = $cache;
    $this->entityRepository = \Drupal::service('entity.repository');
  }

  protected function getAllowedFields(FieldableEntityInterface $entity) {
    $field_definitions = $entity->getFieldDefinitions();
    $field_key = $entity->getEntityTypeId() . '--' . $entity->bundle();
    $raw_allowed_fields = $this->fields[$field_key] ?? array_keys($field_definitions);
    if (is_array($raw_allowed_fields)) {
      $this->fields[$field_key] = $raw_allowed_fields;
      sort($this->fields[$field_key]);
      return $this->fields[$field_key];
    }
    $this->fields[$field_key] = explode(',', $raw_allowed_fields);
    sort($this->fields[$field_key]);
    return $this->fields[$field_key];
  }

  protected function computeCid(FieldableEntityInterface $entity) {
    $cache_tag = $entity->getEntityTypeId() . ':' . $entity->id();
    $string_fields = json_encode($this->fields);
    $string_exclude = json_encode($this->exclude);
    $revision_id = '';
    if ($entity instanceof RevisionableInterface) {
      $revision_id = $entity->getRevisionId();
    }
    return md5($cache_tag . $string_fields . $string_exclude . $revision_id);
  }

  public function computeInclude(FieldableEntityInterface $entity, $prefix = '', $exclude = []) {
    $this->cacheTags[] = $entity->getEntityTypeId() . ':' . $entity->id();
    $allowed_fields = $this->getAllowedFields($entity);
    $include = [];
    foreach ($allowed_fields as $field_name) {
      $field_definition = $entity->get($field_name)->getFieldDefinition();
      $rel_name = !empty($prefix) ? $prefix . '.' . $field_name : $field_name;
      if (in_array($rel_name, $exclude)) {
        continue;
      }
      switch ($field_definition->getType()) {
        case 'file':
        case 'image':
          $include[] = $rel_name;
          break;

        case 'entity_reference_revisions':
        case 'entity_reference':
          $target_type = $field_definition->getSetting('target_type');
          if (!in_array($target_type, $this->includedEntityTypes)) {
            break;
          }
          $referenced_entities = $entity->get($field_name)->referencedEntities();
          if (!empty($referenced_entities)) {
            $include[] = $rel_name;
            foreach ($referenced_entities as $ref_entity) {
              $include = array_unique(array_merge($include, $this->computeInclude($ref_entity, $rel_name, $exclude)));
            }
          }
          break;
      }
    }
    return $include;
  }

  public function transformRequest(Request $request) {
    $raw_include = $request->query->get('include', '');
    $include = is_array($raw_include) ? $raw_include : explode(',', $raw_include);
    if (count($include) <= 1) {
      return;
    }
    $raw_exclude = $request->query->get('exclude', '');
    $exclude = explode(',', $raw_exclude);
    sort($this->exclude);
    [$format, $entity_types] = [$include[0], array_slice($include, 1)];
    if ($format !== 'entity_type') {
      return;
    }
    $path_parts = explode('/', $request->getPathInfo());
    $uuid = count($path_parts) === 5 ? $path_parts[4] : NULL;
    if (empty($uuid) && $path_parts[2] === 'node_preview') {
      $uuid = $path_parts[3];
    }
    if (empty($uuid)) {
      return;
    }
    $cid = md5($request->getPathInfo() . $request->getQueryString());
    $cache_item = $this->cache->get($cid);
    if (!empty($cache_item)) {
      $new_include = $cache_item->data;
      $request->query->set('include', implode(',', $new_include));
      return;
    }
    $entity_type = $path_parts[2] === 'node_preview' ? 'node' : $path_parts[2];
    $entity = $this->entityRepository->loadEntityByUuid($entity_type, $uuid);
    if (empty($entity)) {
      return;
    }
    $this->includedEntityTypes = $entity_types;
    $raw_fields = $request->query->get('fields', []);
    $this->fields = is_array($raw_fields) ? $raw_fields : [];
    $new_include = $this->computeInclude($entity, '', $exclude);
    $request->query->set('include', implode(',', $new_include));
    $this->cache->set($cid, $new_include, Cache::PERMANENT, $this->cacheTags);
  }

}
