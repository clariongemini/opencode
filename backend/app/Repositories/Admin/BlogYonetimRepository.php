<?php

declare(strict_types=1);

namespace Kamelya\Repositories\Admin;

use PDO;

/** Blog + çeviri yazma erişimi. */
final class BlogYonetimRepository
{
    public function __construct(private PDO $baglanti)
    {
    }

    /** @param array<string, mixed> $veri */
    public function olustur(array $veri): int
    {
        $ifade = $this->baglanti->prepare(
            'INSERT INTO blog_yazilari (yazar_id, kapak_resmi, yayin_durumu, yayin_tarihi) VALUES (?, ?, ?, ?)'
        );
        $ifade->execute([
            $veri['yazar_id'],
            $veri['kapak_resmi'] ?? null,
            $veri['yayin_durumu'] ?? 'taslak',
            $veri['yayin_tarihi'] ?? null,
        ]);

        return (int) $this->baglanti->lastInsertId();
    }

    /** @param array<string, mixed> $veri */
    public function guncelle(int $id, array $veri): bool
    {
        $alanlar = [];
        $degerler = [];
        foreach (['kapak_resmi', 'yayin_durumu', 'yayin_tarihi'] as $sutun) {
            if (array_key_exists($sutun, $veri)) {
                $alanlar[] = $sutun . ' = ?';
                $degerler[] = $veri[$sutun];
            }
        }

        if ($alanlar === []) {
            return true;
        }

        $degerler[] = $id;
        $ifade = $this->baglanti->prepare('UPDATE blog_yazilari SET ' . implode(', ', $alanlar) . ' WHERE id = ?');

        return $ifade->execute($degerler);
    }

    public function softSil(int $id): bool
    {
        $ifade = $this->baglanti->prepare('UPDATE blog_yazilari SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL');

        return $ifade->execute([$id]);
    }

    /** @return array<string, mixed>|null */
    public function hamGetir(int $id): ?array
    {
        $ifade = $this->baglanti->prepare('SELECT * FROM blog_yazilari WHERE id = ?');
        $ifade->execute([$id]);
        $satir = $ifade->fetch();

        return $satir === false ? null : $satir;
    }

    /** @return array<string, array<string, mixed>> */
    public function cevirileriGetir(int $yaziId): array
    {
        $ifade = $this->baglanti->prepare('SELECT * FROM blog_yazisi_cevirileri WHERE yazi_id = ? ORDER BY dil_kodu ASC');
        $ifade->execute([$yaziId]);
        $sonuc = [];
        foreach ($ifade->fetchAll() as $satir) {
            $sonuc[$satir['dil_kodu']] = $satir;
        }

        return $sonuc;
    }

    /** @param array<string, mixed> $ceviri */
    public function ceviriKaydet(int $yaziId, string $dil, array $ceviri): void
    {
        $ifade = $this->baglanti->prepare(
            'INSERT INTO blog_yazisi_cevirileri
             (yazi_id, dil_kodu, baslik, ozet, icerik, slug, seo_baslik, seo_aciklama)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE baslik = VALUES(baslik), ozet = VALUES(ozet), icerik = VALUES(icerik),
             slug = VALUES(slug), seo_baslik = VALUES(seo_baslik), seo_aciklama = VALUES(seo_aciklama)'
        );
        $ifade->execute([
            $yaziId, $dil, $ceviri['baslik'], $ceviri['ozet'] ?? null, $ceviri['icerik'],
            $ceviri['slug'], $ceviri['seo_baslik'] ?? null, $ceviri['seo_aciklama'] ?? null,
        ]);
    }

    public function slugBaskaMi(string $dil, string $slug, ?int $haricId = null): bool
    {
        $sql = 'SELECT c.yazi_id FROM blog_yazisi_cevirileri c JOIN blog_yazilari y ON y.id = c.yazi_id
                WHERE c.dil_kodu = ? AND c.slug = ? AND y.deleted_at IS NULL';
        $params = [$dil, $slug];
        if ($haricId !== null) {
            $sql .= ' AND c.yazi_id != ?';
            $params[] = $haricId;
        }

        $ifade = $this->baglanti->prepare($sql);
        $ifade->execute($params);

        return $ifade->fetch() !== false;
    }

    /** @return array{satirlar: array, toplam: int} */
    public function adminListe(int $sayfa, int $adet): array
    {
        $sayac = $this->baglanti->query('SELECT COUNT(*) FROM blog_yazilari WHERE deleted_at IS NULL');
        $toplam = (int) $sayac->fetchColumn();

        $ifade = $this->baglanti->prepare(
            "SELECT y.*, c.baslik AS baslik_tr FROM blog_yazilari y
             LEFT JOIN blog_yazisi_cevirileri c ON c.yazi_id = y.id AND c.dil_kodu = 'tr'
             WHERE y.deleted_at IS NULL ORDER BY y.id DESC LIMIT :lim OFFSET :off"
        );
        $ifade->bindValue(':lim', $adet, PDO::PARAM_INT);
        $ifade->bindValue(':off', ($sayfa - 1) * $adet, PDO::PARAM_INT);
        $ifade->execute();

        return ['satirlar' => $ifade->fetchAll(), 'toplam' => $toplam];
    }
}
