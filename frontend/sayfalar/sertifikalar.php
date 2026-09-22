<?php

declare(strict_types=1);

/** Sertifikalar — logo + kurum + belge + modal detay. */

$liste = apiGet('/sertifikalar', $dil);
$satirlar = $liste['data'] ?? [];

$SEO = [
    'baslik' => 'Sertifikalarımız — CE, TÜV ve ISO Belgeleri — Kamelya',
    'aciklama' => 'Kamelya sertifika vitrini: CE, TÜV, ISO belgeleri, belge numaraları ve geçerlilik bilgileriyle üretim güvencesi sunar. Belgeler günceldir ve düzenli denetlenir.',
    'yol' => $MEVCUT_YOL,
];
?>
<?= kirinti([['etiket' => t('anasayfa'), 'yol' => siteUrl('/')], ['etiket' => t('hakkimizda_baslik'), 'yol' => siteUrl('/hakkimizda')], ['etiket' => 'Sertifikalar', 'yol' => null]]) ?>
<h1>Sertifikalarımız</h1>
<div class="izgara izgara-3">
  <?php foreach ($satirlar as $s): ?>
  <article class="card"><div class="card-govde">
    <h3><?= htmlspecialchars($s['kurum'] ?? '', ENT_QUOTES, 'UTF-8') ?> — <?= htmlspecialchars($s['baslik'] ?? '', ENT_QUOTES, 'UTF-8') ?></h3>
    <p>Belge No: <?= htmlspecialchars($s['belge_no'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
    <p>Geçerlilik: <?= htmlspecialchars($s['gecerlilik_tarihi'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
  </div></article>
  <?php endforeach; ?>
</div>
<?php if ($satirlar === []): ?>
<p><?= htmlspecialchars(t('urun_bos'), ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>
