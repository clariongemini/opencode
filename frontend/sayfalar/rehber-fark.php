<?php

declare(strict_types=1);

/** Çardak / Pergola farkı — kapsam satır 74. */

$SATIRLAR = [
    'tr' => [
        ['Çardak', 'Ahşaptan, tam kapalı veya yarı kapalı', '4 mevsim kullanım (duvarlı)', 'Yüksek (izolasyon, camlı)', 'Mutfak, oturma odası genişletmesi', 'Ruhsat/izin genellikle gerekir'],
        ['Pergola', 'Ahşap/alüminyum/kompozit, açık üst yapı', 'Yaz/geçiş mevsimi (gölgelik)', 'Orta/ Düşük (temel montaj)', 'Bahçe oturma, araçlık, yürüyüş yolu', 'Bazı bölgelerde izinsiz (örtüsüz)'],
    ],
    'en' => [
        ['Gazebo', 'Wood, fully or semi-enclosed', '4-season use (walled)', 'High (insulation, glazed)', 'Kitchen, living room extension', 'Permit usually required'],
        ['Pergola', 'Wood/aluminium/composite, open roof', 'Summer/transitional (shade)', 'Medium/Low (basic install)', 'Garden seating, carport, walkway', 'Permit-free in some areas (uncovered)'],
    ],
    'de' => [
        ['Pavillon', 'Holz, ganz oder halb geschlossen', 'Ganzjahresnutzung (verglast)', 'Hoch (Isolierung, verglast)', 'Küche, Wohnzimmererweiterung', 'Baugenehmigung meist nötig'],
        ['Pergola', 'Holz/Alu/Verbund, offenes Dach', 'Sommer/Übergang (Schatten)', 'Mittel/Niedrig (Basis)', 'Gartensitz, Carport, Gehweg', 'Teilweise genehmigungsfrei (unbedeckt)'],
    ],
    'fr' => [
        ['Kiosque / Gazebo', 'Bois, entièrement ou semi-fermé', '4 saisons (avec parois)', 'Élevé (isolation, vitré)', 'Extension cuisine, salon', 'Permis généralement requis'],
        ['Pergola', 'Bois/alu/composite, toit ouvert', 'Été/mi-saison (ombrage)', 'Moyen/Faible (pose simple)', 'Salon jardin, carport, allée', 'Sans permis dans certaines zones (sans toit)'],
    ],
    'it' => [
        ['Gazebo', 'Legno, completamente o semichiuso', '4 stagioni (con pareti)', 'Alto (isolamento, vetrato)', 'Estensione cucina, soggiorno', 'Permesso solitamente richiesto'],
        ['Pergola', 'Legno/alluminio/composito, tetto aperto', 'Estate/mezza stagione (ombra)', 'Medio/Basso (installazione base)', 'Salotto giardino, carport, vialetto', 'Senza permesso in alcune zone (scoperto)'],
    ],
    'ar' => [
        ['كوش / جناح', 'خشب، مغلق بالكامل أو جزئياً', 'استخدام 4 فصول (بجدران)', 'عالي (عزل، زجاجي)', 'تمديد المطبخ، غرفة المعيشة', 'تصريح مطلوب عادة'],
        ['برجولة', 'خشب/ألمنيوم/مركب، سقف مفتوح', 'صيف/فصل انتقالي (ظل)', 'متوسط/منخفض (تركيب أساسي)', 'جلسة حديقة، كاربورت، ممر', 'بدون تصريح في بعض المناطق (غير مغطى)'],
    ],
];

$satirlar = $SATIRLAR[$dil] ?? $SATIRLAR['tr'];

$SEO = [
    'baslik' => t('fark_baslik') . ' — Kamelya',
    'aciklama' => 'Çardak ve pergola farkları: yapı, kullanım, maliyet, izin ve uygulama alanları karşılaştırma tablosu.',
    'yol' => $MEVCUT_YOL,
];
?>
<?= kirinti([['etiket' => t('anasayfa'), 'yol' => siteUrl('/')], ['etiket' => t('blog_baslik'), 'yol' => siteUrl('/blog')], ['etiket' => t('fark_baslik'), 'yol' => null]]) ?>
<h1><?= htmlspecialchars(t('fark_baslik'), ENT_QUOTES, 'UTF-8') ?></h1>
<p class="giris"><?= htmlspecialchars(t('fark_giris'), ENT_QUOTES, 'UTF-8') ?></p>
<table class="card">
  <thead>
    <tr>
      <th scope="col"><?= htmlspecialchars(t('fark_tur'), ENT_QUOTES, 'UTF-8') ?></th>
      <th scope="col"><?= htmlspecialchars(t('fark_yapi'), ENT_QUOTES, 'UTF-8') ?></th>
      <th scope="col"><?= htmlspecialchars(t('fark_kullanim'), ENT_QUOTES, 'UTF-8') ?></th>
      <th scope="col"><?= htmlspecialchars(t('fark_maliyet'), ENT_QUOTES, 'UTF-8') ?></th>
      <th scope="col"><?= htmlspecialchars(t('fark_alan'), ENT_QUOTES, 'UTF-8') ?></th>
      <th scope="col"><?= htmlspecialchars(t('fark_izin'), ENT_QUOTES, 'UTF-8') ?></th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($satirlar as $s): ?>
    <tr>
      <th scope="row"><?= htmlspecialchars($s[0], ENT_QUOTES, 'UTF-8') ?></th>
      <td><?= htmlspecialchars($s[1], ENT_QUOTES, 'UTF-8') ?></td>
      <td><?= htmlspecialchars($s[2], ENT_QUOTES, 'UTF-8') ?></td>
      <td><?= htmlspecialchars($s[3], ENT_QUOTES, 'UTF-8') ?></td>
      <td><?= htmlspecialchars($s[4], ENT_QUOTES, 'UTF-8') ?></td>
      <td><?= htmlspecialchars($s[5], ENT_QUOTES, 'UTF-8') ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<div class="cta-alan">
  <a href="<?= htmlspecialchars(siteUrl('/teklif-al'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-birincil"><?= htmlspecialchars(t('cta_kesif'), ENT_QUOTES, 'UTF-8') ?></a>
</div>