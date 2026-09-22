<?php

declare(strict_types=1);

namespace Kamelya\Validators\Admin;

/** Admin kapasite çarpanı girdisi — allowlist + sayısal bantlar. */
final class AdminKapasiteValidator
{
    /** @param array<string, mixed> $veri */
    public static function dogrula(array $veri, bool $guncelleme = false): array
    {
        $hatalar = [];

        if (!$guncelleme || isset($veri['kategori_id'])) {
            if (!isset($veri['kategori_id']) || !is_numeric($veri['kategori_id'])) {
                $hatalar[] = ['field' => 'kategori_id', 'issue' => 'required'];
            }
        }

        if (!$guncelleme || isset($veri['m2_per_kisi'])) {
            if (!isset($veri['m2_per_kisi']) || !is_numeric($veri['m2_per_kisi']) || (float) $veri['m2_per_kisi'] <= 0) {
                $hatalar[] = ['field' => 'm2_per_kisi', 'issue' => 'invalid'];
            }
        }

        if (!$guncelleme || isset($veri['gecerlilik_baslangici'])) {
            if (!isset($veri['gecerlilik_baslangici']) || !is_string($veri['gecerlilik_baslangici'])) {
                $hatalar[] = ['field' => 'gecerlilik_baslangici', 'issue' => 'required'];
            } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $veri['gecerlilik_baslangici'])) {
                $hatalar[] = ['field' => 'gecerlilik_baslangici', 'issue' => 'invalid_format'];
            }
        }

        if (isset($veri['aktif']) && !is_bool($veri['aktif']) && !is_numeric($veri['aktif'])) {
            $hatalar[] = ['field' => 'aktif', 'issue' => 'invalid'];
        }

        return ['gecerli' => $hatalar === [], 'hatalar' => $hatalar];
    }
}