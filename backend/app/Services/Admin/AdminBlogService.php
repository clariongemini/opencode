<?php

declare(strict_types=1);

namespace Kamelya\Services\Admin;

use Kamelya\Core\Hata;
use Kamelya\Core\Slug;
use Kamelya\Repositories\Admin\BlogYonetimRepository;
use Kamelya\Services\AuditLogService;
use PDO;

/** Blog yönetimi — yazar = işlem yapan kullanıcı; soft delete. */
final class AdminBlogService
{
    public const DURUMLAR = ['taslak', 'yayinda', 'arsiv'];

    public function __construct(
        private PDO $baglanti,
        private BlogYonetimRepository $yazilar,
        private AuditLogService $denetim
    ) {
    }

    /** @return array{satirlar: array, toplam: int} */
    public function liste(int $sayfa, int $adet): array
    {
        return $this->yazilar->adminListe($sayfa, $adet);
    }

    /** @return array<string, mixed> */
    public function detay(int $id): array
    {
        $ham = $this->yazilar->hamGetir($id);
        if ($ham === null || $ham['deleted_at'] !== null) {
            throw new Hata('NOT_FOUND', 'Yazı bulunamadı.', [['field' => 'id', 'issue' => 'not_found']], 404);
        }

        $ham['ceviriler'] = $this->yazilar->cevirileriGetir($id);

        return $ham;
    }

    /** @param array<string, mixed> $veri */
    public function olustur(array $kullanici, array $veri, string $ip, string $ajan): array
    {
        if (isset($veri['yayin_durumu']) && !in_array($veri['yayin_durumu'], self::DURUMLAR, true)) {
            throw new Hata('VALIDATION_ERROR', 'Geçersiz yayın durumu.', [['field' => 'yayin_durumu', 'issue' => 'invalid']], 422);
        }

        $veri['yazar_id'] = (int) $kullanici['id'];

        $this->baglanti->beginTransaction();
        try {
            $id = $this->yazilar->olustur($veri);
            $this->cevirileriKaydet($id, $veri['ceviriler'] ?? [], null);
            if ($this->baglanti->inTransaction()) {
                $this->baglanti->commit();
            }
        } catch (\Throwable $hata) {
            if ($this->baglanti->inTransaction()) {
                $this->baglanti->rollBack();
            }

            throw $hata;
        }

        $this->denetim->kaydet((int) $kullanici['id'], 'create', 'blog', $id, null, ['yayin_durumu' => $veri['yayin_durumu'] ?? 'taslak'], $ip, $ajan);

        return ['id' => $id];
    }

    /** @param array<string, mixed> $veri */
    public function guncelle(array $kullanici, int $id, array $veri, string $ip, string $ajan): array
    {
        $eski = $this->yazilar->hamGetir($id);
        if ($eski === null || $eski['deleted_at'] !== null) {
            throw new Hata('NOT_FOUND', 'Yazı bulunamadı.', [['field' => 'id', 'issue' => 'not_found']], 404);
        }

        $this->baglanti->beginTransaction();
        try {
            $this->yazilar->guncelle($id, $veri);
            $this->cevirileriKaydet($id, $veri['ceviriler'] ?? [], $id);
            if ($this->baglanti->inTransaction()) {
                $this->baglanti->commit();
            }
        } catch (\Throwable $hata) {
            if ($this->baglanti->inTransaction()) {
                $this->baglanti->rollBack();
            }

            throw $hata;
        }

        $this->denetim->kaydet((int) $kullanici['id'], 'update', 'blog', $id, ['yayin_durumu' => $eski['yayin_durumu']], ['yayin_durumu' => $veri['yayin_durumu'] ?? $eski['yayin_durumu']], $ip, $ajan);

        return ['id' => $id];
    }

    public function sil(array $kullanici, int $id, string $ip, string $ajan): void
    {
        $eski = $this->yazilar->hamGetir($id);
        if ($eski === null || $eski['deleted_at'] !== null) {
            throw new Hata('NOT_FOUND', 'Yazı bulunamadı.', [['field' => 'id', 'issue' => 'not_found']], 404);
        }

        $this->yazilar->softSil($id);
        $this->denetim->kaydet((int) $kullanici['id'], 'delete', 'blog', $id, ['yayin_durumu' => $eski['yayin_durumu']], null, $ip, $ajan);
    }

    /** @param array<string, array<string, mixed>> $ceviriler */
    private function cevirileriKaydet(int $yaziId, array $ceviriler, ?int $haricId): void
    {
        foreach ($ceviriler as $dil => $ceviri) {
            if (!is_array($ceviri) || trim((string) ($ceviri['baslik'] ?? '')) === '' || trim((string) ($ceviri['icerik'] ?? '')) === '') {
                continue;
            }

            $slug = trim((string) ($ceviri['slug'] ?? ''));
            if ($slug === '') {
                $slug = Slug::uret((string) $ceviri['baslik']);
            }

            $taban = $slug;
            $sayac = 2;
            while ($this->yazilar->slugBaskaMi($dil, $slug, $haricId)) {
                $slug = $taban . '-' . $sayac;
                $sayac++;
            }

            $ceviri['slug'] = $slug;
            $this->yazilar->ceviriKaydet($yaziId, $dil, $ceviri);
        }
    }
}
