<?php

declare(strict_types=1);

namespace Kamelya\Middleware;

use Kamelya\Core\Config;
use Kamelya\Core\Request;
use Kamelya\Core\Response;
use Throwable;

/** En dış halka: yakalanmamış hatayı 500 JSON'a çevirir, detay sızdırmaz. */
final class ErrorHandlerMiddleware
{
    public function isle(Request $istek, callable $sonraki): void
    {
        try {
            $sonraki($istek);
        } catch (Throwable $hata) {
            error_log('[kamelya][hata] ' . get_class($hata) . ': ' . $hata->getMessage());
            $ayiklama = (bool) Config::al('app.hata_ayiklama', false);
            $ayrinti = $ayiklama ? [['issue' => $hata->getMessage()]] : [];
            Response::hata('INTERNAL_ERROR', 'Beklenmeyen bir hata oluştu.', $ayrinti, 500);
        }
    }
}
