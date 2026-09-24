<?php

declare(strict_types=1);

namespace Kamelya\Services;

use Kamelya\Core\Hata;
use Kamelya\Repositories\BlogRepository;
use Kamelya\Repositories\KategoriRepository;
use Kamelya\Repositories\SeoAnalitikRepository;
use Kamelya\Repositories\SeoVerisiRepository;
use Kamelya\Repositories\UrunRepository;

/**
 * SEO köprüsü (seo-analyzer teknik denetim kanalı):
 * - Ürün/kategori/blog/statik sayfalar için seo_verileri çözümleme.
 * - /seo/check: URL → sayfa çözümleme + meta bant kontrolü (title 50–60,
 *   description 150–160, canonical, hreflang, kapak görseli).
 */
final class SeoService
{
    public function __construct(
        private SeoVerisiRepository $seo,
        private UrunRepository $urunler,
        private KategoriRepository $kategoriler,
        private ?SeoAnalitikRepository $analitik = null,
        private ?BlogRepository $bloglar = null
    ) {
    }

    /** @return array<string, mixed>|null */
    public function sayfaSeo(string $tip, string $dil, ?int $referansId = null, ?string $sayfaKodu = null): ?array
    {
        $satir = $this->seo->sayfaTipiVeDilIleGetir($tip, $dil, $referansId, $sayfaKodu);
        if ($satir === null) {
            return null;
        }

        $satir['hreflang'] = $this->hreflangCoz((string) ($satir['hreflang_json'] ?? ''));

        return $satir;
    }

    /** @return array<int, array<string, string>> */
    private function hreflangCoz(string $ham): array
    {
        if ($ham === '') {
            return [];
        }

        $cozum = json_decode($ham, true);

        return is_array($cozum) ? $cozum : [];
    }

    /** @return array<string, mixed> */
    public function denetle(string $url, string $dil): array
    {
        $yol = '/' . ltrim((string) strtok(trim($url), '?'), '/');
        $cozulen = $this->sayfaCoz($yol, $dil);
        if ($cozulen === null) {
            throw new Hata(
                'SEO_NOT_FOUND',
                'URL için SEO kaydı bulunamadı.',
                [['field' => 'url', 'issue' => 'not_found']],
                404
            );
        }

        $baslik = (string) ($cozulen['seo']['meta_baslik'] ?? $cozulen['baslik'] ?? '');
        $aciklama = (string) ($cozulen['seo']['meta_aciklama'] ?? '');
        $canonical = (string) ($cozulen['seo']['canonical_url'] ?? '');
        $hreflang = $cozulen['seo']['hreflang'] ?? [];

        $kontroller = [
            $this->bantKontrol('title', $baslik, 50, 60),
            $this->bantKontrol('meta_description', $aciklama, 150, 160),
            ['alan' => 'canonical', 'durum' => $canonical !== '' ? 'PASS' : 'FAIL', 'deger' => $canonical],
            ['alan' => 'hreflang', 'durum' => count($hreflang) > 0 ? 'PASS' : 'FAIL', 'deger' => (string) count($hreflang)],
            ['alan' => 'kapak_resmi', 'durum' => ($cozulen['kapak'] ?? '') !== '' ? 'PASS' : 'FAIL', 'deger' => (string) ($cozulen['kapak'] ?? '')],
        ];

        $puan = 0;
        foreach ($kontroller as $kontrol) {
            if ($kontrol['durum'] === 'PASS') {
                $puan += 20;
            }
        }

        return [
            'url' => $yol,
            'lang' => $dil,
            'sayfa' => $cozulen['sayfa'],
            'checks' => $kontroller,
            'score' => $puan,
            'oneriler' => $this->oneriUret($yol),
        ];
    }

