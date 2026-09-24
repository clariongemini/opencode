<?php

declare(strict_types=1);

namespace Kamelya\Services\Admin;

use Kamelya\Core\Hata;
use Kamelya\Repositories\Admin\SssYonetimRepository;
use Kamelya\Services\AuditLogService;
use PDO;

/** SSS yönetimi — silme = pasifleştirme (deleted_at sütunu yok). */
final class AdminSssService
{
    /** F16.2.3 — 8 kategori (görev Bölüm 2). */
    public const KAPSAMLAR = ['fiyatlama', 'malzeme', 'bakim', 'montaj', 'garanti', 'teknik', 'kullanim', 'karsilastirma'];

    public function __construct(
        private PDO $baglanti,
        private SssYonetimRepository $sorular,
        private AuditLogService $denetim
    ) {
    }

    /** @return array<int, array<string, mixed>> */
    public function liste(): array
    {
        return $this->sorular->adminListe();
    }

    /** @param array<string, mixed> $veri */
    public function olustur(array $kullanici, array $veri, string $ip, string $ajan): array
    {
        $this->baglanti->beginTransaction();
        try {
            $id = $this->sorular->olustur($veri);
            $this->cevirileriKaydet($id, $veri['ceviriler'] ?? []);
            if ($this->baglanti->inTransaction()) {
                $this->baglanti->commit();
            }
        } catch (\Throwable $hata) {
            if ($this->baglanti->inTransaction()) {
                $this->baglanti->rollBack();
            }

            throw $hata;
        }

        $this->denetim->kaydet((int) $kullanici['id'], 'create', 'sss', $id, null, ['sayfa_kapsami' => $veri['sayfa_kapsami'] ?? null], $ip, $ajan);

        return ['id' => $id];
    }

    /** @param array<string, mixed> $veri */
    public function guncelle(array $kullanici, int $id, array $veri, string $ip, string $ajan): array
    {
        $eski = $this->sorular->hamGetir($id);
        if ($eski === null) {
            throw new Hata('NOT_FOUND', 'Soru bulunamadı.', [['field' => 'id', 'issue' => 'not_found']], 404);
        }

        $this->baglanti->beginTransaction();
        try {
            $this->sorular->guncelle($id, $veri);
            $this->cevirileriKaydet($id, $veri['ceviriler'] ?? []);
            if ($this->baglanti->inTransaction()) {
                $this->baglanti->commit();
            }
        } catch (\Throwable $hata) {
            if ($this->baglanti->inTransaction()) {
                $this->baglanti->rollBack();
            }

            throw $hata;
        }

        $this->denetim->kaydet((int) $kullanici['id'], 'update', 'sss', $id, ['sira' => $eski['sira']], ['sira' => $veri['sira'] ?? $eski['sira']], $ip, $ajan);

        return ['id' => $id];
    }

    public function sil(array $kullanici, int $id, string $ip, string $ajan): void
    {
        $eski = $this->sorular->hamGetir($id);
        if ($eski === null) {
            throw new Hata('NOT_FOUND', 'Soru bulunamadı.', [['field' => 'id', 'issue' => 'not_found']], 404);
        }

        $this->sorular->pasiflestir($id);
        $this->denetim->kaydet((int) $kullanici['id'], 'delete', 'sss', $id, ['aktif' => $eski['aktif']], null, $ip, $ajan);
    }

    /** @param array<string, array<string, mixed>> $ceviriler */
    private function cevirileriKaydet(int $soruId, array $ceviriler): void
    {
        foreach ($ceviriler as $dil => $ceviri) {
            if (!is_array($ceviri) || trim((string) ($ceviri['soru'] ?? '')) === '' || trim((string) ($ceviri['cevap'] ?? '')) === '') {
                continue;
            }

            $this->sorular->ceviriKaydet($soruId, $dil, (string) $ceviri['soru'], (string) $ceviri['cevap']);
        }
    }
}
