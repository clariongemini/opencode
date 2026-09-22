<?php

declare(strict_types=1);

namespace Kamelya\Repositories;

use PDO;

/** Kategori çarpanı okuma — en güncel aktif satır (gecerlilik_baslangici DESC). */
final class FiyatCarpaniRepository
{
    public function __construct(private PDO $baglanti)
    {
    }

    /** @return array<string, mixed>|null */
    public function kategoriIdIleGetir(int $kategoriId): ?array
    {
        $ifade = $this->baglanti->prepare(
            'SELECT * FROM fiyat_carpanlari
             WHERE kategori_id = ? AND aktif = 1
             ORDER BY gecerlilik_baslangici DESC, id DESC LIMIT 1'
        );
        $ifade->execute([$kategoriId]);
        $satir = $ifade->fetch();

        return $satir === false ? null : $satir;
    }

    /** @return array<string, mixed>|null */
    public function turKodIleGetir(string $tur, string $kod): ?array
    {
        $ifade = $this->baglanti->prepare(
            'SELECT f.* FROM fiyat_carpanlari f
             JOIN kategoriler k ON k.id = f.kategori_id
             WHERE k.tur = ? AND k.kod = ? AND f.aktif = 1
             ORDER BY f.gecerlilik_baslangici DESC, f.id DESC LIMIT 1'
        );
        $ifade->execute([$tur, $kod]);
        $satir = $ifade->fetch();

        return $satir === false ? null : $satir;
    }
}
