<?php

declare(strict_types=1);

/** Garanti — aktif dilde ayar metinleri (JSON 6-dil). */

$ayarlar = apiGet('/ayarlar', $dil);
$veri = $ayarlar['data'] ?? [];

$coz = function ($anahtar) use ($veri, $dil) {
    $ham = $veri[$anahtar] ?? null;
    if ($ham === null) {
        return '';
    }

    $cozum = json_decode((string) $ham, true);
    if (is_array($cozum)) {
        return (string) ($cozum[$dil] ?? $cozum['tr'] ?? '');
    }

    return (string) $ham;
};

$sure = (string) ($veri['garanti_suresi'] ?? '');
$kapsam = $coz('garanti_kapsami');
$istisna = $coz('garanti_istisnalari');

$SEO = [
    'baslik' => 'Garanti Koşulları — Süre, Kapsam ve İstisnalar — Kamelya',
    'aciklama' => $kapsam !== ''
        ? 'Kamelya garanti koşulları: süre, kapsam ve istisnalar. ' . kisaAciklama($kapsam, 100)
        : 'Kamelya garanti koşulları: süre, kapsam ve istisnalar hakkında detaylı bilgi. Sertifikalı üretim ve açık garanti taahhüdü. Sorularınız için bize ulaşın.',
    'yol' => $MEVCUT_YOL,
];
?>
<?= kirinti([['etiket' => t('anasayfa'), 'yol' => siteUrl('/')], ['etiket' => 'Garanti', 'yol' => null]]) ?>
<h1>Garanti Koşulları</h1>
<?php if ($sure !== ''): ?>
<p><strong><?= htmlspecialchars($sure, ENT_QUOTES, 'UTF-8') ?></strong></p>
<?php endif; ?>
<?php if ($kapsam !== ''): ?>
<h2>Kapsam</h2>
<p><?= htmlspecialchars($kapsam, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>
<?php if ($istisna !== ''): ?>
<h2>İstisnalar</h2>
<p><?= htmlspecialchars($istisna, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>
<?php if ($sure === '' && $kapsam === '' && $istisna === ''): ?>
<p><?= htmlspecialchars(t('urun_bos'), ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>
