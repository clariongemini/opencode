<?php

declare(strict_types=1);

namespace Kamelya\Repositories\Admin;

use PDO;

/** Ürün yazma erişimi — public UrunRepository'e dokunulmaz. */
final class UrunYonetimRepository
{
    public function __construct(private PDO $baglanti)
    {
    }

    /** @param array<string, mixed> $veri */
    public function olustur(array $veri): int
    {
        $ifade = $this->baglanti->prepare(
            'INSERT INTO urunler
             (urun_kodu, model_kategori_id, malzeme_kategori_id, kullanim_kategori_id,
              genislik_varsayilan, derinlik_varsayilan, alan_varsayilan,
              cati_tipi, korkuluk_malzeme, korkuluk_yukseklik_cm,
              aktif, one_cikan, sira)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $ifade->execute([
            $veri['urun_kodu'],
            $veri['model_kategori_id'],
            $veri['malzeme_kategori_id'],
            $veri['kullanim_kategori_id'] ?? null,
            $veri['genislik_varsayilan'] ?? null,
            $veri['derinlik_varsayilan'] ?? null,
            $veri['alan_varsayilan'] ?? null,
            $veri['cati_tipi'] ?? null,
            $veri['korkuluk_malzeme'] ?? null,
            $veri['korkuluk_yukseklik_cm'] ?? null,
            $veri['aktif'] ?? 1,
            $veri['one_cikan'] ?? 0,
            $veri['sira'] ?? 0,
        ]);

        return (int) $this->baglanti->lastInsertId();
    }

    /** @param array<string, mixed> $veri */
    public function guncelle(int $id, array $veri): bool
    {
        $alanlar = [];
        $degerler = [];
        foreach (['urun_kodu', 'model_kategori_id', 'malzeme_kategori_id', 'kullanim_kategori_id',
            'genislik_varsayilan', 'derinlik_varsayilan', 'alan_varsayilan',
            'cati_tipi', 'korkuluk_malzeme', 'korkuluk_yukseklik_cm',
            'aktif', 'one_cikan', 'sira'] as $sutun) {
            if (array_key_exists($sutun, $veri)) {
                $alanlar[] = $sutun . ' = ?';
                $degerler[] = $veri[$sutun];
            }
        }

        if ($alanlar === []) {
            return true;
        }

        $degerler[] = $id;
        $ifade = $this->baglanti->prepare('UPDATE urunler SET ' . implode(', ', $alanlar) . ' WHERE id = ?');

        return $ifade->execute($degerler);
    }

    public function softSil(int $id): bool
    {
        $ifade = $this->baglanti->prepare('UPDATE urunler SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL');

        return $ifade->execute([$id]);
    }

    /** @return array<string, mixed>|null */
    public function hamGetir(int $id): ?array
    {
        $ifade = $this->baglanti->prepare('SELECT * FROM urunler WHERE id = ?');
        $ifade->execute([$id]);
        $satir = $ifade->fetch();

        return $satir === false ? null : $satir;
    }

    /** @return array<string, array<string, mixed>> */
    public function cevirileriGetir(int $urunId): array
    {
        $ifade = $this->baglanti->prepare('SELECT * FROM urun_cevirileri WHERE urun_id = ? ORDER BY dil_kodu ASC');
        $ifade->execute([$urunId]);
        $sonuc = [];
        foreach ($ifade->fetchAll() as $satir) {
            $sonuc[$satir['dil_kodu']] = $satir;
        }

        return $sonuc;
    }

    /** @param array<string, mixed> $ceviri */
    public function ceviriKaydet(int $urunId, string $dil, array $ceviri): void
    {
        $ifade = $this->baglanti->prepare(
            'INSERT INTO urun_cevirileri
             (urun_id, dil_kodu, baslik, kisa_aciklama, detayli_aciklama, seo_baslik, seo_aciklama, seo_anahtar_kelimeler, slug, cati_tipi_aciklama, korkuluk_aciklama)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE baslik = VALUES(baslik), kisa_aciklama = VALUES(kisa_aciklama),
             detayli_aciklama = VALUES(detayli_aciklama), seo_baslik = VALUES(seo_baslik),
             seo_aciklama = VALUES(seo_aciklama), seo_anahtar_kelimeler = VALUES(seo_anahtar_kelimeler), slug = VALUES(slug),
             cati_tipi_aciklama = VALUES(cati_tipi_aciklama), korkuluk_aciklama = VALUES(korkuluk_aciklama)'
        );
        $ifade->execute([
            $urunId, $dil,
            $ceviri['baslik'],
            $ceviri['kisa_aciklama'] ?? null,
            $ceviri['detayli_aciklama'] ?? null,
            $ceviri['seo_baslik'] ?? null,
            $ceviri['seo_aciklama'] ?? null,
            $ceviri['seo_anahtar_kelimeler'] ?? null,
            $ceviri['slug'],
            $ceviri['cati_tipi_aciklama'] ?? null,
            $ceviri['korkuluk_aciklama'] ?? null,
        ]);
    }

    public function slugBaskaMi(string $dil, string $slug, ?int $haricId = null): bool
    {
        $sql = 'SELECT c.urun_id FROM urun_cevirileri c JOIN urunler u ON u.id = c.urun_id
                WHERE c.dil_kodu = ? AND c.slug = ? AND u.deleted_at IS NULL';
        $params = [$dil, $slug];
        if ($haricId !== null) {
            $sql .= ' AND c.urun_id != ?';
            $params[] = $haricId;
        }

        $ifade = $this->baglanti->prepare($sql);
        $ifade->execute($params);

        return $ifade->fetch() !== false;
    }

    /** @return array<int, array<string, mixed>> */
    public function adminListe(?string $arama, int $sayfa, int $adet): array
    {
        $where = 'WHERE u.deleted_at IS NULL';
        $params = [];
        if ($arama !== null && $arama !== '') {
            $where .= ' AND (u.urun_kodu LIKE :a OR c.baslik LIKE :a)';
            $params[':a'] = '%' . $arama . '%';
        }

        $sayac = $this->baglanti->prepare(
            "SELECT COUNT(DISTINCT u.id) FROM urunler u
             LEFT JOIN urun_cevirileri c ON c.urun_id = u.id AND c.dil_kodu = 'tr' {$where}"
        );
        foreach ($params as $ad => $deger) {
            $sayac->bindValue($ad, $deger);
        }

        $sayac->execute();
        $toplam = (int) $sayac->fetchColumn();

        $ifade = $this->baglanti->prepare(
            "SELECT u.*, c.baslik AS baslik_tr FROM urunler u
             LEFT JOIN urun_cevirileri c ON c.urun_id = u.id AND c.dil_kodu = 'tr'
             {$where} GROUP BY u.id ORDER BY u.id DESC LIMIT :lim OFFSET :off"
        );
        foreach ($params as $ad => $deger) {
            $ifade->bindValue($ad, $deger);
        }

        $ifade->bindValue(':lim', $adet, PDO::PARAM_INT);
        $ifade->bindValue(':off', ($sayfa - 1) * $adet, PDO::PARAM_INT);
        $ifade->execute();

        return ['satirlar' => $ifade->fetchAll(), 'toplam' => $toplam];
    }
}
