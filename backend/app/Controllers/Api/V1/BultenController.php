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
use Kamelya\Repositories\BultenRepository;
use Kamelya\Services\AuditLogService;
use Kamelya\Services\BildirimService;
use Kamelya\Services\BultenService;

/** Public bülten uçları (çift onay). Onay/iptal GET istisnası: e-posta link akışı. */
final class BultenController
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
    public function kaydol(Request $istek, array $rota = []): void
    {
        try {
            $veri = $istek->govde ?? [];
            if (!Diller::gecerli($veri['dil_kodu'] ?? 'tr')) {
                throw new Hata('VALIDATION_ERROR', 'dil_kodu geçersiz.', [['field' => 'dil_kodu', 'issue' => 'invalid']], 422);
            }

            Response::basari($this->service->kaydol($veri, $istek->istemciIp, (string) \Kamelya\Core\Config::al('app.url', '')), null, 201);
        } catch (Hata $hata) {
            Response::hata($hata->hataKodu, $hata->getMessage(), $hata->ayrintilar, $hata->httpDurum);
        }
    }

    /** @param array<string, string> $rota */
    public function onayla(Request $istek, array $rota = []): void
    {
        try {
            $token = (string) ($istek->sorgu['token'] ?? '');
            if ($token === '') {
                throw new Hata('VALIDATION_ERROR', 'token zorunludur.', [['field' => 'token', 'issue' => 'required']], 422);
            }

            Response::basari($this->service->onayla($token));
        } catch (Hata $hata) {
            Response::hata($hata->hataKodu, $hata->getMessage(), $hata->ayrintilar, $hata->httpDurum);
        }
    }

    /** @param array<string, string> $rota */
    public function iptal(Request $istek, array $rota = []): void
    {
        try {
            $token = (string) ($istek->sorgu['token'] ?? '');
            if ($token === '') {
                throw new Hata('VALIDATION_ERROR', 'token zorunludur.', [['field' => 'token', 'issue' => 'required']], 422);
            }

            Response::basari($this->service->iptalEt($token));
        } catch (Hata $hata) {
            Response::hata($hata->hataKodu, $hata->getMessage(), $hata->ayrintilar, $hata->httpDurum);
        }
    }
}
