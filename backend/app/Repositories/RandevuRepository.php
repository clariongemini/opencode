<?php

declare(strict_types=1);

namespace Kamelya\Repositories;

use PDO;

/** Randevu yazma/okuma — slot çakışma sorgusu dahil, kural Service'te. */
final class RandevuRepository
{
    public function __construct(private PDO $baglanti)
    {
    }

    /** @param array<string, mixed> $veri */
    public function olustur(array $veri): int
    {
        $ifade = $this->baglanti->prepare(
            "INSERT INTO randevular
             (talep_id, ad_soyad, telefon, eposta, randevu_tarihi, durum, notlar)
             VALUES (?, ?, ?, ?, ?, 'bekliyor', ?)"
        );
        $ifade->execute([
            $veri['talep_id'] ?? null,
            $veri['ad_soyad'],
            $veri['telefon'],
            $veri['eposta'] ?? null,
            $veri['randevu_tarihi'],
            $veri['notlar'] ?? null,
        ]);

        return (int) $this->baglanti->lastInsertId();
    }

    /** Belirli aralıktaki iptal-dışı randevular (slot doluluk kontrolü). */
    /** @return array<int, array<string, mixed>> */
    public function tarihAraligiIleGetir(string $baslangic, string $bitis): array
    {
        $ifade = $this->baglanti->prepare(
            "SELECT id, randevu_tarihi FROM randevular
             WHERE randevu_tarihi BETWEEN ? AND ? AND durum != 'iptal'"
        );
        $ifade->execute([$baslangic, $bitis]);

        return $ifade->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function idIleGetir(int $id): ?array
    {
        $ifade = $this->baglanti->prepare('SELECT * FROM randevular WHERE id = ?');
        $ifade->execute([$id]);
        $satir = $ifade->fetch();

        return $satir === false ? null : $satir;
    }

    /**
     * Ay görünümü — talep (şehir) + ekip (ad) birleşimli.
     *
     * @param array<string, string> $filtreler tur|ekip_uyesi_id (sehir PHP'de süzülür)
     * @return array<int, array<string, mixed>>
     */
    public function tarihAraligiDetayliGetir(string $baslangic, string $bitis, array $filtreler = []): array
    {
        $kosullar = ['r.randevu_tarihi BETWEEN ? AND ?', "r.durum != 'iptal'"];
        $params = [$baslangic, $bitis];
        if (isset($filtreler['tur']) && $filtreler['tur'] !== '') {
            $kosullar[] = 'r.tur = ?';
            $params[] = $filtreler['tur'];
        }

        if (isset($filtreler['ekip_uyesi_id']) && $filtreler['ekip_uyesi_id'] !== '') {
            $kosullar[] = 'r.ekip_uyesi_id = ?';
            $params[] = (int) $filtreler['ekip_uyesi_id'];
        }

        $ifade = $this->baglanti->prepare(
            'SELECT r.*, t.sehir, k.ad_soyad AS ekip_adi
             FROM randevular r
             LEFT JOIN talepler t ON t.id = r.talep_id
             LEFT JOIN kullanicilar k ON k.id = r.ekip_uyesi_id
             WHERE ' . implode(' AND ', $kosullar) . ' ORDER BY r.randevu_tarihi ASC'
        );
        $ifade->execute($params);

        return $ifade->fetchAll();
    }

    /** Ekip çakışma testi — kesin örtüşme (iptaller hariç, kendisi hariç). */
    public function ekipCakisiyorMu(int $ekipId, string $yeniBas, string $yeniBit, int $haricId = 0): bool
    {
        $ifade = $this->baglanti->prepare(
            "SELECT id FROM randevular
             WHERE ekip_uyesi_id = ? AND durum != 'iptal' AND id != ?
             AND randevu_tarihi < ? AND DATE_ADD(randevu_tarihi, INTERVAL sure_dakika MINUTE) > ?
             LIMIT 1"
        );
        $ifade->execute([$ekipId, $haricId, $yeniBit, $yeniBas]);

        return $ifade->fetch() !== false;
    }

    /** @return array<int, array<string, mixed>> */
    public function gunlukIslerGetir(string $tarih): array
    {
        return $this->tarihAraligiDetayliGetir($tarih . ' 00:00:00', $tarih . ' 23:59:59');
    }

    /** @param array<string, mixed> $veri */
    public function guncelle(int $id, array $veri): bool
    {
        $alanlar = [];
        $degerler = [];
        foreach (['talep_id', 'ad_soyad', 'telefon', 'eposta', 'randevu_tarihi', 'durum', 'notlar',
            'tur', 'ekip_uyesi_id', 'sure_dakika', 'adres', 'oncelik', 'tamamlanma_notu', 'tamamlanma_at'] as $sutun) {
            if (array_key_exists($sutun, $veri)) {
                $alanlar[] = $sutun . ' = ?';
                $degerler[] = $veri[$sutun];
            }
        }

        if ($alanlar === []) {
            return true;
        }

        $degerler[] = $id;
        $ifade = $this->baglanti->prepare('UPDATE randevular SET ' . implode(', ', $alanlar) . ' WHERE id = ?');

        return $ifade->execute($degerler);
    }
}
