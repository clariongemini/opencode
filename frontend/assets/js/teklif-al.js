/* Teklif + randevu formları — API fetch + dönüşüm event'leri. */
(function () {
  document.querySelectorAll('form[data-uc]').forEach(function (form) {
    form.addEventListener('submit', function (o) {
      o.preventDefault();
      var veri = {};
      new FormData(form).forEach(function (d, a) { veri[a] = d; });
      var kvkk = form.querySelector('[name=kvkk_onayi]');
      if (kvkk) veri.kvkk_onayi = kvkk.checked;
      var sonuc = document.getElementById(form.getAttribute('data-sonuc'));
      var ok = form.getAttribute('data-ok') || 'OK';
      var hata = form.getAttribute('data-hata') || 'Hata';
      fetch(form.getAttribute('data-api') + form.getAttribute('data-uc'), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify(veri)
      }).then(function (y) { return y.json(); }).then(function (g) {
        if (sonuc) {
          sonuc.innerHTML = '<div class="uyari ' + (g.success ? 'uyari-basarili' : 'uyari-hata') + '">' +
            (g.success ? ok : hata) + '</div>';
        }
        if (g.success && typeof kamelyaOlay === 'function') {
          if (form.getAttribute('data-uc') === '/leads') {
            kamelyaOlay('teklif_formu_gonderildi', { sehir: veri.sehir || '', dil: veri.dil_kodu || '' });
          } else if (form.getAttribute('data-uc') === '/appointments') {
            kamelyaOlay('randevu_talebi_olusturuldu', {});
          }
        }
      }).catch(function () {
        if (sonuc) sonuc.innerHTML = '<div class="uyari uyari-hata">' + hata + '</div>';
      });
    });
  });
})();
