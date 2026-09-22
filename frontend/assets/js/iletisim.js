/* İletişim formu — POST /api/v1/iletisim. */
(function () {
  var form = document.getElementById('iletisim-formu');
  if (!form) return;
  form.addEventListener('submit', function (o) {
    o.preventDefault();
    var veri = {};
    new FormData(form).forEach(function (d, a) { veri[a] = d; });
    veri.kvkk_onayi = form.querySelector('[name=kvkk_onayi]').checked;
    veri.dil_kodu = form.getAttribute('data-dil');
    delete veri.tur;
    veri.tur = 'iletisim';
    var sonuc = document.getElementById('iletisim-sonuc');
    fetch(form.getAttribute('data-api') + '/iletisim', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify(veri)
    }).then(function (y) { return y.json(); }).then(function (g) {
      sonuc.innerHTML = '<div class="uyari ' + (g.success ? 'uyari-basarili' : 'uyari-hata') + '">' +
        (g.success ? form.getAttribute('data-ok') : form.getAttribute('data-hata')) + '</div>';
      if (g.success) form.reset();
    }).catch(function () {
      sonuc.innerHTML = '<div class="uyari uyari-hata">' + form.getAttribute('data-hata') + '</div>';
    });
  });

  /* Harita tıklayınca yüklenir (KVKK: önden iframe yok). */
  var haritaBtn = document.getElementById('harita-ac');
  if (haritaBtn) {
    haritaBtn.addEventListener('click', function () {
      var kap = haritaBtn.closest('.harita-katman');
      var url = kap.getAttribute('data-harita');
      var cerceve = document.createElement('iframe');
      cerceve.src = url;
      cerceve.width = '100%';
      cerceve.height = '360';
      cerceve.loading = 'lazy';
      cerceve.title = 'Harita';
      kap.innerHTML = '';
      kap.appendChild(cerceve);
    });
  }
})();
