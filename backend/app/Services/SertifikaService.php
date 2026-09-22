<?php

declare(strict_types=1);

namespace Kamelya\Services;

use Kamelya\Core\Hata;
use Kamelya\Repositories\SertifikaRepository;
use PDO;

/** Sertifika vitrini — public liste + admin CRUD (audit'li). */
final class SertifikaService
{
    public const KURUMLAR = ['CE', 'TUV', 'ISO', 'Diger'];

    public function __construct(
        private PDO $baglanti,
        private SertifikaRepository $sertifikalar,
        private AuditLogService $denetim
    ) {
    }

    /** @return array<int, array<string, mixed>> */
    public function liste(string $dil): array
    {
        $cikti = [];
        foreach ($this->sertifikalar->aktifListe($dil) as $satir) {
            $cikti[] = [
                'id' => (int) $satir['id'],
                'baslik' => $satir['ceviri_baslik'] ?? $satir['baslik'],
                'kurum' => $satir['kurum'],
                'belge_no' => $satir['belge_no'],
                'gecerlilik_tarihi' => $satir['gecerlilik_tarihi'],
                'logo_yolu' => $satir['logo_yolu'],
                'aciklama' => $satir['ceviri_aciklama'] ?? $satir['aciklama'],
            ];
        }

        return $cikti;
    }

    /** @return array<int, array<string, mixed>> */
    public function adminListe(): array
    {
        return $this->sertifikalar->adminListe();
    }

    /** @param array<string, mixed> $veri */
    public function olustur(array $kullanici, array $veri, string $ip, string $ajan): array
    {
        if (isset($veri['kurum']) && !in_array($veri['kurum'], self::KURUMLAR, true)) {
            throw new Hata('VALIDATION_ERROR', 'Geçersiz kurum.', [['field' => 'kurum', 'issue' => 'invalid']], 422);
        }

        if (trim((string) ($veri['baslik'] ?? '')) === '') {
            throw new Hata('VALIDATION_ERROR', 'Başlık zorunludur.', [['field' => 'baslik', 'issue' => 'required']], 422);
        }

        $id = $this->sertifikalar->olustur($veri);
        $this->cevirileriKaydet($id, $veri['ceviriler'] ?? []);
        $this->denetim->kaydet((int) $kullanici['id'], 'create', 'sertifika', $id, null, ['baslik' => $veri['baslik']], $ip, $ajan);

        return ['id' => $id];
    }

    /** @param array<string, mixed> $veri */
    public function guncelle(array $kullanici, int $id, array $veri, string $ip, string $ajan): array
    {
        $eski = $this->sertifikalar->idIleGetir($id);
        if ($eski === null) {
            throw new Hata('NOT_FOUND', 'Sertifika bulunamadı.', [['field' => 'id', 'issue' => 'not_found']], 404);
        }

        $this->sertifikalar->guncelle($id, $veri);
        $this->cevirileriKaydet($id, $veri['ceviriler'] ?? []);
        $this->denetim->kaydet((int) $kullanici['id'], 'update', 'sertifika', $id, ['baslik' => $eski['baslik']], ['baslik' => $veri['baslik'] ?? $eski['baslik']], $ip, $ajan);

        return ['id' => $id];
    }

    public function sil(array $kullanici, int $id, string $ip, string $ajan): void
    {
        $eski = $this->sertifikalar->idIleGetir($id);
        if ($eski === null) {
            throw new Hata('NOT_FOUND', 'Sertifika bulunamadı.', [['field' => 'id', 'issue' => 'not_found']], 404);
        }

        $this->sertifikalar->pasiflestir($id);
        $this->denetim->kaydet((int) $kullanici['id'], 'delete', 'sertifika', $id, ['baslik' => $eski['baslik']], null, $ip, $ajan);
    }

    /** @param array<string, array<string, mixed>> $ceviriler */
    private function cevirileriKaydet(int $id, array $ceviriler): void
    {
        foreach ($ceviriler as $dil => $ceviri) {
            if (!is_array($ceviri) || trim((string) ($ceviri['baslik'] ?? '')) === '') {
                continue;
            }

            $this->sertifikalar->ceviriKaydet($id, $dil, (string) $ceviri['baslik'], $ceviri['aciklama'] ?? null);
        }
    }
}
