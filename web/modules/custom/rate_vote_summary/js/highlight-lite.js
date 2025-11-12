(function (Drupal) {
  Drupal.behaviors.rateVoteSummaryInstant = {
    attach: function (context) {
      // Only bind once per page context.
      if (context._rvsiBound) return;
      context._rvsiBound = true;

      document.addEventListener('click', function (e) {
        // We only care about the actual inputs (radio/checkbox) the Rate widget renders.
        const input = e.target.closest('input[type="radio"][data-rate-widget], input[type="checkbox"][data-rate-widget]');
        if (!input) return;

        // These data-* come from your form_alter().
        const widget = input.dataset.rateWidget;
        const entityId = input.dataset.entityId;
        if (!widget || !entityId) return;

        // Scope to the form if possible to avoid touching unrelated widgets on the page.
        const scope = input.form || document;

        // Remove highlight from all labels in the same widget/entity group.
        scope.querySelectorAll(
          `label[data-rate-widget="${widget}"][data-entity-id="${entityId}"]`
        ).forEach(label => label.classList.remove('my-vote'));

        // Add highlight to the clicked option's label.
        // Standard pattern: label[for="{input.id}"]
        if (input.id) {
          const label = scope.querySelector(`label[for="${CSS.escape(input.id)}"]`);
          if (label) {
            label.classList.add('my-vote');
          }
        }
        // No preventDefault: let Rate’s own AJAX proceed normally.
        // After the AJAX/refresh, your preprocess will re-apply the correct class server-side.
      }, { passive: true });
    }
  };
})(Drupal);
