// Get and autoplay youtube video from datatag.
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

    vidIframe.load(function() {
      $(theModal+' iframe').focus();
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
