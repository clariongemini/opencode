<?php

declare(strict_types=1);

namespace Kamelya\Core;

/** Slug üretimi — Türkçe karakter dönüşümü (DB isimlendirme kuralıyla aynı harita). */
final class Slug
{
    public static function uret(string $metin, int $maks = 220): string
    {
        $harita = [
            'ç' => 'c', 'Ç' => 'c', 'ğ' => 'g', 'Ğ' => 'g', 'ı' => 'i', 'I' => 'i', 'İ' => 'i',
            'ö' => 'o', 'Ö' => 'o', 'ş' => 's', 'Ş' => 's', 'ü' => 'u', 'Ü' => 'u',
        ];
        $duz = strtr($metin, $harita);
        $duz = mb_strtolower($duz);
        $duz = (string) preg_replace('/[^a-z0-9]+/u', '-', $duz);
        $duz = trim($duz, '-');
        if ($duz === '') {
            $duz = 'kayit';
        }

        return mb_substr($duz, 0, $maks);
    }
}
