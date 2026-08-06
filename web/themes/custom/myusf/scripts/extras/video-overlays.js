(( $ ) => {
  // Get and autoplay youtube video from datatag.
  function spaceBarControl(vidIframe) {
    document.addEventListener('keydown', function (e) {
      if (e.key === " ") {
        e.preventDefault();
        vidIframe.focus();
      }
    });
  }

  function openModal(theModal) {
    const modalElement = document.querySelector(theModal);

    if (!modalElement) {
      return;
    }

    if (window.bootstrap && window.bootstrap.Modal) {
      window.bootstrap.Modal.getOrCreateInstance(modalElement).show();
      return;
    }

    if (typeof $(theModal).modal === 'function') {
      $(theModal).modal('show');
    }
  }

  function normalizeVideoUrl(videoUrl) {
    if (!videoUrl) {
      return '';
    }

    let normalizedUrl = videoUrl.trim();

    if (!normalizedUrl) {
      return '';
    }

    try {
      const parsedUrl = new URL(normalizedUrl, window.location.origin);
      const host = parsedUrl.hostname.toLowerCase();

      if (host.includes('youtu.be')) {
        const videoId = parsedUrl.pathname.replace(/^\/+/, '').split('/')[0];
        return videoId ? `https://www.youtube.com/embed/${videoId}` : normalizedUrl;
      }

      if (host.includes('youtube.com')) {
        if (parsedUrl.pathname.startsWith('/embed/')) {
          return normalizedUrl;
        }

        const queryVideoId = parsedUrl.searchParams.get('v');
        if (queryVideoId) {
          return `https://www.youtube.com/embed/${queryVideoId}`;
        }

        const pathParts = parsedUrl.pathname.split('/').filter(Boolean);
        const embedIndex = ['shorts', 'live', 'v', 'vi'].includes(pathParts[0]) ? 1 : -1;
        if (embedIndex === 1 && pathParts[1]) {
          return `https://www.youtube.com/embed/${pathParts[1]}`;
        }

        return '';
      }

      if (host.includes('vimeo.com')) {
        if (host.includes('player.vimeo.com')) {
          return normalizedUrl;
        }

        const pathParts = parsedUrl.pathname.split('/').filter(Boolean);
        const videoId = pathParts.reverse().find((part) => /^\d+$/.test(part));
        return videoId ? `https://player.vimeo.com/video/${videoId}` : '';
      }
    }
    catch (error) {
      return normalizedUrl;
    }

    return normalizedUrl;
  }

  function buildAutoplayUrl(videoUrl) {
    const embedUrl = normalizeVideoUrl(videoUrl);

    if (!embedUrl) {
      return '';
    }

    return embedUrl + (embedUrl.includes('?') ? '&autoplay=1' : '?autoplay=1');
  }

  function bindVideoOverlay() {
    $('body')
      .off('click.videoOverlay', '.video-component[data-the-video]')
      .on('click.videoOverlay', '.video-component[data-the-video]', function (event) {
      event.preventDefault();

        const theModal = this.getAttribute('data-bs-target') || this.getAttribute('data-target');
        const videoSRC = this.getAttribute('data-the-video') || this.getAttribute('data-video-href');
        const videoSRCauto = buildAutoplayUrl(videoSRC);
        const vidIframe = theModal ? $(theModal + ' iframe') : $();

        if (!theModal || !videoSRC || !videoSRCauto || !vidIframe.length) {
          return;
        }

        // first make sure the src is empty
        vidIframe.attr('src', '');
        vidIframe.attr('src', videoSRCauto);
        openModal(theModal);

        vidIframe.on('load', function () {
          vidIframe.focus();
          spaceBarControl(vidIframe);
        });

        $(theModal)
          .off('hidden.bs.modal.videoOverlay click.videoOverlay')
          .on('hidden.bs.modal.videoOverlay click.videoOverlay', 'button.close, [data-bs-dismiss="modal"], [data-dismiss="modal"]', function () {
            $(theModal + ' iframe').attr('src', '');
          });
      });
  }

  $(document).ready(function () {
    bindVideoOverlay();
  });
})(jQuery);
