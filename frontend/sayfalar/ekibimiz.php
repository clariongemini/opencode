<?php

declare(strict_types=1);

/** Ekibimiz — Person JSON-LD ile kart görünümü. */

$liste = apiGet('/ekip', $dil);
$uyeler = $liste['data'] ?? [];

$kisiListesi = [];
foreach ($uyeler as $i => $u) {
    $kisiListesi[] = [
        '@type' => 'ListItem',
        'position' => $i + 1,
        'item' => ['@type' => 'Person', 'name' => $u['ad_soyad'], 'jobTitle' => $u['unvan']],
    ];
}

$SEO = [
    'baslik' => 'Ekibimiz — Usta, Marangoz ve Montaj Kadrosu — Kamelya',
    'aciklama' => 'Kamelya ekibiyle tanışın: usta, marangoz ve montaj kadrosunun unvan, uzmanlık ve deneyim bilgileriyle güven veren hizmet anlayışı. Tanışmaya bekleriz.',
    'yol' => $MEVCUT_YOL,
    'jsonld' => ['@context' => 'https://schema.org', '@type' => 'ItemList', 'itemListElement' => $kisiListesi],
];
?>
<?= kirinti([['etiket' => t('anasayfa'), 'yol' => siteUrl('/')], ['etiket' => t('hakkimizda_baslik'), 'yol' => siteUrl('/hakkimizda')], ['etiket' => 'Ekibimiz', 'yol' => null]]) ?>
<h1>Ekibimiz</h1>
<div class="izgara izgara-3">
  <?php foreach ($uyeler as $u): ?>
  <article class="card"><div class="card-govde">
    <h3><?= htmlspecialchars($u['ad_soyad'] ?? '', ENT_QUOTES, 'UTF-8') ?></h3>
    <p><strong><?= htmlspecialchars($u['unvan'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong></p>
    <p><?= htmlspecialchars($u['uzmanlik_alani'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
    <p><?= htmlspecialchars($u['biyografi'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
  </div></article>
  <?php endforeach; ?>
</div>
<?php if ($uyeler === []): ?>
<p><?= htmlspecialchars(t('urun_bos'), ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>
