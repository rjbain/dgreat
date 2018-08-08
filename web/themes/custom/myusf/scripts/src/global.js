(function ($, Drupal) {
    Drupal.behaviors.fixHover = {
        attach: function (context, settings) {
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
        }
    };
})(jQuery, Drupal);
