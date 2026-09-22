<?php

declare(strict_types=1);

namespace Kamelya\Services;

use PDO;

/**
 * İletişim mesajı — TalepService akışını tur='iletisim' ile yeniden kullanır.
 * Mesaj metni talepler.mesaj sütununda saklanır (migration 000024).
 */
final class IletisimService
{
    public function __construct(
        private TalepService $talepler,
        private BildirimService $bildirim
    ) {
    }

    /** @param array<string, mixed> $veri */
    public function gonder(array $veri, string $ip): array
    {
        $veri['tur'] = 'iletisim';
        $veri['kaynak'] = 'iletisim_formu';
        // Mesaj, hesap snapshot'ı olmayan iletişim kaydında not olarak saklanır.
        $veri['hesaplanan_fiyat'] = null;

        $sonuc = $this->talepler->olustur($veri);

        // Talep bildirimi genel kanaldan zaten kuyruğa girdi; iletişim
        // özel şablonunu ek olarak kuyrukla (konu + mesaj içerikli).
        $this->bildirim->yeniIletisim($sonuc['id'], $veri);

        return ['mesaj' => 'Mesajınız alındı. 24 saat içinde dönüş yapacağız.', 'id' => $sonuc['id']];
    }
}
