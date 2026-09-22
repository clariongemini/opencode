<?php

declare(strict_types=1);

namespace Kamelya\Services;

use Kamelya\Core\Hata;
use Kamelya\Repositories\TalepRepository;
use Kamelya\Repositories\UrunRepository;
use PDO;

/** Talep kaydı — KVKK guards + fiyat anlık görüntüsü (kayıt engellenmez). */
final class TalepService
{
    public function __construct(
        private PDO $baglanti,
        private TalepRepository $talepler,
        private UrunRepository $urunler,
        private ?HesapService $hesap = null,
        private ?BildirimService $bildirim = null,
        private ?AuditLogService $denetim = null
    ) {
    }

    /** @param array<string, mixed> $veri */
    public function olustur(array $veri): array
    {
        if (($veri['kvkk_onayi'] ?? false) !== true) {
            throw new Hata(
                'VALIDATION_ERROR',
                'KVKK onayı zorunludur.',
                [['field' => 'kvkk_onayi', 'issue' => 'required']],
                422
            );
        }

        if (isset($veri['urun_id']) && $veri['urun_id'] !== null && $veri['urun_id'] !== '') {
            if ($this->urunler->idIleGetir((int) $veri['urun_id']) === null) {
                throw new Hata(
                    'PRODUCT_NOT_FOUND',
                    'Ürün bulunamadı.',
                    [['field' => 'urun_id', 'issue' => 'not_found']],
                    404
                );
            }
        }

        $veri['hesaplanan_fiyat'] = $this->anlikFiyat($veri);

        $this->baglanti->beginTransaction();
        try {
            $id = $this->talepler->olustur($veri);
            if ($this->baglanti->inTransaction()) {
                $this->baglanti->commit();
            }
        } catch (\Throwable $hata) {
            if ($this->baglanti->inTransaction()) {
                $this->baglanti->rollBack();
            }

            throw $hata;
        }

        if ($this->bildirim !== null && ($veri['tur'] ?? 'teklif') === 'teklif') {
            $this->bildirim->yeniTalep($id, $veri);
        }

        return ['id' => $id, 'hesaplanan_fiyat' => $veri['hesaplanan_fiyat']];
    }

    /** @param array<string, mixed> $veri */
    private function anlikFiyat(array $veri): ?float
    {
        if ($this->hesap === null) {
            return null;
        }

        $olcuVar = isset($veri['alan_m2']) || (isset($veri['genislik'], $veri['derinlik']));
        $kodVar = isset($veri['malzeme_kodu'], $veri['model_kodu'], $veri['kullanim_kodu']);
        if (!$olcuVar || !$kodVar) {
            return null;
        }

        try {
            $sonuc = $this->hesap->hesapla([
                'lang' => $veri['dil_kodu'],
                'area_m2' => $veri['alan_m2'] ?? null,
                'width' => $veri['genislik'] ?? null,
                'length' => $veri['derinlik'] ?? null,
                'material' => $veri['malzeme_kodu'],
                'model' => $veri['model_kodu'],
                'usage' => $veri['kullanim_kodu'],
            ]);

            return (float) $sonuc['final_price'];
        } catch (Hata $hata) {
            return null;
        }
    }

    public const DURUMLAR = ['yeni', 'arandi', 'kesif', 'teklif', 'kazanildi', 'kaybedildi'];

    public function durumDegistir(array $kullanici, int $id, string $yeni, string $ip, string $ajan): array
    {
        if (!in_array($yeni, self::DURUMLAR, true)) {
            throw new Hata('VALIDATION_ERROR', 'Geçersiz durum.', [['field' => 'durum', 'issue' => 'invalid']], 422);
        }

        $eski = $this->talepler->idIleGetir($id);
        if ($eski === null) {
            throw new Hata('NOT_FOUND', 'Talep bulunamadı.', [['field' => 'id', 'issue' => 'not_found']], 404);
        }

        $this->talepler->durumGuncelle($id, $yeni);
        if ($this->denetim !== null) {
            $this->denetim->kaydet((int) $kullanici['id'], 'durum_degisikligi', 'talep', $id, ['durum' => $eski['durum']], ['durum' => $yeni], $ip, $ajan);
        }

        return ['id' => $id, 'durum' => $yeni];
    }
}
