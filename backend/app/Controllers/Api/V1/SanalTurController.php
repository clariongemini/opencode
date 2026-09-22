<?php

declare(strict_types=1);

namespace Kamelya\Controllers\Api\V1;

use Kamelya\Core\Database;
use Kamelya\Core\Diller;
use Kamelya\Core\Request;
use Kamelya\Core\Response;
use Kamelya\Repositories\SanalTurRepository;
use Kamelya\Services\AuditLogService;
use Kamelya\Services\SanalTurService;

/** GET /api/v1/sanal-tur — public vitrin. */
final class SanalTurController
{
    private SanalTurService $service;

    public function __construct()
    {
        $pdo = Database::baglanti();
        $this->service = new SanalTurService($pdo, new SanalTurRepository($pdo), new AuditLogService($pdo));
    }

    /** @param array<string, string> $rota */
    public function liste(Request $istek, array $rota = []): void
    {
        $dil = $istek->sorgu['lang'] ?? 'tr';
        if (!Diller::gecerli($dil)) {
            Response::hata('VALIDATION_ERROR', 'lang parametresi geçersiz.', [['field' => 'lang', 'issue' => 'invalid']], 422);

            return;
        }

        Response::basari($this->service->liste($dil));
    }
}
