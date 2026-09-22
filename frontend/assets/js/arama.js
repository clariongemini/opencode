/* Site araması: overlay + autocomplete + analytics. */
(function () {
  var ac = document.getElementById('arama-ac');
  var katman = document.getElementById('arama-katman');
  if (ac && katman) {
    ac.addEventListener('click', function () {
      katman.hidden = false;
      document.getElementById('arama-girdi').focus();
    });
    var kapat = document.getElementById('arama-kapat');
    if (kapat) kapat.addEventListener('click', function () { katman.hidden = true; });
  }

  var girdi = document.getElementById('arama-girdi');
  var oneri = document.getElementById('arama-oneri');
  var form = document.getElementById('arama-formu');
  if (girdi && oneri && form) {
    var taban = form.getAttribute('data-api') || '';
    var dil = document.documentElement.lang || 'tr';
    var zamanlayici = null;
    girdi.addEventListener('input', function () {
      clearTimeout(zamanlayici);
      var q = girdi.value.trim();
      if (q.length < 3 || !taban) { oneri.innerHTML = ''; return; }
      zamanlayici = setTimeout(function () {
        fetch(taban + '/arama?q=' + encodeURIComponent(q) + '&lang=' + dil + '&limit=5')
          .then(function (y) { return y.json(); })
          .then(function (g) {
            if (!g.success) return;
            var gruplar = {};
            (g.data.sonuclar || []).forEach(function (s) {
              (gruplar[s.tur] = gruplar[s.tur] || []).push(s);
            });
            oneri.innerHTML = Object.keys(gruplar).map(function (t) {
              return '<p><strong>' + t + ' (' + gruplar[t].length + ')</strong><br>' +
                gruplar[t].map(function (s) { return s.baslik.replace(/&/g, '&amp;').replace(/</g, '&lt;'); }).join('<br>') + '</p>';
            }).join('');
          })
          .catch(function () {});
      }, 300);
    });
    form.addEventListener('submit', function () {
      if (typeof kamelyaOlay === 'function') {
        kamelyaOlay('arama_yapildi', { q: girdi.value.trim(), tur: 'hepsi' });
      }
    });
  }
})();
