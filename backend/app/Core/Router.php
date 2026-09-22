<?php

declare(strict_types=1);

namespace Kamelya\Core;

/** Basit yol eşleştirici — F2 kapsamı: statik yollar; parametreli yollar F3'te. */
final class Router
{
    /** @var array<string, array<string, array{hedef: array{0: class-string, 1: string}, ara: array}>> */
    private array $rotalar = [];

    /** @param class-string $denetleyici */
    public function ekle(string $metot, string $yol, string $denetleyici, string $eylem, array $araKatmanlar = []): void
    {
        $this->rotalar[strtoupper($metot)][$yol] = ['hedef' => [$denetleyici, $eylem], 'ara' => $araKatmanlar];
    }

    /** Önek + ortak ara katmanla grup tanımı (örn. /admin + auth). */
    public function grup(string $onek, array $araKatmanlar, callable $tanim): void
    {
        $gecici = new self();
        $tanim($gecici);
        foreach ($gecici->rotalar as $metot => $rotalar) {
            foreach ($rotalar as $yol => $kayit) {
                $this->rotalar[$metot][$onek . $yol] = [
                    'hedef' => $kayit['hedef'],
                    'ara' => array_merge($araKatmanlar, $kayit['ara']),
                ];
            }
        }
    }

    public function dagit(Request $istek): void
    {
        $yontemRotalari = $this->rotalar[$istek->metot] ?? [];
        foreach ($yontemRotalari as $sablon => $kayit) {
            $parametreler = $this->eslestir($sablon, $istek->yol);
            if ($parametreler !== null) {
                $this->zincir($kayit['hedef'], $kayit['ara'], $istek, $parametreler);

                return;
            }
        }

        foreach ($this->rotalar as $metot => $rotalar) {
            if ($metot === $istek->metot) {
                continue;
            }

            foreach ($rotalar as $sablon => $hedef) {
                if ($this->eslestir($sablon, $istek->yol) !== null) {
                    Response::hata('METHOD_NOT_ALLOWED', 'Bu kaynakta desteklenmeyen metot.', [], 405);

                    return;
                }
            }
        }

        Response::hata('NOT_FOUND', 'Kaynak bulunamadı.', [], 404);
    }

    /**
     * @param array{0: class-string, 1: string} $hedef
     * @param array<int, array{0: class-string, 1?: array}> $araKatmanlar
     * @param array<string, string> $parametreler
     */
    private function zincir(array $hedef, array $araKatmanlar, Request $istek, array $parametreler): void
    {
        $eylem = function (Request $i) use ($hedef, $parametreler): void {
            [$sinif, $yontem] = $hedef;
            (new $sinif())->$yontem($i, $parametreler);
        };

        foreach (array_reverse($araKatmanlar) as $tanim) {
            $onceki = $eylem;
            $eylem = function (Request $i) use ($tanim, $onceki): void {
                [$sinif, $argumanlar] = [$tanim[0], $tanim[1] ?? []];
                (new $sinif(...$argumanlar))->isle($i, $onceki);
            };
        }

        $eylem($istek);
    }

    /** @return array<string, string>|null */
    private function eslestir(string $sablon, string $yol): ?array
    {
        $desen = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $sablon);
        if (!is_string($desen) || preg_match('#^' . $desen . '$#', $yol, $eslesme) !== 1) {
            return null;
        }

        $parametreler = [];
        foreach ($eslesme as $anahtar => $deger) {
            if (is_string($anahtar)) {
                $parametreler[$anahtar] = $deger;
            }
        }

        return $parametreler;
    }
}
