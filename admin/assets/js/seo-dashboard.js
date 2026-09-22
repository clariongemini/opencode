/* SEO Dashboard — özet kartları, Chart.js grafikler, fırsat listesi. */
(function () {
  var kok = document.querySelector('main.icerik');
  if (!kok || kok.getAttribute('data-sayfa') !== 'seo-dashboard') return;
  var API = kok.getAttribute('data-api');

  function jeton() { try { return localStorage.getItem('kamelya_token') || ''; } catch (e) { return ''; } }

  function api(yol) {
    return fetch(API + yol, { headers: { 'Accept': 'application/json', 'Authorization': 'Bearer ' + jeton() } })
      .then(function (y) {
        if (y.status === 401) { window.location.href = '/index.php'; throw new Error('yetki'); }
        return y.json();
      });
  }

  function esc(s) {
    return String(s === null || s === undefined ? '' : s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  function aralik() {
    var gun = parseInt(document.getElementById('seo-aralik').value, 10) || 30;
    var bitis = new Date().toISOString().slice(0, 10);
    var bas = new Date(Date.now() - gun * 86400000).toISOString().slice(0, 10);
    return '?baslangic=' + bas + '&bitis=' + bitis;
  }

  function yukle() {
    var q = aralik();
    api('/admin/seo/ozet' + q).then(function (g) {
      var d = g.data || {};
      document.getElementById('seo-ozet').innerHTML =
        '<div class="card"><div class="card-govde"><h3>Tıklama</h3><p>' + (d.tiklama || 0) + '</p></div></div>' +
        '<div class="card"><div class="card-govde"><h3>Gösterim</h3><p>' + (d.gosterim || 0) + '</p></div></div>' +
        '<div class="card"><div class="card-govde"><h3>CTR</h3><p>%' + ((d.ctr || 0) * 100).toFixed(2) + '</p></div></div>' +
        '<div class="card"><div class="card-govde"><h3>Pozisyon</h3><p>' + (d.pozisyon || 0) + '</p></div></div>';
      var kay = document.getElementById('seo-kaynak');
      if (kay) kay.textContent = 'Kaynak: önbellek tablosu' + (d.gsc_bagli ? ' (GSC bağlı)' : ' (GSC bağlı değil)') +
        (d.son_guncelleme ? ' · son güncelleme ' + d.son_guncelleme : '');
    });
    api('/admin/seo/sorgular' + q + '&limit=20').then(function (g) {
      document.getElementById('seo-sorgular').innerHTML = ((g.data || []).map(function (s) {
        return '<tr><td>' + esc(s.boyut) + '</td><td>' + s.tiklama + '</td><td>' + s.gosterim + '</td><td>%' +
          (parseFloat(s.ctr) * 100).toFixed(2) + '</td><td>' + s.ortalama_pozisyon + '</td></tr>';
      }).join(''));
    });
    api('/admin/seo/sayfalar' + q + '&limit=20').then(function (g) {
      document.getElementById('seo-sayfalar').innerHTML = ((g.data || []).map(function (s) {
        return '<tr><td>' + esc(s.boyut) + '</td><td>' + s.tiklama + '</td><td>' + s.gosterim + '</td><td>%' +
          (parseFloat(s.ctr) * 100).toFixed(2) + '</td></tr>';
      }).join(''));
    });
    api('/admin/seo/trend' + q).then(function (g) {
      cizgi('seo-trend', (g.data || []).map(function (t) { return t.tarih; }),
        [{ etiket: 'Tıklama', deger: (g.data || []).map(function (t) { return t.tiklama; }) },
         { etiket: 'Gösterim', deger: (g.data || []).map(function (t) { return t.gosterim; }) }]);
    });
    api('/admin/seo/ulke-dagilimi' + q).then(function (g) {
      pasta('seo-ulke', g.data || [], 'boyut', 'tiklama');
    });
    api('/admin/seo/cihaz-dagilimi' + q).then(function (g) {
      pasta('seo-cihaz', g.data || [], 'boyut', 'tiklama');
    });
    api('/admin/seo/kelime-firsatlari' + q).then(function (g) {
      var d = g.data || {};
      var html = '<h3>Fırsat Kelimeler</h3><ul>' + ((d.firsatlar || []).map(function (f) {
        return '<li><strong>' + esc(f.sorgu) + '</strong> — ' + f.gosterim + ' gösterim, CTR %' +
          (parseFloat(f.ctr) * 100).toFixed(2) + '. ' + esc(f.oneri) + '</li>';
      }).join('') || '<li>Yok.</li>') + '</ul>';
      html += '<h3>Düşen Sayfalar</h3><ul>' + ((d.dusen_sayfalar || []).map(function (s) {
        return '<li>' + esc(s.sayfa) + ': ' + s.onceki + ' → ' + s.son + '</li>';
      }).join('') || '<li>Yok.</li>') + '</ul>';
      html += '<h3>Şehir Fırsatları</h3><ul>' + ((d.sehir_firsatlari || []).map(function (s) {
        return '<li>' + esc(s.sehir) + ': “' + esc(s.ornek_sorgu) + '” pozisyon ' + s.pozisyon + '</li>';
      }).join('') || '<li>Yok.</li>') + '</ul>';
      document.getElementById('seo-firsatlar').innerHTML = html;
    });
  }

  var grafikler = {};
  function cizgi(id, etiketler, seriler) {
    if (typeof Chart === 'undefined') return;
    if (grafikler[id]) grafikler[id].destroy();
    grafikler[id] = new Chart(document.getElementById(id), {
      type: 'line',
      data: {
        labels: etiketler,
        datasets: seriler.map(function (s, i) {
          return { label: s.etiket, data: s.deger, borderColor: i === 0 ? '#2f6b3c' : '#c08552', tension: 0.2 };
        })
      }
    });
  }

  function pasta(id, satirlar, etiketAlan, degerAlan) {
    if (typeof Chart === 'undefined') return;
    if (grafikler[id]) grafikler[id].destroy();
    grafikler[id] = new Chart(document.getElementById(id), {
      type: 'pie',
      data: {
        labels: satirlar.map(function (s) { return s[etiketAlan]; }),
        datasets: [{ data: satirlar.map(function (s) { return s[degerAlan]; }) }]
      }
    });
  }

  document.getElementById('seo-aralik').addEventListener('change', yukle);
  yukle();
})();
