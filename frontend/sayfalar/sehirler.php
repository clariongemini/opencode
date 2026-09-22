<?php

declare(strict_types=1);

/** Şehir dizini — tüm aktif şehirler. */

$liste = apiGet('/sehirler', $dil);
$sehirler = $liste['data'] ?? [];

$SEO = [
    'baslik' => 'Hizmet Bölgelerimiz — Şehir Bazında Kamelya — Kamelya',
    'aciklama' => 'Kamelya hizmet bölgeleri: İstanbul, Ankara, İzmir ve çevresinde kamelya fiyatları, ücretsiz keşif ve hızlı montaj imkanları burada. Ücretsiz keşif alın.',
    'yol' => $MEVCUT_YOL,
];
?>
<?= kirinti([['etiket' => t('anasayfa'), 'yol' => siteUrl('/')], ['etiket' => 'Bölgeler', 'yol' => null]]) ?>
<h1>Hizmet Bölgelerimiz</h1>
<ul>
  <?php foreach ($sehirler as $s): ?>
  <li><a href="<?= htmlspecialchars(siteUrl('/kamelya-fiyatlari/' . ($s['slug'] ?? $s['kod'])), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($s['ad'], ENT_QUOTES, 'UTF-8') ?></a></li>
  <?php endforeach; ?>
</ul>
