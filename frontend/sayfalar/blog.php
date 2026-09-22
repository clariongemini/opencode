<?php

declare(strict_types=1);

/** Blog dizini — API listesi + kartlar; boşsa rehber bağlantıları. */

$liste = apiGet('/blog', $dil, ['limit' => '20']);
$yazilar = $liste['data'] ?? [];

$SEO = [
    'baslik' => t('blog_baslik') . ' — Kamelya',
    'aciklama' => t('blog_baslik') . ': ' . t('bakim_baslik') . ', ' . t('malzeme_baslik'),
    'yol' => $MEVCUT_YOL,
];
?>
<?= kirinti([['etiket' => t('anasayfa'), 'yol' => siteUrl('/')], ['etiket' => t('blog_baslik'), 'yol' => null]]) ?>
<h1><?= htmlspecialchars(t('blog_baslik'), ENT_QUOTES, 'UTF-8') ?></h1>
<?php if ($yazilar === []): ?>
<p><?= htmlspecialchars(t('blog_bos'), ENT_QUOTES, 'UTF-8') ?></p>
<?php else: ?>
<div class="izgara izgara-3">
  <?php foreach ($yazilar as $y): ?>
  <article class="card"><div class="card-govde">
    <h3><a href="<?= htmlspecialchars(siteUrl('/blog/' . $y['slug']), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($y['baslik'], ENT_QUOTES, 'UTF-8') ?></a></h3>
    <p><?= htmlspecialchars($y['ozet'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
  </div></article>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<ul>
  <li><a href="<?= htmlspecialchars(siteUrl('/rehberler/bakim'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(t('bakim_baslik'), ENT_QUOTES, 'UTF-8') ?></a></li>
  <li><a href="<?= htmlspecialchars(siteUrl('/rehberler/malzeme-karsilastirma'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(t('malzeme_baslik'), ENT_QUOTES, 'UTF-8') ?></a></li>
</ul>
