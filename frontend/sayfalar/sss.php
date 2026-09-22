<?php

declare(strict_types=1);

/** SSS — akordeon + FAQPage JSON-LD (yapılandırılmış veri, kapsam satır 71). */

$SSS = [
    'tr' => [
        ['soru' => 'Fiyat nasıl hesaplanıyor?', 'cevap' => 'Alan (m²) × dilinize özel başlangıç fiyatı × malzeme, model ve kullanım çarpanları. Hesaplama aracıyla anında görebilirsiniz.'],
        ['soru' => 'Keşif ücretli mi?', 'cevap' => 'Hayır. Keşif ve ölçü alma tamamen ücretsizdir.'],
        ['soru' => 'Montaj ne kadar sürer?', 'cevap' => 'Standart modellerde üretim ve montaj planı keşif sonrası netleşir; randevular Pzt–Cmt 09:00–18:00 arasındadır.'],
        ['soru' => 'Garanti var mı?', 'cevap' => 'Evet. CE/TÜV/ISO belgeli üretim ve açık garanti koşulları sunuyoruz.'],
    ],
    'en' => [
        ['soru' => 'How is the price calculated?', 'cevap' => 'Area (m²) × starting price for your language × material, model and usage multipliers. See it instantly with the calculator.'],
        ['soru' => 'Is the survey free?', 'cevap' => 'Yes. Survey and measuring are completely free.'],
        ['soru' => 'How long does installation take?', 'cevap' => 'The production and installation plan is fixed after the survey; appointments run Mon–Sat 09:00–18:00.'],
        ['soru' => 'Is there a warranty?', 'cevap' => 'Yes. CE/TÜV/ISO certified manufacturing with clear warranty terms.'],
    ],
    'de' => [
        ['soru' => 'Wie wird der Preis berechnet?', 'cevap' => 'Fläche (m²) × Startpreis Ihrer Sprache × Material-, Modell- und Verwendungsfaktoren. Sofort im Rechner sichtbar.'],
        ['soru' => 'Ist die Beratung kostenlos?', 'cevap' => 'Ja. Beratung und Aufmaß sind völlig kostenlos.'],
        ['soru' => 'Wie lange dauert die Montage?', 'cevap' => 'Fertigungs- und Montageplan werden nach der Beratung fixiert; Termine Mo–Sa 09:00–18:00 Uhr.'],
        ['soru' => 'Gibt es eine Garantie?', 'cevap' => 'Ja. CE/TÜV/ISO-zertifizierte Fertigung mit klarer Garantie.'],
    ],
    'fr' => [
        ['soru' => 'Comment le prix est-il calculé ?', 'cevap' => 'Surface (m²) × prix de départ de votre langue × coefficients matériau, modèle et usage. Visible dans le calculateur.'],
        ['soru' => 'La visite est-elle gratuite ?', 'cevap' => 'Oui. Visite et métré entièrement gratuits.'],
        ['soru' => 'Combien dure la pose ?', 'cevap' => 'Le plan de fabrication et pose est fixé après la visite ; rendez-vous Lun–Sam 09h00–18h00.'],
        ['soru' => 'Y a-t-il une garantie ?', 'cevap' => 'Oui. Fabrication certifiée CE/TÜV/ISO avec garantie claire.'],
    ],
    'it' => [
        ['soru' => 'Come viene calcolato il prezzo?', 'cevap' => 'Area (m²) × prezzo base della tua lingua × coefficienti materiale, modello e uso. Visibile nel calcolatore.'],
        ['soru' => 'Il sopralluogo è gratuito?', 'cevap' => 'Sì. Sopralluogo e misure completamente gratuiti.'],
        ['soru' => 'Quanto dura la posa?', 'cevap' => 'Il piano di produzione e posa si fissa dopo il sopralluogo; appuntamenti Lun–Sab 09:00–18:00.'],
        ['soru' => 'C’è una garanzia?', 'cevap' => 'Sì. Produzione certificata CE/TÜV/ISO con garanzia chiara.'],
    ],
    'ar' => [
        ['soru' => 'كيف يُحسب السعر؟', 'cevap' => 'المساحة (م²) × سعر البداية بلغتك × معاملات الخامة والموديل والاستخدام. يظهر فوراً في الحاسبة.'],
        ['soru' => 'هل المعاينة مجانية؟', 'cevap' => 'نعم. المعاينة والقياس مجانيان تماماً.'],
        ['soru' => 'كم يستغرق التركيب؟', 'cevap' => 'تُحدد خطة الإنتاج والتركيب بعد المعاينة؛ المواعيد من الاثنين إلى السبت 09:00–18:00.'],
        ['soru' => 'هل يوجد ضمان؟', 'cevap' => 'نعم. إنتاج معتمد CE/TÜV/ISO مع شروط ضمان واضحة.'],
    ],
];

$ogeler = $SSS[$dil] ?? $SSS['tr'];

$SEO = [
    'baslik' => t('sss_baslik') . ' — Fiyat, Keşif, Garanti — Kamelya',
    'aciklama' => $dil === 'tr'
        ? 'Sık Sorulan Sorular: Fiyat nasıl hesaplanıyor? Alan (m²) × başlangıç fiyatı × malzeme, model ve kullanım çarpanları ile anında hesaplanır. Keşif ücretsizdir.'
        : t('sss_baslik') . ': ' . $ogeler[0]['soru'] . ' ' . $ogeler[0]['cevap'],
    'yol' => $MEVCUT_YOL,
    'jsonld' => jsonldSss($ogeler),
];
?>
<?= kirinti([['etiket' => t('anasayfa'), 'yol' => siteUrl('/')], ['etiket' => t('sss_baslik'), 'yol' => null]]) ?>
<h1><?= htmlspecialchars(t('sss_baslik'), ENT_QUOTES, 'UTF-8') ?></h1>
<?= sssAkordeon($ogeler) ?>
