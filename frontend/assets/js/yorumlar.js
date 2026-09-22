/* Yorum modalı + gönderim (honeypot dahil). */
(function () {
  var ac = document.getElementById('yorum-ac');
  if (!ac) return;
  var modal = document.getElementById('yorum-modal');
  ac.addEventListener('click', function () { modal.hidden = false; });
  document.getElementById('yorum-kapat').addEventListener('click', function () { modal.hidden = true; });

  document.getElementById('yorum-formu').addEventListener('submit', function (o) {
    o.preventDefault();
    var form = o.target;
    var veri = {};
    new FormData(form).forEach(function (d, a) { veri[a] = d; });
    veri.kvkk_onayi = form.querySelector('[name=kvkk_onayi]').checked;
    veri.puan = parseInt(veri.puan, 10);
    veri.dil_kodu = form.getAttribute('data-dil');
    var urun = form.getAttribute('data-urun');
    if (urun) veri.urun_id = parseInt(urun, 10);
    var sonuc = document.getElementById('yorum-sonuc');
    fetch(form.getAttribute('data-api') + '/yorumlar', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify(veri)
    }).then(function (y) { return y.json(); }).then(function (g) {
      sonuc.innerHTML = '<div class="uyari ' + (g.success ? 'uyari-basarili' : 'uyari-hata') + '">' +
        (g.success ? sonuc.getAttribute('data-ok') : sonuc.getAttribute('data-hata')) + '</div>';
      if (g.success) form.reset();
    }).catch(function () {
      sonuc.innerHTML = '<div class="uyari uyari-hata">' + sonuc.getAttribute('data-hata') + '</div>';
    });
  });
})();
