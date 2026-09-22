<?php

declare(strict_types=1);

namespace Kamelya\Repositories;

use PDO;

/** Dil bazlı temel fiyat okuma — canlı kur yok, yalnızca sabit satırlar. */
final class UrunFiyatiRepository
{
    public function __construct(private PDO $baglanti)
    {
    }

    /** @return array<string, mixed>|null */
    public function dilIleGetir(string $dil): ?array
    {
        $ifade = $this->baglanti->prepare(
            'SELECT * FROM urun_fiyatlari WHERE dil_kodu = ? AND aktif = 1'
        );
        $ifade->execute([$dil]);
        $satir = $ifade->fetch();

        return $satir === false ? null : $satir;
    }

    /** @return array<int, array<string, mixed>> */
    public function tumAktifFiyatlar(): array
    {
        $ifade = $this->baglanti->query(
            'SELECT * FROM urun_fiyatlari WHERE aktif = 1 ORDER BY dil_kodu ASC'
        );

        return $ifade->fetchAll();
    }
}
