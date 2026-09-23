<?php

declare(strict_types=1);

namespace Kamelya\Services\Admin;

use Kamelya\Core\Hata;
use Kamelya\Core\Slug;
use Kamelya\Repositories\Admin\ResimYonetimRepository;
use Kamelya\Repositories\Admin\UrunYonetimRepository;
use Kamelya\Repositories\KategoriRepository;
use Kamelya\Services\AuditLogService;
use PDO;
use PDOException;

/** Ürün yönetimi — transaction + audit + slug tekilliği + soft delete. */
final class AdminUrunService
{
    public function __construct(
        private PDO $baglanti,
        private UrunYonetimRepository $urunler,
        private KategoriRepository $kategoriler,
        private AuditLogService $denetim,
        private ?ResimYonetimRepository $resimler = null
    ) {
    }

    /** @return array{satirlar: array, toplam: int} */
    public function liste(?string $arama, int $sayfa, int $adet): array
    {
        return $this->urunler->adminListe($arama, $sayfa, $adet);
    }

    /** @return array<string, mixed> */
    public function detay(int $id): array
    {
        $ham = $this->urunler->hamGetir($id);
        if ($ham === null || $ham['deleted_at'] !== null) {
            throw new Hata('PRODUCT_NOT_FOUND', 'Ürün bulunamadı.', [['field' => 'id', 'issue' => 'not_found']], 404);
        }

        $ham['ceviriler'] = $this->urunler->cevirileriGetir($id);
        $ham['resimler'] = $this->resimler !== null ? $this->resimler->urunResimleri($id) : [];

        return $ham;
    }

    /** @param array<string, mixed> $veri */
    public function olustur(array $kullanici, array $veri, string $ip, string $ajan): array
    {
        $this->kategoriKontrol((int) $veri['model_kategori_id'], 'model_kategori_id');
        $this->kategoriKontrol((int) $veri['malzeme_kategori_id'], 'malzeme_kategori_id');
        if (isset($veri['kullanim_kategori_id']) && $veri['kullanim_kategori_id'] !== null && $veri['kullanim_kategori_id'] !== '') {
            $this->kategoriKontrol((int) $veri['kullanim_kategori_id'], 'kullanim_kategori_id');
        }

        $this->baglanti->beginTransaction();
        try {
            $id = $this->urunler->olustur($veri);
        } catch (PDOException $hata) {
            if ($this->baglanti->inTransaction()) {
                $this->baglanti->rollBack();
            }

            if ($hata->getCode() === '23000') {
                throw new Hata('CONFLICT', 'Bu ürün kodu zaten kayıtlı.', [['field' => 'urun_kodu', 'issue' => 'duplicate']], 409);
            }

            throw $hata;
        }

        try {
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

        $this->denetim->kaydet((int) $kullanici['id'], 'create', 'urun', $id, null, ['urun_kodu' => $veri['urun_kodu']], $ip, $ajan);

        return ['id' => $id];
    }

    /** @param array<string, mixed> $veri */
    public function guncelle(array $kullanici, int $id, array $veri, string $ip, string $ajan): array
    {
        $eski = $this->urunler->hamGetir($id);
        if ($eski === null || $eski['deleted_at'] !== null) {
            throw new Hata('PRODUCT_NOT_FOUND', 'Ürün bulunamadı.', [['field' => 'id', 'issue' => 'not_found']], 404);
        }

        foreach (['model_kategori_id' => 'model_kategori_id', 'malzeme_kategori_id' => 'malzeme_kategori_id', 'kullanim_kategori_id' => 'kullanim_kategori_id'] as $alan => $etiket) {
            if (isset($veri[$alan]) && $veri[$alan] !== null && $veri[$alan] !== '') {
                $this->kategoriKontrol((int) $veri[$alan], $etiket);
            }
        }

        $this->baglanti->beginTransaction();
        try {
            $this->urunler->guncelle($id, $veri);
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

        $this->denetim->kaydet((int) $kullanici['id'], 'update', 'urun', $id, ['urun_kodu' => $eski['urun_kodu']], ['urun_kodu' => $veri['urun_kodu'] ?? $eski['urun_kodu']], $ip, $ajan);

        return ['id' => $id];
    }

    public function sil(array $kullanici, int $id, string $ip, string $ajan): void
    {
        $eski = $this->urunler->hamGetir($id);
        if ($eski === null || $eski['deleted_at'] !== null) {
            throw new Hata('PRODUCT_NOT_FOUND', 'Ürün bulunamadı.', [['field' => 'id', 'issue' => 'not_found']], 404);
        }

        $this->urunler->softSil($id);
        $this->denetim->kaydet((int) $kullanici['id'], 'delete', 'urun', $id, ['urun_kodu' => $eski['urun_kodu']], null, $ip, $ajan);
    }

    /** Video gömme URL'si (youtube/vimeo/instagram allowlist). */
    public function videoEkle(array $kullanici, int $id, string $url, string $ip, string $ajan): array
    {
        $eski = $this->urunler->hamGetir($id);
        if ($eski === null || $eski['deleted_at'] !== null) {
            throw new Hata('PRODUCT_NOT_FOUND', 'Ürün bulunamadı.', [['field' => 'id', 'issue' => 'not_found']], 404);
        }

        $parca = parse_url(trim($url));
        $host = strtolower((string) ($parca['host'] ?? ''));
        $izinli = false;
        foreach (['youtube.com', 'youtu.be', 'vimeo.com', 'instagram.com'] as $alan) {
            if (str_ends_with($host, $alan)) {
                $izinli = true;
                break;
            }
        }

        if (!$izinli || ($parca['scheme'] ?? '') !== 'https') {
            throw new Hata('VALIDATION_ERROR', 'Yalnızca https YouTube/Vimeo/Instagram URL kabul edilir.', [['field' => 'video_url', 'issue' => 'invalid']], 422);
        }

        $ifade = $this->baglanti->prepare(
            "INSERT INTO urun_resimleri (urun_id, dosya_yolu, tur, kapak_mi) VALUES (?, ?, 'video', 0)"
        );
        $ifade->execute([$id, trim($url)]);
        $resimId = (int) $this->baglanti->lastInsertId();
        $this->denetim->kaydet((int) $kullanici['id'], 'create', 'urun_resmi', $resimId, null, ['urun_id' => $id, 'tur' => 'video'], $ip, $ajan);

        return ['id' => $resimId];
    }

    /** @param array<string, array<string, mixed>> $ceviriler */
    private function cevirileriKaydet(int $urunId, array $ceviriler, ?int $haricId): void
    {
        foreach ($ceviriler as $dil => $ceviri) {
            if (!is_array($ceviri) || trim((string) ($ceviri['baslik'] ?? '')) === '') {
                continue;
            }

            $slug = trim((string) ($ceviri['slug'] ?? ''));
            if ($slug === '') {
                $slug = Slug::uret((string) $ceviri['baslik']);
            }

            $taban = $slug;
            $sayac = 2;
            while ($this->urunler->slugBaskaMi($dil, $slug, $haricId)) {
                $slug = $taban . '-' . $sayac;
                $sayac++;
            }

            $ceviri['slug'] = $slug;
            $this->urunler->ceviriKaydet($urunId, $dil, $ceviri);
        }
    }

    private function kategoriKontrol(int $id, string $alan): void
    {
        if ($this->kategoriler->idIleGetir($id) === null) {
            throw new Hata('VALIDATION_ERROR', 'Kategori bulunamadı.', [['field' => $alan, 'issue' => 'not_found']], 422);
        }
    }
}
