<?php

declare(strict_types=1);

namespace Kamelya\Middleware;

use Kamelya\Core\Csrf;
use Kamelya\Core\Request;
use Kamelya\Core\Response;

/**
 * Admin yazma uçlarında X-CSRF-Token zorunluluğu (jti-bazlı).
 * Public uçlar (teklif formu) etkilenmez — orada rate limit + KVKK guards yeterli.
 */
final class CsrfMiddleware
{
    public function isle(Request $istek, callable $sonraki): void
    {
        $yazma = in_array($istek->metot, ['POST', 'PUT', 'PATCH', 'DELETE'], true);
        if (!str_starts_with($istek->yol, '/api/v1/admin') || !$yazma) {
            $sonraki($istek);

            return;
        }

        $jti = (string) ($istek->kullanici['jti'] ?? '');
        if (!Csrf::dogrula($istek->baslik('X-CSRF-Token'), $jti)) {
            Response::hata('FORBIDDEN', 'CSRF doğrulaması başarısız.', [], 403);

            return;
        }

        $sonraki($istek);
    }
}
