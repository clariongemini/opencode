<?php

declare(strict_types=1);

/** Bülten onayı — token ile sunucu-tarafı doğrulama + sonuç mesajı. */

$token = (string) ($_GET['token'] ?? '');
$sonuc = null;
if ($token !== '') {
    $url = rtrim($AYAR['api_taban'], '/') . '/bulten/onay?token=' . urlencode($token);
    $ham = @file_get_contents($url);
    $cozum = is_string($ham) ? json_decode($ham, true) : null;
    $sonuc = is_array($cozum) ? $cozum : null;
}

$SEO = [
    'baslik' => 'Bülten Onayı — Kamelya',
    'aciklama' => 'Bülten abonelik doğrulama sonucu.',
    'yol' => $MEVCUT_YOL,
    'robots' => 'noindex, follow',
];
?>
<h1>Bülten Onayı</h1>
<?php if (is_array($sonuc) && ($sonuc['success'] ?? false) === true): ?>
<div class="uyari uyari-basarili"><?= htmlspecialchars($sonuc['data']['mesaj'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
<?php else: ?>
<div class="uyari uyari-hata">Doğrulama bağlantısı geçersiz veya süresi dolmuş.</div>
<?php endif; ?>
