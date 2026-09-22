<?php

declare(strict_types=1);

namespace Kamelya\Services;

use Kamelya\Core\Hata;
use Kamelya\Repositories\AtolyeRepository;
use Kamelya\Services\Admin\YuklemeService;
use PDO;

/** Atölye akışı — liste + yükleme (upload hedefi `atolye`) + silme. */
final class AtolyeService
{
    public function __construct(
        private PDO $baglanti,
        private AtolyeRepository $atolye,
        private AuditLogService $denetim,
        private YuklemeService $yukleme
    ) {
    }

    /** @return array<int, array<string, mixed>> */
    public function liste(): array
    {
        return $this->atolye->aktifListe();
    }

    /** @return array<int, array<string, mixed>> */
    public function adminListe(): array
    {
        return $this->atolye->adminListe();
    }

    /** @param array<string, mixed> $dosya */
    public function yukle(array $kullanici, array $dosya, string $baslik, ?string $aciklama, string $ip, string $ajan): array
    {
        if (trim($baslik) === '' || mb_strlen($baslik) > 190) {
            throw new Hata('VALIDATION_ERROR', 'Başlık zorunludur.', [['field' => 'baslik', 'issue' => 'required']], 422);
        }

        $yuklenen = $this->yukleme->dosyaKaydet($dosya, 'atolye');
        $id = $this->atolye->olustur($baslik, $aciklama, $yuklenen);
        $this->denetim->kaydet((int) $kullanici['id'], 'create', 'atolye', $id, null, ['baslik' => $baslik], $ip, $ajan);

        return ['id' => $id, 'dosya_yolu' => $yuklenen];
    }

    public function sil(array $kullanici, int $id, string $ip, string $ajan): void
    {
        $eski = $this->atolye->idIleGetir($id);
        if ($eski === null) {
            throw new Hata('NOT_FOUND', 'Fotoğraf bulunamadı.', [['field' => 'id', 'issue' => 'not_found']], 404);
        }

        $this->yukleme->dosyaSil((string) $eski['dosya_yolu']);
        $this->atolye->sil($id);
        $this->denetim->kaydet((int) $kullanici['id'], 'delete', 'atolye', $id, ['baslik' => $eski['baslik']], null, $ip, $ajan);
    }
}
