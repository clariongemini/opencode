<?php

declare(strict_types=1);

/** Referanslar — gerçek proje havuzu F7'de; V1'de tanıtım + CTA (uydurma yok). */

$SEO = [
    'baslik' => t('referans_baslik') . ' — Kamelya',
    'aciklama' => t('referans_metin'),
    'yol' => $MEVCUT_YOL,
];
?>
<?= kirinti([['etiket' => t('anasayfa'), 'yol' => siteUrl('/')], ['etiket' => t('referans_baslik'), 'yol' => null]]) ?>
<h1><?= htmlspecialchars(t('referans_baslik'), ENT_QUOTES, 'UTF-8') ?></h1>
<p><?= htmlspecialchars(t('referans_metin'), ENT_QUOTES, 'UTF-8') ?></p>
<p><?= htmlspecialchars(t('urun_bos'), ENT_QUOTES, 'UTF-8') ?></p>
<?php
$donusumler = apiGet('/donusumler', $dil);
$videolar = apiGet('/video-referanslar', $dil);
?>
<?php if (!empty($donusumler['data'])): ?>
<h2>Öncesi / Sonrası</h2>
<?php foreach ($donusumler['data'] as $dn): ?>
<div class="card"><div class="card-govde">
  <h3><?= htmlspecialchars($dn['baslik'] ?? '', ENT_QUOTES, 'UTF-8') ?></h3>
  <div data-oncesi-sonrasi style="position:relative;overflow:hidden">
    <img src="<?= htmlspecialchars($dn['sonrasi_gorsel'] ?? '', ENT_QUOTES, 'UTF-8') ?>" alt="Sonrası" loading="lazy" style="width:100%">
    <img src="<?= htmlspecialchars($dn['oncesi_gorsel'] ?? '', ENT_QUOTES, 'UTF-8') ?>" alt="Öncesi" loading="lazy" style="position:absolute;inset:0;width:100%">
  </div>
</div></div>
<?php endforeach; ?>
<script src="/assets/js/oncesi-sonrasi.js" defer></script>
<?php endif; ?>
<?php if (!empty($videolar['data'])): ?>
<h2>Video Referanslar</h2>
<div class="izgara izgara-3">
  <?php foreach ($videolar['data'] as $v): ?>
  <div class="card"><div class="card-govde">
    <p><strong><?= htmlspecialchars($v['musteri_adi'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong></p>
    <p><button type="button" class="btn btn-ikincil" data-video-ac="<?= htmlspecialchars($v['embed_url'] ?? $v['video_url'], ENT_QUOTES, 'UTF-8') ?>">İzle</button></p>
  </div></div>
  <?php endforeach; ?>
</div>
<script src="/assets/js/video-referans.js" defer></script>
<?php endif; ?>
<p><a class="btn btn-birincil" href="<?= htmlspecialchars(siteUrl('/teklif-al'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(t('cta_kesif'), ENT_QUOTES, 'UTF-8') ?></a></p>
