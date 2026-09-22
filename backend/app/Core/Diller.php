<?php

declare(strict_types=1);

namespace Kamelya\Core;

/** Desteklenen içerik dilleri — tek kaynak (F1.2 §3: lang zorunluluğu). */
final class Diller
{
    public const TUMU = ['tr', 'en', 'de', 'fr', 'it', 'ar'];

    public static function gecerli(mixed $dil): bool
    {
        return is_string($dil) && in_array($dil, self::TUMU, true);
    }
}
