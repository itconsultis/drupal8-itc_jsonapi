<?php

namespace Drupal\itc_jsonapi\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\Validator\Constraint;

/**
 * Check if email is unique.
 *
 * @Constraint(
 *   id = "unique_email",
 *   label = @Translation("Unique email", context = "Validation"),
 * )
 */
class UniqueEmailConstraint extends Constraint {
  use StringTranslationTrait;

  public $notUnique = 'Email "%email" is already used.';

}
