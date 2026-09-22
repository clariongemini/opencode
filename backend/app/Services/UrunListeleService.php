<?php

declare(strict_types=1);

namespace Kamelya\Services;

use Kamelya\Repositories\FiyatCarpaniRepository;
use Kamelya\Repositories\KategoriRepository;
use Kamelya\Repositories\UrunFiyatiRepository;
use Kamelya\Repositories\UrunRepository;
use PDO;

/** Ürün listeleme/detay orkestrasyonu — çeviri birleştirme + fiyat ipucu. */
final class UrunListeleService
{
    /** @var array<string, string> */
    private const SIRALAMA_HARITASI = [
        'created_at' => 'u.created_at',
        '-created_at' => 'u.created_at DESC',
        'sira' => 'u.sira',
        '-sira' => 'u.sira DESC',
        'baslik' => 'c.baslik',
        '-baslik' => 'c.baslik DESC',
    ];

    public function __construct(
        private UrunRepository $urunler,
        private UrunFiyatiRepository $fiyatlar,
        private KategoriRepository $kategoriler,
        private FiyatCarpaniRepository $carpanlar
    ) {
    }

    /**
     * @param array<string, string> $filtreler
     * @return array{satirlar: array, toplam: int}
     */
    public function liste(
        string $dil,
        array $filtreler,
        int $sayfa,
        int $adet,
        string $siralama,
        ?string $arama = null
    ): array {
        $sirali = self::SIRALAMA_HARITASI[$siralama] ?? self::SIRALAMA_HARITASI['-created_at'];
        $parcalar = explode(' ', $sirali);
        $alan = $parcalar[0];
        $yon = ($parcalar[1] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';

        $sonuc = $this->urunler->tumunuGetir($dil, $filtreler, $alan, $yon, $sayfa, $adet, $arama);

        $fiyat = $this->fiyatlar->dilIleGetir($dil);
        $ipucu = $fiyat === null
            ? null
            : ['currency' => $fiyat['para_birimi'], 'base_m2' => (float) $fiyat['fiyat_m2'], 'note' => 'baslangic'];
        foreach ($sonuc['satirlar'] as &$satir) {
            $satir['price_hint'] = $ipucu;
        }

        unset($satir);

        return $sonuc;
    }

    /** @return array<string, mixed>|null */
    public function detay(int $id, string $dil): ?array
    {
        $urun = $this->urunler->ceviriIleGetir($id, $dil);
        if ($urun === null) {
            return null;
        }

        $urun['teknik_detaylar'] = [
            'genislik' => $urun['genislik_varsayilan'] ?? null,
            'derinlik' => $urun['derinlik_varsayilan'] ?? null,
            'alan' => $urun['alan_varsayilan'] ?? null,
            'cati_tipi' => $urun['cati_tipi'] ?? null,
            'cati_tipi_aciklama' => $urun['cati_tipi_aciklama'] ?? null,
            'korkuluk_malzeme' => $urun['korkuluk_malzeme'] ?? null,
            'korkuluk_yukseklik_cm' => $urun['korkuluk_yukseklik_cm'] ?? null,
            'korkuluk_aciklama' => $urun['korkuluk_aciklama'] ?? null,
        ];

        return $urun;
    }
}
