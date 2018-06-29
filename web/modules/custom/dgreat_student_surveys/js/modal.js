Drupal.behaviors.surveyModal = {
  attach: function (context, settings) {
    // Using once() to apply the myCustomBehaviour effect when you want to run just one function.
    (function ($) {
      $('#studentSurveyModal').once('surveyModal').modal('show');
    })(jQuery);
  }
};