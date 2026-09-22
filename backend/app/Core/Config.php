<?php

declare(strict_types=1);

namespace Kamelya\Core;

/**
 * Ortam (.env) + config/*.php yükleyici.
 * Gizli değerler yalnızca ortamda tutulur, dosyaya gömülmez.
 */
final class Config
{
    /** @var array<string, mixed> */
    private static array $veri = [];

    private static bool $yuklendi = false;

    public static function yukle(string $kokDizin): void
    {
        if (self::$yuklendi) {
            return;
        }

        self::$yuklendi = true;
        self::envYukle($kokDizin . '/.env');

        foreach (glob($kokDizin . '/config/*.php') ?: [] as $dosya) {
            $deger = require $dosya;
            if (is_array($deger)) {
                self::$veri[basename($dosya, '.php')] = $deger;
            }
        }
    }

    public static function al(string $anahtar, mixed $varsayilan = null): mixed
    {
        $deger = self::$veri;
        foreach (explode('.', $anahtar) as $parca) {
            if (!is_array($deger) || !array_key_exists($parca, $deger)) {
                return $varsayilan;
            }

            $deger = $deger[$parca];
        }

        return $deger;
    }

    public static function cev(string $ad, ?string $varsayilan = null): ?string
    {
        $deger = getenv($ad);
        if ($deger === false && isset($_SERVER[$ad])) {
            $deger = (string) $_SERVER[$ad];
        }

        return $deger === false ? $varsayilan : (string) $deger;
    }

    private static function envYukle(string $yol): void
    {
        if (!is_file($yol) || !is_readable($yol)) {
            return;
        }

        $satirlar = file($yol, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($satirlar as $satir) {
            $satir = trim($satir);
            if ($satir === '' || str_starts_with($satir, '#')) {
                continue;
            }

            $konum = strpos($satir, '=');
            if ($konum === false) {
                continue;
            }

            $ad = trim(substr($satir, 0, $konum));
            $deger = trim(substr($satir, $konum + 1));
            if (strlen($deger) >= 2 && $deger[0] === '"' && $deger[-1] === '"') {
                $deger = stripcslashes(substr($deger, 1, -1));
            } elseif (strlen($deger) >= 2 && $deger[0] === "'" && $deger[-1] === "'") {
                $deger = substr($deger, 1, -1);
            }

            if (getenv($ad) === false) {
                putenv($ad . '=' . $deger);
                $_SERVER[$ad] = $deger;
            }
        }
    }
}
