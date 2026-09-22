<?php

declare(strict_types=1);

namespace Kamelya\Repositories;

use PDO;

/** Sertifika okuma + yazma (yeni varlık; çeviriler dahil). */
final class SertifikaRepository
{
    public function __construct(private PDO $baglanti)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function aktifListe(string $dil): array
    {
        $ifade = $this->baglanti->prepare(
            "SELECT s.*, c.baslik AS ceviri_baslik, c.aciklama AS ceviri_aciklama
             FROM sertifikalar s
             LEFT JOIN sertifika_cevirileri c ON c.sertifika_id = s.id AND c.dil_kodu = ?
             WHERE s.aktif = 1 ORDER BY s.sira ASC, s.id ASC"
        );
        $ifade->execute([$dil]);

        return $ifade->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function adminListe(): array
    {
        $ifade = $this->baglanti->query('SELECT * FROM sertifikalar ORDER BY sira ASC, id DESC');

        return $ifade->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function idIleGetir(int $id): ?array
    {
        $ifade = $this->baglanti->prepare('SELECT * FROM sertifikalar WHERE id = ?');
        $ifade->execute([$id]);
        $satir = $ifade->fetch();

        return $satir === false ? null : $satir;
    }

    /** @param array<string, mixed> $veri */
    public function olustur(array $veri): int
    {
        $ifade = $this->baglanti->prepare(
            'INSERT INTO sertifikalar (baslik, kurum, belge_no, gecerlilik_tarihi, logo_yolu, aciklama, sira, aktif)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $ifade->execute([
            $veri['baslik'], $veri['kurum'] ?? 'Diger', $veri['belge_no'] ?? null,
            $veri['gecerlilik_tarihi'] ?? null, $veri['logo_yolu'] ?? null,
            $veri['aciklama'] ?? null, $veri['sira'] ?? 0, $veri['aktif'] ?? 1,
        ]);

        return (int) $this->baglanti->lastInsertId();
    }

    /** @param array<string, mixed> $veri */
    public function guncelle(int $id, array $veri): bool
    {
        $alanlar = [];
        $degerler = [];
        foreach (['baslik', 'kurum', 'belge_no', 'gecerlilik_tarihi', 'logo_yolu', 'aciklama', 'sira', 'aktif'] as $sutun) {
            if (array_key_exists($sutun, $veri)) {
                $alanlar[] = $sutun . ' = ?';
                $degerler[] = $veri[$sutun];
            }
        }

        if ($alanlar === []) {
            return true;
        }

        $degerler[] = $id;
        $ifade = $this->baglanti->prepare('UPDATE sertifikalar SET ' . implode(', ', $alanlar) . ' WHERE id = ?');

        return $ifade->execute($degerler);
    }

    public function pasiflestir(int $id): bool
    {
        $ifade = $this->baglanti->prepare('UPDATE sertifikalar SET aktif = 0 WHERE id = ?');

        return $ifade->execute([$id]);
    }

    public function ceviriKaydet(int $sertifikaId, string $dil, string $baslik, ?string $aciklama): void
    {
        $ifade = $this->baglanti->prepare(
            'INSERT INTO sertifika_cevirileri (sertifika_id, dil_kodu, baslik, aciklama) VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE baslik = VALUES(baslik), aciklama = VALUES(aciklama)'
        );
        $ifade->execute([$sertifikaId, $dil, $baslik, $aciklama]);
    }
}
