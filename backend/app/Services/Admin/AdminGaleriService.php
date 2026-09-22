<?php

declare(strict_types=1);

namespace Kamelya\Services\Admin;

use Kamelya\Core\Hata;
use Kamelya\Repositories\Admin\GaleriYonetimRepository;
use Kamelya\Services\AuditLogService;
use PDO;

/** Galeri yönetimi — silme = pasifleştirme (dosya ayrıca silinebilir). */
final class AdminGaleriService
{
    public function __construct(
        private PDO $baglanti,
        private GaleriYonetimRepository $galeri,
        private AuditLogService $denetim
    ) {
    }

    /** @return array<int, array<string, mixed>> */
    public function liste(): array
    {
        return $this->galeri->adminListe();
    }

    /** @param array<string, mixed> $veri */
    public function olustur(array $kullanici, array $veri, string $ip, string $ajan): array
    {
        $id = $this->galeri->olustur($veri);
        $this->denetim->kaydet((int) $kullanici['id'], 'create', 'galeri', $id, null, ['baslik' => $veri['baslik']], $ip, $ajan);

        return ['id' => $id];
    }

    /** @param array<string, mixed> $veri */
    public function guncelle(array $kullanici, int $id, array $veri, string $ip, string $ajan): array
    {
        $eski = $this->galeri->hamGetir($id);
        if ($eski === null) {
            throw new Hata('NOT_FOUND', 'Galeri kaydı bulunamadı.', [['field' => 'id', 'issue' => 'not_found']], 404);
        }

        $this->galeri->guncelle($id, $veri);
        $this->denetim->kaydet((int) $kullanici['id'], 'update', 'galeri', $id, ['baslik' => $eski['baslik']], ['baslik' => $veri['baslik'] ?? $eski['baslik']], $ip, $ajan);

        return ['id' => $id];
    }

    public function sil(array $kullanici, int $id, string $ip, string $ajan): void
    {
        $eski = $this->galeri->hamGetir($id);
        if ($eski === null) {
            throw new Hata('NOT_FOUND', 'Galeri kaydı bulunamadı.', [['field' => 'id', 'issue' => 'not_found']], 404);
        }

        $this->galeri->pasiflestir($id);
        $this->denetim->kaydet((int) $kullanici['id'], 'delete', 'galeri', $id, ['baslik' => $eski['baslik']], null, $ip, $ajan);
    }
}
