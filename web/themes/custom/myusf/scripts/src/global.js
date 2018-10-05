(function ($, Drupal) {
    Drupal.behaviors.mainJS = {
        attach: function (context, settings) {

            // Remove focus from buttons after clicking.

            $(".nav-item").mouseleave(function(){
                this.blur();
            });
            $(".carousel-control-next").mouseleave(function(){
                this.blur();
            });
            $(".carousel-control-prev").mouseleave(function(){
                this.blur();
            });

            $(".header_search").mouseleave(function(){
                this.blur();
            });

            // Fix for Ensemble
            $("[id^=contentView_]").attr("align","center");
        }
    };
    Drupal.behaviors.surveyModal = {
        attach: function (context, settings) {
            // Using once() to apply the myCustomBehaviour effect when you want to run just one function.
            $('#studentSurveyModal').once('surveyModal').modal('show');
        }
    };
})(jQuery, Drupal);
