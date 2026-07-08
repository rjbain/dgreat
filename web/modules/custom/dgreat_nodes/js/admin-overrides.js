/**
 * Admin-only toolbar overrides.
 */
(function (Drupal, once) {
  const HIDDEN_TOOLBAR_PATHS = new Set([
    '/admin/structure/comment',
    '/admin/structure/contact',
    '/admin/structure/display-modes',
    '/admin/structure/flags',
    '/admin/structure/media',
    '/admin/structure/migrate',
    '/admin/structure/rate_widgets',
    '/admin/structure/vote-types',
  ]);

  function normalizePath(value) {
    try {
      return new URL(value, window.location.origin).pathname.replace(/\/+$/, '') || '/';
    }
    catch (e) {
      return value;
    }
  }

  Drupal.behaviors.dgreatAdminToolbarOverrides = {
    attach(context) {
      once('dgreat-toolbar-prune', '#toolbar-item-administration-tray', context).forEach((tray) => {
        tray.querySelectorAll('a[href]').forEach((link) => {
          const path = normalizePath(link.getAttribute('href') || '');
          if (!HIDDEN_TOOLBAR_PATHS.has(path)) {
            return;
          }

          const item = link.closest('li.menu-item');
          if (item) {
            item.remove();
          }
        });
      });
    },
  };
})(Drupal, once);
