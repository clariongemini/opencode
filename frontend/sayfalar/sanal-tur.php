<?php

declare(strict_types=1);

/** Sanal tur — liste + consent-kapılı iframe viewer. */

$liste = apiGet('/sanal-tur', $dil);
$turlar = $liste['data'] ?? [];

$SEO = [
    'baslik' => 'Sanal Tur — Showroom ve Projeleri 360° Gezin — Kamelya',
    'aciklama' => 'Kamelya showroom ve projelerini sanal turla gezin: 360 derece dış mekân deneyimi, ücretsiz keşif ve hızlı montaj imkanlarıyla tanışın. Hemen başlatın.',
    'yol' => $MEVCUT_YOL,
];
?>
<?= kirinti([['etiket' => t('anasayfa'), 'yol' => siteUrl('/')], ['etiket' => 'Sanal Tur', 'yol' => null]]) ?>
<h1>Sanal Tur</h1>
<?php foreach ($turlar as $tur): ?>
<article class="card"><div class="card-govde">
  <h3><?= htmlspecialchars($tur['baslik'] ?? '', ENT_QUOTES, 'UTF-8') ?></h3>
  <p><?= htmlspecialchars($tur['aciklama'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
  <div class="sanal-kap" data-kaynak="<?= htmlspecialchars($tur['embed_url'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <button type="button" class="btn btn-birincil sanal-ac">Turu Başlat</button>
    <p><small>Tur harici sunucudan yüklenir; başlatınca çerez politikası geçerlidir.</small></p>
  </div>
</div></article>
<?php endforeach; ?>
<?php if ($turlar === []): ?>
<p><?= htmlspecialchars(t('urun_bos'), ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>
<script>
(function () {
  document.querySelectorAll('.sanal-ac').forEach(function (b) {
    b.addEventListener('click', function () {
      var kap = b.closest('.sanal-kap');
      var cerceve = document.createElement('iframe');
      cerceve.src = kap.getAttribute('data-kaynak');
      cerceve.width = '100%';
      cerceve.height = '480';
      cerceve.loading = 'lazy';
      cerceve.title = 'Sanal tur';
      cerceve.allowFullscreen = true;
      kap.innerHTML = '';
      kap.appendChild(cerceve);
    });
  });
})();
