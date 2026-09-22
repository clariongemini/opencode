<?php

declare(strict_types=1);

namespace Kamelya\Repositories;

use PDO;

/** Bülten abone yazma/okuma — çift onay akışlı. */
final class BultenRepository
{
    public function __construct(private PDO $baglanti)
    {
    }

    /** @return array<string, mixed>|null */
    public function epostaIleGetir(string $eposta): ?array
    {
        $ifade = $this->baglanti->prepare('SELECT * FROM bulten_aboneleri WHERE eposta = ?');
        $ifade->execute([$eposta]);
        $satir = $ifade->fetch();

        return $satir === false ? null : $satir;
    }

    /** @return array<string, mixed>|null */
    public function tokenIleGetir(string $token): ?array
    {
        $ifade = $this->baglanti->prepare('SELECT * FROM bulten_aboneleri WHERE onay_token = ?');
        $ifade->execute([$token]);
        $satir = $ifade->fetch();

        return $satir === false ? null : $satir;
    }

    /** @param array<string, mixed> $veri */
    public function olustur(array $veri): int
    {
        $ifade = $this->baglanti->prepare(
            'INSERT INTO bulten_aboneleri (eposta, ad_soyad, dil_kodu, durum, onay_token, kvkk_onayi, ip_adresi)
             VALUES (?, ?, ?, \'bekliyor\', ?, ?, ?)'
        );
        $ifade->execute([
            $veri['eposta'], $veri['ad_soyad'] ?? null, $veri['dil_kodu'],
            $veri['onay_token'], $veri['kvkk_onayi'] ? 1 : 0, $veri['ip_adresi'] ?? null,
        ]);

        return (int) $this->baglanti->lastInsertId();
    }

    public function onayla(int $id): void
    {
        $ifade = $this->baglanti->prepare(
            "UPDATE bulten_aboneleri SET durum = 'onaylandi', onaylanma_at = NOW() WHERE id = ?"
        );
        $ifade->execute([$id]);
    }

    public function iptal(int $id): void
    {
        $ifade = $this->baglanti->prepare("UPDATE bulten_aboneleri SET durum = 'iptal' WHERE id = ?");
        $ifade->execute([$id]);
    }

    /** @return array<int, array<string, mixed>> */
    public function onaylilar(): array
    {
        $ifade = $this->baglanti->query(
            "SELECT eposta, ad_soyad, dil_kodu FROM bulten_aboneleri WHERE durum = 'onaylandi' ORDER BY id ASC"
        );

        return $ifade->fetchAll();
    }

    /** @return array{satirlar: array, toplam: int} */
    public function adminListe(int $sayfa, int $adet): array
    {
        $sayac = $this->baglanti->query('SELECT COUNT(*) FROM bulten_aboneleri');
        $toplam = (int) $sayac->fetchColumn();

        $ifade = $this->baglanti->prepare(
            'SELECT id, eposta, ad_soyad, dil_kodu, durum, created_at, onaylanma_at
             FROM bulten_aboneleri ORDER BY id DESC LIMIT :lim OFFSET :off'
        );
        $ifade->bindValue(':lim', $adet, PDO::PARAM_INT);
        $ifade->bindValue(':off', ($sayfa - 1) * $adet, PDO::PARAM_INT);
        $ifade->execute();

        return ['satirlar' => $ifade->fetchAll(), 'toplam' => $toplam];
    }
}
