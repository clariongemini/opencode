<?php

declare(strict_types=1);

namespace Kamelya\Services;

use Kamelya\Repositories\RandevuRepository;
use PDO;

/** Takvim okuma orkestrasyonu — ay ızgarası, gün detayı, yaklaşan işler. */
final class TakvimService
{
    public function __construct(
        private PDO $baglanti,
        private RandevuRepository $randevular
    ) {
    }

    /**
     * @param array<string, string> $filtreler tur|ekip_uyesi_id|sehir
     * @return array<string, mixed>
     */
    public function aylikTakvimOlustur(int $yil, int $ay, array $filtreler = []): array
    {
        $bas = sprintf('%04d-%02d-01', $yil, $ay);
        $gunSayisi = (int) date('t', strtotime($bas));
        $ilkGun = (int) date('N', strtotime($bas));
        $bit = sprintf('%04d-%02d-%02d 23:59:59', $yil, $ay, $gunSayisi);

        $satirlar = $this->randevular->tarihAraligiDetayliGetir($bas . ' 00:00:00', $bit, $filtreler);

        $sehir = $filtreler['sehir'] ?? '';
        $gunler = [];
        foreach ($satirlar as $satir) {
            if ($sehir !== '' && mb_strtolower((string) ($satir['sehir'] ?? '')) !== mb_strtolower($sehir)) {
                continue;
            }

            $tarih = substr((string) $satir['randevu_tarihi'], 0, 10);
            $gunler[$tarih][] = $this->ozetSatir($satir);
        }

        return [
            'yil' => $yil,
            'ay' => $ay,
            'gun_sayisi' => $gunSayisi,
            'ilk_gun' => $ilkGun,
            'gunler' => $gunler,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function gunDetay(string $tarih): array
    {
        $cikti = [];
        foreach ($this->randevular->gunlukIslerGetir($tarih) as $satir) {
            $cikti[] = $this->ozetSatir($satir);
        }

        return $cikti;
    }

    /** @return array<int, array<string, mixed>> */
    public function yaklasanIsler(int $gunSayisi = 7): array
    {
        $bas = date('Y-m-d 00:00:00');
        $bit = date('Y-m-d 23:59:59', strtotime('+' . max(1, min(30, $gunSayisi)) . ' days'));
        $cikti = [];
        foreach ($this->randevular->tarihAraligiDetayliGetir($bas, $bit) as $satir) {
            $cikti[] = $this->ozetSatir($satir);
        }

        return $cikti;
    }

    /** @param array<string, mixed> $satir */
    private function ozetSatir(array $satir): array
    {
        return [
            'id' => (int) $satir['id'],
            'ad_soyad' => $satir['ad_soyad'],
            'telefon' => $satir['telefon'],
            'randevu_tarihi' => $satir['randevu_tarihi'],
            'durum' => $satir['durum'],
            'tur' => $satir['tur'] ?? 'gorusme',
            'sure_dakika' => (int) ($satir['sure_dakika'] ?? 60),
            'ekip_uyesi_id' => $satir['ekip_uyesi_id'] === null ? null : (int) $satir['ekip_uyesi_id'],
            'ekip_adi' => $satir['ekip_adi'] ?? null,
            'oncelik' => $satir['oncelik'] ?? 'normal',
            'sehir' => $satir['sehir'] ?? null,
            'adres' => $satir['adres'] ?? null,
        ];
    }
}
