<?php

declare(strict_types=1);

namespace Kamelya\Controllers\Api\V1\Admin;

use Kamelya\Core\Database;
use Kamelya\Core\Hata;
use Kamelya\Core\Request;
use Kamelya\Core\Response;
use Kamelya\Repositories\Admin\AyarYonetimRepository;
use Kamelya\Repositories\BildirimRepository;
use Kamelya\Repositories\BultenRepository;
use Kamelya\Services\AuditLogService;
use Kamelya\Services\BildirimService;
use Kamelya\Services\BultenService;

/** Admin bülten: liste, CSV, toplu gönderim — rol: yonetici|editor. */
final class AdminBultenController
{
    private BultenService $service;

    public function __construct()
    {
        $pdo = Database::baglanti();
        $this->service = new BultenService(
            $pdo,
            new BultenRepository($pdo),
            new BildirimService($pdo, new BildirimRepository($pdo), new AyarYonetimRepository($pdo)),
            new AuditLogService($pdo)
        );
    }

    /** @param array<string, string> $rota */
    public function liste(Request $istek, array $rota = []): void
    {
        $sayfa = max(1, (int) ($istek->sorgu['page'] ?? 1));
        $adet = min(100, max(1, (int) ($istek->sorgu['per_page'] ?? 20)));

        if (($istek->sorgu['format'] ?? '') === 'csv') {
            $sonuc = $this->service->liste(1, 10000);
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="bulten-aboneleri.csv"');
            echo "eposta,ad_soyad,dil_kodu,durum,created_at\n";
            foreach ($sonuc['satirlar'] as $satir) {
                echo implode(',', [
                    $satir['eposta'], '"' . str_replace('"', '""', (string) ($satir['ad_soyad'] ?? '')) . '"',
                    $satir['dil_kodu'], $satir['durum'], $satir['created_at'],
                ]) . "\n";
            }

            return;
        }

        $sonuc = $this->service->liste($sayfa, $adet);
        Response::basari($sonuc['satirlar'], ['page' => $sayfa, 'per_page' => $adet, 'total' => $sonuc['toplam']]);
    }

    /** @param array<string, string> $rota */
    public function toplu(Request $istek, array $rota = []): void
    {
        try {
            if ($istek->kullanici === null) {
                throw new Hata('UNAUTHORIZED', 'Kimlik doğrulama gerekli.', [], 401);
            }

            $veri = $istek->govde ?? [];
            Response::basari($this->service->topluGonder(
                $istek->kullanici,
                (string) ($veri['konu'] ?? ''),
                (string) ($veri['govde'] ?? ''),
                $istek->istemciIp,
                $istek->baslik('User-Agent') ?? ''
            ), null, 201);
        } catch (Hata $hata) {
            Response::hata($hata->hataKodu, $hata->getMessage(), $hata->ayrintilar, $hata->httpDurum);
        }
    }
}
