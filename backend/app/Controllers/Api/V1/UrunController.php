<?php

declare(strict_types=1);

namespace Kamelya\Controllers\Api\V1;

use Kamelya\Core\Database;
use Kamelya\Core\Diller;
use Kamelya\Core\Hata;
use Kamelya\Core\Request;
use Kamelya\Core\Response;
use Kamelya\Repositories\FiyatCarpaniRepository;
use Kamelya\Repositories\KategoriRepository;
use Kamelya\Repositories\UrunFiyatiRepository;
use Kamelya\Repositories\UrunRepository;
use Kamelya\Services\UrunListeleService;

/** GET /api/v1/products — ince katman: doğrulama + Service + JSON. */
final class UrunController
{
    private UrunListeleService $service;

    public function __construct()
    {
        $pdo = Database::baglanti();
        $this->service = new UrunListeleService(
            new UrunRepository($pdo),
            new UrunFiyatiRepository($pdo),
            new KategoriRepository($pdo),
            new FiyatCarpaniRepository($pdo)
        );
    }

    /** @param array<string, string> $rota */
    public function liste(Request $istek, array $rota = []): void
    {
        try {
            $dil = $istek->sorgu['lang'] ?? null;
            if (!Diller::gecerli($dil)) {
                throw new Hata(
                    'VALIDATION_ERROR',
                    'lang parametresi zorunludur.',
                    [['field' => 'lang', 'issue' => 'required_or_invalid']],
                    422
                );
            }

            $sayfa = max(1, (int) ($istek->sorgu['page'] ?? 1));
            $adet = (int) ($istek->sorgu['per_page'] ?? 20);
            $adet = min(100, max(1, $adet));
            $siralama = (string) ($istek->sorgu['sort'] ?? '-created_at');

            $hamFiltre = $istek->sorgu['filter'] ?? [];
            $filtreler = [];
            if (is_array($hamFiltre)) {
                foreach (['material', 'model', 'usage'] as $anahtar) {
                    if (isset($hamFiltre[$anahtar]) && is_string($hamFiltre[$anahtar])) {
                        $filtreler[$anahtar] = $hamFiltre[$anahtar];
                    }
                }
            }

            $arama = isset($istek->sorgu['q']) && is_string($istek->sorgu['q']) ? $istek->sorgu['q'] : null;

            $sonuc = $this->service->liste($dil, $filtreler, $sayfa, $adet, $siralama, $arama);
            Response::basari($sonuc['satirlar'], [
                'page' => $sayfa,
                'per_page' => $adet,
                'total' => $sonuc['toplam'],
            ]);
        } catch (Hata $hata) {
            Response::hata($hata->hataKodu, $hata->getMessage(), $hata->ayrintilar, $hata->httpDurum);
        }
    }

    /** @param array<string, string> $rota */
    public function detay(Request $istek, array $rota = []): void
    {
        try {
            $dil = $istek->sorgu['lang'] ?? null;
            if (!Diller::gecerli($dil)) {
                throw new Hata(
                    'VALIDATION_ERROR',
                    'lang parametresi zorunludur.',
                    [['field' => 'lang', 'issue' => 'required_or_invalid']],
                    422
                );
            }

            $id = (int) ($rota['id'] ?? 0);
            $urun = $this->service->detay($id, $dil);
            if ($urun === null) {
                throw new Hata(
                    'PRODUCT_NOT_FOUND',
                    'Ürün bulunamadı.',
                    [['field' => 'id', 'issue' => 'not_found']],
                    404
                );
            }

            Response::basari($urun);
        } catch (Hata $hata) {
            Response::hata($hata->hataKodu, $hata->getMessage(), $hata->ayrintilar, $hata->httpDurum);
        }
    }
}
