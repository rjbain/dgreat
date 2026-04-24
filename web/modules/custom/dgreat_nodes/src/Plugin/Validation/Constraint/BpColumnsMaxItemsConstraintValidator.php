<?php

namespace Drupal\dgreat_nodes\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the BpColumnsMaxItems constraint.
 */
class BpColumnsMaxItemsConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    // $value is the field item list for bp_column_content.
    // count() returns the number of non-empty items currently on the field.
    if ($value->count() > $constraint->max) {
      $this->context->addViolation($constraint->message, [
        '@max' => $constraint->max,
      ]);
    }
  }

}
