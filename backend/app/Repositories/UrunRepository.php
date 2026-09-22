<?php

declare(strict_types=1);

namespace Kamelya\Repositories;

use PDO;

/** Ürün okuma erişimi — yalnızca prepared statement, iş mantığı yok. */
final class UrunRepository
{
    public function __construct(private PDO $baglanti)
    {
    }

    /**
     * @param array<string, string> $filtreler material|model|usage (kategori kod)
     * @return array{satirlar: array, toplam: int}
     */
    public function tumunuGetir(
        string $dil,
        array $filtreler,
        string $siralamaAlan,
        string $siralamaYon,
        int $sayfa,
        int $adet,
        ?string $arama = null
    ): array {
        $kosullar = ['u.aktif = 1', 'u.deleted_at IS NULL', 'c.dil_kodu = :dil'];
        $parametreler = [':dil' => $dil];

        $eslesme = ['material' => 'km.kod', 'model' => 'kmo.kod', 'usage' => 'kk.kod'];
        foreach ($eslesme as $anahtar => $sutun) {
            if (isset($filtreler[$anahtar]) && $filtreler[$anahtar] !== '') {
                $kosullar[] = $sutun . ' = :' . $anahtar;
                $parametreler[':' . $anahtar] = $filtreler[$anahtar];
            }
        }

        if ($arama !== null && $arama !== '') {
            $kosullar[] = 'c.baslik LIKE :arama';
            $parametreler[':arama'] = '%' . $arama . '%';
        }

        $where = implode(' AND ', $kosullar);
        $from = 'FROM urunler u
            JOIN urun_cevirileri c ON c.urun_id = u.id
            JOIN kategoriler kmo ON kmo.id = u.model_kategori_id
            JOIN kategoriler km ON km.id = u.malzeme_kategori_id
            LEFT JOIN kategoriler kk ON kk.id = u.kullanim_kategori_id';

        $sayacIfade = $this->baglanti->prepare("SELECT COUNT(*) {$from} WHERE {$where}");
        foreach ($parametreler as $ad => $deger) {
            $sayacIfade->bindValue($ad, $deger);
        }

        $sayacIfade->execute();
        $toplam = (int) $sayacIfade->fetchColumn();

        $ifade = $this->baglanti->prepare(
            "SELECT u.id, u.urun_kodu, c.baslik, c.slug, c.kisa_aciklama,
                    km.kod AS malzeme, kmo.kod AS model, kk.kod AS kullanim,
                    r.dosya_yolu AS kapak_resmi
             {$from}
             LEFT JOIN urun_resimleri r ON r.urun_id = u.id AND r.kapak_mi = 1
             WHERE {$where} ORDER BY {$siralamaAlan} {$siralamaYon} LIMIT :lim OFFSET :off"
        );
        foreach ($parametreler as $ad => $deger) {
            $ifade->bindValue($ad, $deger);
        }

        $ifade->bindValue(':lim', $adet, PDO::PARAM_INT);
        $ifade->bindValue(':off', ($sayfa - 1) * $adet, PDO::PARAM_INT);
        $ifade->execute();

        return ['satirlar' => $ifade->fetchAll(), 'toplam' => $toplam];
    }

    /** @return array<string, mixed>|null */
    public function idIleGetir(int $id): ?array
    {
        $ifade = $this->baglanti->prepare(
            'SELECT * FROM urunler WHERE id = ? AND aktif = 1 AND deleted_at IS NULL'
        );
        $ifade->execute([$id]);
        $satir = $ifade->fetch();

        return $satir === false ? null : $satir;
    }

    /** Detay: ürün + aktif dil çevirisi + kategori kodları + görseller + SEO. */
    /** @return array<string, mixed>|null */
    public function ceviriIleGetir(int $id, string $dil): ?array
    {
        $ifade = $this->baglanti->prepare(
            'SELECT u.*, c.baslik, c.kisa_aciklama, c.detayli_aciklama, c.slug,
                    c.seo_baslik, c.seo_aciklama, c.seo_anahtar_kelimeler,
                    c.cati_tipi_aciklama, c.korkuluk_aciklama,
                    km.kod AS malzeme, kmo.kod AS model, kk.kod AS kullanim
             FROM urunler u
             JOIN urun_cevirileri c ON c.urun_id = u.id AND c.dil_kodu = ?
             JOIN kategoriler kmo ON kmo.id = u.model_kategori_id
             JOIN kategoriler km ON km.id = u.malzeme_kategori_id
             LEFT JOIN kategoriler kk ON kk.id = u.kullanim_kategori_id
             WHERE u.id = ? AND u.aktif = 1 AND u.deleted_at IS NULL'
        );
        $ifade->execute([$dil, $id]);
        $urun = $ifade->fetch();
        if ($urun === false) {
            return null;
        }

        $resimIfade = $this->baglanti->prepare(
            'SELECT dosya_yolu, kucuk_resim_yolu, tur, kapak_mi, sira
             FROM urun_resimleri WHERE urun_id = ? ORDER BY kapak_mi DESC, sira ASC'
        );
        $resimIfade->execute([$id]);
        $urun['resimler'] = $resimIfade->fetchAll();

        $seoIfade = $this->baglanti->prepare(
            "SELECT canonical_url, hreflang_json, meta_baslik, meta_aciklama, robots
             FROM seo_verileri WHERE sayfa_tipi = 'urun' AND referans_id = ? AND dil_kodu = ?"
        );
        $seoIfade->execute([$id, $dil]);
        $seo = $seoIfade->fetch();
        $urun['seo'] = $seo === false ? null : $seo;

        return $urun;
    }

    /** @return array<string, mixed>|null */
    public function slugIleGetir(string $dil, string $slug): ?array
    {
        $ifade = $this->baglanti->prepare(
            'SELECT u.id, c.baslik, c.slug, c.kisa_aciklama
             FROM urunler u
             JOIN urun_cevirileri c ON c.urun_id = u.id AND c.dil_kodu = ?
             WHERE c.slug = ? AND u.aktif = 1 AND u.deleted_at IS NULL'
        );
        $ifade->execute([$dil, $slug]);
        $satir = $ifade->fetch();

        return $satir === false ? null : $satir;
    }
}
