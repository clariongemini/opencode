/* Hesaplama aracı — POST /api/v1/calculate tüketimi (aktif dil otomatik). Kapasite gösterimi (F15). */
(function () {
  var form = document.getElementById('hesap-formu');
  if (!form) return;
  var sonuc = document.getElementById('hesap-sonuc');
  var teklifYolu = form.getAttribute('data-teklif') || '/teklif-al';

  form.addEventListener('submit', function (olay) {
    olay.preventDefault();
    var veri = {
      lang: form.getAttribute('data-dil') || 'tr',
      material: form.material.value,
      model: form.model.value,
      usage: form.usage.value
    };
    var alan = parseFloat(form.area_m2.value);
    if (!isNaN(alan) && alan > 0) {
      veri.area_m2 = alan;
    } else {
      veri.width = parseFloat(form.width.value);
      veri.length = parseFloat(form.length.value);
    }

    sonuc.textContent = '…';
    fetch(form.getAttribute('data-api') + '/calculate', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify(veri)
    })
      .then(function (yanit) { return yanit.json(); })
      .then(function (govde) {
        if (!govde.success) throw new Error('hata');
        var d = govde.data;
        if (typeof kamelyaOlay === 'function') {
          kamelyaOlay('hesaplama_yapildi', { alan: d.area_m2, malzeme: veri.material, model: veri.model, dil: veri.lang, fiyat: d.final_price });
        }
        sonuc.innerHTML = '';
        var p = document.createElement('p');
        p.textContent = d.area_m2 + ' m² × ' + d.base_price + ' ' + d.currency + ' = ' + d.final_price + ' ' + d.currency;
        sonuc.appendChild(p);

        // Kapasite gösterimi (F15) — varsa kart ekle
        if (d.capacity && typeof d.capacity.people === 'number') {
          var kart = document.createElement('div');
          kart.className = 'hesap-kapasite';
          kart.innerHTML = '<strong>' + d.capacity.people + ' kişilik</strong><br>' +
            '<small>' + d.capacity.calculation + '</small>' +
            (d.capacity.note ? '<br><small class="muted">' + d.capacity.note + '</small>' : '');
          sonuc.appendChild(kart);
        }

        var a = document.createElement('a');
        a.className = 'btn btn-birincil';
        a.href = teklifYolu + '?alan=' + encodeURIComponent(d.area_m2);
        a.textContent = form.getAttribute('data-teklif-metin') || 'Teklif Al';
        sonuc.appendChild(a);
      })
      .catch(function () {
        sonuc.textContent = form.getAttribute('data-hata') || 'Hesaplama yapılamadı.';
      });
  });
})();