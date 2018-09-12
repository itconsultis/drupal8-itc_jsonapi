<?php

namespace Drupal\itc_jsonapi\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 *
 */
class UniqueEmailConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * Service entity_type.manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   *
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'));
  }

  /**
   *
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    /** @var \Drupal\Core\Field\FieldItemListInterface[] $items */
    $entity = $items->getEntity();
    $storage = $this->entityTypeManager->getStorage($entity->getEntityTypeId());
    $emails = [];
    foreach ($items as $item) {
      $emails[] = $item->getString();
    }
    $query = $storage->getQuery();
    $query->condition('field_email', $emails, 'IN');
    $results = $query->execute();
    if (count($results) > 0) {
      $this->context->addViolation($constraint->notUnique, ['%email' => implode(',', $emails)]);
    }
  }

}
