/* Karşılaştırma: localStorage sepeti + API tablosu + fark vurgusu. */
(function () {
  var ANAHTAR = 'karsilastirma_sepeti';

  function sepetOku() {
    try { return JSON.parse(localStorage.getItem(ANAHTAR) || '[]'); }
    catch (e) { return []; }
  }

  function sepetYaz(dizi) {
    try { localStorage.setItem(ANAHTAR, JSON.stringify(dizi.slice(0, 4))); } catch (e) {}
  }

  /* Ürün listesi: checkbox + floating bar. */
  var kutular = document.querySelectorAll('[data-karsilastir]');
  if (kutular.length > 0) {
    var bar = document.createElement('div');
    bar.id = 'karsilastirma-bar';
    bar.style.cssText = 'position:fixed;bottom:16px;inset-inline:16px;text-align:center;z-index:60;display:none';
    document.body.appendChild(bar);

    var guncelle = function () {
      var sepet = sepetOku();
      kutular.forEach(function (k) {
        k.checked = sepet.indexOf(k.getAttribute('data-karsilastir')) !== -1;
      });
      if (sepet.length >= 2) {
        bar.style.display = 'block';
        bar.innerHTML = '<a class="btn btn-birincil" href="/karsilastir?ids=' +
          sepet.map(encodeURIComponent).join(',') + '">' + sepet.length + ' ürün seçildi — Karşılaştır</a>';
      } else {
        bar.style.display = 'none';
      }
    };

    kutular.forEach(function (k) {
      k.addEventListener('change', function () {
        var sepet = sepetOku();
        var id = k.getAttribute('data-karsilastir');
        if (k.checked) { if (sepet.indexOf(id) === -1) sepet.push(id); }
        else { sepet = sepet.filter(function (x) { return x !== id; }); }
        sepetYaz(sepet);
        guncelle();
      });
    });
    guncelle();
  }

  /* Karşılaştırma sayfası: tablo. */
  var alan = document.getElementById('karsilastirma-alan');
  if (alan) {
    var params = new URLSearchParams(window.location.search);
    var ids = params.get('ids') || sepetOku().join(',');
    fetch(alan.getAttribute('data-api') + '/karsilastir?ids=' + encodeURIComponent(ids) + '&lang=' + alan.getAttribute('data-dil'))
      .then(function (y) { return y.json(); })
      .then(function (g) {
        if (!g.success) { alan.innerHTML = '<p>En az 2 ürün seçin.</p>'; return; }
        var d = g.data;
        var satirlar = [
          ['Başlık', 'baslik'], ['Fiyat', 'fiyat'], ['Malzeme', 'malzeme'],
          ['Model', 'model'], ['Kullanım', 'kullanim'], ['Ölçü', 'olcu'], ['Garanti', 'garanti']
        ];
        var html = '<table class="card"><tbody>';
        satirlar.forEach(function (s) {
          var fark = (d.farkli_alanlar || []).indexOf(s[1]) !== -1;
          html += '<tr' + (fark ? ' style="background:#FEF3C7"' : '') + '><th scope="row">' + s[0] + '</th>';
          d.urunler.forEach(function (u) {
            html += '<td>' + String(u[s[1]] === null || u[s[1]] === undefined ? '-' : u[s[1]]).replace(/&/g, '&amp;').replace(/</g, '&lt;') + '</td>';
          });
          html += '</tr>';
        });
        alan.innerHTML = html + '</tbody></table>';
      })
      .catch(function () { alan.innerHTML = '<p>Yüklenemedi.</p>'; });
  }
})();
