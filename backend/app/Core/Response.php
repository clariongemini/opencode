<?php

declare(strict_types=1);

namespace Kamelya\Core;

/** Tek JSON sözleşmesi: başarı {success,data,meta?} · hata {success,error{code,message,details}}. */
final class Response
{
    /** @param array<string, mixed> $veri */
    public static function json(array $veri, int $durum = 200): void
    {
        http_response_code($durum);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($veri, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /** @param array<string, mixed> $veri */
    public static function basari(array $veri, ?array $ustVeri = null, int $durum = 200): void
    {
        $yanit = ['success' => true, 'data' => $veri];
        if ($ustVeri !== null) {
            $yanit['meta'] = $ustVeri;
        }

        self::json($yanit, $durum);
    }

    /** @param array<int, array<string, string>> $ayrintilar */
    public static function hata(string $kod, string $mesaj, array $ayrintilar = [], int $durum = 400): void
    {
        self::json([
            'success' => false,
            'error' => ['code' => $kod, 'message' => $mesaj, 'details' => $ayrintilar],
        ], $durum);
    }
}
