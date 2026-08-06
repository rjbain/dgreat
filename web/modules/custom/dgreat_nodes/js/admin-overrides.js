/**
 * Admin-only toolbar overrides.
 */
(function (Drupal, once) {
  const HIDDEN_STRUCTURE_PATHS = new Set([
    '/admin/structure/comment',
    '/admin/structure/contact',
    '/admin/structure/display-modes',
    '/admin/structure/flags',
    '/admin/structure/media',
    '/admin/structure/migrate',
    '/admin/structure/rate_widgets',
    '/admin/structure/vote-types',
  ]);
  const HIDDEN_ADD_CONTENT_PATHS = new Set([
    '/node/add/favorite_link',
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
        const structureMenu = getDirectChildMenu(tray, '/admin/structure');
        const addContentMenu = getDirectChildMenu(tray, '/node/add');

        if (structureMenu) {
          pruneDirectChildLinks(structureMenu, HIDDEN_STRUCTURE_PATHS);
        }

        if (addContentMenu) {
          pruneDirectChildLinks(addContentMenu, HIDDEN_ADD_CONTENT_PATHS);
        }
      });
    },
  };

  function getDirectChildMenu(tray, href) {
    const link = tray.querySelector(`a[href="${href}"]`);
    const item = link ? link.closest('li.menu-item') : null;
    return item ? item.querySelector(':scope > ul.toolbar-menu') : null;
  }

  function pruneDirectChildLinks(menu, hiddenPaths) {
    menu.querySelectorAll(':scope > li.menu-item > a[href]').forEach((link) => {
      const path = normalizePath(link.getAttribute('href') || '');
      if (!hiddenPaths.has(path)) {
        return;
      }

      const item = link.closest('li.menu-item');
      if (item) {
        item.remove();
      }
    });
  }
})(Drupal, once);
