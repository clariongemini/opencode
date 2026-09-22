<?php

declare(strict_types=1);

namespace Kamelya\Validators;

use Kamelya\Core\Diller;

/** POST /api/v1/iletisim girdisi — KVKK + honeypot + konu allowlist. */
final class IletisimValidator
{
    public const KONULAR = ['genel', 'teklif', 'sikayet', 'diger'];

    /** @param array<string, mixed> $veri */
    public static function dogrula(array $veri): array
    {
        $hatalar = [];

        if (isset($veri['web_sitesi']) && trim((string) $veri['web_sitesi']) !== '') {
            $hatalar[] = ['field' => 'web_sitesi', 'issue' => 'spam'];
        }

        $ad = trim((string) ($veri['ad_soyad'] ?? ''));
        if ($ad === '' || mb_strlen($ad) < 3 || mb_strlen($ad) > 120) {
            $hatalar[] = ['field' => 'ad_soyad', 'issue' => 'required_or_invalid'];
        }

        if (!isset($veri['eposta']) || filter_var($veri['eposta'], FILTER_VALIDATE_EMAIL) === false) {
            $hatalar[] = ['field' => 'eposta', 'issue' => 'invalid_format'];
        }

        if (!TalepValidator::telefonGecerli($veri['telefon'] ?? null)) {
            $hatalar[] = ['field' => 'telefon', 'issue' => 'invalid_format'];
        }

        if (isset($veri['konu']) && $veri['konu'] !== '' && !in_array($veri['konu'], self::KONULAR, true)) {
            $hatalar[] = ['field' => 'konu', 'issue' => 'invalid'];
        }

        $mesaj = trim((string) ($veri['mesaj'] ?? ''));
        if ($mesaj === '' || mb_strlen($mesaj) > 5000) {
            $hatalar[] = ['field' => 'mesaj', 'issue' => 'required_or_invalid'];
        }

        if (!Diller::gecerli($veri['dil_kodu'] ?? null)) {
            $hatalar[] = ['field' => 'dil_kodu', 'issue' => 'required_or_invalid'];
        }

        if (($veri['kvkk_onayi'] ?? false) !== true) {
            $hatalar[] = ['field' => 'kvkk_onayi', 'issue' => 'required'];
        }

        return ['gecerli' => $hatalar === [], 'hatalar' => $hatalar];
    }
}
