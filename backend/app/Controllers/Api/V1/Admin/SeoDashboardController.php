<?php

declare(strict_types=1);

namespace Kamelya\Controllers\Api\V1\Admin;

use Kamelya\Core\Database;
use Kamelya\Core\Hata;
use Kamelya\Core\Request;
use Kamelya\Core\Response;
use Kamelya\Repositories\KategoriRepository;
use Kamelya\Repositories\SeoAnalitikRepository;
use Kamelya\Repositories\SeoVerisiRepository;
use Kamelya\Repositories\UrunRepository;
use Kamelya\Services\Admin\SeoDashboardService;
use Kamelya\Services\AuditLogService;
use Kamelya\Services\Google\GscService;
use Kamelya\Services\SeoService;

/** SEO Dashboard — salt-okunur (senkron hariç); rol: yonetici. */
final class SeoDashboardController
{
    private SeoDashboardService $service;

    private SeoService $seo;

    public function __construct()
    {
        $pdo = Database::baglanti();
        $this->service = new SeoDashboardService(
            $pdo,
            new SeoAnalitikRepository($pdo),
            new GscService(),
            new AuditLogService($pdo)
        );
        $this->seo = new SeoService(
            new SeoVerisiRepository($pdo),
            new UrunRepository($pdo),
            new KategoriRepository($pdo),
            new SeoAnalitikRepository($pdo)
        );
    }

    /** @param array<string, string> $rota */
    public function ozet(Request $istek, array $rota = []): void
    {
        [$baslangic, $bitis] = $this->aralik($istek);
        Response::basari($this->service->ozet($baslangic, $bitis));
    }

    /** @param array<string, string> $rota */
    public function sorgular(Request $istek, array $rota = []): void
    {
        [$baslangic, $bitis] = $this->aralik($istek);
        Response::basari($this->service->sorgular($baslangic, $bitis, $this->limit($istek)));
    }

    /** @param array<string, string> $rota */
    public function sayfalar(Request $istek, array $rota = []): void
    {
        [$baslangic, $bitis] = $this->aralik($istek);
        Response::basari($this->service->sayfalar($baslangic, $bitis, $this->limit($istek)));
    }

    /** @param array<string, string> $rota */
    public function ulkeDagilimi(Request $istek, array $rota = []): void
    {
        [$baslangic, $bitis] = $this->aralik($istek);
        Response::basari($this->service->ulkeDagilimi($baslangic, $bitis));
    }

    /** @param array<string, string> $rota */
    public function cihazDagilimi(Request $istek, array $rota = []): void
    {
        [$baslangic, $bitis] = $this->aralik($istek);
        Response::basari($this->service->cihazDagilimi($baslangic, $bitis));
    }

    /** @param array<string, string> $rota */
    public function trend(Request $istek, array $rota = []): void
    {
        [$baslangic, $bitis] = $this->aralik($istek);
        Response::basari($this->service->trend($baslangic, $bitis));
    }

    /** @param array<string, string> $rota */
    public function senkronize(Request $istek, array $rota = []): void
    {
        try {
            $gun = isset($istek->govde['gun']) ? max(1, min(90, (int) $istek->govde['gun'])) : 30;
            $kullanici = $istek->kullanici;
            if ($kullanici === null) {
                throw new Hata('UNAUTHORIZED', 'Kimlik doğrulama gerekli.', [], 401);
            }

            Response::basari($this->service->senkronize($kullanici, $istek->istemciIp, $istek->baslik('User-Agent') ?? '', $gun));
        } catch (Hata $hata) {
            Response::hata($hata->hataKodu, $hata->getMessage(), $hata->ayrintilar, $hata->httpDurum);
        }
    }

    /** @param array<string, string> $rota */
    public function firsatlar(Request $istek, array $rota = []): void
    {
        [$baslangic, $bitis] = $this->aralik($istek);
        Response::basari($this->service->kelimeFirsatlari($baslangic, $bitis));
    }

    /** @param array<string, string> $rota */
    public function denetle(Request $istek, array $rota = []): void
    {
        try {
            $veri = $istek->govde ?? [];
            $url = trim((string) ($veri['url'] ?? ''));
            $dil = (string) ($veri['lang'] ?? 'tr');
            if ($url === '' || !\Kamelya\Core\Diller::gecerli($dil)) {
                throw new Hata('VALIDATION_ERROR', 'Doğrulama hatası.', [['field' => 'url', 'issue' => 'required']], 422);
            }

            Response::basari($this->seo->denetle($url, $dil));
        } catch (Hata $hata) {
            Response::hata($hata->hataKodu, $hata->getMessage(), $hata->ayrintilar, $hata->httpDurum);
        }
    }

    /** @return array{0: string, 1: string} */
    private function aralik(Request $istek): array
    {
        $bitis = $istek->sorgu['bitis'] ?? date('Y-m-d');
        $baslangic = $istek->sorgu['baslangic'] ?? date('Y-m-d', strtotime('-30 days'));
        foreach (['baslangic' => $baslangic, 'bitis' => $bitis] as $alan => $deger) {
            $tarih = \DateTimeImmutable::createFromFormat('Y-m-d', (string) $deger);
            if ($tarih === false) {
                throw new Hata('VALIDATION_ERROR', 'Tarih biçimi Y-m-d olmalı.', [['field' => $alan, 'issue' => 'invalid_format']], 422);
            }
        }

        if ($baslangic > $bitis) {
            throw new Hata('VALIDATION_ERROR', 'Başlangıç bitişten sonra olamaz.', [['field' => 'baslangic', 'issue' => 'invalid_range']], 422);
        }

        return [$baslangic, $bitis];
    }

    private function limit(Request $istek): int
    {
        return min(100, max(1, (int) ($istek->sorgu['limit'] ?? 20)));
    }
}
