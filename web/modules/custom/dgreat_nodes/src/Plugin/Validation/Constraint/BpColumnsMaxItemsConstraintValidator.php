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
    if ($value->count() > $constraint->max) {
      $this->context->addViolation($constraint->message, [
        '@max' => $constraint->max,
      ]);
    }
  }

}
