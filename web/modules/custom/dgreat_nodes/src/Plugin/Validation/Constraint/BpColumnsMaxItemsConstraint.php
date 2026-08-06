<?php

namespace Drupal\dgreat_nodes\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Limits bp_columns content to three items.
 *
 * @Constraint(
 *   id = "BpColumnsMaxItems",
 *   label = @Translation("BP Columns max column items", context = "Validation"),
 *   type = "field"
 * )
 */
class BpColumnsMaxItemsConstraint extends Constraint {

  /**
   * Maximum number of column content items allowed.
   *
   * @var int
   */
  public $max = 3;

  /**
   * Validation message shown when the limit is exceeded.
   *
   * @var string
   */
  public $message = 'Column content may not contain more than @max items. Please remove the extra column(s) before saving.';

}
