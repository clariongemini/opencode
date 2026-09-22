<?php

declare(strict_types=1);

namespace Kamelya\Validators\Admin;

use Kamelya\Core\Diller;
use Kamelya\Services\Admin\AdminKategoriService;

/** Admin kategori yazma girdisi — kod deseni filtrelerle uyumlu olmalı. */
final class AdminKategoriValidator
{
    /** @param array<string, mixed> $veri */
    public static function dogrula(array $veri, bool $guncelleme = false): array
    {
        $hatalar = [];

        if (!$guncelleme || array_key_exists('tur', $veri)) {
            if (!in_array($veri['tur'] ?? null, AdminKategoriService::TURLER, true)) {
                $hatalar[] = ['field' => 'tur', 'issue' => 'invalid'];
            }
        }

        if (!$guncelleme || array_key_exists('kod', $veri)) {
            $kod = (string) ($veri['kod'] ?? '');
            if ($kod === '' || strlen($kod) > 60 || preg_match('/^[a-z0-9_]+$/', $kod) !== 1) {
                $hatalar[] = ['field' => 'kod', 'issue' => 'required_or_invalid'];
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
