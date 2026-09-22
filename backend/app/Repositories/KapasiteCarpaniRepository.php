<?php

declare(strict_types=1);

namespace Kamelya\Repositories;

use PDO;

/** Kapasite çarpanı okuma — en güncel aktif satır (gecerlilik_baslangici DESC). */
final class KapasiteCarpaniRepository
{
    public function __construct(private PDO $baglanti)
    {
    }

    /** @return array<string, mixed>|null */
    public function kategoriIdIleGetir(int $kategoriId): ?array
    {
        $ifade = $this->baglanti->prepare(
            'SELECT * FROM kapasite_carpanlari
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
            'SELECT kc.* FROM kapasite_carpanlari kc
             JOIN kategoriler k ON k.id = kc.kategori_id
             WHERE k.tur = ? AND k.kod = ? AND kc.aktif = 1
             ORDER BY kc.gecerlilik_baslangici DESC, kc.id DESC LIMIT 1'
        );
        $ifade->execute([$tur, $kod]);
        $satir = $ifade->fetch();

        return $satir === false ? null : $satir;
    }
}