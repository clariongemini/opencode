/* Genel etkileşimler: akordeon + dönüşüm tıklamaları. */
(function () {
  document.querySelectorAll('[data-akordeon]').forEach(function (dugme) {
    dugme.addEventListener('click', function () {
      var icerik = dugme.nextElementSibling;
      var acik = dugme.getAttribute('aria-expanded') === 'true';
      dugme.setAttribute('aria-expanded', acik ? 'false' : 'true');
      if (icerik) icerik.hidden = acik;
    });
  });

  function olay(ad, veri) {
    if (typeof kamelyaOlay === 'function') kamelyaOlay(ad, veri || {});
  }

  var wa = document.querySelector('.whatsapp-sabit');
  if (wa) wa.addEventListener('click', function () { olay('whatsapp_tiklandi', {}); });

  document.querySelectorAll('a[href^="tel:"]').forEach(function (a) {
    a.addEventListener('click', function () { olay('telefon_tiklandi', {}); });
  });

  document.querySelectorAll('.dil-secici a').forEach(function (a) {
    a.addEventListener('click', function () {
      var m = (a.getAttribute('href') || '').match(/\/(tr|en|de|fr|it|ar)(\/|$)/);
      olay('dil_degistirildi', { hedef_dil: m ? m[1] : 'tr' });
    });
  });

  /* Footer bülten formu. */
  /* Footer bülten formu (kapalıysa sayfada yoktur). */
  var bulten = document.getElementById('bulten-formu');
  if (bulten) {
    bulten.addEventListener('submit', function (o) {
      o.preventDefault();
      var eposta = document.getElementById('b-eposta').value;
      var sonuc = document.getElementById('bulten-sonuc');
      fetch(bulten.getAttribute('data-api') + '/bulten', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ eposta: eposta, dil_kodu: bulten.getAttribute('data-dil'), kvkk_onayi: true })
      }).then(function (y) { return y.json(); }).then(function (g) {
        sonuc.textContent = g.success ? 'Kaydınız alındı. E-postanızı doğrulayın.' : 'Kayıt başarısız.';
      }).catch(function () { sonuc.textContent = 'Kayıt başarısız.'; });
    });
  }
  /* Kampanya bandı: kapatma 24 saat saklanır + tıklama eventi. */
  var kband = document.getElementById('kampanya-band');
  if (kband) {
    var kid = kband.getAttribute('data-kampanya');
    var anahtar = 'kampanya_kapatildi_' + kid;
    var kayit = null;
    try { kayit = localStorage.getItem(anahtar); } catch (e) {}
    if (kayit && (Date.now() - parseInt(kayit, 10)) < 24 * 3600 * 1000) {
      kband.hidden = true;
    }
    var kapat = document.getElementById('kampanya-kapat');
    if (kapat) kapat.addEventListener('click', function () {
      kband.hidden = true;
      try { localStorage.setItem(anahtar, String(Date.now())); } catch (e) {}
    });
    kband.addEventListener('click', function (o) {
      if (o.target && o.target.id !== 'kampanya-kapat') olay('kampanya_tiklama', { id: kid });
    });
  }
})();
