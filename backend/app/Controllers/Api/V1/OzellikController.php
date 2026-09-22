<?php

declare(strict_types=1);

namespace Kamelya\Controllers\Api\V1;

use Kamelya\Core\Database;
use Kamelya\Core\Request;
use Kamelya\Core\Response;
use Kamelya\Services\AuditLogService;
use Kamelya\Services\OzellikToggleService;

/** GET /api/v1/ozellikler — public etkin harita (önbellekli). */
final class OzellikController
{
    private OzellikToggleService $service;

    public function __construct()
    {
        $pdo = Database::baglanti();
        $this->service = new OzellikToggleService($pdo, new AuditLogService($pdo));
    }

    /** @param array<string, string> $rota */
    public function harita(Request $istek, array $rota = []): void
    {
        $cikti = [];
        foreach ($this->service->agacGetir() as $kok) {
            $this->duzlestir($kok, $cikti);
        }

        Response::basari($cikti);
    }

    /** @param array<string, mixed> $dugum */
    private function duzlestir(array $dugum, array &$cikti): void
    {
        $cikti[$dugum['anahtar']] = $dugum['etkin_aktif'];
        foreach ($dugum['cocuklar'] as $cocuk) {
            $this->duzlestir($cocuk, $cikti);
        }
    }
}
