<?php

declare(strict_types=1);

namespace Kamelya\Services;

use Kamelya\Core\Diller;
use Kamelya\Core\Hata;
use Kamelya\Repositories\SssRepository;
use Kamelya\Services\Admin\AdminSssService;

/** Public SSS — dil doğrulama + kapsam filtresi + meta. */
final class SssService
{
    public function __construct(private SssRepository $sorular)
    {
    }

    /**
     * @return array{satirlar: array<int, array<string, mixed>>, meta: array<string, mixed>}
     *
     * @throws Hata
     */
    public function liste(?string $dil, ?string $kapsam): array
    {
        if (!Diller::gecerli($dil)) {
            throw new Hata(
                'UNSUPPORTED_LANG',
                'Desteklenen bir lang parametresi zorunludur.',
                [['field' => 'lang', 'issue' => 'required_or_invalid']],
                422
            );
        }

        $kapsamTemiz = null;
        if ($kapsam !== null && $kapsam !== '') {
            if (!in_array($kapsam, AdminSssService::KAPSAMLAR, true)) {
                throw new Hata(
                    'VALIDATION_ERROR',
                    'Geçersiz kapsam değeri.',
                    [['field' => 'kapsam', 'issue' => 'invalid']],
                    422
                );
            }
            $kapsamTemiz = $kapsam;
        }

        $satirlar = $this->sorular->liste((string) $dil, $kapsamTemiz);

        return [
            'satirlar' => $satirlar,
            'meta' => [
                'total' => count($satirlar),
                'dil' => $dil,
                'kapsam' => $kapsamTemiz,
            ],
        ];
    }
}
