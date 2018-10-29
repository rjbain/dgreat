<?php /**
 * @file
 * Contains \Drupal\pdf_reader\Plugin\Field\FieldFormatter\FieldPdfReaderField.
 */

namespace Drupal\weight\Plugin\Field\FieldFormatter;


use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;


/**
 * @FieldFormatter(
 *  id = "default_weight",
 *  label = @Translation("Default"),
 *  field_types = {"weight"}
 * )
 */
class WeightFieldFormatter extends FormatterBase {
  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      // The text value has no text format assigned to it, so the user input
      // should equal the output, including newlines.
      $elements[$delta] = [
       '#markup' => $item->value,
      ];
    }

    return $elements;
  }

}