<?php

declare(strict_types=1);

namespace Kamelya\Repositories;

use PDO;

/** SEO analitik cache yazma/okuma — UPSERT idempotent. */
final class SeoAnalitikRepository
{
    /** @var array<int, string> */
    public const TIPLER = ['sorgu', 'sayfa', 'ulke', 'cihaz', 'trend'];

    public function __construct(private PDO $baglanti)
    {
    }

    public function kaydet(
        string $veriTipi,
        string $tarih,
        ?string $boyut,
        int $tiklama,
        int $gosterim,
        float $ctr,
        float $pozisyon
    ): void {
        if (!in_array($veriTipi, self::TIPLER, true)) {
            throw new \InvalidArgumentException('Geçersiz veri tipi: ' . $veriTipi);
        }

        // boyut NULL olabilir; UNIQUE(veri_tipi, tarih, boyut) NULL'u ayırt etmez —
        // trend satırları için sabit 'gunluk' boyutu kullanılır (çağıran sorumlu).
        $ifade = $this->baglanti->prepare(
            'INSERT INTO seo_analitik_verileri
             (veri_tipi, tarih, boyut, tiklama, gosterim, ctr, ortalama_pozisyon)
             VALUES (?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE tiklama = VALUES(tiklama), gosterim = VALUES(gosterim),
             ctr = VALUES(ctr), ortalama_pozisyon = VALUES(ortalama_pozisyon)'
        );
        $ifade->execute([$veriTipi, $tarih, $boyut, $tiklama, $gosterim, $ctr, $pozisyon]);
    }

    /** @return array<int, array<string, mixed>> */
    public function tarihAraligiIleGetir(string $veriTipi, string $baslangic, string $bitis, int $limit = 500): array
    {
        $ifade = $this->baglanti->prepare(
            'SELECT * FROM seo_analitik_verileri
             WHERE veri_tipi = :tip AND tarih BETWEEN :bas AND :bit
             ORDER BY tiklama DESC, gosterim DESC LIMIT :lim'
        );
        $ifade->bindValue(':tip', $veriTipi);
        $ifade->bindValue(':bas', $baslangic);
        $ifade->bindValue(':bit', $bitis);
        $ifade->bindValue(':lim', $limit, PDO::PARAM_INT);
        $ifade->execute();

        return $ifade->fetchAll();
    }

    public function sonGuncellemeTarihi(string $veriTipi): ?string
    {
        $ifade = $this->baglanti->prepare(
            'SELECT MAX(updated_at) FROM seo_analitik_verileri WHERE veri_tipi = ?'
        );
        $ifade->execute([$veriTipi]);
        $deger = $ifade->fetchColumn();

        return $deger === false || $deger === null ? null : (string) $deger;
    }

    /** @return array<string, mixed> */
    public function ozet(string $baslangic, string $bitis): array
    {
        $ifade = $this->baglanti->prepare(
            "SELECT
               SUM(CASE WHEN veri_tipi = 'sorgu' THEN tiklama ELSE 0 END) AS tiklama,
               SUM(CASE WHEN veri_tipi = 'sorgu' THEN gosterim ELSE 0 END) AS gosterim,
               AVG(CASE WHEN veri_tipi = 'sorgu' AND gosterim > 0 THEN ortalama_pozisyon ELSE NULL END) AS pozisyon
             FROM seo_analitik_verileri WHERE tarih BETWEEN ? AND ?"
        );
        $ifade->execute([$baslangic, $bitis]);
        $satir = $ifade->fetch() ?: [];

        $tiklama = (int) ($satir['tiklama'] ?? 0);
        $gosterim = (int) ($satir['gosterim'] ?? 0);

        return [
            'tiklama' => $tiklama,
            'gosterim' => $gosterim,
            'ctr' => $gosterim > 0 ? round($tiklama / $gosterim, 4) : 0.0,
            'pozisyon' => $satir['pozisyon'] === null ? 0.0 : round((float) $satir['pozisyon'], 2),
        ];
    }
}
