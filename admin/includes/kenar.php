<?php

declare(strict_types=1);

/** @var string $aktif */
$aktif = $aktif ?? 'dashboard';
$oge = function (string $ad, string $yol, string $etiket, ?string $rozet = null) use ($aktif): string {
    $sinif = $ad === $aktif ? ' class="aktif"' : '';
    $r = $rozet !== null ? ' <span class="rozet">' . $rozet . '</span>' : '';
    return '<li' . $sinif . '><a href="' . $yol . '">' . $etiket . $r . '</a></li>';
};
?>
<aside class="kenar">
  <p class="logo">Kamelya Admin</p>
  <nav aria-label="panel">
    <ul>
      <?= $oge('dashboard', '/panel.php', 'Dashboard') ?>
      <?= $oge('talepler', '/panel.php?sayfa=talepler', 'Talepler') ?>
      <?= $oge('urunler', '/panel.php?sayfa=urunler', 'Ürünler') ?>
      <?= $oge('kategoriler', '/panel.php?sayfa=kategoriler', 'Kategoriler') ?>
      <?= $oge('blog', '/panel.php?sayfa=blog', 'Blog') ?>
      <?= $oge('yorumlar', '/panel.php?sayfa=yorumlar', 'Yorumlar') ?>
      <?= $oge('sertifikalar', '/panel.php?sayfa=sertifikalar', 'Sertifikalar') ?>
      <?= $oge('ekip', '/panel.php?sayfa=ekip', 'Ekip') ?>
      <?= $oge('bulten', '/panel.php?sayfa=bulten', 'Bülten') ?>
      <?= $oge('sss', '/panel.php?sayfa=sss', 'SSS') ?>
      <?= $oge('kampanyalar', '/panel.php?sayfa=kampanyalar', 'Kampanyalar') ?>
      <?= $oge('sanal-turlar', '/panel.php?sayfa=sanal-turlar', 'Sanal Tur') ?>
      <?= $oge('donusumler', '/panel.php?sayfa=donusumler', 'Dönüşümler') ?>
      <?= $oge('video-referanslar', '/panel.php?sayfa=video-referanslar', 'Video Referans') ?>
      <?= $oge('sicaklik-formulu', '/panel.php?sayfa=sicaklik-formulu', 'Sıcaklık Formülü') ?>
      <?= $oge('sehirler', '/panel.php?sayfa=sehirler', 'Şehirler') ?>
      <?= $oge('ozellikler', '/panel.php?sayfa=ozellikler', 'Özellikler') ?>
      <?= $oge('galeri', '/panel.php?sayfa=galeri', 'Galeri') ?>
      <?= $oge('atolye', '/panel.php?sayfa=atolye', 'Atölye') ?>
      <?= $oge('ayarlar', '/panel.php?sayfa=ayarlar', 'Ayarlar') ?>
      <?= $oge('seo', '/panel.php?sayfa=seo-dashboard', 'SEO Dashboard') ?>
      <?= $oge('takvim', '/panel.php?sayfa=takvim', 'Takvim') ?>
    </ul>
  </nav>
  <p><button id="cikis" type="button">Çıkış</button></p>
</aside>
