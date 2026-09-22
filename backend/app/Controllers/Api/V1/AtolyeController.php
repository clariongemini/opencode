<?php

declare(strict_types=1);

namespace Kamelya\Controllers\Api\V1;

use Kamelya\Core\Database;
use Kamelya\Core\Request;
use Kamelya\Core\Response;
use Kamelya\Repositories\AtolyeRepository;
use Kamelya\Repositories\Admin\BlogYonetimRepository;
use Kamelya\Repositories\Admin\GaleriYonetimRepository;
use Kamelya\Repositories\Admin\ResimYonetimRepository;
use Kamelya\Repositories\Admin\UrunYonetimRepository;
use Kamelya\Services\Admin\YuklemeService;
use Kamelya\Services\AtolyeService;
use Kamelya\Services\AuditLogService;

/** GET /api/v1/atolye — public vitrin. */
final class AtolyeController
{
    private AtolyeService $service;

    public function __construct()
    {
        $pdo = Database::baglanti();
        $this->service = new AtolyeService(
            $pdo,
            new AtolyeRepository($pdo),
            new AuditLogService($pdo),
            new YuklemeService(
                $pdo,
                new ResimYonetimRepository($pdo),
                new UrunYonetimRepository($pdo),
                new BlogYonetimRepository($pdo),
                new GaleriYonetimRepository($pdo),
                new AuditLogService($pdo)
            )
        );
    }

    /** @param array<string, string> $rota */
    public function liste(Request $istek, array $rota = []): void
    {
        Response::basari($this->service->liste());
    }
}
