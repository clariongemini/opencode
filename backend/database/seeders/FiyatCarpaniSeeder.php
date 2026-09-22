<?php

declare(strict_types=1);

namespace Kamelya\Seeders;

use PDO;

/**
 * Varsayılan fiyat çarpanları (F1.2 §0 + geliştirici kararı).
 * NOT: dikdortgen/klasik bilerek seedlenmez — tanımsız çarpan Service'te ×1.0
 * nötr varsayılanla çalışır, değer panelden tanımlanır (uydurma yok).
 */
final class FiyatCarpaniSeeder
{
    public static function veri(): array
    {
        return [
            ['malzeme', 'ahsap', '1.00', 'Temel malzeme'],
            ['malzeme', 'aluminyum', '2.50', 'Aluminyum malzeme carpanı'],
            ['malzeme', 'kompozit', '1.80', 'Kompozit malzeme carpanı'],
            ['model', 'kare', '1.00', 'Temel model'],
            ['model', 'altigen', '1.20', 'Altıgen model carpanı'],
            ['model', 'modern', '1.50', 'Modern model carpanı'],
            ['kullanim_amaci', 'site_bahcesi', '1.00', 'Temel kullanim'],
            ['kullanim_amaci', 'restoran', '1.30', 'Restoran kullanim carpanı'],
            ['kullanim_amaci', 'otel', '1.60', 'Otel kullanim carpanı'],
            ['kullanim_amaci', 'belediye', '1.80', 'Belediye kullanim carpanı'],
        ];
    }

    public static function calistir(PDO $baglanti): int
    {
        $bulIfade = $baglanti->prepare('SELECT id FROM kategoriler WHERE tur = ? AND kod = ?');
        $ifade = $baglanti->prepare(
            'INSERT INTO fiyat_carpanlari (kategori_id, carpan, gecerlilik_baslangici, aciklama)
             VALUES (?, ?, \'2026-09-21\', ?)
             ON DUPLICATE KEY UPDATE carpan = VALUES(carpan), aktif = 1'
        );

        $sayac = 0;
        foreach (self::veri() as [$tur, $kod, $carpan, $aciklama]) {
            $bulIfade->execute([$tur, $kod]);
            $kimlik = $bulIfade->fetchColumn();
            if ($kimlik === false) {
                throw new \RuntimeException("Kategori bulunamadı: {$tur}/{$kod} — önce KategoriSeeder çalışmalı.");
            }

            $ifade->execute([(int) $kimlik, $carpan, $aciklama]);
            $sayac++;
        }

        return $sayac;
    }
}
