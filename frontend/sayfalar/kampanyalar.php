<?php

declare(strict_types=1);

/** Tüm aktif kampanyalar listesi. */

$liste = apiGet('/kampanyalar', $dil);
$satirlar = $liste['data'] ?? [];

$SEO = [
    'baslik' => 'Kampanyalar — Kamelya',
    'aciklama' => 'Kamelya güncel kampanya ve indirimleri: sınırlı süreli fırsatları kaçırmayın.',
    'yol' => $MEVCUT_YOL,
];
?>
<?= kirinti([['etiket' => t('anasayfa'), 'yol' => siteUrl('/')], ['etiket' => 'Kampanyalar', 'yol' => null]]) ?>
<h1>Kampanyalar</h1>
<div class="izgara izgara-2">
  <?php foreach ($satirlar as $k): ?>
  <article class="card"><div class="card-govde">
    <h3><?= htmlspecialchars($k['baslik'] ?? '', ENT_QUOTES, 'UTF-8') ?></h3>
    <p><?= htmlspecialchars($k['aciklama'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
    <p><small>Bitiş: <?= htmlspecialchars($k['bitis'] ?? '', ENT_QUOTES, 'UTF-8') ?></small></p>
  </div></article>
  <?php endforeach; ?>
</div>
<?php if ($satirlar === []): ?>
<p><?= htmlspecialchars(t('urun_bos'), ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>
