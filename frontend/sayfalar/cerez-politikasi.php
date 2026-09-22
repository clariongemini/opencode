<?php

declare(strict_types=1);

/** Çerez Politikası — KVKK uyumu (F8 denetiminde eksik bulundu, eklendi). */

$METIN = [
    'tr' => 'Bu site yalnızca onayınızla analitik çerezleri kullanır. Zorunlu çerezler (dil tercihi, çerez onayı) cihazınızda saklanır; analitik (GA4), ısı haritası (Clarity) ve hız ölçümü yalnızca "Kabul Et" sonrasında yüklenir. Onayınızı dilediğiniz zaman aynı banttan geri alabilirsiniz.',
    'en' => 'This site uses analytics cookies only with your consent. Necessary cookies (language, consent choice) stay on your device; analytics (GA4), heatmaps (Clarity) and speed measurement load only after you click "Accept".',
    'de' => 'Diese Website verwendet Analyse-Cookies nur mit Ihrer Zustimmung. Notwendige Cookies bleiben auf Ihrem Gerät; Analyse (GA4), Heatmaps (Clarity) und Messung laden erst nach „Annehmen".',
    'fr' => 'Ce site utilise des cookies de mesure uniquement avec votre consentement. Les cookies nécessaires restent sur votre appareil ; analyse (GA4), heatmaps (Clarity) et mesure ne chargent qu’après acceptation.',
    'it' => 'Questo sito usa cookie di analisi solo con il tuo consenso. I cookie necessari restano sul tuo dispositivo; analisi (GA4), heatmap (Clarity) e misurazione si caricano solo dopo l’accettazione.',
    'ar' => 'يستخدم هذا الموقع ملفات ارتباط التحليل فقط بموافقتك. تبقى الملفات الضرورية على جهازك؛ ولا تُحمَّل التحليلات إلا بعد الضغط على «قبول».',
];

$SEO = [
    'baslik' => 'Çerez Politikası — Kamelya',
    'aciklama' => kisaAciklama($METIN[$dil] ?? $METIN['tr'], 160),
    'yol' => $MEVCUT_YOL,
];
?>
<?= kirinti([['etiket' => t('anasayfa'), 'yol' => siteUrl('/')], ['etiket' => 'Çerez Politikası', 'yol' => null]]) ?>
<h1>Çerez Politikası</h1>
<p><?= htmlspecialchars($METIN[$dil] ?? $METIN['tr'], ENT_QUOTES, 'UTF-8') ?></p>
