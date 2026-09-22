<?php

declare(strict_types=1);

namespace Kamelya\Services;

use Kamelya\Core\Hata;
use Kamelya\Repositories\KullaniciRepository;
use PDO;

/** Ekip vitrini — public_goster=1 olanlar (PII: e-posta/sifre asla dönmez). */
final class EkipService
{
    public function __construct(
        private PDO $baglanti,
        private KullaniciRepository $kullanicilar,
        private AuditLogService $denetim
    ) {
    }

    /** @return array<int, array<string, mixed>> */
    public function liste(): array
    {
        $cikti = [];
        foreach ($this->kullanicilar->ekipUyeleriGetir() as $satir) {
            if ((int) ($satir['public_goster'] ?? 0) !== 1) {
                continue;
            }

            $cikti[] = [
                'id' => (int) $satir['id'],
                'ad_soyad' => $satir['ad_soyad'],
                'unvan' => $satir['unvan'],
                'biyografi' => $satir['biyografi'],
                'fotograf_yolu' => $satir['fotograf_yolu'],
                'uzmanlik_alani' => $satir['uzmanlik_alani'],
            ];
        }

        return $cikti;
    }

    /** @param array<string, mixed> $veri */
    public function guncelle(array $kullanici, int $id, array $veri, string $ip, string $ajan): array
    {
        $eski = $this->kullanicilar->idIleGetir($id);
        if ($eski === null) {
            throw new Hata('NOT_FOUND', 'Kullanıcı bulunamadı.', [['field' => 'id', 'issue' => 'not_found']], 404);
        }

        $izinli = [];
        foreach (['unvan', 'biyografi', 'fotograf_yolu', 'uzmanlik_alani', 'public_goster', 'ekip_mi'] as $sutun) {
            if (array_key_exists($sutun, $veri)) {
                $izinli[$sutun] = $veri[$sutun];
            }
        }

        if ($izinli !== []) {
            $alanlar = [];
            $degerler = [];
            foreach ($izinli as $sutun => $deger) {
                $alanlar[] = $sutun . ' = ?';
                $degerler[] = $deger;
            }

            $degerler[] = $id;
            $ifade = $this->baglanti->prepare('UPDATE kullanicilar SET ' . implode(', ', $alanlar) . ' WHERE id = ?');
            $ifade->execute($degerler);
        }

        $this->denetim->kaydet((int) $kullanici['id'], 'update', 'ekip', $id, ['ad_soyad' => $eski['ad_soyad']], $izinli, $ip, $ajan);

        return ['id' => $id];
    }
}
