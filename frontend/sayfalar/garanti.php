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

$sure = (string) $coz('garanti_suresi');
$kapsam = (string) $coz('garanti_kapsami');
$istisna = (string) $coz('garanti_istisnalari');

/** seo meta — 6 dil, byte bantlı (title 50–60 / desc 150–160). F16.2.8. */
$SEO_HAM = [
    'tr' => [
        'Garanti Koşulları — Süre, Kapsam, İstisnalar | Kamelya',
        'Kamelya garanti koşulları: 5 yıl süre, taşıyıcı iskelet ve montaj işçiliği kapsamı, afet ve kullanıcı hatası istisnaları ayrıca listelenir.',
    ],
    'en' => [
        'Warranty Terms — Duration, Coverage, Exclusions | Kamelya',
        'Kamelya warranty terms: 5-year duration, load-bearing frame, roof, railing and installation coverage; natural disaster and misuse exclusions listed clearly.',
    ],
    'de' => [
        'Garantie — Dauer, Umfang, Ausschlüsse | Kamelya',
        'Kamelya Garantie: 5 Jahre Laufzeit, tragendes Gerüst, Dacheindeckung, Geländer und Montage im Umfang; Naturkatastrophen und Fehlgebrauch ausgenommen.',
    ],
    'fr' => [
        'Garantie — Durée, couverture, exclusions | Kamelya',
        'Garantie Kamelya : durée de 5 ans, structure porteuse, couverture de toit, garde-corps et pose inclus ; catastrophes naturelles et mauvais usage exclus.',
    ],
    'it' => [
        'Garanzia — Durata, copertura, esclusioni | Kamelya',
        'Garanzia Kamelya: durata 5 anni, struttura portante, copertura del tetto, ringhiera e posa inclusi; calamità naturali e uso improprio esclusi dalla copertura.',
    ],
    'ar' => [
        'ضمان كاميليا: المدة والتغطية',
        'ضمان كاميليا: 5 سنوات تشمل الهيكل والسقف والتركيب؛ ويستثني الكوارث والاستخدام الخاطئ.',
    ],
];
$seoHam = $SEO_HAM[$dil] ?? $SEO_HAM['tr'];

$SEO = [
    'baslik' => $seoHam[0],
    'aciklama' => $seoHam[1],
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
