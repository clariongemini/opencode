<?php

declare(strict_types=1);

$SEO = [
    'baslik' => t('hakkimizda_baslik') . ' — Kamelya',
    'aciklama' => kisaAciklama(t('hakkimizda_metin')),
    'yol' => $MEVCUT_YOL,
    'jsonld' => jsonldOrganizasyon(),
];
?>
<?= kirinti([['etiket' => t('anasayfa'), 'yol' => siteUrl('/')], ['etiket' => t('hakkimizda_baslik'), 'yol' => null]]) ?>
<h1><?= htmlspecialchars(t('hakkimizda_baslik'), ENT_QUOTES, 'UTF-8') ?></h1>
<p><?= htmlspecialchars(t('hakkimizda_metin'), ENT_QUOTES, 'UTF-8') ?></p>
<p><a class="btn btn-ikincil" href="<?= htmlspecialchars(siteUrl('/atolye'), ENT_QUOTES, 'UTF-8') ?>">Atölyemiz</a>
<a class="btn btn-ikincil" href="<?= htmlspecialchars(siteUrl('/ekibimiz'), ENT_QUOTES, 'UTF-8') ?>">Ekibimiz</a></p>
