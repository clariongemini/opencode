<?php

declare(strict_types=1);

namespace Kamelya\Services;

use Kamelya\Core\Hata;
use Kamelya\Repositories\UrunFiyatiRepository;
use Kamelya\Repositories\UrunRepository;

/** Ürün karşılaştırma — 2-4 ürün, fark işaretleme. Salt-okunur. */
final class KarsilastirmaService
{
    /** @var array<int, string> */
    private const ALANLAR = ['baslik', 'fiyat', 'malzeme', 'model', 'kullanim', 'olcu', 'garanti'];

    public function __construct(
        private UrunRepository $urunler,
        private UrunFiyatiRepository $fiyatlar
    ) {
    }

    /** @param array<int, int> $idListesi */
    public function karsilastir(array $idListesi, string $dil): array
    {
        if (count($idListesi) < 2 || count($idListesi) > 4) {
            throw new Hata(
                'VALIDATION_ERROR',
                'En az 2, en fazla 4 ürün seçilmeli.',
                [['field' => 'ids', 'issue' => 'out_of_range']],
                422
            );
        }

        $urunler = [];
        $temel = $this->fiyatlar->dilIleGetir($dil);
        $fiyatIpucu = $temel === null ? null : ((float) $temel['fiyat_m2'] . ' ' . $temel['para_birimi'] . '/m²');
        foreach (array_values(array_unique($idListesi)) as $id) {
            $detay = $this->urunler->ceviriIleGetir($id, $dil);
            if ($detay === null) {
                throw new Hata('PRODUCT_NOT_FOUND', "Ürün bulunamadı: {$id}.", [['field' => 'ids', 'issue' => 'not_found']], 404);
            }

            $urunler[] = [
                'id' => (int) $detay['id'],
                'baslik' => $detay['baslik'],
                'fiyat' => $fiyatIpucu,
                'malzeme' => $detay['malzeme'] ?? null,
                'model' => $detay['model'] ?? null,
                'kullanim' => $detay['kullanim'] ?? null,
                'olcu' => trim(($detay['genislik_varsayilan'] ?? '') . 'x' . ($detay['derinlik_varsayilan'] ?? ''), 'x') ?: null,
                'garanti' => 'CE / TÜV / ISO',
                'slug' => $detay['slug'],
            ];
        }

        if (count($urunler) < 2) {
            throw new Hata('VALIDATION_ERROR', 'En az 2 farklı ürün gerekli.', [['field' => 'ids', 'issue' => 'duplicate']], 422);
        }

        $farkli = [];
        foreach (self::ALANLAR as $alan) {
            $degerler = array_unique(array_map(fn($u) => (string) ($u[$alan] ?? ''), $urunler));
            $farkli[$alan] = count($degerler) > 1;
        }

        return ['urunler' => $urunler, 'farkli_alanlar' => array_keys(array_filter($farkli))];
    }
}
