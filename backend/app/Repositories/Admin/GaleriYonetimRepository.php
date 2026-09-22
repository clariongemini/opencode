<?php

declare(strict_types=1);

namespace Kamelya\Repositories\Admin;

use PDO;

/** Galeri yazma erişimi ([EK-20260921] tablosu). Silme = pasifleştirme. */
final class GaleriYonetimRepository
{
    public function __construct(private PDO $baglanti)
    {
    }

    /** @param array<string, mixed> $veri */
    public function olustur(array $veri): int
    {
        $ifade = $this->baglanti->prepare(
            'INSERT INTO galeri (dil_kodu, baslik, aciklama, dosya_yolu, proje_hikayesi, sira, aktif)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $ifade->execute([
            $veri['dil_kodu'] ?? 'tr', $veri['baslik'], $veri['aciklama'] ?? null,
            $veri['dosya_yolu'], $veri['proje_hikayesi'] ?? null,
            $veri['sira'] ?? 0, $veri['aktif'] ?? 1,
        ]);

        return (int) $this->baglanti->lastInsertId();
    }

    /** @param array<string, mixed> $veri */
    public function guncelle(int $id, array $veri): bool
    {
        $alanlar = [];
        $degerler = [];
        foreach (['dil_kodu', 'baslik', 'aciklama', 'dosya_yolu', 'proje_hikayesi', 'sira', 'aktif'] as $sutun) {
            if (array_key_exists($sutun, $veri)) {
                $alanlar[] = $sutun . ' = ?';
                $degerler[] = $veri[$sutun];
            }
        }

        if ($alanlar === []) {
            return true;
        }

        $degerler[] = $id;
        $ifade = $this->baglanti->prepare('UPDATE galeri SET ' . implode(', ', $alanlar) . ' WHERE id = ?');

        return $ifade->execute($degerler);
    }

    public function pasiflestir(int $id): bool
    {
        $ifade = $this->baglanti->prepare('UPDATE galeri SET aktif = 0 WHERE id = ?');

        return $ifade->execute([$id]);
    }

    /** @return array<string, mixed>|null */
    public function hamGetir(int $id): ?array
    {
        $ifade = $this->baglanti->prepare('SELECT * FROM galeri WHERE id = ?');
        $ifade->execute([$id]);
        $satir = $ifade->fetch();

        return $satir === false ? null : $satir;
    }

    /** @return array<int, array<string, mixed>> */
    public function adminListe(): array
    {
        $ifade = $this->baglanti->query('SELECT * FROM galeri ORDER BY sira ASC, id DESC');

        return $ifade->fetchAll();
    }
}
