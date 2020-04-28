// Get and autoplay youtube video from datatag.
function autoPlayYouTubeModal() {
    var trigger = $("body").find('[data-toggle="modal"]');
    trigger.click(function() {
        var theModal = $(this).data( "target" ),
            // videoSRCauto = videoSRC+"?autoplay=1"
            videoSRC = $(this).attr( "data-theVideo" );
        // first make sure the src is empty
        $(theModal+' iframe').attr('src', "");
        $(theModal+' iframe').attr('src', videoSRC);
        $(theModal+' button.close').click(function () {
            $(theModal+' iframe').attr('src', '');
            // $(theModal+' iframe').attr('src', videoSRC);
        });
    });
}

$(document).ready(function(){
    autoPlayYouTubeModal();
});