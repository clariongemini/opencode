<?php

declare(strict_types=1);

namespace Kamelya\Repositories;

use PDO;

/** Public blog okuma — yalnızca yayında + silinmemiş. */
final class BlogRepository
{
    public function __construct(private PDO $baglanti)
    {
    }

    /** @return array{satirlar: array, toplam: int} */
    public function liste(string $dil, int $sayfa, int $adet): array
    {
        $sayac = $this->baglanti->prepare(
            "SELECT COUNT(*) FROM blog_yazilari y
             JOIN blog_yazisi_cevirileri c ON c.yazi_id = y.id AND c.dil_kodu = :dil
             WHERE y.yayin_durumu = 'yayinda' AND y.deleted_at IS NULL"
        );
        $sayac->bindValue(':dil', $dil);
        $sayac->execute();
        $toplam = (int) $sayac->fetchColumn();

        $ifade = $this->baglanti->prepare(
            "SELECT y.id, y.kapak_resmi, y.yayin_tarihi, c.baslik, c.ozet, c.slug
             FROM blog_yazilari y
             JOIN blog_yazisi_cevirileri c ON c.yazi_id = y.id AND c.dil_kodu = :dil
             WHERE y.yayin_durumu = 'yayinda' AND y.deleted_at IS NULL
             ORDER BY y.yayin_tarihi DESC, y.id DESC LIMIT :lim OFFSET :off"
        );
        $ifade->bindValue(':dil', $dil);
        $ifade->bindValue(':lim', $adet, PDO::PARAM_INT);
        $ifade->bindValue(':off', ($sayfa - 1) * $adet, PDO::PARAM_INT);
        $ifade->execute();

        return ['satirlar' => $ifade->fetchAll(), 'toplam' => $toplam];
    }

    /** @return array<string, mixed>|null */
    public function slugIleGetir(string $dil, string $slug): ?array
    {
        $ifade = $this->baglanti->prepare(
            "SELECT y.id, y.kapak_resmi, y.yayin_tarihi, k.ad_soyad AS yazar,
                    c.baslik, c.ozet, c.icerik, c.slug, c.seo_baslik, c.seo_aciklama
             FROM blog_yazilari y
             JOIN blog_yazisi_cevirileri c ON c.yazi_id = y.id AND c.dil_kodu = ?
             JOIN kullanicilar k ON k.id = y.yazar_id
             WHERE c.slug = ? AND y.yayin_durumu = 'yayinda' AND y.deleted_at IS NULL"
        );
        $ifade->execute([$dil, $slug]);
        $satir = $ifade->fetch();

        return $satir === false ? null : $satir;
    }

    /** @return array<int, array<string, mixed>> */
    public function ilgiliGetir(string $dil, int $haricId, int $adet = 3): array
    {
        $ifade = $this->baglanti->prepare(
            "SELECT y.id, c.baslik, c.slug FROM blog_yazilari y
             JOIN blog_yazisi_cevirileri c ON c.yazi_id = y.id AND c.dil_kodu = :dil
             WHERE y.yayin_durumu = 'yayinda' AND y.deleted_at IS NULL AND y.id != :haric
             ORDER BY y.yayin_tarihi DESC, y.id DESC LIMIT :lim"
        );
        $ifade->bindValue(':dil', $dil);
        $ifade->bindValue(':haric', $haricId, PDO::PARAM_INT);
        $ifade->bindValue(':lim', $adet, PDO::PARAM_INT);
        $ifade->execute();

        return $ifade->fetchAll();
    }
}
