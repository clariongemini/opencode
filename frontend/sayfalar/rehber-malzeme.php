<?php

declare(strict_types=1);

/** Malzeme karşılaştırma — kapsam satır 28/73 (ahşap/alüminyum/kompozit). */

$TABLO = [
    'tr' => [['Ahşap', 'Doğal görünüm, sıcak doku', 'Düzenli vernik gerekir', '×1,0'], ['Alüminyum', 'Bakım gerektirmez, modern', 'Yüksek ilk maliyet', '×2,5'], ['Kompozit', 'Dayanıklı, dengeli', 'Sınırlı renk', '×1,8']],
    'en' => [['Wood', 'Natural look, warm feel', 'Needs regular varnish', '×1.0'], ['Aluminium', 'Maintenance-free, modern', 'Higher upfront cost', '×2.5'], ['Composite', 'Durable, balanced', 'Limited colors', '×1.8']],
    'de' => [['Holz', 'Natürliche Optik, warm', 'Braucht Lasur', '×1,0'], ['Aluminium', 'Pflegefrei, modern', 'Höhere Anschaffung', '×2,5'], ['Verbund', 'Robust, ausgewogen', 'Wenig Farben', '×1,8']],
    'fr' => [['Bois', 'Aspect naturel, chaleureux', 'Vernis régulier requis', '×1,0'], ['Aluminium', 'Sans entretien, moderne', 'Coût initial élevé', '×2,5'], ['Composite', 'Durable, équilibré', 'Couleurs limitées', '×1,8']],
    'it' => [['Legno', 'Aspetto naturale, caldo', 'Richiede vernice regolare', '×1,0'], ['Alluminio', 'Senza manutenzione, moderno', 'Costo iniziale alto', '×2,5'], ['Composito', 'Resistente, equilibrato', 'Colori limitati', '×1,8']],
    'ar' => [['خشب', 'مظهر طبيعي دافئ', 'يحتاج ورنيش دوري', '×1.0'], ['ألمنيوم', 'بدون صيانة، عصري', 'تكلفة أولية أعلى', '×2.5'], ['مركب', 'متين ومتوازن', 'ألوان محدودة', '×1.8']],
];

$satirlar = $TABLO[$dil] ?? $TABLO['tr'];

$SEO = [
    'baslik' => t('malzeme_baslik') . ' — Kamelya',
    'aciklama' => t('malzeme_baslik') . ': ' . $satirlar[0][0] . ' / ' . $satirlar[1][0] . ' / ' . $satirlar[2][0],
    'yol' => $MEVCUT_YOL,
];
?>
<?= kirinti([['etiket' => t('anasayfa'), 'yol' => siteUrl('/')], ['etiket' => t('blog_baslik'), 'yol' => siteUrl('/blog')], ['etiket' => t('malzeme_baslik'), 'yol' => null]]) ?>
<h1><?= htmlspecialchars(t('malzeme_baslik'), ENT_QUOTES, 'UTF-8') ?></h1>
<table class="card">
  <tbody>
    <?php foreach ($satirlar as $s): ?>
    <tr>
      <th scope="row"><?= htmlspecialchars($s[0], ENT_QUOTES, 'UTF-8') ?></th>
      <td><?= htmlspecialchars($s[1], ENT_QUOTES, 'UTF-8') ?></td>
      <td><?= htmlspecialchars($s[2], ENT_QUOTES, 'UTF-8') ?></td>
      <td><?= htmlspecialchars($s[3], ENT_QUOTES, 'UTF-8') ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
