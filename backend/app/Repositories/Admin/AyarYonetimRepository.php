<?php

declare(strict_types=1);

namespace Kamelya\Repositories\Admin;

use PDO;

/** Ayar + ürün görseli yazma erişimi. */
final class AyarYonetimRepository
{
    /** @var array<int, string> */
    public const IZINLI_ANAHTARLAR = [
        'site_adi', 'iletisim_telefonu', 'whatsapp_numarasi', 'varsayilan_dil',
        'sosyal_instagram', 'sosyal_facebook', 'sosyal_x', 'sosyal_youtube',
        'bildirim_epostalari', 'bildirim_iletisim',
        'garanti_suresi', 'garanti_kapsami', 'garanti_istisnalari',
        'banka_hesaplari', 'odeme_notu',
        'google_maps_embed_url',
        'instagram_hesap', 'instagram_embed_aktif', 'instagram_gonderiler',
        'sosyal_linkedin', 'sosyal_pinterest',
        'sicaklik_formulu_katsayilari',
    ];

    public function __construct(private PDO $baglanti)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function tumunuGetir(): array
    {
        $ifade = $this->baglanti->query('SELECT * FROM ayarlar ORDER BY anahtar ASC');

        return $ifade->fetchAll();
    }

    /** @param array<string, string> $degerler */
    public function topluGuncelle(array $degerler): int
    {
        $ifade = $this->baglanti->prepare(
            'INSERT INTO ayarlar (anahtar, deger) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE deger = VALUES(deger)'
        );

        $sayac = 0;
        foreach ($degerler as $anahtar => $deger) {
            if (!in_array($anahtar, self::IZINLI_ANAHTARLAR, true)) {
                continue;
            }

            $ifade->execute([$anahtar, $deger]);
            $sayac++;
        }

        return $sayac;
    }
}
