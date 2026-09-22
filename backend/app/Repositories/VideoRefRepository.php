<?php

declare(strict_types=1);

namespace Kamelya\Repositories;

use PDO;

/** Video referans okuma + yazma. */
final class VideoRefRepository
{
    public function __construct(private PDO $baglanti)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function aktifListe(): array
    {
        $ifade = $this->baglanti->query(
            'SELECT id, musteri_adi, video_url, kapak_gorsel, aciklama FROM video_referanslari WHERE aktif = 1 ORDER BY sira ASC, id DESC'
        );

        return $ifade->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function adminListe(): array
    {
        $ifade = $this->baglanti->query('SELECT * FROM video_referanslari ORDER BY sira ASC, id DESC');

        return $ifade->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function idIleGetir(int $id): ?array
    {
        $ifade = $this->baglanti->prepare('SELECT * FROM video_referanslari WHERE id = ?');
        $ifade->execute([$id]);
        $satir = $ifade->fetch();

        return $satir === false ? null : $satir;
    }

    /** @param array<string, mixed> $veri */
    public function olustur(array $veri): int
    {
        $ifade = $this->baglanti->prepare(
            'INSERT INTO video_referanslari (musteri_adi, video_url, kapak_gorsel, aciklama, sira, aktif)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $ifade->execute([
            $veri['musteri_adi'], $veri['video_url'], $veri['kapak_gorsel'] ?? null,
            $veri['aciklama'] ?? null, $veri['sira'] ?? 0, $veri['aktif'] ?? 1,
        ]);

        return (int) $this->baglanti->lastInsertId();
    }

    /** @param array<string, mixed> $veri */
    public function guncelle(int $id, array $veri): bool
    {
        $alanlar = [];
        $degerler = [];
        foreach (['musteri_adi', 'video_url', 'kapak_gorsel', 'aciklama', 'sira', 'aktif'] as $sutun) {
            if (array_key_exists($sutun, $veri)) {
                $alanlar[] = $sutun . ' = ?';
                $degerler[] = $veri[$sutun];
            }
        }

        if ($alanlar === []) {
            return true;
        }

        $degerler[] = $id;
        $ifade = $this->baglanti->prepare('UPDATE video_referanslari SET ' . implode(', ', $alanlar) . ' WHERE id = ?');

        return $ifade->execute($degerler);
    }

    public function sil(int $id): bool
    {
        $ifade = $this->baglanti->prepare('DELETE FROM video_referanslari WHERE id = ?');

        return $ifade->execute([$id]);
    }
}
