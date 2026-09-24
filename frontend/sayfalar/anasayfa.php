<?php

declare(strict_types=1);

/** Ana sayfa: hero + öne çıkanlar + hesap aracı + USP + galeri/referans/blog/SSS önizleme. */

$oneCikan = apiGet('/products', $dil, ['per_page' => '3']);
$urunler = $oneCikan['data'] ?? [];

$TANITIM = [
    'tr' => 'Kamelya; bahçe, site, restoran, otel ve belediye alanları için sipariş üzerine kamelya üretir. Tüm modellerde şeffaf m² fiyatı, ücretsiz keşif ve sertifikalı montaj sunuyoruz.',
    'en' => 'Kamelya builds made-to-order gazebos for gardens, residential sites, restaurants, hotels and municipalities — with transparent per-m² pricing, free surveys and certified installation.',
    'de' => 'Kamelya fertigt Pavillons nach Maß für Gärten, Wohnanlagen, Restaurants, Hotels und Kommunen — mit transparentem m²-Preis, kostenloser Beratung und zertifizierter Montage.',
    'fr' => 'Kamelya fabrique des tonnelles sur commande pour jardins, résidences, restaurants, hôtels et municipalités — prix transparent au m², visite gratuite et pose certifiée.',
    'it' => 'Kamelya produce gazebo su ordinazione per giardini, residenze, ristoranti, hotel e comuni — prezzo trasparente al m², sopralluogo gratuito e posa certificata.',
    'ar' => 'تصنع كاميليا الكوش عند الطلب للحدائق والمجمعات السكنية والمطاعم والفنادق والبلديات — بسعر شفاف للمتر المربع ومعاينة مجانية وتركيب معتمد.',
];

$SEO_TR = [
    'tr' => ['Bahçenize Özel Ahşap Kamelya ve m² Fiyatları — Kamelya', 'Şeffaf m² fiyatı ve ücretsiz keşif ile sipariş üzerine ahşap kamelya üretiyoruz. Bahçe, site, restoran, otel ve belediye alanlarına sertifikalı montaj yapılır.'],
    'en' => ['Custom Wooden Garden Gazebo Models and Prices — Kamelya', 'Transparent per-m² pricing and free surveys for made-to-order wooden gazebos, with certified installation for gardens, sites, restaurants and hotels. Trusted.'],
    'de' => ['Holzpavillon-Modelle nach Maß mit m²-Preisen — Kamelya', 'Transparenter m²-Preis und kostenlose Beratung für Holzpavillons nach Maß, mit zertifizierter Montage für Gärten, Anlagen, Restaurants und Hotels. Garantiert.'],
    'fr' => ['Tonnelle en bois sur mesure et prix au m² — Kamelya', 'Prix transparent au m² et visite gratuite pour tonnelles en bois sur commande, avec pose certifiée pour jardins, résidences, restaurants et hôtels. Garanti.'],
    'it' => ['Gazebo in legno su misura e prezzi al m² — Kamelya', 'Prezzo trasparente al m² e sopralluogo gratuito per gazebo in legno su ordinazione, con posa certificata per giardini, residenze, ristoranti, hotel e comuni.'],
    'ar' => ['كوش خشبي مخصص لحديقتك | الموديلات والأسعار — كاميليا', 'سعر شفاف للمتر المربع ومعاينة مجانية وتركيب سريع. ننتج الكوش عند الطلب للحدائق والمجمعات السكنية والمطاعم والفنادق والبلديات بتركيب معتمد وبجودة عالية.'],
];

$seoEv = $SEO_TR[$dil] ?? $SEO_TR['tr'];

$SEO = [
    'baslik' => $seoEv[0],
    'aciklama' => $seoEv[1],
    'yol' => $MEVCUT_YOL,
    'jsonld' => jsonldOrganizasyon(),
];

$kartlar = '';
foreach ($urunler as $urun) {
    $kartlar .= urunKarti($urun);
}

if ($kartlar === '') {
    $kartlar = '<p>' . htmlspecialchars(t('urun_bos'), ENT_QUOTES, 'UTF-8') . '</p>';
}

