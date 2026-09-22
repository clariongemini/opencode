/* Video referans kartları — tıklayınca modalda oynat (lazy). */
(function () {
  document.querySelectorAll('[data-video-ac]').forEach(function (b) {
    b.addEventListener('click', function () {
      var url = b.getAttribute('data-video-ac');
      var modal = document.getElementById('video-modal');
      if (!modal) {
        modal = document.createElement('div');
        modal.id = 'video-modal';
        modal.className = 'modal';
        modal.innerHTML = '<div class="modal-icerik"><div id="video-alan"></div>' +
          '<p><button id="video-kapat" type="button" class="btn">Kapat</button></p></div>';
        document.body.appendChild(modal);
        modal.querySelector('#video-kapat').addEventListener('click', function () {
          document.getElementById('video-alan').innerHTML = '';
          modal.hidden = true;
        });
      }
      document.getElementById('video-alan').innerHTML =
        '<iframe src="' + url.replace(/&/g, '&amp;').replace(/</g, '&lt;') + '" width="100%" height="360" loading="lazy" title="Video" allowfullscreen></iframe>';
      modal.hidden = false;
    });
  });
})();
