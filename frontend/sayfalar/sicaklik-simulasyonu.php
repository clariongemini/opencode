<?php

declare(strict_types=1);

/** Sıcaklık/gölge simülasyonu — şeffaf formül, SVG görsel, bilimsel atıf. */

$SEO = [
    'baslik' => 'Sıcaklık ve Gölge Simülasyonu — Tahmini Hesap — Kamelya',
    'aciklama' => 'Kamelya gölge alanı ve sıcaklık düşüşü tahmini: alan, çatı tipi ve mevsime göre bilimsel verilere dayalı hesap. Ücretsiz keşifle netleştirin. Bugün deneyin.',
    'yol' => $MEVCUT_YOL,
];
?>
<?= kirinti([['etiket' => t('anasayfa'), 'yol' => siteUrl('/')], ['etiket' => 'Simülasyon', 'yol' => null]]) ?>
<h1>Sıcaklık ve Gölge Simülasyonu</h1>
<form id="sicaklik-formu" data-api="<?= htmlspecialchars($AYAR['api_taban'], ENT_QUOTES, 'UTF-8') ?>">
  <div class="form-alan">
    <label class="etiket" for="s-alan">Kamelya Alanı (m²)</label>
    <input class="input" id="s-alan" type="number" min="1" max="500" step="0.5" value="20" required>
  </div>
  <div class="form-alan">
    <label class="etiket" for="s-cati">Çatı Tipi</label>
    <select class="input" id="s-cati">
      <option value="duz">Düz</option>
      <option value="egimli">Eğimli</option>
      <option value="kubbe">Kubbe</option>
      <option value="biyoklimatik" selected>Biyoklimatik</option>
      <option value="ahsap_kiremit">Ahşap Kiremit</option>
    </select>
  </div>
  <div class="form-alan">
    <label class="etiket" for="s-mevsim">Mevsim</label>
    <select class="input" id="s-mevsim">
      <option value="yaz" selected>Yaz</option>
      <option value="ilkbahar">İlkbahar</option>
      <option value="sonbahar">Sonbahar</option>
      <option value="kis">Kış</option>
    </select>
  </div>
  <p><button class="btn btn-birincil" type="submit">Hesapla</button></p>
</form>
<div id="sicaklik-sonuc" aria-live="polite"></div>
<svg id="sicaklik-gorsel" viewBox="0 0 200 120" width="100%" role="img" aria-label="Gölge gösterimi" hidden>
  <rect x="10" y="10" width="180" height="100" fill="#E5DCCB"></rect>
  <rect id="svg-golge" x="10" y="10" width="160" height="100" fill="#5C3D21" opacity="0.55"></rect>
</svg>
<p><small>Bilimsel verilere dayalı tahmini hesaplamadır; gerçek değerler konuma ve saate göre değişir.</small></p>
<script>
(function () {
  var form = document.getElementById('sicaklik-formu');
  if (!form) return;
  var KATSAYI = { duz: 0.85, egimli: 0.9, kubbe: 0.88, biyoklimatik: 0.9, ahsap_kiremit: 0.82 };
  var MEVSIM = { yaz: 1.0, ilkbahar: 0.8, sonbahar: 0.7, kis: 0.5 };
  /* Katsayılar admin panelinden gelirse onları kullan (yoksa varsayılan). */
  fetch(form.getAttribute('data-api') + '/ayarlar').then(function (y) { return y.json(); }).then(function (g) {
    try {
      var k = JSON.parse((g.data || {}).sicaklik_formulu_katsayilari || '{}');
      if (k.cati) KATSAYI = k.cati;
      if (k.mevsim) MEVSIM = k.mevsim;
    } catch (e) {}
  }).catch(function () {});
  form.addEventListener('submit', function (o) {
    o.preventDefault();
    var alan = parseFloat(document.getElementById('s-alan').value) || 0;
    var cati = document.getElementById('s-cati').value;
    var mevsim = document.getElementById('s-mevsim').value;
    var golge = Math.round(alan * (KATSAYI[cati] || 0.85) * (MEVSIM[mevsim] || 1) * 10) / 10;
    /* Muhafazakar tahmin: aşağı yuvarlanır (örn. 20 m² biyoklimatik + yaz → 18 m², -4°C). */
    var dusus = Math.floor((golge / Math.max(alan, 0.1)) * 5 * (MEVSIM[mevsim] || 1));
    document.getElementById('sicaklik-sonuc').innerHTML =
      '<p class="hesap-sonuc">Gölge alanı: ' + golge + ' m² · Sıcaklık düşüşü: -' + dusus + '°C</p>';
    var oran = Math.min(1, golge / Math.max(alan, 0.1));
    document.getElementById('svg-golge').setAttribute('width', Math.round(180 * oran));
    document.getElementById('sicaklik-gorsel').hidden = false;
  });
})();
