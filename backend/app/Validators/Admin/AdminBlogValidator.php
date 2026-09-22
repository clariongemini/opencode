<?php

declare(strict_types=1);

namespace Kamelya\Validators\Admin;

use Kamelya\Core\Diller;
use Kamelya\Services\Admin\AdminBlogService;

/** Admin blog yazma girdisi — tr başlık + içerik zorunlu. */
final class AdminBlogValidator
{
    /** @param array<string, mixed> $veri */
    public static function dogrula(array $veri, bool $guncelleme = false): array
    {
        $hatalar = [];

        if (isset($veri['yayin_durumu']) && !in_array($veri['yayin_durumu'], AdminBlogService::DURUMLAR, true)) {
            $hatalar[] = ['field' => 'yayin_durumu', 'issue' => 'invalid'];
        }

        if (isset($veri['ceviriler']) && !is_array($veri['ceviriler'])) {
            $hatalar[] = ['field' => 'ceviriler', 'issue' => 'invalid'];
        } elseif (!$guncelleme) {
            $tr = is_array($veri['ceviriler'] ?? null) ? ($veri['ceviriler']['tr'] ?? null) : null;
            if (!is_array($tr) || trim((string) ($tr['baslik'] ?? '')) === '' || trim((string) ($tr['icerik'] ?? '')) === '') {
                $hatalar[] = ['field' => 'ceviriler.tr', 'issue' => 'baslik_ve_icerik_zorunlu'];
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
