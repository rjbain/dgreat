<?php

declare(strict_types=1);

namespace Drupal\dgreat_table_scope\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\Attribute\Filter;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\Plugin\FilterInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Adds scope attributes to table header cells for accessibility.
 *
 * CKEditor 5 marks header cells as <th> via its "Header row" / "Header column"
 * toggles, but (as of the version bundled with Drupal core) never emits a
 * scope attribute. This output filter fills that gap at render time, keying off
 * CKEditor 5's own markup structure:
 *   - <th> inside <thead>            -> scope="col"  (heads the columns below)
 *   - <th> elsewhere (e.g. <tbody>)  -> scope="row"  (heads its row)
 *
 * The stored markup is never modified — this runs on the rendered output only,
 * so it also self-heals existing content and reverses cleanly on uninstall.
 *
 * Set the filter's weight AFTER "Limit allowed HTML tags" (filter_html) in each
 * text format so the scope attribute it adds is not stripped.
 */
#[Filter(
  id: "dgreat_table_header_scope",
  title: new TranslatableMarkup("Add scope attributes to table headers"),
  description: new TranslatableMarkup('Adds scope="col"/"row" to &lt;th&gt; cells for accessibility. Place this filter after "Limit allowed HTML tags".'),
  type: FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
  weight: 20,
  settings: [
    "normalize_existing" => FALSE,
  ],
)]
class TableHeaderScope extends FilterBase {

  /**
   * Valid HTML scope token values.
   */
  private const VALID_SCOPES = ['col', 'row', 'colgroup', 'rowgroup'];

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);

    // Cheap bail-out: nothing to do if there are no header cells at all.
    if (stripos($text, '<th') === FALSE) {
      return $result;
    }

    $normalize = (bool) $this->settings['normalize_existing'];

    $dom = Html::load($text);
    $changed = FALSE;

    foreach ($dom->getElementsByTagName('th') as $th) {
      $structural = $this->structuralScope($th);
      $existing = trim($th->getAttribute('scope'));

      if ($existing === '') {
        // Add-if-missing: the common case for CKEditor 5 output.
        $th->setAttribute('scope', $structural);
        $changed = TRUE;
      }
      elseif ($normalize) {
        // Respect deliberate span scopes; correct only plain/invalid values
        // that disagree with the cell's structural position.
        $isGroupScope = in_array($existing, ['colgroup', 'rowgroup'], TRUE);
        if (!$isGroupScope && $existing !== $structural) {
          $th->setAttribute('scope', $structural);
          $changed = TRUE;
        }
      }
      // Otherwise: an existing scope is deliberate (Source editing, migration,
      // pasted markup) — leave it untouched.
    }

    if ($changed) {
      $result->setProcessedText(Html::serialize($dom));
    }

    return $result;
  }

  /**
   * Determines the structural scope for a header cell.
   *
   * Walks up to the containing table: a <th> inside a <thead> heads its
   * columns (scope="col"); anything else heads its row (scope="row"). This
   * matches CKEditor 5's output, which always wraps header rows in <thead>
   * and renders header columns as <th> within <tbody> rows.
   *
   * @param \DOMElement $th
   *   The header cell element.
   *
   * @return string
   *   Either "col" or "row".
   */
  private function structuralScope(\DOMElement $th): string {
    $node = $th->parentNode;
    while ($node instanceof \DOMElement) {
      $name = strtolower($node->nodeName);
      if ($name === 'thead') {
        return 'col';
      }
      if ($name === 'table') {
        break;
      }
      $node = $node->parentNode;
    }
    return 'row';
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['normalize_existing'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Normalize existing scope values'),
      '#default_value' => $this->settings['normalize_existing'],
      '#description' => $this->t('By default an existing scope attribute is respected (assumed deliberate). Enable this to overwrite a plain "col"/"row" value that conflicts with the header cell\'s structural position — useful for cleaning up migrated content. Deliberate "colgroup"/"rowgroup" values are always preserved.'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    return $this->t('Table header cells are given scope="col" or scope="row" automatically for accessibility.');
  }

}
