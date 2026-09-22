<?php

declare(strict_types=1);

namespace Kamelya\Services;

use Kamelya\Core\Hata;
use Kamelya\Repositories\DonusumRepository;
use PDO;

/** Dönüşüm vitrini + admin CRUD (audit'li). */
final class DonusumService
{
    public function __construct(
        private PDO $baglanti,
        private DonusumRepository $donusumler,
        private AuditLogService $denetim
    ) {
    }

    /** @return array<int, array<string, mixed>> */
    public function liste(): array
    {
        return $this->donusumler->aktifListe();
    }

    /** @return array<int, array<string, mixed>> */
    public function adminListe(): array
    {
        return $this->donusumler->adminListe();
    }

    /** @param array<string, mixed> $veri */
    public function olustur(array $kullanici, array $veri, string $ip, string $ajan): array
    {
        $this->dogrula($veri, false);
        $id = $this->donusumler->olustur($veri);
        $this->denetim->kaydet((int) $kullanici['id'], 'create', 'donusum', $id, null, ['baslik' => $veri['baslik']], $ip, $ajan);

        return ['id' => $id];
    }

    /** @param array<string, mixed> $veri */
    public function guncelle(array $kullanici, int $id, array $veri, string $ip, string $ajan): array
    {
        $eski = $this->donusumler->idIleGetir($id);
        if ($eski === null) {
            throw new Hata('NOT_FOUND', 'Kayıt bulunamadı.', [['field' => 'id', 'issue' => 'not_found']], 404);
        }

        $this->dogrula($veri, true);
        $this->donusumler->guncelle($id, $veri);
        $this->denetim->kaydet((int) $kullanici['id'], 'update', 'donusum', $id, ['baslik' => $eski['baslik']], ['baslik' => $veri['baslik'] ?? $eski['baslik']], $ip, $ajan);

        return ['id' => $id];
    }

    public function sil(array $kullanici, int $id, string $ip, string $ajan): void
    {
        $eski = $this->donusumler->idIleGetir($id);
        if ($eski === null) {
            throw new Hata('NOT_FOUND', 'Kayıt bulunamadı.', [['field' => 'id', 'issue' => 'not_found']], 404);
        }

        $this->donusumler->sil($id);
        $this->denetim->kaydet((int) $kullanici['id'], 'delete', 'donusum', $id, ['baslik' => $eski['baslik']], null, $ip, $ajan);
    }

    /** @param array<string, mixed> $veri */
    private function dogrula(array $veri, bool $guncelleme): void
    {
        $hatalar = [];
        if ((!$guncelleme || array_key_exists('baslik', $veri)) && trim((string) ($veri['baslik'] ?? '')) === '') {
            $hatalar[] = ['field' => 'baslik', 'issue' => 'required'];
        }

        foreach (['oncesi_gorsel', 'sonrasi_gorsel'] as $alan) {
            if ((!$guncelleme || array_key_exists($alan, $veri)) && trim((string) ($veri[$alan] ?? '')) === '') {
                $hatalar[] = ['field' => $alan, 'issue' => 'required'];
            }
        }

        if ($hatalar !== []) {
            throw new Hata('VALIDATION_ERROR', 'Doğrulama hatası.', $hatalar, 422);
        }
    }
}
