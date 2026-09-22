<?php

declare(strict_types=1);

require __DIR__ . '/includes/yardimci.php';

$izinli = ['dashboard', 'talepler', 'urunler', 'urun-form', 'kategoriler', 'blog', 'blog-form', 'sss', 'galeri', 'atolye', 'ayarlar', 'seo-dashboard', 'takvim', 'yorumlar', 'sertifikalar', 'ekip', 'bulten', 'kampanyalar', 'ozellikler', 'sehirler', 'sanal-turlar', 'donusumler', 'video-referanslar', 'sicaklik-formulu', 'audit-logs'];
$sayfa = $_GET['sayfa'] ?? 'dashboard';
if (!in_array($sayfa, $izinli, true)) {
    http_response_code(404);
    $sayfa = 'dashboard';
}

$basliklar = [
    'dashboard' => 'Dashboard', 'talepler' => 'Talepler', 'urunler' => 'Ürünler', 'urun-form' => 'Ürün Formu',
    'kategoriler' => 'Kategoriler', 'blog' => 'Blog', 'blog-form' => 'Blog Formu',
    'sss' => 'SSS', 'galeri' => 'Galeri', 'atolye' => 'Atölye', 'ayarlar' => 'Ayarlar', 'seo-dashboard' => 'SEO Dashboard',
    'takvim' => 'Operasyon Takvimi', 'yorumlar' => 'Yorumlar',
    'sertifikalar' => 'Sertifikalar', 'ekip' => 'Ekip', 'bulten' => 'Bülten',
    'kampanyalar' => 'Kampanyalar', 'ozellikler' => 'Özellikler', 'sehirler' => 'Şehirler',
    'sanal-turlar' => 'Sanal Turlar', 'donusumler' => 'Dönüşümler',
    'video-referanslar' => 'Video Referanslar', 'sicaklik-formulu' => 'Sıcaklık Formülü', 'audit-logs' => 'Audit Logları',
];
$sayfaBaslik = $basliklar[$sayfa];
$aktif = $sayfa === 'seo-dashboard' ? 'seo' : ($sayfa === 'takvim' ? 'takvim' : ($sayfa === 'yorumlar' ? 'yorumlar' : ($sayfa === 'audit-logs' ? 'audit-logs' : in_array($sayfa, ['sertifikalar', 'ekip', 'bulten', 'sanal-turlar', 'donusumler', 'video-referanslar', 'sicaklik-formulu'], true) ? $sayfa : ($sayfa === 'talepler' ? 'talepler' : explode('-', $sayfa)[0]))));

require __DIR__ . '/includes/ust.php';
?>
<div class="govde">
  <?php require __DIR__ . '/includes/kenar.php'; ?>
  <main class="icerik" data-sayfa="<?= htmlspecialchars($sayfa, ENT_QUOTES, 'UTF-8') ?>" data-api="<?= API_TABAN ?>" data-diller="<?= dilListesi() ?>">
    <h1><?= htmlspecialchars($sayfaBaslik, ENT_QUOTES, 'UTF-8') ?></h1>
    <div id="panel-uyari" aria-live="polite"></div>
    <?php require __DIR__ . '/sayfa/' . $sayfa . '.php'; ?>
  </main>
</div>
<?php require __DIR__ . '/includes/alt.php'; ?>