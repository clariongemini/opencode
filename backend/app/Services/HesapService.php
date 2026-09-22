<?php

declare(strict_types=1);

namespace Kamelya\Services;

use Kamelya\Core\Hata;
use Kamelya\Repositories\FiyatCarpaniRepository;
use Kamelya\Repositories\KapasiteCarpaniRepository;
use Kamelya\Repositories\KategoriRepository;
use Kamelya\Repositories\UrunFiyatiRepository;

/**
 * ÇEKİRDEK fiyat motoru — tek formül:
 * nihai = alan_m2 × temel_fiyat(lang) × malzeme × model × kullanim.
 * Canlı kur yok; tanımsız çarpan ×1.0 nötr varsayılanla çalışır (raporlanır).
 */
final class HesapService
{
    public function __construct(
        private UrunFiyatiRepository $fiyatlar,
        private KategoriRepository $kategoriler,
        private FiyatCarpaniRepository $carpanlar,
        private KapasiteCarpaniRepository $kapasiteCarpanlari
    ) {
    }

    /** @param array<string, mixed> $veri */
    public function hesapla(array $veri): array
    {
        $dil = (string) ($veri['lang'] ?? '');
        $alan = isset($veri['area_m2']) && $veri['area_m2'] !== null && $veri['area_m2'] !== ''
            ? (float) $veri['area_m2']
            : (float) $veri['width'] * (float) $veri['length'];

        $fiyat = $this->fiyatlar->dilIleGetir($dil);
        if ($fiyat === null) {
            throw new Hata(
                'PRICE_NOT_DEFINED',
                'Bu dil için temel fiyat tanımsız.',
                [['field' => 'lang', 'issue' => 'undefined']],
                404
            );
        }

        $eslesme = ['material' => 'malzeme', 'model' => 'model', 'usage' => 'kullanim_amaci'];
        $carpanDegerleri = [];
        $varsayilanlar = [];
        foreach ($eslesme as $alanAdi => $tur) {
            $kod = (string) ($veri[$alanAdi] ?? '');
            $kategori = $this->kategoriler->kodIleGetir($tur, $kod);
            if ($kategori === null) {
                throw new Hata(
                    'VALIDATION_ERROR',
                    'Bilinmeyen kategori kodu.',
                    [['field' => $alanAdi, 'issue' => 'unknown_code']],
                    422
                );
            }

            $carpan = $this->carpanlar->kategoriIdIleGetir((int) $kategori['id']);
            if ($carpan === null) {
                $carpanDegerleri[$alanAdi] = 1.0;
                $varsayilanlar[] = $kod;
            } else {
                $carpanDegerleri[$alanAdi] = (float) $carpan['carpan'];
            }
        }

        $nihai = round(
            $alan * (float) $fiyat['fiyat_m2']
            * $carpanDegerleri['material'] * $carpanDegerleri['model'] * $carpanDegerleri['usage'],
            2
        );

        // Kapasite hesaplaması — kullanım amacı (usage) bazlı
        $usageKod = (string) ($veri['usage'] ?? '');
        $kapasite = $this->kapasiteCarpanlari->turKodIleGetir('kullanim_amaci', $usageKod);
        if ($kapasite === null) {
            throw new Hata(
                'CAPACITY_NOT_DEFINED',
                'Bu kullanım amacı için kişi başına m² katsayısı tanımsız.',
                [['field' => 'usage', 'issue' => 'capacity_undefined']],
                404
            );
        }
        $m2PerKisi = (float) $kapasite['m2_per_kisi'];
        $insanSayisi = (int) floor($alan / $m2PerKisi);
        $insanSayisi = max(1, $insanSayisi);

        return [
            'area_m2' => $alan,
            'base_price' => (float) $fiyat['fiyat_m2'],
            'currency' => $fiyat['para_birimi'],
            'multipliers' => [
                'material' => $carpanDegerleri['material'],
                'model' => $carpanDegerleri['model'],
                'usage' => $carpanDegerleri['usage'],
            ],
            'final_price' => $nihai,
            'source' => 'admin_fixed',
            'lang' => $dil,
            'defaults_applied' => $varsayilanlar,
            'capacity' => [
                'people' => $insanSayisi,
                'm2_per_person' => $m2PerKisi,
                'calculation' => "{$alan} / {$m2PerKisi} = " . round($alan / $m2PerKisi, 2) . " → floor({$insanSayisi})",
                'usage_category' => $usageKod,
                'note' => $kapasite['aciklama'] ?? null,
            ],
        ];
    }
}