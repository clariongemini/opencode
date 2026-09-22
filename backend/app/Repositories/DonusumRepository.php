<?php

declare(strict_types=1);

namespace Kamelya\Repositories;

use PDO;

/** Proje dönüşümleri (öncesi/sonrası) okuma + yazma. */
final class DonusumRepository
{
    public function __construct(private PDO $baglanti)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function aktifListe(): array
    {
        $ifade = $this->baglanti->query(
            'SELECT * FROM proje_donusumleri WHERE aktif = 1 ORDER BY sira ASC, id DESC'
        );

        return $ifade->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function adminListe(): array
    {
        $ifade = $this->baglanti->query('SELECT * FROM proje_donusumleri ORDER BY sira ASC, id DESC');

        return $ifade->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function idIleGetir(int $id): ?array
    {
        $ifade = $this->baglanti->prepare('SELECT * FROM proje_donusumleri WHERE id = ?');
        $ifade->execute([$id]);
        $satir = $ifade->fetch();

        return $satir === false ? null : $satir;
    }

    /** @param array<string, mixed> $veri */
    public function olustur(array $veri): int
    {
        $ifade = $this->baglanti->prepare(
            'INSERT INTO proje_donusumleri (baslik, oncesi_gorsel, sonrasi_gorsel, aciklama, proje_id, sira, aktif)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $ifade->execute([
            $veri['baslik'], $veri['oncesi_gorsel'], $veri['sonrasi_gorsel'],
            $veri['aciklama'] ?? null, $veri['proje_id'] ?? null,
            $veri['sira'] ?? 0, $veri['aktif'] ?? 1,
        ]);

        return (int) $this->baglanti->lastInsertId();
    }

    /** @param array<string, mixed> $veri */
    public function guncelle(int $id, array $veri): bool
    {
        $alanlar = [];
        $degerler = [];
        foreach (['baslik', 'oncesi_gorsel', 'sonrasi_gorsel', 'aciklama', 'proje_id', 'sira', 'aktif'] as $sutun) {
            if (array_key_exists($sutun, $veri)) {
                $alanlar[] = $sutun . ' = ?';
                $degerler[] = $veri[$sutun];
            }
        }

        if ($alanlar === []) {
            return true;
        }

        $degerler[] = $id;
        $ifade = $this->baglanti->prepare('UPDATE proje_donusumleri SET ' . implode(', ', $alanlar) . ' WHERE id = ?');

        return $ifade->execute($degerler);
    }

    public function sil(int $id): bool
    {
        $ifade = $this->baglanti->prepare('DELETE FROM proje_donusumleri WHERE id = ?');

        return $ifade->execute([$id]);
    }
}
