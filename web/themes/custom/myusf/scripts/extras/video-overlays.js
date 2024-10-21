// Get and autoplay youtube video from datatag.
function spaceBarControl(vidIframe) {
  document.addEventListener('keydown',function(e){
    if(e.key === " ") {
      e.preventDefault();
      vidIframe.focus();
    }
  });
}
function autoPlayYouTubeModal() {
  var trigger = $("body").find('[data-toggle="modal"]');
  trigger.click(function() {
    let theModal = $(this).data( "target" ),
      videoSRC = $(this).attr( "data-theVideo" ),
      videoSRCauto = videoSRC+"?autoplay=1";
    let vidIframe = $(theModal+' iframe');
    // first make sure the src is empty
    vidIframe.attr('src', "");
    vidIframe.attr('src', videoSRCauto);

    vidIframe.on('load', function () {
      vidIframe.focus();
      spaceBarControl(vidIframe);
    });

    $(theModal+' button.close, #videoModal').click(function () {
      $(theModal+' iframe').attr('src', '');
      // $(theModal+' iframe').attr('src', videoSRC);
    });
  });
}

$(document).ready(function(){
  autoPlayYouTubeModal();
});
