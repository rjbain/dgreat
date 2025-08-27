(function ($, Drupal) {
  Drupal.behaviors.smoothScroll = {
    attach: function (context, settings) {
      // Use `once` to ensure this runs only once per page load
      $(once('smooth-scroll', 'a[href^="#"]', context)).on('click', function (e) {
        const targetId = this.getAttribute('href').substring(1);
        const target = document.getElementById(targetId);

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