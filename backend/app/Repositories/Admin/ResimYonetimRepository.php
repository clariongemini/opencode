<?php

declare(strict_types=1);

namespace Kamelya\Repositories\Admin;

use PDO;

/** Ürün görseli yazma — silme fiziksel (dosya + satır), soft değil. */
final class ResimYonetimRepository
{
    public function __construct(private PDO $baglanti)
    {
    }

    public function ekle(int $urunId, string $yol, ?string $kucukYol, string $tur, bool $kapak): int
    {
        $ifade = $this->baglanti->prepare(
            'INSERT INTO urun_resimleri (urun_id, dosya_yolu, kucuk_resim_yolu, tur, kapak_mi) VALUES (?, ?, ?, ?, ?)'
        );
        $ifade->execute([$urunId, $yol, $kucukYol, $tur, $kapak ? 1 : 0]);

        return (int) $this->baglanti->lastInsertId();
    }

    /** @return array<int, array<string, mixed>> */
    public function urunResimleri(int $urunId): array
    {
        $ifade = $this->baglanti->prepare(
            'SELECT id, dosya_yolu, tur, kapak_mi, sira FROM urun_resimleri WHERE urun_id = ? ORDER BY sira ASC, id ASC'
        );
        $ifade->execute([$urunId]);

        return $ifade->fetchAll();
    }

    public function kapakVarMi(int $urunId): bool
    {
        $ifade = $this->baglanti->prepare(
            'SELECT id FROM urun_resimleri WHERE urun_id = ? AND kapak_mi = 1 LIMIT 1'
        );
        $ifade->execute([$urunId]);

        return $ifade->fetch() !== false;
    }

    /** @return array<string, mixed>|null */
    public function idIleGetir(int $id): ?array
    {
        $ifade = $this->baglanti->prepare('SELECT * FROM urun_resimleri WHERE id = ?');
        $ifade->execute([$id]);
        $satir = $ifade->fetch();

        return $satir === false ? null : $satir;
    }

    public function sil(int $id): bool
    {
        $ifade = $this->baglanti->prepare('DELETE FROM urun_resimleri WHERE id = ?');

        return $ifade->execute([$id]);
    }
}
