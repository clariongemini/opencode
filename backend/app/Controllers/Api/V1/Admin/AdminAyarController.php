<?php

declare(strict_types=1);

namespace Kamelya\Controllers\Api\V1\Admin;

use Kamelya\Core\Database;
use Kamelya\Core\Hata;
use Kamelya\Core\Request;
use Kamelya\Core\Response;
use Kamelya\Repositories\Admin\AyarYonetimRepository;
use Kamelya\Services\Admin\AdminAyarService;
use Kamelya\Services\AuditLogService;

/** Admin ayarlar — okuma + allowlist toplu yazma (audit'li). */
final class AdminAyarController
{
    private AdminAyarService $service;

    public function __construct()
    {
        $pdo = Database::baglanti();
        $this->service = new AdminAyarService($pdo, new AyarYonetimRepository($pdo), new AuditLogService($pdo));
    }

    /** @param array<string, string> $rota */
    public function liste(Request $istek, array $rota = []): void
    {
        Response::basari($this->service->liste());
    }

    /** @param array<string, string> $rota */
    public function guncelle(Request $istek, array $rota = []): void
    {
        try {
            $veri = $istek->govde ?? [];
            if (!isset($veri['degerler']) || !is_array($veri['degerler'])) {
                throw new Hata('VALIDATION_ERROR', 'Doğrulama hatası.', [['field' => 'degerler', 'issue' => 'required']], 422);
            }

            Response::basari($this->service->guncelle($this->kullanici($istek), $veri['degerler'], $istek->istemciIp, $istek->baslik('User-Agent') ?? ''));
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
