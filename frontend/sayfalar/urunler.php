<?php

declare(strict_types=1);

/** Ürün listesi: Model/Malzeme/Kullanım filtreleri + kartlar (API). */

$etiketler = [
    'model' => ['kare' => 'Kare', 'altigen' => 'Altıgen', 'dikdortgen' => 'Dikdörtgen', 'modern' => 'Modern', 'klasik' => 'Klasik'],
    'malzeme' => ['ahsap' => 'Ahşap', 'aluminyum' => 'Alüminyum', 'kompozit' => 'Kompozit'],
    'kullanim' => ['site_bahcesi' => 'Site Bahçesi', 'restoran' => 'Restoran', 'otel' => 'Otel', 'belediye' => 'Belediye'],
];

$filtreAdlari = ['model' => 'filter[model]', 'malzeme' => 'filter[material]', 'kullanim' => 'filter[usage]'];
$secili = [
    'model' => $_GET['filter']['model'] ?? '',
    'malzeme' => $_GET['filter']['material'] ?? '',
    'kullanim' => $_GET['filter']['usage'] ?? '',
];

$apiFiltre = [];
if ($secili['model'] !== '') {
    $apiFiltre['model'] = $secili['model'];
}

if ($secili['malzeme'] !== '') {
    $apiFiltre['material'] = $secili['malzeme'];
}

if ($secili['kullanim'] !== '') {
    $apiFiltre['usage'] = $secili['kullanim'];
}

$sonuc = apiGet('/products', $dil, array_merge($apiFiltre, ['per_page' => '20']));
$urunler = $sonuc['data'] ?? [];
$toplam = $sonuc['meta']['total'] ?? count($urunler);

$SEO = [
    'baslik' => t('nav_urunler') . ' Modelleri ve Şeffaf m² Fiyatları — Kamelya',
    'aciklama' => 'Kamelya modellerini m² başlangıç fiyatlarıyla inceleyin: kare, altıgen, modern; ahşap, alüminyum, kompozit. Ücretsiz keşif ve hızlı montaj ile üretim.',
    'yol' => $MEVCUT_YOL,
];

if ($urunler !== []) {
    $ogeListesi = [];
    foreach ($urunler as $i => $urun) {
        $ogeListesi[] = [
            '@type' => 'ListItem',
            'position' => $i + 1,
            'url' => siteUrl('/urun/' . $urun['slug']),
            'name' => $urun['baslik'],
        ];
    }

    $SEO['jsonld'] = ['@context' => 'https://schema.org', '@type' => 'ItemList', 'itemListElement' => $ogeListesi];
}

$secenek = function (string $tur, string $ad, string $seciliDeger, string $baslik) use ($etiketler): string {
    $cikti = '<div class="form-alan"><label class="etiket" for="f-' . $tur . '">' . htmlspecialchars($baslik, ENT_QUOTES, 'UTF-8') . '</label>';
    $cikti .= '<select class="input" id="f-' . $tur . '" name="' . htmlspecialchars($ad, ENT_QUOTES, 'UTF-8') . '">';
    $cikti .= '<option value="">' . htmlspecialchars(t('urun_filtre_tumu'), ENT_QUOTES, 'UTF-8') . '</option>';
    foreach ($etiketler[$tur] as $kod => $etiket) {
        $sec = $kod === $seciliDeger ? ' selected' : '';
        $cikti .= '<option value="' . $kod . '"' . $sec . '>' . htmlspecialchars($etiket, ENT_QUOTES, 'UTF-8') . '</option>';
    }

    return $cikti . '</select></div>';
};

$kartlar = '';
foreach ($urunler as $urun) {
    $kartlar .= urunKarti($urun);
}

if ($kartlar === '') {
    $kartlar = '<p>' . htmlspecialchars(t('urun_bos'), ENT_QUOTES, 'UTF-8') . '</p>';
}
?>
<?= kirinti([['etiket' => t('anasayfa'), 'yol' => siteUrl('/')], ['etiket' => t('nav_urunler'), 'yol' => null]]) ?>
<h1><?= htmlspecialchars(t('nav_urunler'), ENT_QUOTES, 'UTF-8') ?></h1>
<form method="get" action="">
  <div class="izgara izgara-3">
    <?= $secenek('model', $filtreAdlari['model'], $secili['model'], t('urun_filtre_model')) ?>
    <?= $secenek('malzeme', $filtreAdlari['malzeme'], $secili['malzeme'], t('urun_filtre_malzeme')) ?>
    <?= $secenek('kullanim', $filtreAdlari['kullanim'], $secili['kullanim'], t('urun_filtre_kullanim')) ?>
  </div>
  <p><button class="btn btn-birincil" type="submit"><?= htmlspecialchars(t('urun_filtre_tumu'), ENT_QUOTES, 'UTF-8') ?></button></p>
</form>
<p><small>(<?= (int) $toplam ?>)</small></p>
<h2><?= htmlspecialchars(t('one_cikan'), ENT_QUOTES, 'UTF-8') ?></h2>
<div class="izgara izgara-3"><?= $kartlar ?></div>
<script src="/assets/js/karsilastirma.js" defer></script>
