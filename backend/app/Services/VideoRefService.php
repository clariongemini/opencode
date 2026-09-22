<?php

declare(strict_types=1);

namespace Kamelya\Services;

use Kamelya\Core\Hata;
use Kamelya\Repositories\VideoRefRepository;
use PDO;

/** Video referans — URL allowlistli + audit'li CRUD. */
final class VideoRefService
{
    /** @var array<int, string> */
    public const IZINLI_HOST = ['youtube.com', 'youtu.be', 'vimeo.com', 'instagram.com'];

    public function __construct(
        private PDO $baglanti,
        private VideoRefRepository $videolar,
        private AuditLogService $denetim
    ) {
    }

    /** @return array<int, array<string, mixed>> */
    public function liste(): array
    {
        $cikti = [];
        foreach ($this->videolar->aktifListe() as $satir) {
            $satir['embed_url'] = self::embedCevir((string) $satir['video_url']);
            $cikti[] = $satir;
        }

        return $cikti;
    }

    /** @return array<int, array<string, mixed>> */
    public function adminListe(): array
    {
        return $this->videolar->adminListe();
    }

    /** @param array<string, mixed> $veri */
    public function olustur(array $kullanici, array $veri, string $ip, string $ajan): array
    {
        $this->dogrula($veri, false);
        $id = $this->videolar->olustur($veri);
        $this->denetim->kaydet((int) $kullanici['id'], 'create', 'video_referans', $id, null, ['musteri_adi' => $veri['musteri_adi']], $ip, $ajan);

        return ['id' => $id];
    }

    /** @param array<string, mixed> $veri */
    public function guncelle(array $kullanici, int $id, array $veri, string $ip, string $ajan): array
    {
        $eski = $this->videolar->idIleGetir($id);
        if ($eski === null) {
            throw new Hata('NOT_FOUND', 'Kayıt bulunamadı.', [['field' => 'id', 'issue' => 'not_found']], 404);
        }

        $this->dogrula($veri, true);
        $this->videolar->guncelle($id, $veri);
        $this->denetim->kaydet((int) $kullanici['id'], 'update', 'video_referans', $id, ['musteri_adi' => $eski['musteri_adi']], ['musteri_adi' => $veri['musteri_adi'] ?? $eski['musteri_adi']], $ip, $ajan);

        return ['id' => $id];
    }

    public function sil(array $kullanici, int $id, string $ip, string $ajan): void
    {
        $eski = $this->videolar->idIleGetir($id);
        if ($eski === null) {
            throw new Hata('NOT_FOUND', 'Kayıt bulunamadı.', [['field' => 'id', 'issue' => 'not_found']], 404);
        }

        $this->videolar->sil($id);
        $this->denetim->kaydet((int) $kullanici['id'], 'delete', 'video_referans', $id, ['musteri_adi' => $eski['musteri_adi']], null, $ip, $ajan);
    }

    public static function videoGecerli(string $url): bool
    {
        $parca = parse_url(trim($url));
        if (!is_array($parca) || ($parca['scheme'] ?? '') !== 'https') {
            return false;
        }

        $host = strtolower((string) ($parca['host'] ?? ''));
        foreach (self::IZINLI_HOST as $alan) {
            if (str_ends_with($host, $alan)) {
                return true;
            }
        }

        return false;
    }

    public static function embedCevir(string $url): string
    {
        return trim($url);
    }

    /** @param array<string, mixed> $veri */
    private function dogrula(array $veri, bool $guncelleme): void
    {
        $hatalar = [];
        if ((!$guncelleme || array_key_exists('musteri_adi', $veri)) && trim((string) ($veri['musteri_adi'] ?? '')) === '') {
            $hatalar[] = ['field' => 'musteri_adi', 'issue' => 'required'];
        }

        if ((!$guncelleme || array_key_exists('video_url', $veri)) && !self::videoGecerli((string) ($veri['video_url'] ?? ''))) {
            $hatalar[] = ['field' => 'video_url', 'issue' => 'invalid_domain'];
        }

        if ($hatalar !== []) {
            throw new Hata('VALIDATION_ERROR', 'Doğrulama hatası.', $hatalar, 422);
        }
    }
}
