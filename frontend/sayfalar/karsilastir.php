<?php

declare(strict_types=1);

/** Karşılaştırma — API'den 2-4 ürün, farklar vurgulu. SEO: noindex. */

$SEO = [
    'baslik' => 'Ürün Karşılaştırma — Kamelya',
    'aciklama' => 'Kamelya modellerini yan yana karşılaştırın: fiyat, malzeme, model ve ölçü farkları vurgulu tabloda.',
    'yol' => $MEVCUT_YOL,
    'robots' => 'noindex, follow',
    'canonical' => siteUrl('/urunler'),
];
?>
<?= kirinti([['etiket' => t('anasayfa'), 'yol' => siteUrl('/')], ['etiket' => t('nav_urunler'), 'yol' => siteUrl('/urunler')], ['etiket' => 'Karşılaştır', 'yol' => null]]) ?>
<h1>Ürün Karşılaştırma</h1>
<div id="karsilastirma-alan" data-api="<?= htmlspecialchars($AYAR['api_taban'], ENT_QUOTES, 'UTF-8') ?>" data-dil="<?= htmlspecialchars($dil, ENT_QUOTES, 'UTF-8') ?>">
  <p>Yükleniyor…</p>
</div>
<script src="/assets/js/karsilastirma.js" defer></script>
