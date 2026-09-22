<?php

declare(strict_types=1);

namespace Kamelya\Validators\Admin;

/** Takvim randevu yazma girdisi — iş kuralları Service'te. */
final class TakvimValidator
{
    public const TURLER = ['gorusme', 'kesif', 'uretim', 'montaj', 'teslim'];

    public const ONCELIKLER = ['dusuk', 'normal', 'yuksek', 'acil'];

    public const SURELER = [30, 60, 90, 120, 180, 240];

    public const DURUMLAR = ['bekliyor', 'devam_ediyor', 'tamamlandi', 'iptal'];

    /** @param array<string, mixed> $veri */
    public static function dogrula(array $veri, bool $guncelleme = false): array
    {
        $hatalar = [];

        if (!$guncelleme || array_key_exists('tur', $veri)) {
            if (!in_array($veri['tur'] ?? null, self::TURLER, true)) {
                $hatalar[] = ['field' => 'tur', 'issue' => 'invalid'];
            }
        }

        if (!$guncelleme || array_key_exists('randevu_tarihi', $veri)) {
            $tarih = \DateTimeImmutable::createFromFormat('Y-m-d H:i', (string) ($veri['randevu_tarihi'] ?? ''));
            if ($tarih === false) {
                // Service 'Y-m-d H:i:s' biçimine de izin verir; burada yalnızca boşluk kontrolü.
                if (trim((string) ($veri['randevu_tarihi'] ?? '')) === '') {
                    $hatalar[] = ['field' => 'randevu_tarihi', 'issue' => 'required'];
                }
            }
        }

        if (isset($veri['sure_dakika']) && !in_array((int) $veri['sure_dakika'], self::SURELER, true)) {
            $hatalar[] = ['field' => 'sure_dakika', 'issue' => 'invalid'];
        }

        if (isset($veri['oncelik']) && !in_array($veri['oncelik'], self::ONCELIKLER, true)) {
            $hatalar[] = ['field' => 'oncelik', 'issue' => 'invalid'];
        }

        $tur = $veri['tur'] ?? null;
        if (in_array($tur, ['kesif', 'montaj'], true) && trim((string) ($veri['adres'] ?? '')) === '') {
            $hatalar[] = ['field' => 'adres', 'issue' => 'required_for_kesif_montaj'];
        }

        if (isset($veri['ekip_uyesi_id']) && $veri['ekip_uyesi_id'] !== null && $veri['ekip_uyesi_id'] !== ''
            && (!is_numeric($veri['ekip_uyesi_id']) || (int) $veri['ekip_uyesi_id'] <= 0)) {
            $hatalar[] = ['field' => 'ekip_uyesi_id', 'issue' => 'invalid'];
        }

        if (!$guncelleme) {
            $ad = trim((string) ($veri['ad_soyad'] ?? ''));
            if ($ad === '' || mb_strlen($ad) < 3) {
                $hatalar[] = ['field' => 'ad_soyad', 'issue' => 'required'];
            }

            if (!\Kamelya\Validators\TalepValidator::telefonGecerli($veri['telefon'] ?? null)) {
                $hatalar[] = ['field' => 'telefon', 'issue' => 'invalid_format'];
            }
        }

        return ['gecerli' => $hatalar === [], 'hatalar' => $hatalar];
    }
}
