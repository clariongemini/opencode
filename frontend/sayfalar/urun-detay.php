<?php

declare(strict_types=1);

/**
 * Ürün detay — API'de slug ucu olmadığı için (backend'e dokunulmaz) liste
 * eşleşmesiyle çözülür; tam çeviri/görsel/SEO birleşimi F7'de slug ucuyla.
 */

$bulunan = null;
$liste = apiGet('/products', $dil, ['per_page' => '100']);
foreach ($liste['data'] ?? [] as $urun) {
    if (($urun['slug'] ?? '') === $SLUG) {
        $bulunan = $urun;

        break;
    }
}

if ($bulunan === null) {
    http_response_code(404);
    $SEO = ['baslik' => 'Bulunamadı — Kamelya', 'aciklama' => t('urun_bos'), 'yol' => $MEVCUT_YOL, 'robots' => 'noindex, follow'];
    echo '<h1>404</h1><p>' . htmlspecialchars(t('urun_bos'), ENT_QUOTES, 'UTF-8') . '</p>';

    return;
}

// Tam detay (teknik_detaylar dahil) id üzerinden çekilir.
$detayYanit = apiGet('/products/' . (int) $bulunan['id'], $dil);
if (is_array($detayYanit) && isset($detayYanit['data']) && is_array($detayYanit['data'])) {
    $bulunan = array_merge($bulunan, $detayYanit['data']);
}

$BILGI = [
    'tr' => 'Bu model sipariş üzerine üretilir. Net ölçü ve montaj planı ücretsiz keşif sonrası belirlenir; randevular Pzt–Cmt 09:00–18:00 arasındadır.',
    'en' => 'This model is made to order. Final dimensions and the installation plan are fixed after the free survey; appointments run Mon–Sat 09:00–18:00.',
    'de' => 'Dieses Modell wird auf Bestellung gefertigt. Maße und Montageplan werden nach der kostenlosen Beratung fixiert; Termine Mo–Sa 09:00–18:00 Uhr.',
    'fr' => 'Ce modèle est fabriqué sur commande. Dimensions et plan de pose fixés après la visite gratuite ; rendez-vous Lun–Sam 09h00–18h00.',
    'it' => 'Questo modello è prodotto su ordinazione. Misure e piano di posa fissati dopo il sopralluogo gratuito; appuntamenti Lun–Sab 09:00–18:00.',
    'ar' => 'يُصنع هذا الموديل عند الطلب. تُحدد المقاسات وخطة التركيب بعد المعاينة المجانية؛ المواعيد من الاثنين إلى السبت 09:00–18:00.',
];

$kisa = trim((string) ($bulunan['kisa_aciklama'] ?? ''));
$aciklamaSeo = mb_strlen($kisa) >= 120 ? $kisa : trim($kisa . ' ' . ($BILGI[$dil] ?? $BILGI['tr']));

$ozetYanit = apiGet('/yorumlar/ozet', $dil, ['urun_id' => (string) $bulunan['id']]);
$derecelendirme = is_array($ozetYanit) && isset($ozetYanit['data']) && is_array($ozetYanit['data']) ? $ozetYanit['data'] : null;

// SEO meta: DB bandlı alanları tercih et (50-60 / 150-160 byte); yoksa eski türev.
$seoBaslik = trim((string) ($bulunan['seo_baslik'] ?? ''));
$seoAciklama = trim((string) ($bulunan['seo_aciklama'] ?? ''));
$SEO = [
    'baslik' => $seoBaslik !== '' ? $seoBaslik : $bulunan['baslik'] . ' — Kamelya',
    'aciklama' => $seoAciklama !== '' ? $seoAciklama : kisaAciklama($aciklamaSeo, 160),
    'yol' => $MEVCUT_YOL,
    'jsonld' => jsonldUrun($bulunan, $dil, $derecelendirme),
];

