<?php

declare(strict_types=1);

namespace Kamelya\Services\Admin;

use Kamelya\Core\Hata;
use Kamelya\Repositories\SeoAnalitikRepository;
use Kamelya\Services\AuditLogService;
use Kamelya\Services\Google\GscService;
use PDO;

/**
 * SEO Dashboard orkestrasyonu — önce cache tablosu (hızlı + kotasız),
 * senkron isteğinde GSC canlı çekiş + UPSERT.
 */
final class SeoDashboardService
{
    /** F1.1 şehir haritası — şehir fırsatı eşleşmesinde kullanılır. */
    public const SEHIRLER = ['istanbul', 'ankara', 'izmir', 'cerkezkoy', 'bursa', 'antalya'];

    public function __construct(
        private PDO $baglanti,
        private SeoAnalitikRepository $depo,
        private GscService $gsc,
        private AuditLogService $denetim
    ) {
    }

    /** @return array<string, mixed> */
    public function ozet(string $baslangic, string $bitis): array
    {
        $ozet = $this->depo->ozet($baslangic, $bitis);
        $ozet['son_guncelleme'] = $this->depo->sonGuncellemeTarihi('sorgu');
        $ozet['gsc_bagli'] = $this->gsc->yapilandirildiMi();

        return $ozet;
    }

    /** @return array<int, array<string, mixed>> */
    public function sorgular(string $baslangic, string $bitis, int $limit): array
    {
        return array_slice($this->depo->tarihAraligiIleGetir('sorgu', $baslangic, $bitis, $limit), 0, $limit);
    }

    /** @return array<int, array<string, mixed>> */
    public function sayfalar(string $baslangic, string $bitis, int $limit): array
    {
        return array_slice($this->depo->tarihAraligiIleGetir('sayfa', $baslangic, $bitis, $limit), 0, $limit);
    }

    /** @return array<int, array<string, mixed>> */
    public function ulkeDagilimi(string $baslangic, string $bitis): array
    {
        return $this->depo->tarihAraligiIleGetir('ulke', $baslangic, $bitis, 50);
    }

    /** @return array<int, array<string, mixed>> */
    public function cihazDagilimi(string $baslangic, string $bitis): array
    {
        return $this->depo->tarihAraligiIleGetir('cihaz', $baslangic, $bitis, 10);
    }

    /** @return array<int, array<string, mixed>> */
    public function trend(string $baslangic, string $bitis): array
    {
        $satirlar = $this->depo->tarihAraligiIleGetir('trend', $baslangic, $bitis, 100);
        usort($satirlar, fn($a, $b) => strcmp((string) $a['tarih'], (string) $b['tarih']));

        return $satirlar;
    }

    /** @return array<string, mixed> */
    public function senkronize(array $kullanici, string $ip, string $ajan, int $gun = 30): array
    {
        $this->gsc->yapilandirmaKontrol();

        $bitis = date('Y-m-d', strtotime('-3 days'));
        $baslangic = date('Y-m-d', strtotime("-{$gun} days", strtotime($bitis)));

        $toplam = 0;
        foreach ([
            'sorgu' => $this->gsc->sorgularGetir($baslangic, $bitis),
            'sayfa' => $this->gsc->sayfalarGetir($baslangic, $bitis),
            'ulke' => $this->gsc->ulkeDagilimi($baslangic, $bitis),
            'cihaz' => $this->gsc->cihazDagilimi($baslangic, $bitis),
        ] as $tip => $satirlar) {
            foreach ($satirlar as $satir) {
                $this->depo->kaydet($tip, $bitis, $satir['boyut'], $satir['tiklama'], $satir['gosterim'], $satir['ctr'], $satir['pozisyon']);
                $toplam++;
            }
        }

        $this->denetim->kaydet((int) $kullanici['id'], 'gsc_senkron', 'seo', null, null, ['satir' => $toplam], $ip, $ajan);

        return ['satir' => $toplam, 'baslangic' => $baslangic, 'bitis' => $bitis];
    }

    /**
     * Öneri motoru: yüksek gösterim + düşük CTR fırsatları, düşen sayfalar,
     * GSC sorgularında geçen F1.1 şehirleri.
     *
     * @return array<string, mixed>
     */
    public function kelimeFirsatlari(string $baslangic, string $bitis): array
    {
        $sorgular = $this->depo->tarihAraligiIleGetir('sorgu', $baslangic, $bitis, 500);

        $firsatlar = [];
        foreach ($sorgular as $satir) {
            if ((int) $satir['gosterim'] >= 100 && (float) $satir['ctr'] < 0.03) {
                $firsatlar[] = [
                    'sorgu' => $satir['boyut'],
                    'gosterim' => (int) $satir['gosterim'],
                    'ctr' => (float) $satir['ctr'],
                    'pozisyon' => (float) $satir['ortalama_pozisyon'],
                    'oneri' => 'Başlık ve meta açıklama güçlendirilmeli (yüksek görünürlük, düşük tıklama).',
                ];
            }
        }

        usort($firsatlar, fn($a, $b) => $b['gosterim'] <=> $a['gosterim']);

        // Düşen sayfalar: aralığı ikiye böl, ikinci yarıda tıklaması azalanlar.
        $sayfalar = $this->depo->tarihAraligiIleGetir('sayfa', $baslangic, $bitis, 500);
        $dusenler = [];
        $gruplu = [];
        foreach ($sayfalar as $satir) {
            $gruplu[(string) $satir['boyut']][] = $satir;
        }

        foreach ($gruplu as $url => $satirlar) {
            if (count($satirlar) < 2) {
                continue;
            }

            usort($satirlar, fn($a, $b) => strcmp((string) $a['tarih'], (string) $b['tarih']));
            $orta = (int) ceil(count($satirlar) / 2);
            $once = array_sum(array_map(fn($s) => (int) $s['tiklama'], array_slice($satirlar, 0, $orta)));
            $sonra = array_sum(array_map(fn($s) => (int) $s['tiklama'], array_slice($satirlar, $orta)));
            if ($once > 0 && $sonra < $once * 0.7) {
                $dusenler[] = ['sayfa' => $url, 'onceki' => $once, 'son' => $sonra];
            }
        }

        // Şehir fırsatları: sorgularda geçen F1.1 şehirleri + en iyi pozisyon.
        $sehirler = [];
        foreach ($sorgular as $satir) {
            $metin = mb_strtolower((string) $satir['boyut']);
            foreach (self::SEHIRLER as $sehir) {
                if (str_contains($metin, $sehir)) {
                    if (!isset($sehirler[$sehir]) || (float) $satir['ortalama_pozisyon'] < (float) $sehirler[$sehir]['pozisyon']) {
                        $sehirler[$sehir] = [
                            'sehir' => $sehir,
                            'ornek_sorgu' => $satir['boyut'],
                            'gosterim' => (int) $satir['gosterim'],
                            'pozisyon' => (float) $satir['ortalama_pozisyon'],
                        ];
                    }
                }
            }
        }

        return [
            'firsatlar' => array_slice($firsatlar, 0, 20),
            'dusen_sayfalar' => array_slice($dusenler, 0, 20),
            'sehir_firsatlari' => array_values($sehirler),
        ];
    }
}
