/* 360° görüntüleyici — Pannellum CDN (onay sonrası lazy), modal tam ekran. */
(function () {
  function yukle(cb) {
    if (window.pannellum) { cb(); return; }
    var s = document.createElement('script');
    s.src = 'https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.js';
    s.onload = cb;
    document.head.appendChild(s);
    var l = document.createElement('link');
    l.rel = 'stylesheet';
    l.href = 'https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.css';
    document.head.appendChild(l);
  }

  document.querySelectorAll('[data-viewer360]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var url = btn.getAttribute('data-viewer360');
      var modal = document.getElementById('viewer360-modal');
      if (!modal) {
        modal = document.createElement('div');
        modal.id = 'viewer360-modal';
        modal.className = 'modal';
        modal.innerHTML = '<div class="modal-icerik"><div id="viewer360-alan" style="height:60vh"></div>' +
          '<p><button id="viewer360-kapat" type="button" class="btn">Kapat</button></p></div>';
        document.body.appendChild(modal);
        modal.querySelector('#viewer360-kapat').addEventListener('click', function () { modal.hidden = true; });
      }
      modal.hidden = false;
      yukle(function () {
        window.pannellum.viewer('viewer360-alan', { type: 'equirectangular', panorama: url, autoLoad: true });
      });
    });
  });
})();
