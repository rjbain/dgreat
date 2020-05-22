function handleTouchScreens() {
    window.addEventListener('touchstart', function() {
        // the user touched the screen!
        const IS_TOUCHSCREEN = true;
        if (IS_TOUCHSCREEN === true) {
            $(".overlay-link img").bind("click", function(e) {
                e.preventDefault();
            });
        }
    });
}

$(document).ready(function(){
    handleTouchScreens();
});