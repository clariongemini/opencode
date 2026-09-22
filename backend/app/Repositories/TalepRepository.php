<?php

declare(strict_types=1);

namespace Kamelya\Repositories;

use PDO;

/** Talep yazma/okuma — iş kuralı yok, yalnızca veri erişimi. */
final class TalepRepository
{
    public function __construct(private PDO $baglanti)
    {
    }

    /** @param array<string, mixed> $veri */
    public function olustur(array $veri): int
    {
        $ifade = $this->baglanti->prepare(
            'INSERT INTO talepler
             (ad_soyad, telefon, eposta, sehir, urun_id, genislik, derinlik, alan_m2,
              dil_kodu, para_birimi, hesaplanan_fiyat, durum, tur, mesaj, kaynak, kvkk_onayi, ip_adresi)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'yeni\', ?, ?, ?, ?, ?)'
        );
        $ifade->execute([
            $veri['ad_soyad'],
            $veri['telefon'],
            $veri['eposta'] ?? null,
            $veri['sehir'] ?? null,
            $veri['urun_id'] ?? null,
            $veri['genislik'] ?? null,
            $veri['derinlik'] ?? null,
            $veri['alan_m2'] ?? null,
            $veri['dil_kodu'],
            $veri['para_birimi'] ?? 'TRY',
            $veri['hesaplanan_fiyat'] ?? null,
            $veri['tur'] ?? 'teklif',
            $veri['mesaj'] ?? null,
            $veri['kaynak'] ?? 'web',
            $veri['kvkk_onayi'] ? 1 : 0,
            $veri['ip_adresi'] ?? null,
        ]);

        return (int) $this->baglanti->lastInsertId();
    }

    /** @return array<string, mixed>|null */
    public function idIleGetir(int $id): ?array
    {
        $ifade = $this->baglanti->prepare('SELECT * FROM talepler WHERE id = ?');
        $ifade->execute([$id]);
        $satir = $ifade->fetch();

        return $satir === false ? null : $satir;
    }

    public function durumGuncelle(int $id, string $durum): bool
    {
        $ifade = $this->baglanti->prepare('UPDATE talepler SET durum = ? WHERE id = ?');

        return $ifade->execute([$durum, $id]);
    }

    /**
     * @param array<string, string> $filtreler durum|sehir|tur
     * @return array{satirlar: array, toplam: int}
     */
    public function listele(array $filtreler, int $sayfa, int $adet): array
    {
        $kosullar = [];
        $parametreler = [];
        foreach (['durum', 'sehir', 'tur'] as $alan) {
            if (isset($filtreler[$alan]) && $filtreler[$alan] !== '') {
                $kosullar[] = $alan . ' = :' . $alan;
                $parametreler[':' . $alan] = $filtreler[$alan];
            }
        }

        $where = $kosullar === [] ? '' : 'WHERE ' . implode(' AND ', $kosullar);

        $sayacIfade = $this->baglanti->prepare("SELECT COUNT(*) FROM talepler {$where}");
        foreach ($parametreler as $ad => $deger) {
            $sayacIfade->bindValue($ad, $deger);
        }

        $sayacIfade->execute();
        $toplam = (int) $sayacIfade->fetchColumn();

        $ifade = $this->baglanti->prepare(
            "SELECT * FROM talepler {$where} ORDER BY created_at DESC LIMIT :lim OFFSET :off"
        );
        foreach ($parametreler as $ad => $deger) {
            $ifade->bindValue($ad, $deger);
        }

        $ifade->bindValue(':lim', $adet, PDO::PARAM_INT);
        $ifade->bindValue(':off', ($sayfa - 1) * $adet, PDO::PARAM_INT);
        $ifade->execute();

        return ['satirlar' => $ifade->fetchAll(), 'toplam' => $toplam];
    }
}
