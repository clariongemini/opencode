<?php

declare(strict_types=1);

namespace Kamelya\Repositories;

use PDO;

/** Kategori okuma erişimi — yalnızca prepared statement. */
final class KategoriRepository
{
    public function __construct(private PDO $baglanti)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function turIleGetir(string $tur, bool $yalnizAktif = true): array
    {
        $sql = 'SELECT * FROM kategoriler WHERE tur = ?';
        if ($yalnizAktif) {
            $sql .= ' AND aktif = 1';
        }

        $sql .= ' ORDER BY sira ASC';
        $ifade = $this->baglanti->prepare($sql);
        $ifade->execute([$tur]);

        return $ifade->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function kodIleGetir(string $tur, string $kod): ?array
    {
        $ifade = $this->baglanti->prepare(
            'SELECT * FROM kategoriler WHERE tur = ? AND kod = ? AND aktif = 1'
        );
        $ifade->execute([$tur, $kod]);
        $satir = $ifade->fetch();

        return $satir === false ? null : $satir;
    }

    /** @return array<string, mixed>|null */
    public function idIleGetir(int $id): ?array
    {
        $ifade = $this->baglanti->prepare('SELECT * FROM kategoriler WHERE id = ?');
        $ifade->execute([$id]);
        $satir = $ifade->fetch();

        return $satir === false ? null : $satir;
    }

    /** @return array<string, mixed>|null */
    public function slugIleGetir(string $dil, string $slug): ?array
    {
        $ifade = $this->baglanti->prepare(
            'SELECT k.*, c.isim, c.slug
             FROM kategoriler k
             JOIN kategori_cevirileri c ON c.kategori_id = k.id AND c.dil_kodu = ?
             WHERE c.slug = ? AND k.aktif = 1'
        );
        $ifade->execute([$dil, $slug]);
        $satir = $ifade->fetch();

        return $satir === false ? null : $satir;
    }
}
