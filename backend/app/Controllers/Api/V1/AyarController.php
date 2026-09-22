<?php

declare(strict_types=1);

namespace Kamelya\Controllers\Api\V1;

use Kamelya\Core\Database;
use Kamelya\Core\Request;
use Kamelya\Core\Response;
use Kamelya\Repositories\Admin\AyarYonetimRepository;

/**
 * Public ayar okuma — allowlist'li salt-okunur alt küme
 * (garanti metinleri, site adı; iletişim sırları yok).
 */
final class AyarController
{
    /** @var array<int, string> */
    private const PUBLIC_ANAHTARLAR = [
        'site_adi', 'varsayilan_dil', 'garanti_suresi', 'garanti_kapsami', 'garanti_istisnalari',
        'banka_hesaplari', 'odeme_notu', 'google_maps_embed_url',
        'instagram_hesap', 'instagram_embed_aktif', 'instagram_gonderiler',
        'sosyal_facebook', 'sosyal_instagram', 'sosyal_twitter', 'sosyal_x',
        'sosyal_linkedin', 'sosyal_youtube', 'sosyal_pinterest',
    ];

    private AyarYonetimRepository $ayarlar;

    public function __construct()
    {
        $this->ayarlar = new AyarYonetimRepository(Database::baglanti());
    }

    /** @param array<string, string> $rota */
    public function liste(Request $istek, array $rota = []): void
    {
        $cikti = [];
        foreach ($this->ayarlar->tumunuGetir() as $satir) {
            if (in_array($satir['anahtar'], self::PUBLIC_ANAHTARLAR, true)) {
                $cikti[$satir['anahtar']] = $satir['deger'];
            }
        }

        Response::basari($cikti);
    }
}
