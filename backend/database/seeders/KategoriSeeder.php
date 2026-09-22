<?php

declare(strict_types=1);

namespace Kamelya\Seeders;

use PDO;

/** V1 kategori iskeleti: kapsam §2.2 (Model/Malzeme/Kullanım) + TR çeviri. */
final class KategoriSeeder
{
    /** @return array{0: string, 1: string, 2: int, 3: string} */
    public static function veri(): array
    {
        return [
            ['model', 'kare', 1, 'Kare'],
            ['model', 'altigen', 2, 'Altıgen'],
            ['model', 'dikdortgen', 3, 'Dikdörtgen'],
            ['model', 'modern', 4, 'Modern'],
            ['model', 'klasik', 5, 'Klasik'],
            ['malzeme', 'ahsap', 1, 'Ahşap'],
            ['malzeme', 'aluminyum', 2, 'Alüminyum'],
            ['malzeme', 'kompozit', 3, 'Kompozit'],
            ['kullanim_amaci', 'site_bahcesi', 1, 'Site Bahçesi'],
            ['kullanim_amaci', 'restoran', 2, 'Restoran'],
            ['kullanim_amaci', 'otel', 3, 'Otel'],
            ['kullanim_amaci', 'belediye', 4, 'Belediye'],
        ];
    }

    public static function calistir(PDO $baglanti): int
    {
        $kategoriIfade = $baglanti->prepare(
            'INSERT INTO kategoriler (tur, kod, sira) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE sira = VALUES(sira), aktif = 1'
        );
        $ceviriIfade = $baglanti->prepare(
            'INSERT INTO kategori_cevirileri (kategori_id, dil_kodu, isim, slug) VALUES (?, \'tr\', ?, ?)
             ON DUPLICATE KEY UPDATE isim = VALUES(isim)'
        );
        $bulIfade = $baglanti->prepare('SELECT id FROM kategoriler WHERE tur = ? AND kod = ?');

        $sayac = 0;
        foreach (self::veri() as [$tur, $kod, $sira, $isim]) {
            $kategoriIfade->execute([$tur, $kod, $sira]);
            $bulIfade->execute([$tur, $kod]);
            $kimlik = $bulIfade->fetchColumn();
            if ($kimlik !== false) {
                $ceviriIfade->execute([(int) $kimlik, $isim, $kod]);
                $sayac++;
            }
        }

        return $sayac;
    }
}
