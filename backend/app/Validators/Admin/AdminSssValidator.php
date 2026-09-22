<?php

declare(strict_types=1);

namespace Kamelya\Validators\Admin;

use Kamelya\Core\Diller;
use Kamelya\Services\Admin\AdminSssService;

/** Admin SSS yazma girdisi — tr soru + cevap zorunlu. */
final class AdminSssValidator
{
    /** @param array<string, mixed> $veri */
    public static function dogrula(array $veri, bool $guncelleme = false): array
    {
        $hatalar = [];

        if (isset($veri['sayfa_kapsami']) && $veri['sayfa_kapsami'] !== null && $veri['sayfa_kapsami'] !== ''
            && !in_array($veri['sayfa_kapsami'], AdminSssService::KAPSAMLAR, true)) {
            $hatalar[] = ['field' => 'sayfa_kapsami', 'issue' => 'invalid'];
        }

        if (isset($veri['sira']) && !is_numeric($veri['sira'])) {
            $hatalar[] = ['field' => 'sira', 'issue' => 'invalid'];
        }

        if (isset($veri['ceviriler']) && !is_array($veri['ceviriler'])) {
            $hatalar[] = ['field' => 'ceviriler', 'issue' => 'invalid'];
        } elseif (!$guncelleme) {
            $tr = is_array($veri['ceviriler'] ?? null) ? ($veri['ceviriler']['tr'] ?? null) : null;
            if (!is_array($tr) || trim((string) ($tr['soru'] ?? '')) === '' || trim((string) ($tr['cevap'] ?? '')) === '') {
                $hatalar[] = ['field' => 'ceviriler.tr', 'issue' => 'soru_ve_cevap_zorunlu'];
            }
        }

        if (isset($veri['ceviriler']) && is_array($veri['ceviriler'])) {
            foreach ($veri['ceviriler'] as $dil => $ceviri) {
                if (!Diller::gecerli($dil)) {
                    $hatalar[] = ['field' => 'ceviriler', 'issue' => 'unsupported_lang'];
                    break;
                }
            }
        }

        return ['gecerli' => $hatalar === [], 'hatalar' => $hatalar];
    }
}
