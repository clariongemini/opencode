<?php

declare(strict_types=1);

namespace Kamelya\Seeders;

use PDO;

/** Seed orkestrasyonu — FK sırasına uygun çağrı dizisi. */
final class DatabaseSeeder
{
    public static function calistir(PDO $baglanti): int
    {
        $toplam = 0;

        $adimlar = [
            'kategoriler' => [KategoriSeeder::class, 'calistir'],
            'kategori_cevirileri' => [KategoriCeviriSeeder::class, 'calistir'],
            'fiyat_carpanlari' => [FiyatCarpaniSeeder::class, 'calistir'],
            'urun_fiyatlari' => [UrunFiyatiSeeder::class, 'calistir'],
            'kullanicilar' => [KullaniciSeeder::class, 'calistir'],
        ];

        foreach ($adimlar as $tablo => $cagri) {
            $satir = $cagri($baglanti);
            echo "  [tamam] {$tablo}: {$satir} satır.\n";
            $toplam += $satir;
        }

        return $toplam;
    }
}
