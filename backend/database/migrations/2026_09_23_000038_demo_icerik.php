<?php

declare(strict_types=1);

use Kamelya\Core\Migration;

/**
 * B.2 — demo_icerik bayrağı (ayarlar tablosu).
 * demo_icerik='1' iken admin panelde "⚠️ Demo İçerik" rozeti görünür;
 * public AyarController allowlist'i bu anahtarı içermez → frontend'e sızmaz.
 * Geri alınabilir: down() satırı siler.
 */
return new class implements Migration {
    public function up(PDO $baglanti): void
    {
        $ifade = $baglanti->prepare(
            'INSERT INTO ayarlar (anahtar, deger, aciklama) VALUES (?, ?, ?)'
        );
        $ifade->execute([
            'demo_icerik',
            '1',
            'Placeholder içerik aktif — gerçek içerik tamamlanınca 0 yapılır.',
        ]);
    }

    public function down(PDO $baglanti): void
    {
        $ifade = $baglanti->prepare('DELETE FROM ayarlar WHERE anahtar = ?');
        $ifade->execute(['demo_icerik']);
    }
};
