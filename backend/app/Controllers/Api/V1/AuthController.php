<?php

declare(strict_types=1);

namespace Kamelya\Controllers\Api\V1;

use Kamelya\Core\Csrf;
use Kamelya\Core\Database;
use Kamelya\Core\Hata;
use Kamelya\Core\Jwt;
use Kamelya\Core\Request;
use Kamelya\Core\Response;
use Kamelya\Repositories\KullaniciRepository;
use Kamelya\Services\AuditLogService;
use Kamelya\Services\AuthService;

/** POST /api/v1/auth/* — ince katman, rate limit Router dışı (özel kova). */
final class AuthController
{
    private AuthService $service;

    public function __construct()
    {
        $pdo = Database::baglanti();
        $this->service = new AuthService(
            $pdo,
            new KullaniciRepository($pdo),
            new AuditLogService($pdo)
        );
    }

    /** @param array<string, string> $rota */
    public function giris(Request $istek, array $rota = []): void
    {
        try {
            $veri = $istek->govde ?? [];
            $eposta = trim((string) ($veri['eposta'] ?? ''));
            $sifre = (string) ($veri['sifre'] ?? '');
            if ($eposta === '' || $sifre === '') {
                throw new Hata('VALIDATION_ERROR', 'Doğrulama hatası.', [['field' => 'eposta', 'issue' => 'required']], 422);
            }

            $sonuc = $this->service->giris($eposta, $sifre, $istek->istemciIp, $istek->baslik('User-Agent') ?? '');
            $sonuc['csrf_token'] = $this->csrfUret($sonuc['access_token']);
            Response::basari($sonuc);
        } catch (Hata $hata) {
            Response::hata($hata->hataKodu, $hata->getMessage(), $hata->ayrintilar, $hata->httpDurum);
        }
    }

    /** @param array<string, string> $rota */
    public function yenile(Request $istek, array $rota = []): void
    {
        try {
            $veri = $istek->govde ?? [];
            $jeton = (string) ($veri['refresh_token'] ?? '');
            if ($jeton === '') {
                throw new Hata('VALIDATION_ERROR', 'Doğrulama hatası.', [['field' => 'refresh_token', 'issue' => 'required']], 422);
            }

            $sonuc = $this->service->yenile($jeton, $istek->istemciIp, $istek->baslik('User-Agent') ?? '');
            $sonuc['csrf_token'] = $this->csrfUret($sonuc['access_token']);
            Response::basari($sonuc);
        } catch (Hata $hata) {
            Response::hata($hata->hataKodu, $hata->getMessage(), $hata->ayrintilar, $hata->httpDurum);
        }
    }

    /** @param array<string, string> $rota */
    public function cikis(Request $istek, array $rota = []): void
    {
        $baslik = $istek->baslik('Authorization') ?? '';
        if (str_starts_with($baslik, 'Bearer ')) {
            $this->service->cikis(substr($baslik, 7), $istek->istemciIp, $istek->baslik('User-Agent') ?? '');
        }

        Response::basari(['cikis' => true]);
    }

    private function csrfUret(string $accessJeton): string
    {
        $yuk = Jwt::dogrula($accessJeton);

        return Csrf::tokenUret((string) ($yuk['jti'] ?? ''));
    }
}
