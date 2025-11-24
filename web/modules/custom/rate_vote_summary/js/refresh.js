(function (Drupal, drupalSettings, once) {
  // Enable logs in Console with: window.__rvsDebug = true
  function log() { if (window.__rvsDebug) console.log.apply(console, ['[RVS]'].concat([].slice.call(arguments))); }

  // Fetch fresh HTML for all configured blocks.
  function refreshAll() {
    var cfgs = drupalSettings.rateVoteSummary || {};
    Object.keys(cfgs).forEach(function (key) {
      var cfg = cfgs[key];
      var wrapper = document.getElementById(cfg.wrapperId);
      if (!wrapper || !cfg.refreshUrl) return;

      log('Refreshing', cfg.wrapperId, 'from', cfg.refreshUrl);
      fetch(cfg.refreshUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) { return r.text(); })
        .then(function (html) {
          var tmp = document.createElement('div');
          tmp.innerHTML = html;
          var fresh = tmp.firstElementChild;
          if (fresh && wrapper.parentNode) {
            wrapper.parentNode.replaceChild(fresh, wrapper);
          }
        })
        .catch(function (e) { log('Refresh failed', e); });
    });
  }

  Drupal.behaviors.rateVoteSummaryRefresh = {
    attach: function (context) {
      // Bind once per page load.
      once('rate-vote-summary-global-hook', 'html', context).forEach(function () {
        // (A) Hook ALL Drupal AJAX successes (covers drupal_ajax submits).
        if (Drupal.Ajax && Drupal.Ajax.prototype && !Drupal.Ajax.prototype.__rvsPatched) {
          var origSuccess = Drupal.Ajax.prototype.success;
          Drupal.Ajax.prototype.success = function () {
            var result = origSuccess.apply(this, arguments);
            // Give the server a tick to write rows & rebuild caches.
            setTimeout(refreshAll, 150);
            return result;
          };
          Drupal.Ajax.prototype.__rvsPatched = true;
          log('Patched Drupal.Ajax.prototype.success');
        }

        // (B) jQuery ajaxComplete fallback (if site still uses jQuery AJAX elsewhere).
        if (window.jQuery && window.jQuery(document).ajaxComplete && !window.__rvsJqHooked) {
          window.__rvsJqHooked = true;
          window.jQuery(document).ajaxComplete(function () {
            setTimeout(refreshAll, 150);
          });
          log('Bound jQuery ajaxComplete fallback');
        }

        // (C) Click fallback on common Rate wrappers (last resort).
        var candidates = context.querySelectorAll('.rate, .rate-widget, .rate-widget-thumbs-up-down, [data-drupal-selector*="rate"]');
        candidates.forEach(function (el) {
          el.addEventListener('click', function () {
            setTimeout(refreshAll, 300);
          }, { passive: true });
        });
      });
    }
  };
})(Drupal, drupalSettings, once);
