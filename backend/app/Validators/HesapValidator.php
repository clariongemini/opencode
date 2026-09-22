<?php

declare(strict_types=1);

namespace Kamelya\Validators;

use Kamelya\Core\Diller;

/** POST /api/v1/calculate girdisi — allowlist + ölçü bantları. */
final class HesapValidator
{
    public const MIN_OLCU = 0.5;

    public const MAK_OLCU = 100.0;

    public const MIN_ALAN = 0.5;

    public const MAK_ALAN = 5000.0;

    /** @param array<string, mixed> $veri */
    public static function dogrula(array $veri): array
    {
        $hatalar = [];

        if (!Diller::gecerli($veri['lang'] ?? null)) {
            $hatalar[] = ['field' => 'lang', 'issue' => 'required_or_invalid'];
        }

        foreach (['material', 'model', 'usage'] as $alan) {
            if (!isset($veri[$alan]) || !is_string($veri[$alan]) || trim($veri[$alan]) === '' || strlen($veri[$alan]) > 60) {
                $hatalar[] = ['field' => $alan, 'issue' => 'required'];
            }
        }

        $alanVerildi = isset($veri['area_m2']) && $veri['area_m2'] !== null && $veri['area_m2'] !== '';
        if ($alanVerildi) {
            if (!is_numeric($veri['area_m2']) || (float) $veri['area_m2'] < self::MIN_ALAN || (float) $veri['area_m2'] > self::MAK_ALAN) {
                $hatalar[] = ['field' => 'area_m2', 'issue' => 'out_of_range'];
            }
        } else {
            foreach (['width', 'length'] as $alan) {
                if (!isset($veri[$alan]) || !is_numeric($veri[$alan])
                    || (float) $veri[$alan] < self::MIN_OLCU || (float) $veri[$alan] > self::MAK_OLCU) {
                    $hatalar[] = ['field' => $alan, 'issue' => 'out_of_range'];
                }
            }
        }

        return ['gecerli' => $hatalar === [], 'hatalar' => $hatalar];
    }
}
