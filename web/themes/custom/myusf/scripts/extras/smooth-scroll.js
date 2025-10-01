(function ($, Drupal) {
  Drupal.behaviors.smoothScroll = {
    attach: function (context, settings) {
      $(once('smooth-scroll', 'a[href^="#"]', context)).on('click', function (e) {
        const href = this.getAttribute('href');
        if (!href || href === '#' || href.length <= 1) return;

        const targetId = href.substring(1);
        const target = document.getElementById(targetId);

        // Ignore carousel, collapse toggles, tabs, modals, etc.
        if (
          this.closest('.carousel') ||
          this.hasAttribute('data-slide') ||
          this.hasAttribute('data-bs-slide') ||
          this.hasAttribute('data-toggle') ||
          this.hasAttribute('data-bs-toggle')
        ) {
          return; // let Bootstrap handle it
        }

        if (target) {
          e.preventDefault();
          target.scrollIntoView({
            behavior: 'smooth'
          });
        } else {
          console.warn(`Element with ID "${targetId}" not found.`);
        }
      });
    }
  };
})(jQuery, Drupal);
