<?php

declare(strict_types=1);

namespace Kamelya\Repositories;

use PDO;

/** Kampanya okuma + yazma (çeviriler dahil). */
final class KampanyaRepository
{
    public function __construct(private PDO $baglanti)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function aktifListe(string $dil): array
    {
        $ifade = $this->baglanti->prepare(
            "SELECT k.*, c.baslik AS ceviri_baslik, c.aciklama AS ceviri_aciklama, c.cta_metni
             FROM kampanyalar k
             LEFT JOIN kampanya_cevirileri c ON c.kampanya_id = k.id AND c.dil_kodu = ?
             WHERE k.aktif = 1 AND k.baslangic <= NOW() AND k.bitis >= NOW()
             ORDER BY k.sira ASC, k.id DESC"
        );
        $ifade->execute([$dil]);

        return $ifade->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function adminListe(): array
    {
        $ifade = $this->baglanti->query('SELECT * FROM kampanyalar ORDER BY sira ASC, id DESC');

        return $ifade->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function idIleGetir(int $id): ?array
    {
        $ifade = $this->baglanti->prepare('SELECT * FROM kampanyalar WHERE id = ?');
        $ifade->execute([$id]);
        $satir = $ifade->fetch();

        return $satir === false ? null : $satir;
    }

    /** @param array<string, mixed> $veri */
    public function olustur(array $veri): int
    {
        $ifade = $this->baglanti->prepare(
            'INSERT INTO kampanyalar (kod, indirim_orani, indirim_tipi, baslangic, bitis, banner_gorsel, link_url, aktif, sira)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $ifade->execute([
            $veri['kod'], $veri['indirim_orani'] ?? null, $veri['indirim_tipi'] ?? 'yuzde',
            $veri['baslangic'], $veri['bitis'], $veri['banner_gorsel'] ?? null,
            $veri['link_url'] ?? null, $veri['aktif'] ?? 1, $veri['sira'] ?? 0,
        ]);

        return (int) $this->baglanti->lastInsertId();
    }

    /** @param array<string, mixed> $veri */
    public function guncelle(int $id, array $veri): bool
    {
        $alanlar = [];
        $degerler = [];
        foreach (['kod', 'indirim_orani', 'indirim_tipi', 'baslangic', 'bitis', 'banner_gorsel', 'link_url', 'aktif', 'sira'] as $sutun) {
            if (array_key_exists($sutun, $veri)) {
                $alanlar[] = $sutun . ' = ?';
                $degerler[] = $veri[$sutun];
            }
        }

        if ($alanlar === []) {
            return true;
        }

        $degerler[] = $id;
        $ifade = $this->baglanti->prepare('UPDATE kampanyalar SET ' . implode(', ', $alanlar) . ' WHERE id = ?');

        return $ifade->execute($degerler);
    }

    public function sil(int $id): bool
    {
        $ifade = $this->baglanti->prepare('DELETE FROM kampanyalar WHERE id = ?');

        return $ifade->execute([$id]);
    }

    public function ceviriKaydet(int $kampanyaId, string $dil, string $baslik, ?string $aciklama, ?string $cta): void
    {
        $ifade = $this->baglanti->prepare(
            'INSERT INTO kampanya_cevirileri (kampanya_id, dil_kodu, baslik, aciklama, cta_metni) VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE baslik = VALUES(baslik), aciklama = VALUES(aciklama), cta_metni = VALUES(cta_metni)'
        );
        $ifade->execute([$kampanyaId, $dil, $baslik, $aciklama, $cta]);
    }
}
