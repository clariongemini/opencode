<?php

declare(strict_types=1);

namespace Kamelya\Repositories;

use PDO;

/** Sayfa SEO verisi okuma — polimorfik çözüm Service'te doğrulanır. */
final class SeoVerisiRepository
{
    public function __construct(private PDO $baglanti)
    {
    }

    /** @return array<string, mixed>|null */
    public function sayfaTipiVeDilIleGetir(
        string $tip,
        string $dil,
        ?int $referansId = null,
        ?string $sayfaKodu = null
    ): ?array {
        if ($referansId !== null) {
            $ifade = $this->baglanti->prepare(
                'SELECT * FROM seo_verileri
                 WHERE sayfa_tipi = ? AND referans_id = ? AND dil_kodu = ?'
            );
            $ifade->execute([$tip, $referansId, $dil]);
        } else {
            $ifade = $this->baglanti->prepare(
                'SELECT * FROM seo_verileri
                 WHERE sayfa_tipi = ? AND sayfa_kodu = ? AND dil_kodu = ?'
            );
            $ifade->execute([$tip, $sayfaKodu, $dil]);
        }

        $satir = $ifade->fetch();

        return $satir === false ? null : $satir;
    }
}
