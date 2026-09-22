<?php

declare(strict_types=1);

namespace Kamelya\Repositories;

use PDO;

/** Sanal tur okuma + yazma (çeviriler dahil). */
final class SanalTurRepository
{
    public function __construct(private PDO $baglanti)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function aktifListe(string $dil): array
    {
        $ifade = $this->baglanti->prepare(
            "SELECT t.id, t.embed_url, t.kapak_gorsel, c.baslik, c.aciklama
             FROM sanal_turlar t
             LEFT JOIN sanal_tur_cevirileri c ON c.tur_id = t.id AND c.dil_kodu = ?
             WHERE t.aktif = 1 ORDER BY t.sira ASC, t.id DESC"
        );
        $ifade->execute([$dil]);

        return $ifade->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function adminListe(): array
    {
        $ifade = $this->baglanti->query('SELECT * FROM sanal_turlar ORDER BY sira ASC, id DESC');

        return $ifade->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function idIleGetir(int $id): ?array
    {
        $ifade = $this->baglanti->prepare('SELECT * FROM sanal_turlar WHERE id = ?');
        $ifade->execute([$id]);
        $satir = $ifade->fetch();

        return $satir === false ? null : $satir;
    }

    /** @param array<string, mixed> $veri */
    public function olustur(array $veri): int
    {
        $ifade = $this->baglanti->prepare(
            'INSERT INTO sanal_turlar (baslik, aciklama, embed_url, kapak_gorsel, sira, aktif)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $ifade->execute([
            $veri['baslik'], $veri['aciklama'] ?? null, $veri['embed_url'],
            $veri['kapak_gorsel'] ?? null, $veri['sira'] ?? 0, $veri['aktif'] ?? 1,
        ]);

        return (int) $this->baglanti->lastInsertId();
    }

    /** @param array<string, mixed> $veri */
    public function guncelle(int $id, array $veri): bool
    {
        $alanlar = [];
        $degerler = [];
        foreach (['baslik', 'aciklama', 'embed_url', 'kapak_gorsel', 'sira', 'aktif'] as $sutun) {
            if (array_key_exists($sutun, $veri)) {
                $alanlar[] = $sutun . ' = ?';
                $degerler[] = $veri[$sutun];
            }
        }

        if ($alanlar === []) {
            return true;
        }

        $degerler[] = $id;
        $ifade = $this->baglanti->prepare('UPDATE sanal_turlar SET ' . implode(', ', $alanlar) . ' WHERE id = ?');

        return $ifade->execute($degerler);
    }

    public function sil(int $id): bool
    {
        $ifade = $this->baglanti->prepare('DELETE FROM sanal_turlar WHERE id = ?');

        return $ifade->execute([$id]);
    }

    public function ceviriKaydet(int $turId, string $dil, string $baslik, ?string $aciklama, string $slug): void
    {
        $ifade = $this->baglanti->prepare(
            'INSERT INTO sanal_tur_cevirileri (tur_id, dil_kodu, baslik, aciklama, slug) VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE baslik = VALUES(baslik), aciklama = VALUES(aciklama), slug = VALUES(slug)'
        );
        $ifade->execute([$turId, $dil, $baslik, $aciklama, $slug]);
    }
}
