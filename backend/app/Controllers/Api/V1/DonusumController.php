<?php

declare(strict_types=1);

namespace Kamelya\Controllers\Api\V1;

use Kamelya\Core\Database;
use Kamelya\Core\Request;
use Kamelya\Core\Response;
use Kamelya\Repositories\DonusumRepository;
use Kamelya\Services\AuditLogService;
use Kamelya\Services\DonusumService;

/** GET /api/v1/donusumler — public vitrin. */
final class DonusumController
{
    private DonusumService $service;

    public function __construct()
    {
        $pdo = Database::baglanti();
        $this->service = new DonusumService($pdo, new DonusumRepository($pdo), new AuditLogService($pdo));
    }

    /** @param array<string, string> $rota */
    public function liste(Request $istek, array $rota = []): void
    {
        Response::basari($this->service->liste());
    }
}
