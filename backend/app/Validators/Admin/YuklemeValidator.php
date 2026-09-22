<?php

declare(strict_types=1);

namespace Kamelya\Validators\Admin;

/** Yükleme girdisi — içerik kuralı YuklemeService'te (MIME/boyut). */
final class YuklemeValidator
{
    /** @param array<string, mixed> $veri */
    public static function dogrula(array $dosya, array $veri): array
    {
        $hatalar = [];

        if (!isset($dosya['tmp_name']) || ($dosya['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $hatalar[] = ['field' => 'file', 'issue' => 'required'];
        }

        if (!in_array($veri['hedef'] ?? null, ['urun', 'blog', 'galeri', 'atolye', 'donusum'], true)) {
            $hatalar[] = ['field' => 'hedef', 'issue' => 'invalid'];
        }

        if (!isset($veri['hedef_id']) || !is_numeric($veri['hedef_id']) || (int) $veri['hedef_id'] <= 0) {
            // Galeri/dönüşüm hedeflerinde hedef_id 0 olabilir (saf dosya yükleme).
            if (!in_array($veri['hedef'] ?? '', ['galeri', 'donusum'], true)) {
                $hatalar[] = ['field' => 'hedef_id', 'issue' => 'required'];
            }
        }

        return ['gecerli' => $hatalar === [], 'hatalar' => $hatalar];
    }
}
