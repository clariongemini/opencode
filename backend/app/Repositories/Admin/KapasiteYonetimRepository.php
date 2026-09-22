<?php

declare(strict_types=1);

namespace Kamelya\Repositories\Admin;

use PDO;

/** Kapasite çarpanı admin yönetimi — CRUD + pasifleştirme. */
final class KapasiteYonetimRepository
{
    public function __construct(private PDO $baglanti)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function adminListe(): array
    {
        $ifade = $this->baglanti->prepare(
            'SELECT kc.*, k.kod, k.tur
             FROM kapasite_carpanlari kc
             JOIN kategoriler k ON k.id = kc.kategori_id
             ORDER BY k.tur, k.kod, kc.gecerlilik_baslangici DESC'
        );
        $ifade->execute();
        $sonuc = $ifade->fetchAll();

        return $sonuc === false ? [] : $sonuc;
    }

    /** @return array<string, mixed>|null */
    public function hamGetir(int $id): ?array
    {
        $ifade = $this->baglanti->prepare('SELECT * FROM kapasite_carpanlari WHERE id = ?');
        $ifade->execute([$id]);
        $satir = $ifade->fetch();

        return $satir === false ? null : $satir;
    }

    /** @param array<string, mixed> $veri
     * @return int */
    public function olustur(array $veri): int
    {
        $ifade = $this->baglanti->prepare(
            'INSERT INTO kapasite_carpanlari (kategori_id, m2_per_kisi, aktif, gecerlilik_baslangici, aciklama)
             VALUES (?, ?, ?, ?, ?)'
        );
        $ifade->execute([
            $veri['kategori_id'],
            $veri['m2_per_kisi'],
            $veri['aktif'] ?? 1,
            $veri['gecerlilik_baslangici'],
            $veri['aciklama'] ?? null,
        ]);

        return (int) $this->baglanti->lastInsertId();
    }

    /** @param array<string, mixed> $veri */
    public function guncelle(int $id, array $veri): void
    {
        $ifade = $this->baglanti->prepare(
            'UPDATE kapasite_carpanlari
             SET m2_per_kisi = ?, aktif = ?, gecerlilik_baslangici = ?, aciklama = ?
             WHERE id = ?'
        );
        $ifade->execute([
            $veri['m2_per_kisi'],
            $veri['aktif'] ?? 1,
            $veri['gecerlilik_baslangici'],
            $veri['aciklama'] ?? null,
            $id,
        ]);
    }

    public function pasiflestir(int $id): void
    {
        $ifade = $this->baglanti->prepare('UPDATE kapasite_carpanlari SET aktif = 0 WHERE id = ?');
        $ifade->execute([$id]);
    }
}