$sssYanit = apiGet('/sss-sorulari', $dil);
$sss = [];
foreach (($sssYanit['data'] ?? []) as $sssSatir) {
    $sss[] = [
        'soru' => (string) ($sssSatir['soru'] ?? ''),
        'cevap' => (string) ($sssSatir['cevap'] ?? ''),
    ];
    if (count($sss) >= 3) {
        break;
    }
}
if ($sss === []) {
    $sss = [
        ['soru' => t('sss_baslik') . ' — 1', 'cevap' => t('usp_fiyat_alt')],
        ['soru' => t('sss_baslik') . ' — 2', 'cevap' => t('usp_kesif_alt')],
        ['soru' => t('sss_baslik') . ' — 3', 'cevap' => t('usp_garanti_alt')],
    ];
}
?>
<section class="hero">
  <div class="kapsayici">
    <h1><?= htmlspecialchars(t('hero_baslik'), ENT_QUOTES, 'UTF-8') ?></h1>
    <p><?= htmlspecialchars(t('hero_alt'), ENT_QUOTES, 'UTF-8') ?></p>
    <p>
      <a class="btn btn-birincil" href="<?= htmlspecialchars(siteUrl('/teklif-al'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(t('cta_kesif'), ENT_QUOTES, 'UTF-8') ?></a>
      <a class="btn btn-ikincil" href="<?= htmlspecialchars(siteUrl('/urunler'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(t('cta_urunler'), ENT_QUOTES, 'UTF-8') ?></a>
    </p>
  </div>
</section>

<?php if (etkinMi('urunler')): ?>
<section>
  <h2><?= htmlspecialchars(t('one_cikan'), ENT_QUOTES, 'UTF-8') ?></h2>
  <p><?= htmlspecialchars($TANITIM[$dil] ?? $TANITIM['tr'], ENT_QUOTES, 'UTF-8') ?></p>
  <div class="izgara izgara-3"><?= $kartlar ?></div>
</section>
<?php endif; ?>

<?php if (etkinMi('urun_hesaplama')): ?>
<?= hesapAraci() ?>
<?php endif; ?>

<section>
  <h2><?= htmlspecialchars(t('neden_baslik'), ENT_QUOTES, 'UTF-8') ?></h2>
  <div class="izgara izgara-3">
    <div class="card"><div class="card-govde">
      <h3><?= htmlspecialchars(t('usp_fiyat'), ENT_QUOTES, 'UTF-8') ?></h3>
      <p><?= htmlspecialchars(t('usp_fiyat_alt'), ENT_QUOTES, 'UTF-8') ?></p>
    </div></div>
    <div class="card"><div class="card-govde">
      <h3><?= htmlspecialchars(t('usp_kesif'), ENT_QUOTES, 'UTF-8') ?></h3>
      <p><?= htmlspecialchars(t('usp_kesif_alt'), ENT_QUOTES, 'UTF-8') ?></p>
    </div></div>
    <div class="card"><div class="card-govde">
      <h3><?= htmlspecialchars(t('usp_garanti'), ENT_QUOTES, 'UTF-8') ?></h3>
      <p><?= htmlspecialchars(t('usp_garanti_alt'), ENT_QUOTES, 'UTF-8') ?></p>
    </div></div>
  </div>
</section>

<?= sertifikaBand() ?>

<?php if (etkinMi('referanslar')): ?>
<section>
  <h2><?= htmlspecialchars(t('referans_baslik'), ENT_QUOTES, 'UTF-8') ?></h2>
  <p><?= htmlspecialchars(t('referans_metin'), ENT_QUOTES, 'UTF-8') ?></p>
</section>

<?= oneCikanYorumlar() ?>
<?php endif; ?>
<script src="/assets/js/karsilastirma.js" defer></script>

<?php if (etkinMi('sss')): ?>
<section>
  <h2><?= htmlspecialchars(t('sss_baslik'), ENT_QUOTES, 'UTF-8') ?></h2>
  <?= sssAkordeon($sss) ?>
</section>
<?php endif; ?>
