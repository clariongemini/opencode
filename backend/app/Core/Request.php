<?php

declare(strict_types=1);

namespace Kamelya\Core;

/** HTTP girdisi — Service katmanı bu nesneyi değil, doğrulanmış diziyi alır. */
final class Request
{
    public string $metot = 'GET';

    public string $yol = '/';

    /** @var array<string, mixed> */
    public array $sorgu = [];

    /** @var array<string, mixed>|null */
    public ?array $govde = null;

    public string $istemciIp = '127.0.0.1';

    /** AuthMiddleware tarafından doldurulur: id|eposta|rol|jti. */
    /** @var array<string, mixed>|null */
    public ?array $kullanici = null;

    public static function yakala(): self
    {
        $istek = new self();
        $istek->metot = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $hamYol = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $istek->yol = (string) strtok($hamYol, '?');
        $istek->sorgu = $_GET;
        $istek->istemciIp = (string) ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');

        $icerikTipi = (string) ($_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '');
        if (str_contains($icerikTipi, 'application/json')) {
            $ham = file_get_contents('php://input');
            if (is_string($ham) && $ham !== '') {
                $cozum = json_decode($ham, true);
                $istek->govde = is_array($cozum) ? $cozum : null;
            }
        }

        return $istek;
    }

    public function baslik(string $ad): ?string
    {
        $anahtar = 'HTTP_' . strtoupper(str_replace('-', '_', $ad));

        return isset($_SERVER[$anahtar]) ? (string) $_SERVER[$anahtar] : null;
    }
}
