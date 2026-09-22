<?php

declare(strict_types=1);

require __DIR__ . '/includes/yardimci.php';

$sayfaBaslik = 'Giriş';
require __DIR__ . '/includes/ust.php';
?>
<main class="giris">
  <h1>Kamelya Admin</h1>
  <form id="giris-formu">
    <div class="form-alan">
      <label class="etiket" for="g-eposta">E-posta</label>
      <input class="input" id="g-eposta" name="eposta" type="email" required autocomplete="username">
    </div>
    <div class="form-alan">
      <label class="etiket" for="g-sifre">Şifre</label>
      <input class="input" id="g-sifre" name="sifre" type="password" required autocomplete="current-password">
    </div>
    <p><button class="btn btn-birincil" type="submit">Giriş Yap</button></p>
    <div id="giris-sonuc" aria-live="polite"></div>
  </form>
</main>
<script>
(function () {
  document.getElementById('giris-formu').addEventListener('submit', function (o) {
    o.preventDefault();
    var veri = {
      eposta: document.getElementById('g-eposta').value,
      sifre: document.getElementById('g-sifre').value
    };
    fetch('<?= API_TABAN ?>/auth/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify(veri)
    }).then(function (y) { return y.json(); }).then(function (g) {
      if (!g.success) {
        document.getElementById('giris-sonuc').innerHTML =
          '<div class="uyari uyari-hata">E-posta veya şifre hatalı.</div>';
        return;
      }
      try {
        localStorage.setItem('kamelya_token', g.data.access_token);
        localStorage.setItem('kamelya_csrf', g.data.csrf_token || '');
        localStorage.setItem('kamelya_rol', (g.data.kullanici || {}).rol || '');
      } catch (e) {}
      window.location.href = '/panel.php';
    }).catch(function () {
      document.getElementById('giris-sonuc').innerHTML =
        '<div class="uyari uyari-hata">Bağlantı hatası.</div>';
    });
  });
})();
</script>
<?php require __DIR__ . '/includes/alt.php'; ?>
