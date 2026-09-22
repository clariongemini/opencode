<?php

declare(strict_types=1);

namespace Kamelya\Seeders;

use PDO;

/** Dil bazlı temel m² fiyatları — geliştirici kararları (21.09.2026). Canlı kur yok. */
final class UrunFiyatiSeeder
{
    public static function veri(): array
    {
        return [
            ['tr', 'TRY', '12000.00', 'Temel ahsap, baslangic m2 fiyati'],
            ['en', 'USD', '3.00', 'Base price, standard wood'],
            ['de', 'EUR', '2.50', 'Basispreis, Standardholz'],
            ['fr', 'EUR', '2.50', 'Prix de base, bois standard'],
            ['it', 'EUR', '2.50', 'Prezzo base, legno standard'],
            ['ar', 'USD', '3.00', 'Korfez bolgesel fiyat'],
        ];
    }

    public static function calistir(PDO $baglanti): int
    {
        $ifade = $baglanti->prepare(
            'INSERT INTO urun_fiyatlari (dil_kodu, para_birimi, fiyat_m2, gecerlilik_baslangici, aciklama)
             VALUES (?, ?, ?, \'2026-09-21\', ?)
             ON DUPLICATE KEY UPDATE para_birimi = VALUES(para_birimi), fiyat_m2 = VALUES(fiyat_m2), aktif = 1'
        );

        $sayac = 0;
        foreach (self::veri() as $satir) {
            $ifade->execute($satir);
            $sayac++;
        }

        return $sayac;
    }
}
