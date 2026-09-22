<?php

declare(strict_types=1);

/** Arama sonuçları — noindex, tür sekmeli, sayfalı, boş-durumlu. */

$q = trim((string) ($_GET['q'] ?? ''));
$tur = (string) ($_GET['tur'] ?? 'hepsi');
$sayfaNo = max(1, (int) ($_GET['sayfa'] ?? 1));

$SEO = [
    'baslik' => 'Arama: ' . ($q !== '' ? $q : '—') . ' — Kamelya',
    'aciklama' => 'Kamelya site içi arama sonuçları: ürünler, rehberler ve sık sorulan sorular.',
    'yol' => $MEVCUT_YOL,
    'robots' => 'noindex, follow',
];

$sonuc = null;
if (mb_strlen($q) >= 3) {
    $sonuc = apiGet('/arama', $dil, ['q' => $q, 'tur' => $tur, 'sayfa' => (string) $sayfaNo, 'limit' => '20']);
}

$sekmeler = ['hepsi' => 'Tümü', 'urun' => 'Ürünler', 'blog' => 'Blog', 'sss' => 'SSS'];
?>
<h1>Arama<?= $q !== '' ? ': ' . htmlspecialchars($q, ENT_QUOTES, 'UTF-8') : '' ?></h1>
<div class="sekmeler">
  <?php foreach ($sekmeler as $kod => $etiket): ?>
  <a href="<?= htmlspecialchars(siteUrl('/arama') . '?q=' . urlencode($q) . '&tur=' . $kod, ENT_QUOTES, 'UTF-8') ?>"
     class="<?= $kod === $tur ? 'aktif' : '' ?>"><?= htmlspecialchars($etiket, ENT_QUOTES, 'UTF-8') ?></a>
  <?php endforeach; ?>
</div>
<?php if ($sonuc === null): ?>
<p>Aramak için en az 3 karakter yazın.</p>
<?php elseif (($sonuc['data']['toplam'] ?? 0) === 0): ?>
<p>Sonuç bulunamadı. <a href="<?= htmlspecialchars(siteUrl('/urunler'), ENT_QUOTES, 'UTF-8') ?>">Kamelya modelleri</a> sayfasına göz atın.</p>
<?php else: ?>
<?php foreach ($sonuc['data']['sonuclar'] as $s): ?>
<article class="card"><div class="card-govde">
  <p><small>[<?= htmlspecialchars($s['tur'] ?? '', ENT_QUOTES, 'UTF-8') ?>]</small></p>
  <h3><a href="<?= htmlspecialchars(siteUrl($s['yol'] ?? '/'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($s['baslik'] ?? '', ENT_QUOTES, 'UTF-8') ?></a></h3>
  <p><?= htmlspecialchars($s['ozet'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
</div></article>
<?php endforeach; ?>
<p><small>Toplam <?= (int) ($sonuc['data']['toplam'] ?? 0) ?> sonuç</small></p>
<?php endif; ?>
