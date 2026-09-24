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

/** seo meta — 6 dil, byte bantlı (title 50–60 / desc 150–160). F16.2.9. */
$SEO_HAM = [
    'tr' => [
        'Kamelya Bakım Rehberi: Temizlik, Boya, Küf | Kamelya',
        'Kamelya bakım rehberi: ilkbahar ve sonbahar yıkama, iki yılda bir boya, küf ve nem kontrolü, alüminyum ve kompozit için düşük bakım adımları.',
    ],
    'en' => [
        'Gazebo Care Guide: Seasonal Wash, Paint, Mold | Kamelya',
        'Gazebo care guide: spring and autumn washes, paint every two years, mold and moisture checks, plus low-maintenance steps for aluminium and composite builds.',
    ],
    'de' => [
        'Kamelya Pflege: Waschen, Lasur, Schimmel | Kamelya',
        'Kamelya Pflegeanleitung: Frühjahrs- und Herbstwäsche, Lasur alle zwei Jahre, Schimmel- und Feuchtecheck, Pflegetipps für Aluminium und Verbundstoff.',
    ],
    'fr' => [
        'Guide entretien kamélia : lavage, vernis | Kamelya',
        'Guide d’entretien kamélia : lavages de printemps et d’automne, vernis tous les deux ans, contrôle humidité et moisissures, entretien alu et composite.',
    ],
    'it' => [
        'Guida manutenzione kamelya: lavaggio, muffa | Kamelya',
        'Guida alla manutenzione kamelya: lavaggi primavera e autunno, vernice ogni due anni, controllo umidità e muffa, cura alluminio e composito anche inverno.',
    ],
    'ar' => [
        'دليل صيانة الكاميليا 2026 | Kamelya',
        'دليل صيانة الكاميليا: غسيل الربيع والخريف وورنيش كل عامين مع فحص العفن والرطوبة وخطوات.',
    ],
];
$seoHam = $SEO_HAM[$dil] ?? $SEO_HAM['tr'];

$SEO = [
    'baslik' => $seoHam[0],
    'aciklama' => $seoHam[1],
    'yol' => $MEVCUT_YOL,
];
?>
<?= kirinti([['etiket' => t('anasayfa'), 'yol' => siteUrl('/')], ['etiket' => t('blog_baslik'), 'yol' => siteUrl('/blog')], ['etiket' => t('bakim_baslik'), 'yol' => null]]) ?>
<h1><?= htmlspecialchars(t('bakim_baslik'), ENT_QUOTES, 'UTF-8') ?></h1>
<?php foreach ($paragraflar as $p): ?>
<p><?= htmlspecialchars($p, ENT_QUOTES, 'UTF-8') ?></p>
<?php endforeach; ?>
