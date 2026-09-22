<?php

declare(strict_types=1);

namespace Kamelya\Validators\Admin;

use Kamelya\Core\Diller;

/** Admin ürün yazma girdisi — tr başlık zorunlu, slug otomatik (boş geçilebilir). */
final class AdminUrunValidator
{
    /** @param array<string, mixed> $veri */
    public static function dogrula(array $veri, bool $guncelleme = false): array
    {
        $hatalar = [];

        if (!$guncelleme || array_key_exists('urun_kodu', $veri)) {
            $kod = trim((string) ($veri['urun_kodu'] ?? ''));
            if ($kod === '' || strlen($kod) > 60) {
                $hatalar[] = ['field' => 'urun_kodu', 'issue' => 'required_or_invalid'];
            }
        }

        foreach (['model_kategori_id', 'malzeme_kategori_id'] as $alan) {
            if (!$guncelleme || array_key_exists($alan, $veri)) {
                if (!isset($veri[$alan]) || !is_numeric($veri[$alan]) || (int) $veri[$alan] <= 0) {
                    $hatalar[] = ['field' => $alan, 'issue' => 'required'];
                }
            }
        }

        $catiTipleri = ['duz', 'egimli', 'kubbe', 'biyoklimatik', 'ahsap_kiremit'];
        if (isset($veri['cati_tipi']) && $veri['cati_tipi'] !== null && $veri['cati_tipi'] !== ''
            && !in_array($veri['cati_tipi'], $catiTipleri, true)) {
            $hatalar[] = ['field' => 'cati_tipi', 'issue' => 'invalid'];
        }

        $korkuluklar = ['ahsap', 'aluminyum', 'kompozit', 'ferforje', 'yok'];
        if (isset($veri['korkuluk_malzeme']) && $veri['korkuluk_malzeme'] !== null && $veri['korkuluk_malzeme'] !== ''
            && !in_array($veri['korkuluk_malzeme'], $korkuluklar, true)) {
            $hatalar[] = ['field' => 'korkuluk_malzeme', 'issue' => 'invalid'];
        }

        if (isset($veri['korkuluk_yukseklik_cm']) && $veri['korkuluk_yukseklik_cm'] !== null && $veri['korkuluk_yukseklik_cm'] !== ''
            && (!is_numeric($veri['korkuluk_yukseklik_cm']) || (int) $veri['korkuluk_yukseklik_cm'] < 0 || (int) $veri['korkuluk_yukseklik_cm'] > 300)) {
            $hatalar[] = ['field' => 'korkuluk_yukseklik_cm', 'issue' => 'out_of_range'];
        }

        if (isset($veri['ceviriler']) && !is_array($veri['ceviriler'])) {
            $hatalar[] = ['field' => 'ceviriler', 'issue' => 'invalid'];
        } elseif (!$guncelleme) {
            $tr = is_array($veri['ceviriler'] ?? null) ? ($veri['ceviriler']['tr'] ?? null) : null;
            if (!is_array($tr) || trim((string) ($tr['baslik'] ?? '')) === '') {
                $hatalar[] = ['field' => 'ceviriler.tr.baslik', 'issue' => 'required'];
            }
        }

        if (isset($veri['ceviriler']) && is_array($veri['ceviriler'])) {
            foreach ($veri['ceviriler'] as $dil => $ceviri) {
                if (!Diller::gecerli($dil)) {
                    $hatalar[] = ['field' => 'ceviriler', 'issue' => 'unsupported_lang'];
                    break;
                }

                if (is_array($ceviri) && isset($ceviri['slug']) && strlen((string) $ceviri['slug']) > 240) {
                    $hatalar[] = ['field' => "ceviriler.{$dil}.slug", 'issue' => 'too_long'];
                }
            }
        }

        return ['gecerli' => $hatalar === [], 'hatalar' => $hatalar];
    }
}
