(function (Drupal) {
  Drupal.behaviors.bootstrapCompat = {
    attach: function (context) {
      const scope = context && context.querySelectorAll ? context : document;

      scope.querySelectorAll('[data-toggle]').forEach((el) => {
        if (!el.hasAttribute('data-bs-toggle')) {
          el.setAttribute('data-bs-toggle', el.getAttribute('data-toggle'));
        }
      });

      scope.querySelectorAll('[data-target]').forEach((el) => {
        if (!el.hasAttribute('data-bs-target')) {
          el.setAttribute('data-bs-target', el.getAttribute('data-target'));
        }
      });

      scope.querySelectorAll('[data-parent]').forEach((el) => {
        if (!el.hasAttribute('data-bs-parent')) {
          el.setAttribute('data-bs-parent', el.getAttribute('data-parent'));
        }
      });

      scope.querySelectorAll('[data-slide]').forEach((el) => {
        if (!el.hasAttribute('data-bs-slide')) {
          el.setAttribute('data-bs-slide', el.getAttribute('data-slide'));
        }
      });

      scope.querySelectorAll('[data-slide-to]').forEach((el) => {
        if (!el.hasAttribute('data-bs-slide-to')) {
          el.setAttribute('data-bs-slide-to', el.getAttribute('data-slide-to'));
        }
      });

      scope.querySelectorAll('[data-dismiss]').forEach((el) => {
        if (!el.hasAttribute('data-bs-dismiss')) {
          el.setAttribute('data-bs-dismiss', el.getAttribute('data-dismiss'));
        }
      });

      scope.querySelectorAll('[data-ride]').forEach((el) => {
        if (!el.hasAttribute('data-bs-ride')) {
          el.setAttribute('data-bs-ride', el.getAttribute('data-ride'));
        }
      });

      scope.querySelectorAll('[data-interval]').forEach((el) => {
        if (!el.hasAttribute('data-bs-interval')) {
          el.setAttribute('data-bs-interval', el.getAttribute('data-interval'));
        }
      });

      scope.querySelectorAll('.collapse.in').forEach((el) => {
        el.classList.remove('in');
        el.classList.add('show');
      });

      if (!window.bootstrap) {
        return;
      }

      scope.querySelectorAll('.carousel').forEach((el) => {
        window.bootstrap.Carousel.getOrCreateInstance(el);
      });

      scope.querySelectorAll('.collapse').forEach((el) => {
        window.bootstrap.Collapse.getOrCreateInstance(el, { toggle: false });
      });

      scope.querySelectorAll('[data-bs-toggle="dropdown"]').forEach((el) => {
        window.bootstrap.Dropdown.getOrCreateInstance(el);
      });

      scope.querySelectorAll('.modal').forEach((el) => {
        window.bootstrap.Modal.getOrCreateInstance(el);
      });
    }
  };
})(Drupal);
