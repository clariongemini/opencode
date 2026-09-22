<?php

declare(strict_types=1);

namespace Kamelya\Controllers\Api\V1;

use Kamelya\Core\Database;
use Kamelya\Core\Diller;
use Kamelya\Core\Hata;
use Kamelya\Core\Request;
use Kamelya\Core\Response;
use Kamelya\Repositories\FiyatCarpaniRepository;
use Kamelya\Repositories\KategoriRepository;
use Kamelya\Repositories\UrunFiyatiRepository;

/** GET /api/v1/pricing — F1.2 §3.6 (temel + çarpanlar, lang zorunlu). */
final class FiyatController
{
    private UrunFiyatiRepository $fiyatlar;

    private KategoriRepository $kategoriler;

    private FiyatCarpaniRepository $carpanlar;

    public function __construct()
    {
        $pdo = Database::baglanti();
        $this->fiyatlar = new UrunFiyatiRepository($pdo);
        $this->kategoriler = new KategoriRepository($pdo);
        $this->carpanlar = new FiyatCarpaniRepository($pdo);
    }

    /** @param array<string, string> $rota */
    public function liste(Request $istek, array $rota = []): void
    {
        try {
            $dil = $istek->sorgu['lang'] ?? null;
            if (!Diller::gecerli($dil)) {
                throw new Hata(
                    'UNSUPPORTED_LANG',
                    'Desteklenen bir lang parametresi zorunludur.',
                    [['field' => 'lang', 'issue' => 'required_or_invalid']],
                    422
                );
            }

            $temel = $this->fiyatlar->dilIleGetir($dil);
            if ($temel === null) {
                throw new Hata(
                    'PRICE_NOT_DEFINED',
                    'Bu dil için fiyat tanımsız.',
                    [['field' => 'lang', 'issue' => 'undefined']],
                    404
                );
            }

            $gruplar = [];
            $tanimsizlar = [];
            foreach (['malzeme', 'model', 'kullanim_amaci'] as $tur) {
                $gruplar[$tur] = [];
                foreach ($this->kategoriler->turIleGetir($tur) as $kategori) {
                    $carpan = $this->carpanlar->kategoriIdIleGetir((int) $kategori['id']);
                    if ($carpan === null) {
                        $tanimsizlar[] = $kategori['kod'];
                        continue;
                    }

                    $gruplar[$tur][$kategori['kod']] = (float) $carpan['carpan'];
                }
            }

            Response::basari([
                'lang' => $dil,
                'currency' => $temel['para_birimi'],
                'unit' => 'm2',
                'base_price' => (float) $temel['fiyat_m2'],
                'source' => 'admin_fixed',
                'effective_from' => $temel['gecerlilik_baslangici'],
                'display' => [
                    'decimal_separator' => in_array($dil, ['de', 'fr', 'it', 'tr'], true) ? ',' : '.',
                    'symbol_position' => 'suffix',
                ],
                'multipliers' => $gruplar,
                'undefined_multipliers' => $tanimsizlar,
            ]);
        } catch (Hata $hata) {
            Response::hata($hata->hataKodu, $hata->getMessage(), $hata->ayrintilar, $hata->httpDurum);
        }
    }
}
