<?php

declare(strict_types=1);

namespace Kamelya\Services;

use Kamelya\Core\Hata;
use Kamelya\Core\Jwt;
use Kamelya\Repositories\KullaniciRepository;
use PDO;

/**
 * Kimlik doğrulama — access 1 saat, refresh 7 gün.
 * Refresh rotasyonu: eski jti kara listeye alınır, yeni çift üretilir.
 */
final class AuthService
{
    public const ACCESS_SURE = 3600;

    public const REFRESH_SURE = 7 * 24 * 3600;

    public function __construct(
        private PDO $baglanti,
        private KullaniciRepository $kullanicilar,
        private AuditLogService $denetim
    ) {
    }

    /** @return array<string, mixed> */
    public function giris(string $eposta, string $sifre, string $ip, string $ajan): array
    {
        $kullanici = $this->kullanicilar->epostaIleGetir($eposta);
        if ($kullanici === null || (int) $kullanici['aktif'] !== 1
            || !password_verify($sifre, (string) $kullanici['sifre_hash'])) {
            // Bilerek genel mesaj: kullanıcı varlığı sızdırılmaz.
            throw new Hata(
                'UNAUTHORIZED',
                'E-posta veya şifre hatalı.',
                [['field' => 'eposta', 'issue' => 'invalid_credentials']],
                401
            );
        }

        $id = (int) $kullanici['id'];
        $rol = (string) $kullanici['rol'];
        $cift = $this->ciftUret($id, $rol);
        $this->kullanicilar->sonGirisGuncelle($id);
        $this->denetim->kaydet($id, 'login', 'kullanici', $id, null, ['rol' => $rol], $ip, $ajan);

        return $cift + ['kullanici' => ['id' => $id, 'eposta' => $kullanici['eposta'], 'rol' => $rol]];
    }

    /** @return array<string, mixed> */
    public function yenile(string $refreshJeton, string $ip, string $ajan): array
    {
        $yuk = Jwt::dogrula($refreshJeton);
        if ($yuk === null || ($yuk['tip'] ?? '') !== 'refresh') {
            throw new Hata('UNAUTHORIZED', 'Refresh token geçersiz.', [], 401);
        }

        if ($this->karaListede((string) $yuk['jti'])) {
            throw new Hata('UNAUTHORIZED', 'Refresh token geçersiz.', [], 401);
        }

        $id = (int) $yuk['sub'];
        $kullanici = $this->kullanicilar->idIleGetir($id);
        if ($kullanici === null || (int) $kullanici['aktif'] !== 1) {
            throw new Hata('UNAUTHORIZED', 'Refresh token geçersiz.', [], 401);
        }

        $this->karaListeyeAl((string) $yuk['jti'], $id, 'refresh', (int) $yuk['exp']);
        $cift = $this->ciftUret($id, (string) $kullanici['rol']);
        $this->denetim->kaydet($id, 'token_yenileme', 'kullanici', $id, null, null, $ip, $ajan);

        return $cift;
    }

    public function cikis(string $accessJeton, string $ip, string $ajan): void
    {
        $yuk = Jwt::dogrula($accessJeton);
        if ($yuk === null) {
            return;
        }

        $id = (int) $yuk['sub'];
        $this->karaListeyeAl((string) $yuk['jti'], $id, 'access', (int) $yuk['exp']);
        $this->denetim->kaydet($id, 'logout', 'kullanici', $id, null, null, $ip, $ajan);
    }

    public function karaListede(string $jti): bool
    {
        $ifade = $this->baglanti->prepare('SELECT id FROM token_karalistesi WHERE jti = ?');
        $ifade->execute([$jti]);

        return $ifade->fetch() !== false;
    }

    /** @return array<string, mixed> */
    private function ciftUret(int $id, string $rol): array
    {
        return [
            'access_token' => Jwt::uret($id, $rol, self::ACCESS_SURE, 'access'),
            'refresh_token' => Jwt::uret($id, $rol, self::REFRESH_SURE, 'refresh'),
            'token_type' => 'Bearer',
            'expires_in' => self::ACCESS_SURE,
        ];
    }

    private function karaListeyeAl(string $jti, int $kullaniciId, string $tur, int $exp): void
    {
        $ifade = $this->baglanti->prepare(
            'INSERT INTO token_karalistesi (jti, kullanici_id, tur, gecerlilik_sonu)
             VALUES (?, ?, ?, FROM_UNIXTIME(?)) ON DUPLICATE KEY UPDATE jti = jti'
        );
        $ifade->execute([$jti, $kullaniciId, $tur, $exp]);
        // Fırsatçı temizlik: süresi dolmuş kara liste satırları.
        $this->baglanti->exec('DELETE FROM token_karalistesi WHERE gecerlilik_sonu < NOW()');
    }
}
