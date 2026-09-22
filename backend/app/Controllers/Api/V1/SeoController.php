<?php

declare(strict_types=1);

namespace Kamelya\Controllers\Api\V1;

use Kamelya\Core\Database;
use Kamelya\Core\Diller;
use Kamelya\Core\Hata;
use Kamelya\Core\Request;
use Kamelya\Core\Response;
use Kamelya\Repositories\KategoriRepository;
use Kamelya\Repositories\SeoVerisiRepository;
use Kamelya\Repositories\UrunRepository;
use Kamelya\Services\SeoService;

/**
 * GET /api/v1/seo/check — seo-analyzer teknik denetim kanalının ilk ucu.
 * F4'te frontend geldiğinde Playwright çıktısıyla tam puan kartına bağlanır.
 */
final class SeoController
{
    private SeoService $service;

    public function __construct()
    {
        $pdo = Database::baglanti();
        $this->service = new SeoService(
            new SeoVerisiRepository($pdo),
            new UrunRepository($pdo),
            new KategoriRepository($pdo)
        );
    }

    /** @param array<string, string> $rota */
    public function kontrol(Request $istek, array $rota = []): void
    {
        try {
            $url = $istek->sorgu['url'] ?? null;
            $dil = $istek->sorgu['lang'] ?? null;
            if (!is_string($url) || trim($url) === '') {
                throw new Hata(
                    'VALIDATION_ERROR',
                    'url parametresi zorunludur.',
                    [['field' => 'url', 'issue' => 'required']],
                    422
                );
            }

            if (!Diller::gecerli($dil)) {
                throw new Hata(
                    'VALIDATION_ERROR',
                    'lang parametresi zorunludur.',
                    [['field' => 'lang', 'issue' => 'required_or_invalid']],
                    422
                );
            }

            Response::basari($this->service->denetle($url, $dil));
        } catch (Hata $hata) {
            Response::hata($hata->hataKodu, $hata->getMessage(), $hata->ayrintilar, $hata->httpDurum);
        }
    }
}
