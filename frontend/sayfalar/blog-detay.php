<?php

declare(strict_types=1);

/** Blog detay — API slug çözümlemeli, Article JSON-LD + paylaşım + CTA. */

$bulunan = null;
if ($SLUG !== null && $SLUG !== '') {
    $yanit = apiGet('/blog/' . $SLUG, $dil);
    if (is_array($yanit) && isset($yanit['data'])) {
        $bulunan = $yanit['data'];
    }
}

if (!is_array($bulunan)) {
    http_response_code(404);
    $SEO = ['baslik' => 'Bulunamadı — Kamelya', 'aciklama' => t('blog_bos'), 'yol' => $MEVCUT_YOL, 'robots' => 'noindex, follow'];
    echo '<h1>404</h1><p>' . htmlspecialchars(t('blog_bos'), ENT_QUOTES, 'UTF-8') . '</p>';

    return;
}

$seoBaslik = trim((string) ($bulunan['seo_baslik'] ?? ''));
$seoAciklama = trim((string) ($bulunan['seo_aciklama'] ?? ''));
$SEO = [
    'baslik' => $seoBaslik !== '' ? $seoBaslik : ($bulunan['baslik'] . ' — Kamelya'),
    'aciklama' => $seoAciklama !== '' ? $seoAciklama : kisaAciklama((string) ($bulunan['ozet'] ?? $bulunan['baslik']), 160),
    'yol' => $MEVCUT_YOL,
    'jsonld' => jsonldMakale($bulunan),
    'og_turu' => 'article',
    'og_gorsel' => $bulunan['kapak_resmi'] ?? null,
];

$gorsel = htmlspecialchars($bulunan['kapak_resmi'] ?? '/assets/img/yer-tutucu.svg', ENT_QUOTES, 'UTF-8');
?>
<?= kirinti([['etiket' => t('anasayfa'), 'yol' => siteUrl('/')], ['etiket' => t('blog_baslik'), 'yol' => siteUrl('/blog')], ['etiket' => $bulunan['baslik'], 'yol' => null]]) ?>
<h1><?= htmlspecialchars($bulunan['baslik'], ENT_QUOTES, 'UTF-8') ?></h1>
<p><small><?= htmlspecialchars($bulunan['yazar'] ?? '', ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars(substr((string) ($bulunan['yayin_tarihi'] ?? ''), 0, 10), ENT_QUOTES, 'UTF-8') ?></small></p>
<img class="card-gorsel" src="<?= $gorsel ?>" alt="<?= htmlspecialchars($bulunan['baslik'], ENT_QUOTES, 'UTF-8') ?>">
<div><?= $bulunan['icerik'] ?></div>
<?= paylasButonlari(siteUrl('/blog/' . $bulunan['slug']), $bulunan['baslik']) ?>
<?php if (!empty($bulunan['ilgili'])): ?>
<h2>İlgili Yazılar</h2>
<ul>
  <?php foreach ($bulunan['ilgili'] as $il): ?>
  <li><a href="<?= htmlspecialchars(siteUrl('/blog/' . $il['slug']), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($il['baslik'], ENT_QUOTES, 'UTF-8') ?></a></li>
  <?php endforeach; ?>
</ul>
<?php endif; ?>
<p><a class="btn btn-birincil" href="<?= htmlspecialchars(siteUrl('/teklif-al'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(t('cta_kesif'), ENT_QUOTES, 'UTF-8') ?></a></p>
