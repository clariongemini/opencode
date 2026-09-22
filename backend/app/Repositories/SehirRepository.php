<?php

declare(strict_types=1);

namespace Kamelya\Repositories;

use PDO;

/** Şehir okuma — landing + dizin. */
final class SehirRepository
{
    public function __construct(private PDO $baglanti)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function aktifListe(string $dil): array
    {
        $ifade = $this->baglanti->prepare(
            "SELECT s.id, s.kod, s.ad, c.slug FROM sehirler s
             LEFT JOIN sehir_cevirileri c ON c.sehir_id = s.id AND c.dil_kodu = ?
             WHERE s.aktif = 1 ORDER BY s.sira ASC"
        );
        $ifade->execute([$dil]);

        return $ifade->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function slugIleGetir(string $dil, string $slug): ?array
    {
        $ifade = $this->baglanti->prepare(
            'SELECT s.id, s.kod, s.ad, c.seo_baslik, c.seo_aciklama, c.icerik, c.slug
             FROM sehirler s
             JOIN sehir_cevirileri c ON c.sehir_id = s.id AND c.dil_kodu = ?
             WHERE c.slug = ? AND s.aktif = 1'
        );
        $ifade->execute([$dil, $slug]);
        $satir = $ifade->fetch();
        if ($satir === false) {
            return null;
        }

        $bIfade = $this->baglanti->prepare(
            'SELECT ilce FROM sehir_hizmet_bolgeleri WHERE sehir_id = ? ORDER BY sira ASC'
        );
        $bIfade->execute([(int) $satir['id']]);
        $satir['bolgeler'] = $bIfade->fetchAll(PDO::FETCH_COLUMN);

        return $satir;
    }

    /** @return array<int, array<string, mixed>> */
    public function adminListe(): array
    {
        $ifade = $this->baglanti->query('SELECT * FROM sehirler ORDER BY sira ASC');

        return $ifade->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function idIleGetir(int $id): ?array
    {
        $ifade = $this->baglanti->prepare('SELECT * FROM sehirler WHERE id = ?');
        $ifade->execute([$id]);
        $satir = $ifade->fetch();

        return $satir === false ? null : $satir;
    }

    public function ceviriKaydet(int $sehirId, string $dil, ?string $seoBaslik, ?string $seoAciklama, ?string $icerik, string $slug): void
    {
        $ifade = $this->baglanti->prepare(
            'INSERT INTO sehir_cevirileri (sehir_id, dil_kodu, seo_baslik, seo_aciklama, icerik, slug)
             VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE seo_baslik = VALUES(seo_baslik), seo_aciklama = VALUES(seo_aciklama),
             icerik = VALUES(icerik), slug = VALUES(slug)'
        );
        $ifade->execute([$sehirId, $dil, $seoBaslik, $seoAciklama, $icerik, $slug]);
    }

    public function bolgeEkle(int $sehirId, string $ilce): int
    {
        $sira = (int) $this->baglanti->query('SELECT COALESCE(MAX(sira), 0) + 1 FROM sehir_hizmet_bolgeleri WHERE sehir_id = ' . $sehirId)->fetchColumn();
        $ifade = $this->baglanti->prepare('INSERT INTO sehir_hizmet_bolgeleri (sehir_id, ilce, sira) VALUES (?, ?, ?)');
        $ifade->execute([$sehirId, $ilce, $sira]);

        return (int) $this->baglanti->lastInsertId();
    }

    public function bolgeSil(int $bolgeId): void
    {
        $ifade = $this->baglanti->prepare('DELETE FROM sehir_hizmet_bolgeleri WHERE id = ?');
        $ifade->execute([$bolgeId]);
    }
}
