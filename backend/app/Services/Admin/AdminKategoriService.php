<?php

declare(strict_types=1);

namespace Kamelya\Services\Admin;

use Kamelya\Core\Hata;
use Kamelya\Core\Slug;
use Kamelya\Repositories\Admin\KategoriYonetimRepository;
use Kamelya\Services\AuditLogService;
use PDO;
use PDOException;

/** Kategori yönetimi — tur allowlist, kod tekilliği, pasif silme. */
final class AdminKategoriService
{
    public const TURLER = ['model', 'malzeme', 'kullanim_amaci'];

    public function __construct(
        private PDO $baglanti,
        private KategoriYonetimRepository $kategoriler,
        private AuditLogService $denetim
    ) {
    }

    /** @return array<int, array<string, mixed>> */
    public function liste(?string $tur): array
    {
        return $this->kategoriler->adminListe($tur);
    }

    /** @param array<string, mixed> $veri */
    public function olustur(array $kullanici, array $veri, string $ip, string $ajan): array
    {
        if (!in_array($veri['tur'], self::TURLER, true)) {
            throw new Hata('VALIDATION_ERROR', 'Geçersiz kategori türü.', [['field' => 'tur', 'issue' => 'invalid']], 422);
        }

        if (isset($veri['ust_kategori_id']) && $veri['ust_kategori_id'] !== null && $veri['ust_kategori_id'] !== '') {
            if ($this->kategoriler->hamGetir((int) $veri['ust_kategori_id']) === null) {
                throw new Hata('VALIDATION_ERROR', 'Üst kategori bulunamadı.', [['field' => 'ust_kategori_id', 'issue' => 'not_found']], 422);
            }
        }

        $this->baglanti->beginTransaction();
        try {
            $id = $this->kategoriler->olustur($veri);
        } catch (PDOException $hata) {
            if ($this->baglanti->inTransaction()) {
                $this->baglanti->rollBack();
            }

            if ($hata->getCode() === '23000') {
                throw new Hata('CONFLICT', 'Bu türde bu kod zaten kayıtlı.', [['field' => 'kod', 'issue' => 'duplicate']], 409);
            }

            throw $hata;
        }

        try {
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

        $this->denetim->kaydet((int) $kullanici['id'], 'create', 'kategori', $id, null, ['tur' => $veri['tur'], 'kod' => $veri['kod']], $ip, $ajan);

        return ['id' => $id];
    }

    /** @param array<string, mixed> $veri */
    public function guncelle(array $kullanici, int $id, array $veri, string $ip, string $ajan): array
    {
        $eski = $this->kategoriler->hamGetir($id);
        if ($eski === null) {
            throw new Hata('NOT_FOUND', 'Kategori bulunamadı.', [['field' => 'id', 'issue' => 'not_found']], 404);
        }

        $this->baglanti->beginTransaction();
        try {
            $this->kategoriler->guncelle($id, $veri);
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

        $this->denetim->kaydet((int) $kullanici['id'], 'update', 'kategori', $id, ['kod' => $eski['kod']], ['kod' => $veri['kod'] ?? $eski['kod']], $ip, $ajan);

        return ['id' => $id];
    }

    public function sil(array $kullanici, int $id, string $ip, string $ajan): void
    {
        $eski = $this->kategoriler->hamGetir($id);
        if ($eski === null) {
            throw new Hata('NOT_FOUND', 'Kategori bulunamadı.', [['field' => 'id', 'issue' => 'not_found']], 404);
        }

        $this->kategoriler->pasiflestir($id);
        $this->denetim->kaydet((int) $kullanici['id'], 'delete', 'kategori', $id, ['kod' => $eski['kod']], null, $ip, $ajan);
    }

    /** @param array<string, array<string, mixed>> $ceviriler */
    private function cevirileriKaydet(int $kategoriId, array $ceviriler): void
    {
        foreach ($ceviriler as $dil => $ceviri) {
            if (!is_array($ceviri) || trim((string) ($ceviri['isim'] ?? '')) === '') {
                continue;
            }

            $slug = trim((string) ($ceviri['slug'] ?? ''));
            if ($slug === '') {
                $slug = Slug::uret((string) $ceviri['isim']);
            }

            $this->kategoriler->ceviriKaydet($kategoriId, $dil, (string) $ceviri['isim'], $ceviri['aciklama'] ?? null, $slug);
        }
    }
}
