function handleTouchScreens() {

    var IS_TOUCHSCREEN;

    window.addEventListener('touchstart', function() {
        // the user touched the screen!
        IS_TOUCHSCREEN = true;
        if (IS_TOUCHSCREEN === true) {
            $(".overlay-link").bind("click", function(e) {
                e.preventDefault();
            });
        }
    });

    // Open the link if you click on a descendant of the .overlay class.
    $(".overlay").on('touchend', '*', function(e) {
        if (IS_TOUCHSCREEN === true) {
            $('.overlay').trigger('touchend');
            $(".overlay-link").click();
            if (event.target.tagName.toLowerCase() === "h2" || event.target.className === "overlay-arrow") {
                return;
            } else {
                // Get the URL for linking
                var link = $(this).parents('.overlay-link').attr('href');
                window.open(link);
            }
        }
    });

} // End of handleTouchScreens

$(document).ready(function(){
    handleTouchScreens();
});