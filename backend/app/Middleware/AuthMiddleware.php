<?php

declare(strict_types=1);

namespace Kamelya\Middleware;

use Kamelya\Core\Database;
use Kamelya\Core\Jwt;
use Kamelya\Core\Request;
use Kamelya\Core\Response;
use Kamelya\Repositories\KullaniciRepository;

/** Bearer doğrulama — kullanici bağlamını Request'e enjekte eder. */
final class AuthMiddleware
{
    public function isle(Request $istek, callable $sonraki): void
    {
        $baslik = $istek->baslik('Authorization') ?? '';
        if (!str_starts_with($baslik, 'Bearer ')) {
            Response::hata('UNAUTHORIZED', 'Kimlik doğrulama gerekli.', [], 401);

            return;
        }

        $yuk = Jwt::dogrula(substr($baslik, 7));
        if ($yuk === null || ($yuk['tip'] ?? '') !== 'access') {
            Response::hata('UNAUTHORIZED', 'Token geçersiz veya süresi dolmuş.', [], 401);

            return;
        }

        $pdo = Database::baglanti();
        $kara = $pdo->prepare('SELECT id FROM token_karalistesi WHERE jti = ?');
        $kara->execute([(string) $yuk['jti']]);
        if ($kara->fetch() !== false) {
            Response::hata('UNAUTHORIZED', 'Token geçersiz veya süresi dolmuş.', [], 401);

            return;
        }

        $kullanici = (new KullaniciRepository($pdo))->idIleGetir((int) $yuk['sub']);
        if ($kullanici === null || (int) $kullanici['aktif'] !== 1) {
            Response::hata('UNAUTHORIZED', 'Token geçersiz veya süresi dolmuş.', [], 401);

            return;
        }

        $istek->kullanici = [
            'id' => (int) $kullanici['id'],
            'eposta' => $kullanici['eposta'],
            'rol' => $kullanici['rol'],
            'jti' => (string) $yuk['jti'],
        ];

        $sonraki($istek);
    }
}
