<?php

declare(strict_types=1);

namespace Kamelya\Middleware;

use Kamelya\Core\Request;
use Kamelya\Core\Response;

/** Rol kapısı — AuthMiddleware sonrası çalışır (kullanici bağlamı zorunlu). */
final class RbacMiddleware
{
    /** @var array<int, string> */
    private array $roller;

    public function __construct(string ...$roller)
    {
        $this->roller = $roller;
    }

    public function isle(Request $istek, callable $sonraki): void
    {
        $rol = $istek->kullanici['rol'] ?? null;
        if (!is_string($rol) || !in_array($rol, $this->roller, true)) {
            Response::hata('FORBIDDEN', 'Bu işlem için yetkiniz yok.', [], 403);

            return;
        }

        $sonraki($istek);
    }
}
