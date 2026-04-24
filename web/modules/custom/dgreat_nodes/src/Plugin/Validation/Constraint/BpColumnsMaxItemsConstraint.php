<?php

namespace Drupal\dgreat_nodes\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Limits the number of items in bp_columns' bp_column_content field to 3.
 *
 * Applied via hook_entity_bundle_field_info_alter() in dgreat_nodes.module.
 * Using a validation constraint (rather than changing field storage
 * cardinality) allows existing over-limit content to remain readable and
 * editable while preventing any new saves that exceed the maximum.
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
