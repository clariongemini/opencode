<?php

declare(strict_types=1);

namespace Kamelya\Controllers\Api\V1;

use Kamelya\Core\Database;
use Kamelya\Core\Diller;
use Kamelya\Core\Hata;
use Kamelya\Core\Request;
use Kamelya\Core\Response;
use Kamelya\Repositories\SertifikaRepository;
use Kamelya\Services\AuditLogService;
use Kamelya\Services\SertifikaService;

/** GET /api/v1/sertifikalar — public vitrin. */
final class SertifikaController
{
    private SertifikaService $service;

    public function __construct()
    {
        $pdo = Database::baglanti();
        $this->service = new SertifikaService($pdo, new SertifikaRepository($pdo), new AuditLogService($pdo));
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
