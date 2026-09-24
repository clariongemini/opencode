<?php

declare(strict_types=1);

/** Şehir landing — hesap aracı + referans + yerel SSS + CTA + LocalBusiness. */

$bulunan = null;
if ($SLUG !== null && $SLUG !== '') {
    $yanit = apiGet('/sehirler/' . $SLUG, $dil);
    if (is_array($yanit) && isset($yanit['data'])) {
        $bulunan = $yanit['data'];
    }
}

if (!is_array($bulunan)) {
    http_response_code(404);
    $SEO = ['baslik' => 'Bulunamadı — Kamelya', 'aciklama' => t('urun_bos'), 'yol' => $MEVCUT_YOL, 'robots' => 'noindex, follow'];
    echo '<h1>404</h1><p>' . htmlspecialchars(t('urun_bos'), ENT_QUOTES, 'UTF-8') . '</p>';

    return;
}

$sehirAd = (string) ($bulunan['ad'] ?? '');
// SEO meta: DB bandlı alanları tercih et (title 50-60 / desc 150-160 byte); seo_baslik tam (marka eki dahil).
$seoBaslik = trim((string) ($bulunan['seo_baslik'] ?? ''));
$seoAciklama = trim((string) ($bulunan['seo_aciklama'] ?? ''));
$SEO = [
    'baslik' => $seoBaslik !== '' ? $seoBaslik : $sehirAd . ' Kamelya Fiyatları | Kamelya',
    'aciklama' => $seoAciklama !== '' ? $seoAciklama : kisaAciklama((string) ($bulunan['icerik'] ?? ''), 160),
    'yol' => $MEVCUT_YOL,
    'jsonld' => [
        '@context' => 'https://schema.org',
        '@type' => 'LocalBusiness',
        'name' => 'Kamelya ' . $sehirAd,
        'address' => ['@type' => 'PostalAddress', 'addressLocality' => $sehirAd, 'addressCountry' => 'TR'],
        'url' => siteUrl($MEVCUT_YOL),
    ],
];
$h1Metin = $seoBaslik !== '' ? $seoBaslik : $sehirAd . ' Kamelya Fiyatları | Kamelya';
?>
<?= kirinti([['etiket' => t('anasayfa'), 'yol' => siteUrl('/')], ['etiket' => 'Kamelya Fiyatları', 'yol' => null], ['etiket' => $sehirAd, 'yol' => null]]) ?>
<h1><?= htmlspecialchars($h1Metin, ENT_QUOTES, 'UTF-8') ?></h1>
<?php if (!empty($bulunan['icerik'])): ?>
<p><?= htmlspecialchars($bulunan['icerik'], ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>
<?= hesapAraci() ?>
<?php if (!empty($bulunan['bolgeler'])): ?>
<h2>Hizmet Bölgeleri</h2>
<ul>
  <?php foreach ($bulunan['bolgeler'] as $b): ?>
  <li><?= htmlspecialchars($b, ENT_QUOTES, 'UTF-8') ?></li>
  <?php endforeach; ?>
</ul>
<?php endif; ?>
<p><a class="btn btn-birincil" href="<?= htmlspecialchars(siteUrl('/teklif-al'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($sehirAd, ENT_QUOTES, 'UTF-8') ?>'da <?= htmlspecialchars(t('cta_kesif'), ENT_QUOTES, 'UTF-8') ?></a></p>
