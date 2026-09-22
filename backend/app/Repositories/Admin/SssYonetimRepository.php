<?php

declare(strict_types=1);

namespace Kamelya\Repositories\Admin;

use PDO;

/** SSS + çeviri yazma erişimi (silme = pasifleştirme; deleted_at sütunu yok). */
final class SssYonetimRepository
{
    public function __construct(private PDO $baglanti)
    {
    }

    /** @param array<string, mixed> $veri */
    public function olustur(array $veri): int
    {
        $ifade = $this->baglanti->prepare(
            'INSERT INTO sss_sorulari (sira, aktif, sayfa_kapsami) VALUES (?, ?, ?)'
        );
        $ifade->execute([$veri['sira'] ?? 0, $veri['aktif'] ?? 1, $veri['sayfa_kapsami'] ?? null]);

        return (int) $this->baglanti->lastInsertId();
    }

    /** @param array<string, mixed> $veri */
    public function guncelle(int $id, array $veri): bool
    {
        $alanlar = [];
        $degerler = [];
        foreach (['sira', 'aktif', 'sayfa_kapsami'] as $sutun) {
            if (array_key_exists($sutun, $veri)) {
                $alanlar[] = $sutun . ' = ?';
                $degerler[] = $veri[$sutun];
            }
        }

        if ($alanlar === []) {
            return true;
        }

        $degerler[] = $id;
        $ifade = $this->baglanti->prepare('UPDATE sss_sorulari SET ' . implode(', ', $alanlar) . ' WHERE id = ?');

        return $ifade->execute($degerler);
    }

    public function pasiflestir(int $id): bool
    {
        $ifade = $this->baglanti->prepare('UPDATE sss_sorulari SET aktif = 0 WHERE id = ?');

        return $ifade->execute([$id]);
    }

    /** @return array<string, mixed>|null */
    public function hamGetir(int $id): ?array
    {
        $ifade = $this->baglanti->prepare('SELECT * FROM sss_sorulari WHERE id = ?');
        $ifade->execute([$id]);
        $satir = $ifade->fetch();

        return $satir === false ? null : $satir;
    }

    public function ceviriKaydet(int $soruId, string $dil, string $soru, string $cevap): void
    {
        $ifade = $this->baglanti->prepare(
            'INSERT INTO sss_cevirileri (soru_id, dil_kodu, soru, cevap) VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE soru = VALUES(soru), cevap = VALUES(cevap)'
        );
        $ifade->execute([$soruId, $dil, $soru, $cevap]);
    }

    /** @return array<int, array<string, mixed>> */
    public function adminListe(): array
    {
        $ifade = $this->baglanti->query(
            "SELECT s.*, c.soru AS soru_tr FROM sss_sorulari s
             LEFT JOIN sss_cevirileri c ON c.soru_id = s.id AND c.dil_kodu = 'tr'
             ORDER BY s.sira ASC, s.id ASC"
        );

        return $ifade->fetchAll();
    }
}
