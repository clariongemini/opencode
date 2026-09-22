<?php

declare(strict_types=1);

namespace Kamelya\Services;

use Kamelya\Core\Hata;
use Kamelya\Core\Slug;
use Kamelya\Repositories\SanalTurRepository;
use PDO;

/** Sanal tur — embed domain allowlistli + audit'li CRUD. */
final class SanalTurService
{
    /** @var array<int, string> */
    public const IZINLI_DOMAIN = ['matterport.com', 'kuula.co', 'google.com'];

    public function __construct(
        private PDO $baglanti,
        private SanalTurRepository $turlar,
        private AuditLogService $denetim
    ) {
    }

    /** @return array<int, array<string, mixed>> */
    public function liste(string $dil): array
    {
        return $this->turlar->aktifListe($dil);
    }

    /** @return array<int, array<string, mixed>> */
    public function adminListe(): array
    {
        return $this->turlar->adminListe();
    }

    /** @param array<string, mixed> $veri */
    public function olustur(array $kullanici, array $veri, string $ip, string $ajan): array
    {
        $this->dogrula($veri, false);
        $id = $this->turlar->olustur($veri);
        $this->cevirileriKaydet($id, $veri['ceviriler'] ?? []);
        $this->denetim->kaydet((int) $kullanici['id'], 'create', 'sanal_tur', $id, null, ['baslik' => $veri['baslik']], $ip, $ajan);

        return ['id' => $id];
    }

    /** @param array<string, mixed> $veri */
    public function guncelle(array $kullanici, int $id, array $veri, string $ip, string $ajan): array
    {
        $eski = $this->turlar->idIleGetir($id);
        if ($eski === null) {
            throw new Hata('NOT_FOUND', 'Tur bulunamadı.', [['field' => 'id', 'issue' => 'not_found']], 404);
        }

        $this->dogrula($veri, true);
        $this->turlar->guncelle($id, $veri);
        $this->cevirileriKaydet($id, $veri['ceviriler'] ?? []);
        $this->denetim->kaydet((int) $kullanici['id'], 'update', 'sanal_tur', $id, ['baslik' => $eski['baslik']], ['baslik' => $veri['baslik'] ?? $eski['baslik']], $ip, $ajan);

        return ['id' => $id];
    }

    public function sil(array $kullanici, int $id, string $ip, string $ajan): void
    {
        $eski = $this->turlar->idIleGetir($id);
        if ($eski === null) {
            throw new Hata('NOT_FOUND', 'Tur bulunamadı.', [['field' => 'id', 'issue' => 'not_found']], 404);
        }

        $this->turlar->sil($id);
        $this->denetim->kaydet((int) $kullanici['id'], 'delete', 'sanal_tur', $id, ['baslik' => $eski['baslik']], null, $ip, $ajan);
    }

    /** @param array<string, mixed> $veri */
    private function dogrula(array $veri, bool $guncelleme): void
    {
        $hatalar = [];
        if ((!$guncelleme || array_key_exists('baslik', $veri)) && trim((string) ($veri['baslik'] ?? '')) === '') {
            $hatalar[] = ['field' => 'baslik', 'issue' => 'required'];
        }

        if ((!$guncelleme || array_key_exists('embed_url', $veri)) && !$this->embedGecerli((string) ($veri['embed_url'] ?? ''))) {
            $hatalar[] = ['field' => 'embed_url', 'issue' => 'invalid_domain'];
        }

        if ($hatalar !== []) {
            throw new Hata('VALIDATION_ERROR', 'Doğrulama hatası.', $hatalar, 422);
        }
    }

    public static function embedGecerli(string $url): bool
    {
        $parca = parse_url(trim($url));
        if (!is_array($parca) || ($parca['scheme'] ?? '') !== 'https') {
            return false;
        }

        $host = strtolower((string) ($parca['host'] ?? ''));
        foreach (self::IZINLI_DOMAIN as $alan) {
            if (str_ends_with($host, $alan)) {
                return true;
            }
        }

        return false;
    }

    /** @param array<string, array<string, mixed>> $ceviriler */
    private function cevirileriKaydet(int $id, array $ceviriler): void
    {
        foreach ($ceviriler as $dil => $ceviri) {
            if (!is_array($ceviri) || trim((string) ($ceviri['baslik'] ?? '')) === '') {
                continue;
            }

            $slug = trim((string) ($ceviri['slug'] ?? ''));
            if ($slug === '') {
                $slug = Slug::uret((string) $ceviri['baslik']);
            }

            $this->turlar->ceviriKaydet($id, $dil, (string) $ceviri['baslik'], $ceviri['aciklama'] ?? null, $slug);
        }
    }
}
