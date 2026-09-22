<?php

declare(strict_types=1);

namespace Kamelya\Controllers\Api\V1;

use Kamelya\Core\Database;
use Kamelya\Core\Request;
use Kamelya\Core\Response;
use Kamelya\Repositories\VideoRefRepository;
use Kamelya\Services\AuditLogService;
use Kamelya\Services\VideoRefService;

/** GET /api/v1/video-referanslar — public vitrin (embed URL dahil). */
final class VideoRefController
{
    private VideoRefService $service;

    public function __construct()
    {
        $pdo = Database::baglanti();
        $this->service = new VideoRefService($pdo, new VideoRefRepository($pdo), new AuditLogService($pdo));
    }

    /** @param array<string, string> $rota */
    public function liste(Request $istek, array $rota = []): void
    {
        Response::basari($this->service->liste());
    }
}
