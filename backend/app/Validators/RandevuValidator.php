<?php

declare(strict_types=1);

namespace Kamelya\Validators;

/** POST /api/v1/appointments girdisi — biçim kontrolü (iş kuralları Service'te). */
final class RandevuValidator
{
    /** @param array<string, mixed> $veri */
    public static function dogrula(array $veri): array
    {
        $hatalar = [];

        $ad = trim((string) ($veri['ad_soyad'] ?? ''));
        if ($ad === '' || mb_strlen($ad) < 3 || mb_strlen($ad) > 120) {
            $hatalar[] = ['field' => 'ad_soyad', 'issue' => 'required_or_invalid'];
        }

        if (!TalepValidator::telefonGecerli($veri['telefon'] ?? null)) {
            $hatalar[] = ['field' => 'telefon', 'issue' => 'invalid_format'];
        }

        if (isset($veri['eposta']) && $veri['eposta'] !== null && $veri['eposta'] !== ''
            && filter_var($veri['eposta'], FILTER_VALIDATE_EMAIL) === false) {
            $hatalar[] = ['field' => 'eposta', 'issue' => 'invalid_format'];
        }

        $tarih = \DateTimeImmutable::createFromFormat('Y-m-d H:i', (string) ($veri['randevu_tarihi'] ?? ''));
        if ($tarih === false) {
            $hatalar[] = ['field' => 'randevu_tarihi', 'issue' => 'invalid_format'];
        }

        if (isset($veri['talep_id']) && $veri['talep_id'] !== null && $veri['talep_id'] !== ''
            && (!is_numeric($veri['talep_id']) || (int) $veri['talep_id'] <= 0)) {
            $hatalar[] = ['field' => 'talep_id', 'issue' => 'invalid'];
        }

        return ['gecerli' => $hatalar === [], 'hatalar' => $hatalar];
    }
}
