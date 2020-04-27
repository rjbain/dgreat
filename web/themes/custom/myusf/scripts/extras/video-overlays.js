// Get and autoplay youtube video from datatag.
function autoPlayYouTubeModal() { console.log("hi");
    var trigger = $("body").find('[data-toggle="modal"]');
    trigger.click(function() {
        var theModal = $(this).data( "target" ),
            videoSRC = $(this).attr( "data-theVideo" ),
            videoSRCauto = videoSRC+"?autoplay=1" ;
        console.log("The value of videoSRC is: " + videoSRC);
        $(theModal+' iframe').attr('src', videoSRCauto);
        $(theModal+' button.close').click(function () {
            $(theModal+' iframe').attr('src', videoSRC);
        });
    });
}

$(document).ready(function(){
    autoPlayYouTubeModal();
});