<?php

declare(strict_types=1);

namespace Kamelya\Repositories;

use PDO;

/** Bildirim kuyruğu yazma/okuma — gönderim mantığı Service'te. */
final class BildirimRepository
{
    public function __construct(private PDO $baglanti)
    {
    }

    public function kuyrugaEkle(string $tur, string $alici, string $konu, string $govde): int
    {
        $ifade = $this->baglanti->prepare(
            'INSERT INTO bildirim_kuyrugu (tur, alici, konu, govde) VALUES (?, ?, ?, ?)'
        );
        $ifade->execute([$tur, $alici, $konu, $govde]);

        return (int) $this->baglanti->lastInsertId();
    }

    /** @return array<int, array<string, mixed>> */
    public function bekleyenler(int $limit): array
    {
        $ifade = $this->baglanti->prepare(
            "SELECT * FROM bildirim_kuyrugu WHERE durum = 'bekliyor' ORDER BY id ASC LIMIT :lim"
        );
        $ifade->bindValue(':lim', $limit, PDO::PARAM_INT);
        $ifade->execute();

        return $ifade->fetchAll();
    }

    public function durumGuncelle(int $id, string $durum, ?string $hata = null): void
    {
        $ifade = $this->baglanti->prepare(
            'UPDATE bildirim_kuyrugu SET durum = ?, deneme_sayisi = deneme_sayisi + 1, hata = ? WHERE id = ?'
        );
        $ifade->execute([$durum, $hata, $id]);
    }
}
