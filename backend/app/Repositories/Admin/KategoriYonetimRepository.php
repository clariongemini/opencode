<?php

declare(strict_types=1);

namespace Kamelya\Repositories\Admin;

use PDO;

/** Kategori + çeviri yazma erişimi. */
final class KategoriYonetimRepository
{
    public function __construct(private PDO $baglanti)
    {
    }

    /** @param array<string, mixed> $veri */
    public function olustur(array $veri): int
    {
        $ifade = $this->baglanti->prepare(
            'INSERT INTO kategoriler (tur, kod, ust_kategori_id, sira, aktif) VALUES (?, ?, ?, ?, ?)'
        );
        $ifade->execute([
            $veri['tur'], $veri['kod'], $veri['ust_kategori_id'] ?? null,
            $veri['sira'] ?? 0, $veri['aktif'] ?? 1,
        ]);

        return (int) $this->baglanti->lastInsertId();
    }

    /** @param array<string, mixed> $veri */
    public function guncelle(int $id, array $veri): bool
    {
        $alanlar = [];
        $degerler = [];
        foreach (['kod', 'ust_kategori_id', 'sira', 'aktif'] as $sutun) {
            if (array_key_exists($sutun, $veri)) {
                $alanlar[] = $sutun . ' = ?';
                $degerler[] = $veri[$sutun];
            }
        }

        if ($alanlar === []) {
            return true;
        }

        $degerler[] = $id;
        $ifade = $this->baglanti->prepare('UPDATE kategoriler SET ' . implode(', ', $alanlar) . ' WHERE id = ?');

        return $ifade->execute($degerler);
    }

    public function pasiflestir(int $id): bool
    {
        $ifade = $this->baglanti->prepare('UPDATE kategoriler SET aktif = 0 WHERE id = ?');

        return $ifade->execute([$id]);
    }

    /** @return array<string, mixed>|null */
    public function hamGetir(int $id): ?array
    {
        $ifade = $this->baglanti->prepare('SELECT * FROM kategoriler WHERE id = ?');
        $ifade->execute([$id]);
        $satir = $ifade->fetch();

        return $satir === false ? null : $satir;
    }

    public function ceviriKaydet(int $kategoriId, string $dil, string $isim, ?string $aciklama, string $slug): void
    {
        $ifade = $this->baglanti->prepare(
            'INSERT INTO kategori_cevirileri (kategori_id, dil_kodu, isim, aciklama, slug)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE isim = VALUES(isim), aciklama = VALUES(aciklama), slug = VALUES(slug)'
        );
        $ifade->execute([$kategoriId, $dil, $isim, $aciklama, $slug]);
    }

    /** @return array<int, array<string, mixed>> */
    public function adminListe(?string $tur): array
    {
        if ($tur === null || $tur === '') {
            $ifade = $this->baglanti->query('SELECT * FROM kategoriler ORDER BY tur, sira');

            return $ifade->fetchAll();
        }

        $ifade = $this->baglanti->prepare('SELECT * FROM kategoriler WHERE tur = ? ORDER BY sira');
        $ifade->execute([$tur]);

        return $ifade->fetchAll();
    }
}
