<?php

declare(strict_types=1);

namespace Kamelya\Validators;

use Kamelya\Core\Diller;

/** POST /api/v1/yorumlar girdisi — KVKK + puan bandı + honeypot. */
final class YorumValidator
{
    /** @param array<string, mixed> $veri */
    public static function dogrula(array $veri): array
    {
        $hatalar = [];

        // Honeypot: botlar doldurur, insanlar göremez.
        if (isset($veri['web_sitesi']) && trim((string) $veri['web_sitesi']) !== '') {
            $hatalar[] = ['field' => 'web_sitesi', 'issue' => 'spam'];
        }

        $ad = trim((string) ($veri['musteri_adi'] ?? ''));
        if ($ad === '' || mb_strlen($ad) < 2 || mb_strlen($ad) > 120) {
            $hatalar[] = ['field' => 'musteri_adi', 'issue' => 'required_or_invalid'];
        }

        if (!isset($veri['eposta']) || filter_var($veri['eposta'], FILTER_VALIDATE_EMAIL) === false) {
            $hatalar[] = ['field' => 'eposta', 'issue' => 'invalid_format'];
        }

        if (isset($veri['telefon']) && $veri['telefon'] !== null && $veri['telefon'] !== ''
            && !TalepValidator::telefonGecerli($veri['telefon'])) {
            $hatalar[] = ['field' => 'telefon', 'issue' => 'invalid_format'];
        }

        if (!isset($veri['puan']) || !is_numeric($veri['puan']) || (int) $veri['puan'] < 1 || (int) $veri['puan'] > 5) {
            $hatalar[] = ['field' => 'puan', 'issue' => 'out_of_range'];
        }

        $yorum = trim((string) ($veri['yorum'] ?? ''));
        if ($yorum === '' || mb_strlen($yorum) > 5000) {
            $hatalar[] = ['field' => 'yorum', 'issue' => 'required_or_invalid'];
        }

        if (isset($veri['baslik']) && mb_strlen((string) $veri['baslik']) > 190) {
            $hatalar[] = ['field' => 'baslik', 'issue' => 'too_long'];
        }

        if (isset($veri['urun_id']) && $veri['urun_id'] !== null && $veri['urun_id'] !== ''
            && (!is_numeric($veri['urun_id']) || (int) $veri['urun_id'] <= 0)) {
            $hatalar[] = ['field' => 'urun_id', 'issue' => 'invalid'];
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
