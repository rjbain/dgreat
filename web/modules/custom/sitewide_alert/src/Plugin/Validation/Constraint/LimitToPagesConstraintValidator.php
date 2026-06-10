<?php

namespace Drupal\sitewide_alert\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the LimitToPages constraint.
 */
class LimitToPagesConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint): void {
    $value = $entity->get('limit_to_pages')->value;

    if (!empty($value) && $this->validPathsValue($value) === FALSE) {
      $this->context->buildViolation($constraint->messageInvalidPaths)
        ->atPath('limit_to_pages')
        ->addViolation();
    }
  }

  /**
   * Returns TRUE if all paths given are valid. False if any paths are invalid.
   *
   * @param string $pagesValue
   *   The pages config value.
   *
   * @return bool
   *   TRUE if all paths given are valid; False if any paths are invalid.
   */
  private function validPathsValue(string $pagesValue): bool {
    foreach (explode("\n", strip_tags($pagesValue)) as $path) {
      $path = trim($path);

      if (!empty($path) && !str_starts_with($path, '/')) {
        return FALSE;
      }
    }

    return TRUE;
  }

}
