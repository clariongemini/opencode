<?php

declare(strict_types=1);

/** Galeri — görsel havuzu API'si F7'de; V1'de boş-durum + CTA (uydurma yok). */

$SEO = [
    'baslik' => t('galeri_baslik') . ' — Kamelya',
    'aciklama' => t('galeri_baslik') . ': ' . t('referans_metin'),
    'yol' => $MEVCUT_YOL,
];
?>
<?= kirinti([['etiket' => t('anasayfa'), 'yol' => siteUrl('/')], ['etiket' => t('galeri_baslik'), 'yol' => null]]) ?>
<h1><?= htmlspecialchars(t('galeri_baslik'), ENT_QUOTES, 'UTF-8') ?></h1>
<p><?= htmlspecialchars(t('referans_metin'), ENT_QUOTES, 'UTF-8') ?></p>
<p><?= htmlspecialchars(t('urun_bos'), ENT_QUOTES, 'UTF-8') ?></p>
<p><a class="btn btn-birincil" href="<?= htmlspecialchars(siteUrl('/teklif-al'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(t('cta_kesif'), ENT_QUOTES, 'UTF-8') ?></a></p>
<?= instagramBolumu() ?>
