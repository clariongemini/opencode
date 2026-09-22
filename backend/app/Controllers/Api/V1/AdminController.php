<?php

declare(strict_types=1);

namespace Kamelya\Controllers\Api\V1;

use Kamelya\Core\Database;
use Kamelya\Core\Diller;
use Kamelya\Core\Hata;
use Kamelya\Core\Request;
use Kamelya\Core\Response;
use Kamelya\Repositories\Admin\AyarYonetimRepository;
use Kamelya\Repositories\BildirimRepository;
use Kamelya\Repositories\FiyatCarpaniRepository;
use Kamelya\Repositories\KategoriRepository;
use Kamelya\Repositories\TalepRepository;
use Kamelya\Repositories\UrunFiyatiRepository;
use Kamelya\Repositories\UrunRepository;
use Kamelya\Services\AuditLogService;
use Kamelya\Services\BildirimService;
use Kamelya\Services\HesapService;
use Kamelya\Services\TalepService;
use Kamelya\Services\UrunListeleService;

/**
 * Salt-okunur admin uçları (F5 ispat kapsamı) — yazma CRUD F7'de.
 * Auth + RBAC Router'da uygulanır; burası yalnızca veri döndürür.
 */
final class AdminController
{
    private UrunListeleService $urunService;

    private TalepRepository $talepler;

    private AuditLogService $denetim;

    private TalepService $talepService;

    public function __construct()
    {
        $pdo = Database::baglanti();
        $this->urunService = new UrunListeleService(
            new UrunRepository($pdo),
            new UrunFiyatiRepository($pdo),
            new KategoriRepository($pdo),
            new FiyatCarpaniRepository($pdo)
        );
        $this->talepler = new TalepRepository($pdo);
        $this->denetim = new AuditLogService($pdo);
        $this->talepService = new TalepService(
            $pdo,
            new TalepRepository($pdo),
            new UrunRepository($pdo),
            new HesapService(
                new UrunFiyatiRepository($pdo),
                new KategoriRepository($pdo),
                new FiyatCarpaniRepository($pdo)
            ),
            new BildirimService($pdo, new BildirimRepository($pdo), new AyarYonetimRepository($pdo)),
            new AuditLogService($pdo)
        );
    }

    /** @param array<string, string> $rota */
    public function urunler(Request $istek, array $rota = []): void
    {
        $dil = $istek->sorgu['lang'] ?? 'tr';
        if (!Diller::gecerli($dil)) {
            Response::hata('VALIDATION_ERROR', 'lang parametresi geçersiz.', [['field' => 'lang', 'issue' => 'invalid']], 422);

            return;
        }

        $sonuc = $this->urunService->liste($dil, [], 1, 20, '-created_at');
        Response::basari($sonuc['satirlar'], ['page' => 1, 'per_page' => 20, 'total' => $sonuc['toplam']]);
    }

    /** @param array<string, string> $rota */
    public function talepler(Request $istek, array $rota = []): void
    {
        $filtreler = [];
        foreach (['durum', 'sehir', 'tur'] as $alan) {
            if (isset($istek->sorgu[$alan]) && is_string($istek->sorgu[$alan])) {
                $filtreler[$alan] = $istek->sorgu[$alan];
            }
        }

        $sonuc = $this->talepler->listele($filtreler, 1, 20);
        Response::basari($sonuc['satirlar'], ['page' => 1, 'per_page' => 20, 'total' => $sonuc['toplam']]);
    }

    /** @param array<string, string> $rota */
    public function talepDurum(Request $istek, array $rota = []): void
    {
        try {
            if ($istek->kullanici === null) {
                throw new Hata('UNAUTHORIZED', 'Kimlik doğrulama gerekli.', [], 401);
            }

            $veri = $istek->govde ?? [];
            Response::basari($this->talepService->durumDegistir(
                $istek->kullanici,
                (int) ($rota['id'] ?? 0),
                (string) ($veri['durum'] ?? ''),
                $istek->istemciIp,
                $istek->baslik('User-Agent') ?? ''
            ));
        } catch (Hata $hata) {
            Response::hata($hata->hataKodu, $hata->getMessage(), $hata->ayrintilar, $hata->httpDurum);
        }
    }

    /** @param array<string, string> $rota */
    public function denetimKayitlari(Request $istek, array $rota = []): void
    {
        $sonuc = $this->denetim->liste(1, 20);
        Response::basari($sonuc['satirlar'], ['page' => 1, 'per_page' => 20, 'total' => $sonuc['toplam']]);
    }
}
