<?php

declare(strict_types=1);

namespace Kamelya\Repositories;

use PDO;

/** Yönetici/editör/satış okuma-yazma — şifre hash'i yalnızca AuthService'te doğrulanır. */
final class KullaniciRepository
{
    public function __construct(private PDO $baglanti)
    {
    }

    /** @return array<string, mixed>|null */
    public function epostaIleGetir(string $eposta): ?array
    {
        $ifade = $this->baglanti->prepare('SELECT * FROM kullanicilar WHERE eposta = ?');
        $ifade->execute([$eposta]);
        $satir = $ifade->fetch();

        return $satir === false ? null : $satir;
    }

    /** @return array<string, mixed>|null */
    public function idIleGetir(int $id): ?array
    {
        $ifade = $this->baglanti->prepare('SELECT * FROM kullanicilar WHERE id = ?');
        $ifade->execute([$id]);
        $satir = $ifade->fetch();

        return $satir === false ? null : $satir;
    }

    public function sonGirisGuncelle(int $id): void
    {
        $ifade = $this->baglanti->prepare('UPDATE kullanicilar SET son_giris_at = NOW() WHERE id = ?');
        $ifade->execute([$id]);
    }

    /** @return array<int, array<string, mixed>> */
    public function ekipUyeleriGetir(): array
    {
        $ifade = $this->baglanti->query(
            'SELECT id, ad_soyad, eposta, rol, unvan, biyografi, fotograf_yolu, uzmanlik_alani, public_goster
             FROM kullanicilar WHERE ekip_mi = 1 AND aktif = 1 ORDER BY ad_soyad ASC'
        );

        return $ifade->fetchAll();
    }
}
