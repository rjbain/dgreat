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
    "promote_scoped_cells" => TRUE,
  ],
)]
class TableHeaderScope extends FilterBase {

  /**
   * Valid HTML scope token values.
   */
  private const VALID_SCOPES = ['col', 'row', 'colgroup', 'rowgroup'];

  /**
   * A table with this class is left entirely untouched by the filter.
   */
  private const EXEMPT_CLASS = 'no-table-filter';

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);

    // Cheap bail-out: nothing to do if there are no table cells at all.
    if (stripos($text, '<td') === FALSE && stripos($text, '<th') === FALSE) {
      return $result;
    }

    // Read defensively: a filter enabled before a setting was introduced has
    // stored config without that key (Drupal does not merge nested plugin
    // defaults into a partial settings array), so fall back to the defaults.
    $normalize = (bool) ($this->settings['normalize_existing'] ?? FALSE);
    $promote = (bool) ($this->settings['promote_scoped_cells'] ?? TRUE);

    $dom = Html::load($text);
    $changed = FALSE;

    // Cells promoted from <td> carry an editor-authored scope; track them so
    // the structural normalize pass below never overwrites that intent.
    $promoted = new \SplObjectStorage();

    // Pass 1: reconcile scope on <td> cells.
    //
    // CKEditor 5 cannot model an individual header cell, so a <th> an editor
    // adds via Source editing is reverted to <td> on load while (if allowlisted
    // for GHS) keeping its scope attribute — an invalid <td scope="...">.
    // Promote those to real header cells; drop any leftover invalid scope,
    // which is meaningless on a <td> and must never render.
    foreach (iterator_to_array($dom->getElementsByTagName('td')) as $td) {
      if (!$td->hasAttribute('scope') || $this->isExempt($td)) {
        continue;
      }
      $scope = trim($td->getAttribute('scope'));
      if ($promote && in_array($scope, self::VALID_SCOPES, TRUE)) {
        $promoted->attach($this->rename($td, 'th'));
      }
      else {
        $td->removeAttribute('scope');
      }
      $changed = TRUE;
    }

    // Pass 2: ensure every <th> carries a scope value.
    foreach (iterator_to_array($dom->getElementsByTagName('th')) as $th) {
      if ($this->isExempt($th)) {
        continue;
      }
      $structural = $this->structuralScope($th);
      $existing = trim($th->getAttribute('scope'));

      if ($existing === '') {
        // Add-if-missing: the common case for CKEditor 5 header rows/columns.
        $th->setAttribute('scope', $structural);
        $changed = TRUE;
      }
      elseif ($normalize && !$promoted->contains($th)) {
        // Respect deliberate span scopes and editor-authored (promoted) values;
        // correct only plain values that disagree with structural position.
        $isGroupScope = in_array($existing, ['colgroup', 'rowgroup'], TRUE);
        if (!$isGroupScope && $existing !== $structural) {
          $th->setAttribute('scope', $structural);
          $changed = TRUE;
        }
      }
      // Otherwise an existing scope is deliberate (Source editing, migration,
      // pasted markup) — leave it untouched.
    }

    if ($changed) {
      $result->setProcessedText(Html::serialize($dom));
    }

    return $result;
  }

  /**
   * Renames an element while preserving its attributes and children.
   *
   * @param \DOMElement $element
   *   The element to rename.
   * @param string $tag
   *   The new tag name.
   *
   * @return \DOMElement
   *   The replacement element, now in the document in place of the original.
   */
  private function rename(\DOMElement $element, string $tag): \DOMElement {
    $new = $element->ownerDocument->createElement($tag);
    foreach (iterator_to_array($element->attributes) as $attr) {
      $new->setAttribute($attr->nodeName, $attr->nodeValue);
    }
    while ($element->firstChild) {
      $new->appendChild($element->firstChild);
    }
    $element->parentNode->replaceChild($new, $element);
    return $new;
  }

  /**
   * Checks whether a cell's containing table opts out of the filter.
   *
   * A <table> carrying the EXEMPT_CLASS is left completely untouched, giving
   * editors (or themers) a way to disable scope handling for a specific table.
   *
   * @param \DOMElement $cell
   *   The <td> or <th> element.
   *
   * @return bool
   *   TRUE if the nearest ancestor table opts out.
   */
  private function isExempt(\DOMElement $cell): bool {
    for ($node = $cell->parentNode; $node instanceof \DOMElement; $node = $node->parentNode) {
      if (strtolower($node->nodeName) === 'table') {
        $classes = preg_split('/\s+/', trim($node->getAttribute('class')), -1, PREG_SPLIT_NO_EMPTY);
        return in_array(self::EXEMPT_CLASS, $classes, TRUE);
      }
    }
    return FALSE;
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
    $form['promote_scoped_cells'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Promote scoped &lt;td&gt; cells to &lt;th&gt;'),
      '#default_value' => $this->settings['promote_scoped_cells'],
      '#description' => $this->t('CKEditor 5 cannot mark an individual cell as a header, so a &lt;th&gt; added via Source editing reverts to &lt;td&gt; while keeping its scope attribute. When enabled, a &lt;td&gt; with a valid scope ("col"/"row"/"colgroup"/"rowgroup") is rendered as a proper &lt;th&gt;. Requires the scope attribute to be allowlisted for the format so it survives editing. Invalid scope values on &lt;td&gt; are stripped regardless.'),
    ];
    $form['normalize_existing'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Normalize existing scope values'),
      '#default_value' => $this->settings['normalize_existing'],
      '#description' => $this->t('By default an existing scope attribute is respected (assumed deliberate). Enable this to overwrite a plain "col"/"row" value that conflicts with the header cell\'s structural position — useful for cleaning up migrated content. Deliberate "colgroup"/"rowgroup" values, and values on promoted cells, are always preserved.'),
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
