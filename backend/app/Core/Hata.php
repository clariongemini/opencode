<?php

declare(strict_types=1);

namespace Kamelya\Core;

/**
 * Alan hatası: Service/Validator katmanı iş kuralı ihlalini bu istisnayla taşır.
 * Controller hata kodunu + HTTP durumunu yanıt sözleşmesine çevirir.
 */
final class Hata extends \RuntimeException
{
    /** @param array<int, array<string, string>> $ayrintilar */
    public function __construct(
        public readonly string $hataKodu,
        string $mesaj,
        public readonly array $ayrintilar = [],
        public readonly int $httpDurum = 400
    ) {
        parent::__construct($mesaj);
    }
}