$gorsel = htmlspecialchars($bulunan['kapak_resmi'] ?? '/assets/img/yer-tutucu.svg', ENT_QUOTES, 'UTF-8');
$videoGomme = '';
$gorsel360 = null;
foreach ($bulunan['resimler'] ?? [] as $r) {
    if (($r['tur'] ?? 'normal') === 'video' && !empty($r['dosya_yolu'])) {
        $videoGomme .= '<iframe src="' . htmlspecialchars($r['dosya_yolu'], ENT_QUOTES, 'UTF-8') . '" width="100%" height="360" loading="lazy" title="Ürün videosu"></iframe>';
    }

    if (($r['tur'] ?? '') === '360' && $gorsel360 === null && !empty($r['dosya_yolu'])) {
        $gorsel360 = $r['dosya_yolu'];
    }
}
$rozet = '';
if (isset($bulunan['price_hint']) && is_array($bulunan['price_hint'])) {
    $rozet = '<p><span class="rozet">' . htmlspecialchars((string) $bulunan['price_hint']['base_m2'], ENT_QUOTES, 'UTF-8')
        . ' ' . htmlspecialchars((string) $bulunan['price_hint']['currency'], ENT_QUOTES, 'UTF-8')
        . '/m²</span> <small>' . htmlspecialchars(t('urun_fiyat_notu'), ENT_QUOTES, 'UTF-8') . '</small></p>';
}
?>
<?= kirinti([['etiket' => t('anasayfa'), 'yol' => siteUrl('/')], ['etiket' => t('nav_urunler'), 'yol' => siteUrl('/urunler')], ['etiket' => $bulunan['baslik'], 'yol' => null]]) ?>
<h1><?= htmlspecialchars($bulunan['baslik'], ENT_QUOTES, 'UTF-8') ?></h1>
<img class="card-gorsel" src="<?= $gorsel ?>" alt="<?= htmlspecialchars($bulunan['baslik'], ENT_QUOTES, 'UTF-8') ?>">
<?php if ($gorsel360 !== null): ?>
<p><button type="button" class="btn btn-ikincil" data-viewer360="<?= htmlspecialchars($gorsel360, ENT_QUOTES, 'UTF-8') ?>">360° Görüntüle</button></p>
<?php endif; ?>
<?= $videoGomme ?>
<?= $rozet ?>
<h2><?= htmlspecialchars(t('detay_ozellikler'), ENT_QUOTES, 'UTF-8') ?></h2>
<ul>
  <li><?= htmlspecialchars($bulunan['model'] ?? '', ENT_QUOTES, 'UTF-8') ?></li>
  <li><?= htmlspecialchars($bulunan['malzeme'] ?? '', ENT_QUOTES, 'UTF-8') ?></li>
  <li><?= htmlspecialchars($bulunan['kullanim'] ?? '', ENT_QUOTES, 'UTF-8') ?></li>
</ul>
<?php $teknik = $bulunan['teknik_detaylar'] ?? []; ?>
<table class="card"><tbody>
  <tr><th scope="row">Genişlik / Derinlik / Alan</th><td><?= htmlspecialchars(trim(($teknik['genislik'] ?? '') . ' / ' . ($teknik['derinlik'] ?? '') . ' / ' . ($teknik['alan'] ?? ''), ' /'), ENT_QUOTES, 'UTF-8') ?></td></tr>
  <tr><th scope="row">Çatı Tipi</th><td><?= htmlspecialchars($teknik['cati_tipi'] ?? '-', ENT_QUOTES, 'UTF-8') ?><?= !empty($teknik['cati_tipi_aciklama']) ? ' — ' . htmlspecialchars($teknik['cati_tipi_aciklama'], ENT_QUOTES, 'UTF-8') : '' ?></td></tr>
  <tr><th scope="row">Korkuluk</th><td><?= htmlspecialchars($teknik['korkuluk_malzeme'] ?? '-', ENT_QUOTES, 'UTF-8') ?><?= !empty($teknik['korkuluk_yukseklik_cm']) ? ' (' . htmlspecialchars((string) $teknik['korkuluk_yukseklik_cm'], ENT_QUOTES, 'UTF-8') . ' cm)' : '' ?><?= !empty($teknik['korkuluk_aciklama']) ? ' — ' . htmlspecialchars($teknik['korkuluk_aciklama'], ENT_QUOTES, 'UTF-8') : '' ?></td></tr>
</tbody></table>
<p><?= htmlspecialchars((string) ($bulunan['kisa_aciklama'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
<p><?= htmlspecialchars($BILGI[$dil] ?? $BILGI['tr'], ENT_QUOTES, 'UTF-8') ?></p>
<p><a class="btn btn-birincil" href="<?= htmlspecialchars(siteUrl('/teklif-al'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(t('cta_kesif'), ENT_QUOTES, 'UTF-8') ?></a>
<a href="<?= htmlspecialchars(siteUrl('/garanti'), ENT_QUOTES, 'UTF-8') ?>">Garanti Koşulları</a>
<a href="<?= htmlspecialchars(siteUrl('/sicaklik-simulasyonu'), ENT_QUOTES, 'UTF-8') ?>">Gölge Simülasyonu</a></p>
<?= yorumBolumu((int) $bulunan['id']) ?>
<script src="/assets/js/viewer-360.js" defer></script>
