<?php

declare(strict_types=1);

namespace Kamelya\Services;

use Kamelya\Core\Hata;
use Kamelya\Repositories\UrunRepository;
use Kamelya\Repositories\YorumRepository;
use PDO;

/**
 * Yorum akışı — public gönderim (durum=bekliyor) + moderasyon + özetler.
 * Bildirim kancası: Bölüm B'de gelecek BildirimService nullable enjekte edilir;
 * yoksa sessiz geçilir (yorum kaydı engellenmez).
 */
final class YorumService
{
    public function __construct(
        private PDO $baglanti,
        private YorumRepository $yorumlar,
        private UrunRepository $urunler,
        private AuditLogService $denetim,
        private ?BildirimService $bildirim = null
    ) {
    }

    /** @param array<string, mixed> $veri */
    public function gonder(array $veri, string $ip): array
    {
        if (($veri['kvkk_onayi'] ?? false) !== true) {
            throw new Hata('VALIDATION_ERROR', 'KVKK onayı zorunludur.', [['field' => 'kvkk_onayi', 'issue' => 'required']], 422);
        }

        if (isset($veri['urun_id']) && $veri['urun_id'] !== null && $veri['urun_id'] !== '') {
            $urun = $this->urunler->idIleGetir((int) $veri['urun_id']);
            if ($urun === null) {
                throw new Hata('PRODUCT_NOT_FOUND', 'Ürün bulunamadı.', [['field' => 'urun_id', 'issue' => 'not_found']], 404);
            }

            $veri['urun_id'] = (int) $veri['urun_id'];
        } else {
            $veri['urun_id'] = null;
        }

        $veri['puan'] = (int) $veri['puan'];
        $veri['ip_adresi'] = $ip;

        $id = $this->yorumlar->olustur($veri);

        if ($this->bildirim !== null) {
            $this->bildirim->yeniYorum($id, $veri);
        }

        return ['mesaj' => 'Yorumunuz onay için gönderildi.', 'id' => $id];
    }

    /** @return array{satirlar: array, toplam: int, ortalama: float} */
    public function liste(?int $urunId, string $dil, int $sayfa, int $adet, bool $yalnizOneCikan = false): array
    {
        return $this->yorumlar->onayliListe($urunId, $dil, $sayfa, $adet, $yalnizOneCikan);
    }

    /** @return array<string, mixed> */
    public function ozet(?int $urunId = null): array
    {
        return $this->yorumlar->ozet($urunId);
    }

    /** @return array{satirlar: array, toplam: int} */
    public function kuyruk(?string $durum, int $sayfa, int $adet): array
    {
        return $this->yorumlar->moderasyonListesi($durum, $sayfa, $adet);
    }

    /** @return array<string, mixed> */
    public function detay(int $id): array
    {
        $satir = $this->yorumlar->idIleGetir($id);
        if ($satir === null) {
            throw new Hata('NOT_FOUND', 'Yorum bulunamadı.', [['field' => 'id', 'issue' => 'not_found']], 404);
        }

        return $satir;
    }

    public function onayla(array $kullanici, int $id, string $ip, string $ajan): array
    {
        $eski = $this->yorumlar->idIleGetir($id);
        if ($eski === null) {
            throw new Hata('NOT_FOUND', 'Yorum bulunamadı.', [['field' => 'id', 'issue' => 'not_found']], 404);
        }

        $this->yorumlar->guncelle($id, [
            'durum' => 'onaylandi',
            'red_sebebi' => null,
            'onaylayan_id' => (int) $kullanici['id'],
            'onaylanma_at' => date('Y-m-d H:i:s'),
        ]);
        $this->denetim->kaydet((int) $kullanici['id'], 'update', 'yorum', $id, ['durum' => $eski['durum']], ['durum' => 'onaylandi'], $ip, $ajan);

        return ['id' => $id, 'durum' => 'onaylandi'];
    }

    public function reddet(array $kullanici, int $id, string $sebep, string $ip, string $ajan): array
    {
        $eski = $this->yorumlar->idIleGetir($id);
        if ($eski === null) {
            throw new Hata('NOT_FOUND', 'Yorum bulunamadı.', [['field' => 'id', 'issue' => 'not_found']], 404);
        }

        if (trim($sebep) === '') {
            throw new Hata('VALIDATION_ERROR', 'Red sebebi zorunludur.', [['field' => 'red_sebebi', 'issue' => 'required']], 422);
        }

        $this->yorumlar->guncelle($id, [
            'durum' => 'reddedildi',
            'red_sebebi' => $sebep,
            'onaylayan_id' => (int) $kullanici['id'],
            'onaylanma_at' => null,
        ]);
        $this->denetim->kaydet((int) $kullanici['id'], 'update', 'yorum', $id, ['durum' => $eski['durum']], ['durum' => 'reddedildi'], $ip, $ajan);

        return ['id' => $id, 'durum' => 'reddedildi'];
    }

    public function oneCikanDegistir(array $kullanici, int $id, string $ip, string $ajan): array
    {
        $eski = $this->yorumlar->idIleGetir($id);
        if ($eski === null) {
            throw new Hata('NOT_FOUND', 'Yorum bulunamadı.', [['field' => 'id', 'issue' => 'not_found']], 404);
        }

        $yeni = ((int) $eski['one_cikan'] === 1) ? 0 : 1;
        $this->yorumlar->guncelle($id, ['one_cikan' => $yeni]);
        $this->denetim->kaydet((int) $kullanici['id'], 'update', 'yorum', $id, ['one_cikan' => (int) $eski['one_cikan']], ['one_cikan' => $yeni], $ip, $ajan);

        return ['id' => $id, 'one_cikan' => $yeni];
    }

    public function sil(array $kullanici, int $id, string $ip, string $ajan): void
    {
        $eski = $this->yorumlar->idIleGetir($id);
        if ($eski === null) {
            throw new Hata('NOT_FOUND', 'Yorum bulunamadı.', [['field' => 'id', 'issue' => 'not_found']], 404);
        }

        // Soft delete: fiziksel silme yok, reddedilmişe çekilir.
        $this->yorumlar->guncelle($id, ['durum' => 'reddedildi', 'red_sebebi' => 'Yönetici silmesi']);
        $this->denetim->kaydet((int) $kullanici['id'], 'delete', 'yorum', $id, ['durum' => $eski['durum']], ['durum' => 'reddedildi'], $ip, $ajan);
    }
}
