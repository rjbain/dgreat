(function ($, Drupal) {
    Drupal.behaviors.addThis = {
        attach: function (context, settings) {
          $("a.addthis_button > img").replaceWith("<div class='hollow_callout_heading'>Share This Page</div>");
          $("a.addthis_button").addClass("hollow_callout_link share addthis_button_more");
        };
})(jQuery, Drupal);