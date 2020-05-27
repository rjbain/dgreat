function handleTouchScreens() {

    var IS_TOUCHSCREEN;

    window.addEventListener('touchstart', function() {
        // the user touched the screen!
        IS_TOUCHSCREEN = true;
        if (IS_TOUCHSCREEN === true) {
            $(".overlay-link").bind("click", function(e) {
                e.preventDefault();
                // $(this).mouseenter();
                // $(this).toggleClass("overlayed-link");
            });
        }
    });

    // Open the link if you click on a descendant of the .overlay class.
    $(".overlay").on('touchend', '*', function(e) {
        if (IS_TOUCHSCREEN === true) {
            $('.overlay').trigger('touchend');
            $(".overlay-link").click();
            // alert($(this).outerHeight());
            // setTimeout(function(){
            //     if ($(this).outerHeight() > "50") {
                    // Get the URL for linking
                    var link = $(this).parents('.overlay-link').attr('href');
                    // alert($(this).outerHeight());
                    window.open(link);
                // }
            // }, 1000);
        }
    });
    // $(".overlay").bind('webkitTransitionEnd otransitionend oTransitionEnd msTransitionEnd transitionend', function() {
    //     alert("fin");
    // });
    // Prevent the overlay if you swipe up or down on a link

    // overlayLink.addEventListener('touchmove', function() {
    //     // the user touched the screen!
    //     alert("moved");
    //     if (IS_TOUCHSCREEN === true) {
    //         $(".overlay-link").mouseleave();
    //     }
    // });

    // $(".overlay-link").bind("touchmove", function(e) {
    //     $(this).mouseleave();
    // });
    // $(".overlay-link").bind("touchend", function(e) {
    //     $(this).mouseleave();
    // });

    // $(".overlayed-link").bind("click", function(e) {
    //     alert("overlayed-link clicked");
    // });

} // End of handleTouchScreens


$(document).ready(function(){
    handleTouchScreens();
});