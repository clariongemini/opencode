<?php

declare(strict_types=1);

/** Gizlilik/KVKK — hukuki metin TR birincil; diğer dillerde özet (kayıtlı). */

$OZET = [
    'tr' => 'İletişim bilgileriniz yalnızca teklif ve keşif süreçleri için kullanılır; üçüncü kişilerle paylaşılmaz. Talebiniz üzerine verileriniz silinir.',
    'en' => 'Your contact details are used only for quotes and surveys, never shared. Deleted on request.',
    'de' => 'Ihre Kontaktdaten dienen nur Angebot und Beratung und werden nie weitergegeben. Löschung auf Wunsch.',
    'fr' => 'Vos coordonnées servent uniquement aux devis et visites, jamais partagées. Supprimées sur demande.',
    'it' => 'I tuoi contatti servono solo per preventivi e sopralluoghi, mai condivisi. Cancellati su richiesta.',
    'ar' => 'تُستخدم بيانات اتصالك لعروض الأسعار والمعاينة فقط، ولا تُشارك أبداً. تُحذف عند الطلب.',
];

$SEO = [
    'baslik' => t('gizlilik_baslik') . ' — Kamelya',
    'aciklama' => $OZET[$dil] ?? $OZET['tr'],
    'yol' => $MEVCUT_YOL,
];
?>
<?= kirinti([['etiket' => t('anasayfa'), 'yol' => siteUrl('/')], ['etiket' => t('gizlilik_baslik'), 'yol' => null]]) ?>
<h1><?= htmlspecialchars(t('gizlilik_baslik'), ENT_QUOTES, 'UTF-8') ?></h1>
<p><?= htmlspecialchars($OZET[$dil] ?? $OZET['tr'], ENT_QUOTES, 'UTF-8') ?></p>
