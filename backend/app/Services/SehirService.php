<?php

declare(strict_types=1);

namespace Kamelya\Services;

use Kamelya\Core\Hata;
use Kamelya\Repositories\SehirRepository;
use PDO;

/** Şehir landing okuma + admin içerik orkestrasyonu. */
final class SehirService
{
    public function __construct(
        private PDO $baglanti,
        private SehirRepository $sehirler,
        private ?AuditLogService $denetim = null
    ) {
    }

    /** @return array<int, array<string, mixed>> */
    public function liste(string $dil): array
    {
        return $this->sehirler->aktifListe($dil);
    }

    /** @return array<string, mixed> */
    public function detay(string $dil, string $slug): array
    {
        $sehir = $this->sehirler->slugIleGetir($dil, $slug);
        if ($sehir === null) {
            throw new Hata('SEHIR_NOT_FOUND', 'Şehir bulunamadı.', [['field' => 'slug', 'issue' => 'not_found']], 404);
        }

        return $sehir;
    }

    /** @return array<int, array<string, mixed>> */
    public function adminListe(): array
    {
        return $this->sehirler->adminListe();
    }

    /** @param array<string, mixed> $veri */
    public function icerikKaydet(array $kullanici, int $id, array $veri, string $ip, string $ajan): array
    {
        if ($this->sehirler->idIleGetir($id) === null) {
            throw new Hata('SEHIR_NOT_FOUND', 'Şehir bulunamadı.', [['field' => 'id', 'issue' => 'not_found']], 404);
        }

        $dil = (string) ($veri['dil_kodu'] ?? '');
        $slug = trim((string) ($veri['slug'] ?? ''));
        if (!\Kamelya\Core\Diller::gecerli($dil) || $slug === '') {
            throw new Hata('VALIDATION_ERROR', 'dil_kodu ve slug zorunludur.', [['field' => 'dil_kodu', 'issue' => 'required']], 422);
        }

        $this->sehirler->ceviriKaydet($id, $dil, $veri['seo_baslik'] ?? null, $veri['seo_aciklama'] ?? null, $veri['icerik'] ?? null, $slug);
        $this->denetimKaydet($kullanici, 'update', 'sehir', $id, $ip, $ajan);

        return ['id' => $id];
    }

    public function bolgeEkle(array $kullanici, int $id, string $ilce, string $ip, string $ajan): array
    {
        if ($this->sehirler->idIleGetir($id) === null) {
            throw new Hata('SEHIR_NOT_FOUND', 'Şehir bulunamadı.', [['field' => 'id', 'issue' => 'not_found']], 404);
        }

        if (trim($ilce) === '' || mb_strlen($ilce) > 120) {
            throw new Hata('VALIDATION_ERROR', 'İlçe adı geçersiz.', [['field' => 'ilce', 'issue' => 'invalid']], 422);
        }

        $bolgeId = $this->sehirler->bolgeEkle($id, trim($ilce));
        $this->denetimKaydet($kullanici, 'create', 'sehir_bolge', $bolgeId, $ip, $ajan);

        return ['id' => $bolgeId];
    }

    public function bolgeSil(array $kullanici, int $bolgeId, string $ip, string $ajan): void
    {
        $this->sehirler->bolgeSil($bolgeId);
        $this->denetimKaydet($kullanici, 'delete', 'sehir_bolge', $bolgeId, $ip, $ajan);
    }

    private function denetimKaydet(array $kullanici, string $islem, string $tip, ?int $id, string $ip, string $ajan): void
    {
        if ($this->denetim !== null) {
            $this->denetim->kaydet((int) $kullanici['id'], $islem, $tip, $id, null, null, $ip, $ajan);
        }
    }
}
