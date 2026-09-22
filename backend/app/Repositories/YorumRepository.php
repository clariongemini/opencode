<?php

declare(strict_types=1);

namespace Kamelya\Repositories;

use PDO;

/**
 * Yorum veri erişimi — public okuma YALNIZCA onaylılar; e-posta/telefon
 * yanıta dahil edilmez (Service katmanı da ayrıca ayıklar).
 */
final class YorumRepository
{
    public function __construct(private PDO $baglanti)
    {
    }

    /** @param array<string, mixed> $veri */
    public function olustur(array $veri): int
    {
        $ifade = $this->baglanti->prepare(
            "INSERT INTO yorumlar
             (musteri_adi, eposta, telefon, puan, baslik, yorum, urun_id, dil_kodu,
              durum, ip_adresi, kvkk_onayi)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'bekliyor', ?, ?)"
        );
        $ifade->execute([
            $veri['musteri_adi'],
            $veri['eposta'],
            $veri['telefon'] ?? null,
            $veri['puan'],
            $veri['baslik'] ?? null,
            $veri['yorum'],
            $veri['urun_id'] ?? null,
            $veri['dil_kodu'],
            $veri['ip_adresi'] ?? null,
            $veri['kvkk_onayi'] ? 1 : 0,
        ]);

        return (int) $this->baglanti->lastInsertId();
    }

    /**
     * @return array{satirlar: array, toplam: int, ortalama: float}
     */
    public function onayliListe(?int $urunId, string $dil, int $sayfa, int $adet, bool $yalnizOneCikan = false): array
    {
        $kosullar = ["durum = 'onaylandi'", 'dil_kodu = :dil'];
        $params = [':dil' => $dil];
        if ($urunId !== null) {
            $kosullar[] = 'urun_id = :urun';
            $params[':urun'] = $urunId;
        }

        if ($yalnizOneCikan) {
            $kosullar[] = 'one_cikan = 1';
        }

        $where = 'WHERE ' . implode(' AND ', $kosullar);

        $sayac = $this->baglanti->prepare("SELECT COUNT(*), AVG(puan) FROM yorumlar {$where}");
        foreach ($params as $ad => $deger) {
            $sayac->bindValue($ad, $deger);
        }

        $sayac->execute();
        [$toplam, $ortalama] = $sayac->fetch(PDO::FETCH_NUM);

        $ifade = $this->baglanti->prepare(
            "SELECT id, musteri_adi, puan, baslik, yorum, urun_id, dil_kodu, one_cikan, created_at
             FROM yorumlar {$where} ORDER BY created_at DESC LIMIT :lim OFFSET :off"
        );
        foreach ($params as $ad => $deger) {
            $ifade->bindValue($ad, $deger);
        }

        $ifade->bindValue(':lim', $adet, PDO::PARAM_INT);
        $ifade->bindValue(':off', ($sayfa - 1) * $adet, PDO::PARAM_INT);
        $ifade->execute();

        return [
            'satirlar' => $ifade->fetchAll(),
            'toplam' => (int) $toplam,
            'ortalama' => $ortalama === null ? 0.0 : round((float) $ortalama, 1),
        ];
    }

    /** @return array<string, mixed> */
    public function ozet(?int $urunId = null): array
    {
        $where = $urunId === null ? "WHERE durum = 'onaylandi'" : "WHERE urun_id = :urun AND durum = 'onaylandi'";

        $ifade = $this->baglanti->prepare(
            "SELECT COUNT(*) AS toplam, AVG(puan) AS ortalama FROM yorumlar {$where}"
        );
        if ($urunId !== null) {
            $ifade->bindValue(':urun', $urunId, PDO::PARAM_INT);
        }

        $ifade->execute();
        $satir = $ifade->fetch() ?: [];

        $dagilimIfade = $this->baglanti->prepare(
            "SELECT puan, COUNT(*) AS adet FROM yorumlar {$where} GROUP BY puan"
        );
        if ($urunId !== null) {
            $dagilimIfade->bindValue(':urun', $urunId, PDO::PARAM_INT);
        }

        $dagilimIfade->execute();
        $dagilim = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        foreach ($dagilimIfade->fetchAll() as $s) {
            $dagilim[(int) $s['puan']] = (int) $s['adet'];
        }

        return [
            'toplam_yorum' => (int) ($satir['toplam'] ?? 0),
            'ortalama_puan' => $satir['ortalama'] === null ? 0.0 : round((float) $satir['ortalama'], 1),
            'puan_dagilimi' => $dagilim,
        ];
    }

    /** @return array<string, mixed>|null */
    public function idIleGetir(int $id): ?array
    {
        $ifade = $this->baglanti->prepare('SELECT * FROM yorumlar WHERE id = ?');
        $ifade->execute([$id]);
        $satir = $ifade->fetch();

        return $satir === false ? null : $satir;
    }

    /** @param array<string, mixed> $veri */
    public function guncelle(int $id, array $veri): bool
    {
        $alanlar = [];
        $degerler = [];
        foreach (['durum', 'red_sebebi', 'onaylayan_id', 'onaylanma_at', 'one_cikan'] as $sutun) {
            if (array_key_exists($sutun, $veri)) {
                $alanlar[] = $sutun . ' = ?';
                $degerler[] = $veri[$sutun];
            }
        }

        if ($alanlar === []) {
            return true;
        }

        $degerler[] = $id;
        $ifade = $this->baglanti->prepare('UPDATE yorumlar SET ' . implode(', ', $alanlar) . ' WHERE id = ?');

        return $ifade->execute($degerler);
    }

    /**
     * @return array{satirlar: array, toplam: int}
     */
    public function moderasyonListesi(?string $durum, int $sayfa, int $adet): array
    {
        $where = '';
        $params = [];
        if ($durum !== null && $durum !== '') {
            $where = 'WHERE durum = :durum';
            $params[':durum'] = $durum;
        }

        $sayac = $this->baglanti->prepare("SELECT COUNT(*) FROM yorumlar {$where}");
        foreach ($params as $ad => $deger) {
            $sayac->bindValue($ad, $deger);
        }

        $sayac->execute();
        $toplam = (int) $sayac->fetchColumn();

        $ifade = $this->baglanti->prepare(
            "SELECT * FROM yorumlar {$where} ORDER BY created_at DESC LIMIT :lim OFFSET :off"
        );
        foreach ($params as $ad => $deger) {
            $ifade->bindValue($ad, $deger);
        }

        $ifade->bindValue(':lim', $adet, PDO::PARAM_INT);
        $ifade->bindValue(':off', ($sayfa - 1) * $adet, PDO::PARAM_INT);
        $ifade->execute();

        return ['satirlar' => $ifade->fetchAll(), 'toplam' => $toplam];
    }
}