    /**
     * Canlı öneriler — cache'lenmiş GSC sayfa verisinden (yoksa boş dizi).
     *
     * @return array<int, string>
     */
    private function oneriUret(string $yol): array
    {
        if ($this->analitik === null) {
            return [];
        }

        $slug = trim(basename($yol));
        $bitis = date('Y-m-d');
        $baslangic = date('Y-m-d', strtotime('-30 days'));
        $oneriler = [];

        foreach ($this->analitik->tarihAraligiIleGetir('sayfa', $baslangic, $bitis, 500) as $satir) {
            $boyut = (string) $satir['boyut'];
            if ($slug !== '' && !str_contains($boyut, $slug)) {
                continue;
            }

            $gosterim = (int) $satir['gosterim'];
            $ctr = (float) $satir['ctr'];
            $pozisyon = (float) $satir['ortalama_pozisyon'];
            if ($gosterim >= 100 && $ctr < 0.02) {
                $oneriler[] = "“{$boyut}” son 30 günde {$gosterim} gösterim aldı ama CTR %" . round($ctr * 100, 2) . ' — başlık ve meta açıklama iyileştirilmeli.';
            }

            if ($pozisyon > 20 && $gosterim >= 50) {
                $oneriler[] = "“{$boyut}” ortalama {$pozisyon} pozisyonda — içerik güçlendirilmeli.";
            }
        }

        return array_slice(array_unique($oneriler), 0, 5);
    }

    /** @return array<string, mixed>|null */
    private function sayfaCoz(string $yol, string $dil): ?array
    {
        if (preg_match('#^/urun/([^/]+)$#', $yol, $eslesme) === 1) {
            $urun = $this->urunler->slugIleGetir($dil, $eslesme[1]);
            if ($urun === null) {
                return null;
            }

            $detay = $this->urunler->ceviriIleGetir((int) $urun['id'], $dil);
            $seo = $this->sayfaSeo('urun', $dil, (int) $urun['id']);
            $kapak = '';
            foreach ($detay['resimler'] ?? [] as $resim) {
                if ((int) $resim['kapak_mi'] === 1) {
                    $kapak = (string) $resim['dosya_yolu'];
                }
            }

            return [
                'sayfa' => ['tip' => 'urun', 'id' => (int) $urun['id']],
                'baslik' => (string) $detay['baslik'],
                'seo' => $seo ?? [],
                'kapak' => $kapak,
            ];
        }

        if (preg_match('#^/blog/([^/]+)$#', $yol, $eslesme) === 1 && $this->bloglar !== null) {
            $yazi = $this->bloglar->slugIleGetir($dil, $eslesme[1]);
            if ($yazi === null) {
                return null;
            }

            return [
                'sayfa' => ['tip' => 'blog', 'id' => (int) $yazi['id']],
                'baslik' => (string) ($yazi['seo_baslik'] ?: $yazi['baslik']),
                'seo' => $this->sayfaSeo('blog', $dil, (int) $yazi['id']) ?? [],
                'kapak' => (string) ($yazi['kapak_resmi'] ?? ''),
            ];
        }

        if (preg_match('#^/kategori/([^/]+)$#', $yol, $eslesme) === 1) {
            $kategori = $this->kategoriler->slugIleGetir($dil, $eslesme[1]);
            if ($kategori === null) {
                return null;
            }

            return [
                'sayfa' => ['tip' => 'kategori', 'id' => (int) $kategori['id']],
                'baslik' => (string) $kategori['isim'],
                'seo' => $this->sayfaSeo('kategori', $dil, (int) $kategori['id']) ?? [],
                'kapak' => '',
            ];
        }

        $kod = trim($yol, '/');
        $seo = $this->sayfaSeo('statik', $dil, null, $kod === '' ? 'anasayfa' : $kod);
        if ($seo === null) {
            return null;
        }

        return [
            'sayfa' => ['tip' => 'statik', 'kod' => $kod],
            'baslik' => (string) ($seo['meta_baslik'] ?? ''),
            'seo' => $seo,
            'kapak' => '',
        ];
    }

    /** @return array<string, mixed> */
    private function bantKontrol(string $alan, string $deger, int $min, int $maks): array
    {
        // AI_CAĞI SEO §7: UTF-8 byte bandı (mb_strlen değil).
        $uzunluk = strlen($deger);
        $durum = ($uzunluk >= $min && $uzunluk <= $maks) ? 'PASS' : 'FAIL';

        return ['alan' => $alan, 'durum' => $durum, 'deger' => $uzunluk . ' byte (bant ' . $min . '-' . $maks . ')'];
    }
}
