<?php

declare(strict_types=1);

namespace Kamelya\Validators\Admin;

/** Admin galeri + ayar girdileri. */
final class AdminGaleriValidator
{
    /** @param array<string, mixed> $veri */
    public static function dogrula(array $veri, bool $guncelleme = false): array
    {
        $hatalar = [];

        if ((!$guncelleme || array_key_exists('baslik', $veri)) && trim((string) ($veri['baslik'] ?? '')) === '') {
            $hatalar[] = ['field' => 'baslik', 'issue' => 'required'];
        }

        if ((!$guncelleme || array_key_exists('dosya_yolu', $veri)) && trim((string) ($veri['dosya_yolu'] ?? '')) === '') {
            $hatalar[] = ['field' => 'dosya_yolu', 'issue' => 'required'];
        }

        return ['gecerli' => $hatalar === [], 'hatalar' => $hatalar];
    }
}
