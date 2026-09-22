<?php

declare(strict_types=1);

namespace Kamelya\Validators;

use Kamelya\Core\Diller;

/** POST /api/v1/leads girdisi — KVKK guards + telefon biçimi. */
final class TalepValidator
{
    /** @param array<string, mixed> $veri */
    public static function dogrula(array $veri): array
    {
        $hatalar = [];

        $ad = trim((string) ($veri['ad_soyad'] ?? ''));
        if ($ad === '' || mb_strlen($ad) < 3 || mb_strlen($ad) > 120) {
            $hatalar[] = ['field' => 'ad_soyad', 'issue' => 'required_or_invalid'];
        }

        if (!self::telefonGecerli($veri['telefon'] ?? null)) {
            $hatalar[] = ['field' => 'telefon', 'issue' => 'invalid_format'];
        }

        if (isset($veri['eposta']) && $veri['eposta'] !== null && $veri['eposta'] !== ''
            && filter_var($veri['eposta'], FILTER_VALIDATE_EMAIL) === false) {
            $hatalar[] = ['field' => 'eposta', 'issue' => 'invalid_format'];
        }

        if (!Diller::gecerli($veri['dil_kodu'] ?? null)) {
            $hatalar[] = ['field' => 'dil_kodu', 'issue' => 'required_or_invalid'];
        }

        if (($veri['kvkk_onayi'] ?? false) !== true) {
            $hatalar[] = ['field' => 'kvkk_onayi', 'issue' => 'required'];
        }

        if (isset($veri['urun_id']) && $veri['urun_id'] !== null && $veri['urun_id'] !== ''
            && (!is_numeric($veri['urun_id']) || (int) $veri['urun_id'] <= 0)) {
            $hatalar[] = ['field' => 'urun_id', 'issue' => 'invalid'];
        }

        return ['gecerli' => $hatalar === [], 'hatalar' => $hatalar];
    }

    public static function telefonGecerli(mixed $telefon): bool
    {
        if (!is_string($telefon) && !is_numeric($telefon)) {
            return false;
        }

        $rakamlar = preg_replace('/\D/', '', (string) $telefon) ?? '';
        $uzunluk = strlen($rakamlar);

        return $uzunluk >= 7 && $uzunluk <= 20;
    }
}
