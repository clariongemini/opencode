<?php

declare(strict_types=1);

/** Atölye — grid galeri (lightbox yerine yeni sekme). */

$liste = apiGet('/atolye', $dil);
$fotograflar = $liste['data'] ?? [];

$SEO = [
    'baslik' => 'Atölyemiz — Üretim Tesisi ve İmalat Kareleri — Kamelya',
    'aciklama' => 'Kamelya atölyesinden kareler: üretim tesisi, ustalar ve imalat aşamaları. Sipariş üzerine üretim sürecini yerinde görün, ekibimizle tanışın. Ziyarete bekleriz.',
    'yol' => $MEVCUT_YOL,
];

$koku = rtrim($AYAR['api_taban'], '/');
$dosyaUrl = function (string $yol) use ($koku): string {
    $parcalar = explode('/', $yol);
    return $koku . '/dosyalar/atolye/' . end($parcalar);
};
?>
<?= kirinti([['etiket' => t('anasayfa'), 'yol' => siteUrl('/')], ['etiket' => t('hakkimizda_baslik'), 'yol' => siteUrl('/hakkimizda')], ['etiket' => 'Atölye', 'yol' => null]]) ?>
<h1>Atölyemiz</h1>
<div class="izgara-galeri">
  <?php foreach ($fotograflar as $f): ?>
  <a href="<?= htmlspecialchars($dosyaUrl((string) ($f['dosya_yolu'] ?? '')), ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
    <img src="<?= htmlspecialchars($dosyaUrl((string) ($f['dosya_yolu'] ?? '')), ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($f['baslik'] ?? '', ENT_QUOTES, 'UTF-8') ?>" loading="lazy">
  </a>
  <?php endforeach; ?>
</div>
<?php if ($fotograflar === []): ?>
<p><?= htmlspecialchars(t('urun_bos'), ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>
