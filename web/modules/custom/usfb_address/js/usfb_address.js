/**
 * USFB Address JavaScript
 *
 * Progressively enhance the address form.
 */

(function ($) {

Drupal.behaviors.usfb_address = {
  attach: function(context) {
    // Apply the International Telephone Input widget to the phone selector.
    $('.phoneselect', context).once('phoneselect', function () {
      $(this).intlTelInput();
    });
  }
};

})(jQuery);
