<?php

declare(strict_types=1);

namespace Kamelya\Controllers\Api\V1\Admin;

use Kamelya\Core\Database;
use Kamelya\Core\Hata;
use Kamelya\Core\Request;
use Kamelya\Core\Response;
use Kamelya\Services\AuditLogService;
use Kamelya\Services\OzellikToggleService;

/** Admin özellik ağacı + toggle — rol: yonetici. */
final class AdminOzellikController
{
    private OzellikToggleService $service;

    public function __construct()
    {
        $pdo = Database::baglanti();
        $this->service = new OzellikToggleService($pdo, new AuditLogService($pdo));
    }

    /** @param array<string, string> $rota */
    public function agac(Request $istek, array $rota = []): void
    {
        Response::basari($this->service->agacGetir());
    }

    /** @param array<string, string> $rota */
    public function degistir(Request $istek, array $rota = []): void
    {
        try {
            if ($istek->kullanici === null) {
                throw new Hata('UNAUTHORIZED', 'Kimlik doğrulama gerekli.', [], 401);
            }

            $veri = $istek->govde ?? [];
            if (!array_key_exists('aktif', $veri)) {
                throw new Hata('VALIDATION_ERROR', 'aktif zorunludur.', [['field' => 'aktif', 'issue' => 'required']], 422);
            }

            Response::basari($this->service->toggle(
                $istek->kullanici,
                (string) ($rota['anahtar'] ?? ''),
                (bool) $veri['aktif'],
                $istek->istemciIp,
                $istek->baslik('User-Agent') ?? ''
            ));
        } catch (Hata $hata) {
            Response::hata($hata->hataKodu, $hata->getMessage(), $hata->ayrintilar, $hata->httpDurum);
        }
    }

    /** @param array<string, string> $rota */
    public function toplu(Request $istek, array $rota = []): void
    {
        try {
            if ($istek->kullanici === null) {
                throw new Hata('UNAUTHORIZED', 'Kimlik doğrulama gerekli.', [], 401);
            }

            $veri = $istek->govde ?? [];
            if (!array_key_exists('aktif', $veri)) {
                throw new Hata('VALIDATION_ERROR', 'aktif zorunludur.', [['field' => 'aktif', 'issue' => 'required']], 422);
            }

            Response::basari($this->service->topluCocuk(
                $istek->kullanici,
                (string) ($rota['anahtar'] ?? ''),
                (bool) $veri['aktif'],
                $istek->istemciIp,
                $istek->baslik('User-Agent') ?? ''
            ));
        } catch (Hata $hata) {
            Response::hata($hata->hataKodu, $hata->getMessage(), $hata->ayrintilar, $hata->httpDurum);
        }
    }
}
