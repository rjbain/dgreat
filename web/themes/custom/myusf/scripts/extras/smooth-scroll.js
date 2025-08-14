(function ($, Drupal) {
  Drupal.behaviors.smoothScrollLinks = {
    attach: function (context, settings) {
      $('a[href^="#"]', context).once('smooth-scroll').on('click', function (e) {
        const targetId = this.getAttribute('href').substring(1);
        const targetEl = document.getElementById(targetId);
        if (targetEl) {
          e.preventDefault();
          targetEl.scrollIntoView({ behavior: 'smooth' });
        }
      });
    }
  };
})(jQuery, Drupal);