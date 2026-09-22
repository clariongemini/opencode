<?php

declare(strict_types=1);

namespace Kamelya\Services\Admin;

use Kamelya\Core\Hata;
use Kamelya\Repositories\Admin\KapasiteYonetimRepository;
use Kamelya\Services\AuditLogService;
use PDO;
use PDOException;

/** Kapasite çarpanı yönetimi — kategori_id FK, m2_per_kisi, aktif/pasif. */
final class AdminKapasiteService
{
    public function __construct(
        private PDO $baglanti,
        private KapasiteYonetimRepository $kapasite,
        private AuditLogService $denetim
    ) {
    }

    /** @return array<int, array<string, mixed>> */
    public function liste(): array
    {
        return $this->kapasite->adminListe();
    }

    /** @param array<string, mixed> $veri */
    public function olustur(array $kullanici, array $veri, string $ip, string $ajan): array
    {
        $this->dogrula($veri);

        $this->baglanti->beginTransaction();
        try {
            $id = $this->kapasite->olustur($veri);
        } catch (PDOException $hata) {
            if ($this->baglanti->inTransaction()) {
                $this->baglanti->rollBack();
            }

            if ($hata->getCode() === '23000') {
                throw new Hata('CONFLICT', 'Bu kategori ve başlangıç tarihi kombinasyonu zaten kayıtlı.', [['field' => 'kategori_id', 'issue' => 'duplicate']], 409);
            }

            throw $hata;
        }

        if ($this->baglanti->inTransaction()) {
            $this->baglanti->commit();
        }

        $this->denetim->kaydet((int) $kullanici['id'], 'create', 'kapasite_carpani', $id, null, [
            'kategori_id' => $veri['kategori_id'],
            'm2_per_kisi' => $veri['m2_per_kisi'],
        ], $ip, $ajan);

        return ['id' => $id];
    }

    /** @param array<string, mixed> $veri */
    public function guncelle(array $kullanici, int $id, array $veri, string $ip, string $ajan): array
    {
        $eski = $this->kapasite->hamGetir($id);
        if ($eski === null) {
            throw new Hata('NOT_FOUND', 'Kapasite çarpanı bulunamadı.', [['field' => 'id', 'issue' => 'not_found']], 404);
        }

        $this->dogrula($veri, true);

        $this->baglanti->beginTransaction();
        try {
            $this->kapasite->guncelle($id, $veri);
            if ($this->baglanti->inTransaction()) {
                $this->baglanti->commit();
            }
        } catch (PDOException $hata) {
            if ($this->baglanti->inTransaction()) {
                $this->baglanti->rollBack();
            }

            if ($hata->getCode() === '23000') {
                throw new Hata('CONFLICT', 'Bu kategori ve başlangıç tarihi kombinasyonu zaten kayıtlı.', [['field' => 'kategori_id', 'issue' => 'duplicate']], 409);
            }

            throw $hata;
        } catch (\Throwable $hata) {
            if ($this->baglanti->inTransaction()) {
                $this->baglanti->rollBack();
            }
            throw $hata;
        }

        $this->denetim->kaydet((int) $kullanici['id'], 'update', 'kapasite_carpani', $id,
            ['kategori_id' => $eski['kategori_id'], 'm2_per_kisi' => $eski['m2_per_kisi']],
            ['kategori_id' => $veri['kategori_id'] ?? $eski['kategori_id'], 'm2_per_kisi' => $veri['m2_per_kisi'] ?? $eski['m2_per_kisi']],
            $ip, $ajan);

        return ['id' => $id];
    }

    public function sil(array $kullanici, int $id, string $ip, string $ajan): void
    {
        $eski = $this->kapasite->hamGetir($id);
        if ($eski === null) {
            throw new Hata('NOT_FOUND', 'Kapasite çarpanı bulunamadı.', [['field' => 'id', 'issue' => 'not_found']], 404);
        }

        $this->kapasite->pasiflestir($id);
        $this->denetim->kaydet((int) $kullanici['id'], 'delete', 'kapasite_carpani', $id,
            ['kategori_id' => $eski['kategori_id'], 'm2_per_kisi' => $eski['m2_per_kisi']], null, $ip, $ajan);
    }

    /** @param array<string, mixed> $veri */
    private function dogrula(array $veri, bool $guncelleme = false): void
    {
        if (!$guncelleme || isset($veri['kategori_id'])) {
            if (!isset($veri['kategori_id']) || !is_numeric($veri['kategori_id'])) {
                throw new Hata('VALIDATION_ERROR', 'Kategori ID zorunlu.', [['field' => 'kategori_id', 'issue' => 'required']], 422);
            }
        }

        if (!$guncelleme || isset($veri['m2_per_kisi'])) {
            if (!isset($veri['m2_per_kisi']) || !is_numeric($veri['m2_per_kisi']) || (float) $veri['m2_per_kisi'] <= 0) {
                throw new Hata('VALIDATION_ERROR', 'm²/kisi pozitif sayı olmalı.', [['field' => 'm2_per_kisi', 'issue' => 'invalid']], 422);
            }
        }

        if (!$guncelleme || isset($veri['gecerlilik_baslangici'])) {
            if (!isset($veri['gecerlilik_baslangici']) || !is_string($veri['gecerlilik_baslangici'])) {
                throw new Hata('VALIDATION_ERROR', 'Geçerlilik başlangıcı zorunlu (YYYY-MM-DD).', [['field' => 'gecerlilik_baslangici', 'issue' => 'required']], 422);
            }
            // Basit tarih formatı kontrolü
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $veri['gecerlilik_baslangici'])) {
                throw new Hata('VALIDATION_ERROR', 'Tarih formatı YYYY-MM-DD olmalı.', [['field' => 'gecerlilik_baslangici', 'issue' => 'invalid_format']], 422);
            }
        }

        if (isset($veri['aktif']) && !is_bool($veri['aktif']) && !is_numeric($veri['aktif'])) {
            throw new Hata('VALIDATION_ERROR', 'Aktif alanı boolean olmalı.', [['field' => 'aktif', 'issue' => 'invalid']], 422);
        }
    }
}