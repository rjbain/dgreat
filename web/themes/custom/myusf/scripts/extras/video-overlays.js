// Get and autoplay youtube video from datatag.
function autoPlayYouTubeModal() {
    var trigger = $("body").find('[data-toggle="modal"]');
    trigger.click(function() {
        var theModal = $(this).data( "target" ),
            videoSRC = $(this).attr( "data-theVideo" ),
            videoSRCauto = videoSRC+"?autoplay=1";
        // first make sure the src is empty
        $(theModal+' iframe').attr('src', "");
        $(theModal+' iframe').attr('src', videoSRCauto).focus();
        $(theModal+' button.close, #videoModal').click(function () {
            $(theModal+' iframe').attr('src', '');
            // $(theModal+' iframe').attr('src', videoSRC);
        });
    });
}

$(document).ready(function(){
    autoPlayYouTubeModal();
});
