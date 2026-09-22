<?php

declare(strict_types=1);

namespace Kamelya\Controllers\Api\V1\Admin;

use Kamelya\Core\Database;
use Kamelya\Core\Hata;
use Kamelya\Core\Request;
use Kamelya\Core\Response;
use Kamelya\Repositories\Admin\UrunYonetimRepository;
use Kamelya\Repositories\Admin\ResimYonetimRepository;
use Kamelya\Repositories\KategoriRepository;
use Kamelya\Services\Admin\AdminUrunService;
use Kamelya\Services\AuditLogService;
use Kamelya\Validators\Admin\AdminUrunValidator;

/** Admin ürün CRUD — tüm yazma audit'li, silme soft. */
final class AdminUrunController
{
    private AdminUrunService $service;

    public function __construct()
    {
        $pdo = Database::baglanti();
        $this->service = new AdminUrunService(
            $pdo,
            new UrunYonetimRepository($pdo),
            new KategoriRepository($pdo),
            new AuditLogService($pdo),
            new ResimYonetimRepository($pdo)
        );
    }

    /** @param array<string, string> $rota */
    public function liste(Request $istek, array $rota = []): void
    {
        $sayfa = max(1, (int) ($istek->sorgu['page'] ?? 1));
        $adet = min(100, max(1, (int) ($istek->sorgu['per_page'] ?? 20)));
        $arama = isset($istek->sorgu['q']) && is_string($istek->sorgu['q']) ? $istek->sorgu['q'] : null;

        $sonuc = $this->service->liste($arama, $sayfa, $adet);
        Response::basari($sonuc['satirlar'], ['page' => $sayfa, 'per_page' => $adet, 'total' => $sonuc['toplam']]);
    }

    /** @param array<string, string> $rota */
    public function detay(Request $istek, array $rota = []): void
    {
        try {
            $sonuc = $this->service->detay((int) ($rota['id'] ?? 0));
            Response::basari($sonuc);
        } catch (Hata $hata) {
            Response::hata($hata->hataKodu, $hata->getMessage(), $hata->ayrintilar, $hata->httpDurum);
        }
    }

    /** @param array<string, string> $rota */
    public function olustur(Request $istek, array $rota = []): void
    {
        try {
            $veri = $istek->govde ?? [];
            $dogrulama = AdminUrunValidator::dogrula($veri);
            if (!$dogrulama['gecerli']) {
                throw new Hata('VALIDATION_ERROR', 'Doğrulama hatası.', $dogrulama['hatalar'], 422);
            }

            $sonuc = $this->service->olustur($this->kullanici($istek), $veri, $istek->istemciIp, $istek->baslik('User-Agent') ?? '');
            Response::basari($sonuc, null, 201);
        } catch (Hata $hata) {
            Response::hata($hata->hataKodu, $hata->getMessage(), $hata->ayrintilar, $hata->httpDurum);
        }
    }

    /** @param array<string, string> $rota */
    public function guncelle(Request $istek, array $rota = []): void
    {
        try {
            $veri = $istek->govde ?? [];
            $dogrulama = AdminUrunValidator::dogrula($veri, true);
            if (!$dogrulama['gecerli']) {
                throw new Hata('VALIDATION_ERROR', 'Doğrulama hatası.', $dogrulama['hatalar'], 422);
            }

            $sonuc = $this->service->guncelle($this->kullanici($istek), (int) ($rota['id'] ?? 0), $veri, $istek->istemciIp, $istek->baslik('User-Agent') ?? '');
            Response::basari($sonuc);
        } catch (Hata $hata) {
            Response::hata($hata->hataKodu, $hata->getMessage(), $hata->ayrintilar, $hata->httpDurum);
        }
    }

    /** @param array<string, string> $rota */
    public function sil(Request $istek, array $rota = []): void
    {
        try {
            $this->service->sil($this->kullanici($istek), (int) ($rota['id'] ?? 0), $istek->istemciIp, $istek->baslik('User-Agent') ?? '');
            Response::basari(['silindi' => true]);
        } catch (Hata $hata) {
            Response::hata($hata->hataKodu, $hata->getMessage(), $hata->ayrintilar, $hata->httpDurum);
        }
    }

    /** @param array<string, string> $rota */
    public function videoEkle(Request $istek, array $rota = []): void
    {
        try {
            $veri = $istek->govde ?? [];
            Response::basari($this->service->videoEkle(
                $this->kullanici($istek),
                (int) ($rota['id'] ?? 0),
                (string) ($veri['video_url'] ?? ''),
                $istek->istemciIp,
                $istek->baslik('User-Agent') ?? ''
            ), null, 201);
        } catch (Hata $hata) {
            Response::hata($hata->hataKodu, $hata->getMessage(), $hata->ayrintilar, $hata->httpDurum);
        }
    }

    /** @return array<string, mixed> */
    private function kullanici(Request $istek): array
    {
        if ($istek->kullanici === null) {
            throw new Hata('UNAUTHORIZED', 'Kimlik doğrulama gerekli.', [], 401);
        }

        return $istek->kullanici;
    }
}
