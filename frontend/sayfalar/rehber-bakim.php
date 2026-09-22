<?php

declare(strict_types=1);

/** Bakım rehberi — kapsam satır 27/72 (mevsimsel bakım, temizlik, boya, küf). */

$METIN = [
    'tr' => ['Mevsimsel bakım: ilkbaharda ahşabı yıkayın, çatlakları kontrol edin.', 'Temizlik: yumuşak fırça ve su yeterlidir; basınçlı yıkayıcıya dikkat edin.', 'Boya ve koruma: iki yılda bir dış mekân verniği yenileyin; küf ve zararlılara karşı emprenye kontrolü yapın.'],
    'en' => ['Seasonal care: wash the wood in spring and check for cracks.', 'Cleaning: a soft brush and water are enough; be careful with pressure washers.', 'Paint and protection: renew outdoor varnish every two years; check impregnation against mold and pests.'],
    'de' => ['Saisonpflege: Holz im Frühjahr waschen, Risse prüfen.', 'Reinigung: weiche Bürste und Wasser genügen; Vorsicht mit Hochdruckreinigern.', 'Schutz: Außenlasur alle zwei Jahre erneuern; Imprägnierung gegen Schimmel und Schädlinge prüfen.'],
    'fr' => ['Entretien saisonnier : laver le bois au printemps, vérifier les fissures.', 'Nettoyage : brosse douce et eau suffisent ; attention au nettoyeur haute pression.', 'Protection : renouveler le vernis extérieur tous les deux ans ; vérifier l’imprégnation contre moisissures et nuisibles.'],
    'it' => ['Manutenzione stagionale: lavare il legno in primavera, controllare le crepe.', 'Pulizia: spazzola morbida e acqua bastano; attenzione all’idropulitrice.', 'Protezione: rinnovare la vernice da esterno ogni due anni; verificare l’impregnazione contro muffe e parassiti.'],
    'ar' => ['صيانة موسمية: اغسل الخشب في الربيع وتحقق من الشقوق.', 'التنظيف: فرشاة ناعمة وماء يكفيان؛ احذر من الغسيل بالضغط العالي.', 'الحماية: جدد الورنيش الخارجي كل عامين؛ تحقق من التشريب ضد العفن والآفات.'],
];

$paragraflar = $METIN[$dil] ?? $METIN['tr'];

$SEO = [
    'baslik' => t('bakim_baslik') . ' — Kamelya',
    'aciklama' => $paragraflar[0],
    'yol' => $MEVCUT_YOL,
];
?>
<?= kirinti([['etiket' => t('anasayfa'), 'yol' => siteUrl('/')], ['etiket' => t('blog_baslik'), 'yol' => siteUrl('/blog')], ['etiket' => t('bakim_baslik'), 'yol' => null]]) ?>
<h1><?= htmlspecialchars(t('bakim_baslik'), ENT_QUOTES, 'UTF-8') ?></h1>
<?php foreach ($paragraflar as $p): ?>
<p><?= htmlspecialchars($p, ENT_QUOTES, 'UTF-8') ?></p>
<?php endforeach; ?>
