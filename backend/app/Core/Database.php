<?php

declare(strict_types=1);

namespace Kamelya\Core;

use PDO;
use PDOException;

/**
 * Tekil PDO bağlantısı. Tüm sorgular prepared statement ile yapılır
 * (ATTR_EMULATE_PREPARES=false); ham string birleştirme yasaktır.
 */
final class Database
{
    private static ?PDO $baglanti = null;

    public static function baglanti(): PDO
    {
        if (self::$baglanti instanceof PDO) {
            return self::$baglanti;
        }

        $sunucu = (string) Config::al('database.sunucu', '127.0.0.1');
        $port = (int) Config::al('database.port', 3306);
        $adi = (string) Config::al('database.adi', 'kamelya');
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $sunucu, $port, $adi);

        try {
            self::$baglanti = new PDO(
                $dsn,
                (string) Config::al('database.kullanici'),
                (string) Config::al('database.sifre'),
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $hata) {
            error_log('[kamelya][db] baglanti hatasi: ' . $hata->getMessage());

            throw $hata;
        }

        return self::$baglanti;
    }

    public static function sifirla(): void
    {
        self::$baglanti = null;
    }
}
