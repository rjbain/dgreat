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
        const structureLink = tray.querySelector('a[href="/admin/structure"]');
        const structureItem = structureLink ? structureLink.closest('li.menu-item') : null;
        const structureMenu = structureItem ? structureItem.querySelector(':scope > ul.toolbar-menu') : null;

        if (!structureMenu) {
          return;
        }

        structureMenu.querySelectorAll(':scope > li.menu-item > a[href]').forEach((link) => {
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
