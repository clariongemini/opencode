<?php

declare(strict_types=1);

/** 410 Gone — kapatılan özellik sayfası. */

$SEO = [
    'baslik' => 'Sayfa Kaldırıldı — Kamelya',
    'aciklama' => 'Bu sayfa şu an yayında değil.',
    'yol' => $MEVCUT_YOL,
    'robots' => 'noindex, follow',
];
?>
<h1>410 — Sayfa Yayında Değil</h1>
<p>Aradığınız bölüm şu an kapalıdır. <a href="<?= htmlspecialchars(siteUrl('/'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(t('anasayfa'), ENT_QUOTES, 'UTF-8') ?></a> sayfasına dönebilirsiniz.</p>
