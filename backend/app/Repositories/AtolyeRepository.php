<?php

declare(strict_types=1);

namespace Kamelya\Repositories;

use PDO;

/** Atölye fotoğraf okuma + yazma. */
final class AtolyeRepository
{
    public function __construct(private PDO $baglanti)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function aktifListe(): array
    {
        $ifade = $this->baglanti->query(
            'SELECT id, baslik, aciklama, dosya_yolu FROM atolye_fotograflari WHERE aktif = 1 ORDER BY sira ASC, id DESC'
        );

        return $ifade->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function adminListe(): array
    {
        $ifade = $this->baglanti->query('SELECT * FROM atolye_fotograflari ORDER BY sira ASC, id DESC');

        return $ifade->fetchAll();
    }

    public function olustur(string $baslik, ?string $aciklama, string $yol): int
    {
        $ifade = $this->baglanti->prepare(
            'INSERT INTO atolye_fotograflari (baslik, aciklama, dosya_yolu) VALUES (?, ?, ?)'
        );
        $ifade->execute([$baslik, $aciklama, $yol]);

        return (int) $this->baglanti->lastInsertId();
    }

    /** @return array<string, mixed>|null */
    public function idIleGetir(int $id): ?array
    {
        $ifade = $this->baglanti->prepare('SELECT * FROM atolye_fotograflari WHERE id = ?');
        $ifade->execute([$id]);
        $satir = $ifade->fetch();

        return $satir === false ? null : $satir;
    }

    public function sil(int $id): void
    {
        $ifade = $this->baglanti->prepare('DELETE FROM atolye_fotograflari WHERE id = ?');
        $ifade->execute([$id]);
    }
}
