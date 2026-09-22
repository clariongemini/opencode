<?php

declare(strict_types=1);

namespace Kamelya\Seeders;

use PDO;

/**
 * Test yöneticisi — şifre ASLA dosyaya yazılmaz; ADMIN_SIFRE ortamda zorunlu.
 * Eksikse seed atlanır (güvenli varsayılan), hata verilmez.
 */
final class KullaniciSeeder
{
    public static function calistir(PDO $baglanti): int
    {
        $eposta = (string) (getenv('ADMIN_EPOSTA') ?: 'admin@kamelya.local');
        $sifre = getenv('ADMIN_SIFRE');
        if (!is_string($sifre) || strlen($sifre) < 12) {
            echo "  [atlandı] kullanicilar: ADMIN_SIFRE ortamda tanımlı değil (min 12 karakter).\n";

            return 0;
        }

        $bul = $baglanti->prepare('SELECT id FROM kullanicilar WHERE eposta = ?');
        $bul->execute([$eposta]);
        if ($bul->fetchColumn() !== false) {
            echo "  [atlandı] kullanicilar: {$eposta} zaten kayıtlı.\n";

            return 0;
        }

        $ifade = $baglanti->prepare(
            'INSERT INTO kullanicilar (ad_soyad, eposta, sifre_hash, rol) VALUES (?, ?, ?, \'yonetici\')'
        );
        $ifade->execute(['Site Yöneticisi', $eposta, password_hash($sifre, PASSWORD_DEFAULT)]);

        return 1;
    }
}
