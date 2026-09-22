<?php

declare(strict_types=1);

namespace Kamelya\Controllers\Api\V1;

use Kamelya\Core\Database;
use Kamelya\Core\Request;
use Kamelya\Core\Response;
use Kamelya\Repositories\KullaniciRepository;
use Kamelya\Services\AuditLogService;
use Kamelya\Services\EkipService;

/** GET /api/v1/ekip — public vitrin (PII yok). */
final class EkipController
{
    private EkipService $service;

    public function __construct()
    {
        $pdo = Database::baglanti();
        $this->service = new EkipService($pdo, new KullaniciRepository($pdo), new AuditLogService($pdo));
    }

    /** @param array<string, string> $rota */
    public function liste(Request $istek, array $rota = []): void
    {
        Response::basari($this->service->liste());
    }
}
