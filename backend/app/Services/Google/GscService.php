<?php

declare(strict_types=1);

namespace Kamelya\Services\Google;

use Kamelya\Core\Config;
use Kamelya\Core\Hata;

/**
 * Search Console okuma katmanı (salt-okunur scope, bağımsız istemci).
 * Yapılandırma yoksa anlamlı 503 üretir; çağrılar 1 saat dosya-önbelleklidir.
 */
final class GscService
{
    public function yapilandirildiMi(): bool
    {
        $dosya = (string) Config::al('gsc.servis_hesabi', '');
        $site = (string) Config::al('gsc.site_url', '');

        return $dosya !== '' && is_readable($dosya) && $site !== '';
    }

    public function yapilandirmaKontrol(): void
    {
        if (!$this->yapilandirildiMi()) {
            throw new Hata(
                'GSC_YAPILANDIRILMADI',
                'Service Account veya site URL tanımsız. Bkz. docs/gsc-kurulum.md.',
                [['field' => 'GSC_SERVICE_ACCOUNT_JSON', 'issue' => 'required']],
                503
            );
        }
    }

    /** @return array<int, array<string, mixed>> */
    public function sorgularGetir(string $baslangic, string $bitis, int $limit = 100): array
    {
        return $this->sorgula($baslangic, $bitis, ['query'], $limit);
    }

    /** @return array<int, array<string, mixed>> */
    public function sayfalarGetir(string $baslangic, string $bitis, int $limit = 100): array
    {
        return $this->sorgula($baslangic, $bitis, ['page'], $limit);
    }

    /** @return array<int, array<string, mixed>> */
    public function ulkeDagilimi(string $baslangic, string $bitis): array
    {
        return $this->sorgula($baslangic, $bitis, ['country'], 50);
    }

    /** @return array<int, array<string, mixed>> */
    public function cihazDagilimi(string $baslangic, string $bitis): array
    {
        return $this->sorgula($baslangic, $bitis, ['device'], 10);
    }

    /** @return array<int, array<string, mixed>> */
    public function gunlukTrend(string $baslangic, string $bitis): array
    {
        return $this->sorgula($baslangic, $bitis, ['date'], 100);
    }

    /**
     * @param array<int, string> $boyutlar
     * @return array<int, array<string, mixed>>
     */
    private function sorgula(string $baslangic, string $bitis, array $boyutlar, int $limit): array
    {
        $this->yapilandirmaKontrol();

        $anahtar = md5($baslangic . '|' . $bitis . '|' . implode(',', $boyutlar) . '|' . $limit);
        $onbellek = $this->onbellekOku($anahtar);
        if ($onbellek !== null) {
            return $onbellek;
        }

        $baglanti = new GscBaglanti((string) Config::al('gsc.servis_hesabi', ''));
        $jeton = $baglanti->jetonAl();
        $yanit = $baglanti->sorgula((string) Config::al('gsc.site_url', ''), $jeton, [
            'startDate' => $baslangic,
            'endDate' => $bitis,
            'dimensions' => $boyutlar,
            'rowLimit' => $limit,
        ]);

        $satirlar = [];
        foreach ($yanit['rows'] ?? [] as $satir) {
            if (!is_array($satir)) {
                continue;
            }

            $anahtarlar = $satir['keys'] ?? [];
            $satirlar[] = [
                'boyut' => $anahtarlar[0] ?? null,
                'tiklama' => (int) ($satir['clicks'] ?? 0),
                'gosterim' => (int) ($satir['impressions'] ?? 0),
                'ctr' => (float) ($satir['ctr'] ?? 0),
                'pozisyon' => (float) ($satir['position'] ?? 0),
            ];
        }

        $this->onbellekYaz($anahtar, $satirlar);

        return $satirlar;
    }

    /** @return array<int, array<string, mixed>>|null */
    private function onbellekOku(string $anahtar): ?array
    {
        $dosya = $this->onbellekDosya($anahtar);
        if (!is_file($dosya)) {
            return null;
        }

        $ham = json_decode((string) file_get_contents($dosya), true);
        if (!is_array($ham) || ($ham['zaman'] ?? 0) + (int) Config::al('gsc.onbellek_sure', 3600) < time()) {
            return null;
        }

        return $ham['veri'] ?? null;
    }

    /** @param array<int, array<string, mixed>> $veri */
    private function onbellekYaz(string $anahtar, array $veri): void
    {
        $dizin = sys_get_temp_dir() . '/kamelya_gsc';
        if (!is_dir($dizin)) {
            mkdir($dizin, 0700, true);
        }

        file_put_contents($dizin . '/' . $anahtar . '.json', json_encode(['zaman' => time(), 'veri' => $veri]), LOCK_EX);
    }

    private function onbellekDosya(string $anahtar): string
    {
        return sys_get_temp_dir() . '/kamelya_gsc/' . $anahtar . '.json';
    }
}
