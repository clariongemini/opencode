<?php

declare(strict_types=1);

namespace Kamelya\Services;

use Kamelya\Core\Config;
use Kamelya\Repositories\Admin\AyarYonetimRepository;
use Kamelya\Repositories\BildirimRepository;
use PDO;

/**
 * Bildirim orkestrasyonu — tetikleyiciler kuyruğa yazar, worker gönderir.
 * Alıcılar: ayarlar.bildirim_epostalari (virgüllü, doğrulanmış).
 */
final class BildirimService
{
    public const TURLER = ['yeni_yorum', 'yeni_talep', 'yeni_randevu', 'yeni_iletisim', 'bulten', 'bulten_dogrulama'];

    public const MAKS_DENEME = 3;

    public function __construct(
        private PDO $baglanti,
        private BildirimRepository $kuyruk,
        private AyarYonetimRepository $ayarlar
    ) {
    }

    /** @param array<string, mixed> $veri */
    public function yeniYorum(int $id, array $veri): void
    {
        $ozet = mb_substr(trim((string) ($veri['yorum'] ?? '')), 0, 200);
        $this->kuyrugaEkle(
            'yeni_yorum',
            "Yeni yorum onayı bekliyor (#{$id})",
            "Müşteri: {$veri['musteri_adi']}\nPuan: {$veri['puan']}/5\nÜrün: " . ($veri['urun_id'] ?? 'genel') . "\n\n{$ozet}"
        );
    }

    /** @param array<string, mixed> $veri */
    public function yeniTalep(int $id, array $veri): void
    {
        $this->kuyrugaEkle(
            'yeni_talep',
            "Yeni teklif talebi (#{$id})",
            "Ad: {$veri['ad_soyad']}\nTelefon: {$veri['telefon']}\nŞehir: " . ($veri['sehir'] ?? '-') . "\nDil: {$veri['dil_kodu']}"
        );
    }

    /** @param array<string, mixed> $veri */
    public function yeniRandevu(int $id, array $veri): void
    {
        $this->kuyrugaEkle(
            'yeni_randevu',
            "Yeni randevu (#{$id})",
            "Ad: {$veri['ad_soyad']}\nTelefon: {$veri['telefon']}\nTarih: {$veri['randevu_tarihi']}\nTür: " . ($veri['tur'] ?? 'gorusme')
        );
    }

    /** @param array<string, mixed> $veri */
    public function yeniIletisim(int $id, array $veri): void
    {
        if (!$this->iletisimAcikMi()) {
            return;
        }

        $ozet = mb_substr(trim((string) ($veri['mesaj'] ?? '')), 0, 500);
        $this->kuyrugaEkle(
            'yeni_iletisim',
            "✉️ Yeni İletişim Mesajı: {$veri['ad_soyad']} — " . ($veri['konu'] ?? 'genel'),
            "Ad: {$veri['ad_soyad']}\nE-posta: {$veri['eposta']}\nTelefon: {$veri['telefon']}\n"
            . "Konu: " . ($veri['konu'] ?? 'genel') . "\nDil: {$veri['dil_kodu']}\n"
            . "IP (maskeli): " . \Kamelya\Core\Gizlilik::ipMaskele($veri['ip_adresi'] ?? null) . "\n\n{$ozet}"
        );
    }

    private function iletisimAcikMi(): bool
    {
        foreach ($this->ayarlar->tumunuGetir() as $satir) {
            if ($satir['anahtar'] === 'bildirim_iletisim') {
                return (string) ($satir['deger'] ?? '1') === '1';
            }
        }

        return true;
    }

    /** Doğrudan alıcıya kuyruklama (bülten doğrulama/toplu — admin listesi aranmaz). */
    public function dogrudan(string $tur, string $alici, string $konu, string $govde): void
    {
        if (filter_var($alici, FILTER_VALIDATE_EMAIL) === false) {
            return;
        }

        $this->kuyruk->kuyrugaEkle($tur, $alici, $konu, $govde);
    }

    /** @return array{gonderildi: int, hata: int} */
    public function gonderBekleyenler(int $limit = 20): array
    {
        $sonuc = ['gonderildi' => 0, 'hata' => 0];
        foreach ($this->kuyruk->bekleyenler($limit) as $satir) {
            if ((int) $satir['deneme_sayisi'] >= self::MAKS_DENEME) {
                continue;
            }

            try {
                $this->tasiyici()->gonder((string) $satir['alici'], (string) $satir['konu'], (string) $satir['govde']);
                $this->kuyruk->durumGuncelle((int) $satir['id'], 'gonderildi');
                $sonuc['gonderildi']++;
            } catch (\Throwable $hata) {
                $this->kuyruk->durumGuncelle((int) $satir['id'], 'hata', mb_substr($hata->getMessage(), 0, 500));
                $sonuc['hata']++;
            }
        }

        return $sonuc;
    }

    private function kuyrugaEkle(string $tur, string $konu, string $govde): void
    {
        foreach ($this->alicilar() as $alici) {
            $this->kuyruk->kuyrugaEkle($tur, $alici, $konu, $govde);
        }
    }

    /** @return array<int, string> */
    public function alicilar(): array
    {
        $cikti = [];
        foreach ($this->ayarlar->tumunuGetir() as $satir) {
            if ($satir['anahtar'] !== 'bildirim_epostalari') {
                continue;
            }

            foreach (explode(',', (string) ($satir['deger'] ?? '')) as $parca) {
                $eposta = trim($parca);
                if (filter_var($eposta, FILTER_VALIDATE_EMAIL) !== false) {
                    $cikti[] = $eposta;
                }
            }
        }

        return array_values(array_unique($cikti));
    }

    private function tasiyici(): SmtpTasiyici
    {
        return new SmtpTasiyici(
            (string) Config::al('eposta.sunucu', '127.0.0.1'),
            (int) Config::al('eposta.port', 1025),
            Config::al('eposta.kullanici'),
            Config::al('eposta.sifre'),
            (string) Config::al('eposta.sifreleme', 'yok'),
            (string) Config::al('eposta.gonderen', 'info@kamelya.local')
        );
    }
}
