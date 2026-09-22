<?php

declare(strict_types=1);

namespace Kamelya\Services;

use Kamelya\Core\Hata;
use Kamelya\Repositories\RandevuRepository;
use Kamelya\Repositories\TalepRepository;
use Kamelya\Validators\Admin\TakvimValidator;
use PDO;

/**
 * Randevu kuralları (V1): gelecek tarih, en az +24 saat, Pzt–Cmt 09:00–18:00,
 * tek ekip varsayımıyla ±30 dk çakışmada 409 SLOT_DOLU.
 */
final class RandevuService
{
    public function __construct(
        private PDO $baglanti,
        private RandevuRepository $randevular,
        private TalepRepository $talepler,
        private ?AuditLogService $denetim = null,
        private ?BildirimService $bildirim = null
    ) {
    }

    /** @param array<string, mixed> $veri */
    public function olustur(array $veri): array
    {
        $tarih = \DateTimeImmutable::createFromFormat('Y-m-d H:i', (string) $veri['randevu_tarihi']);
        if ($tarih === false) {
            throw new Hata(
                'VALIDATION_ERROR',
                'Randevu tarihi biçimi geçersiz (Y-m-d H:i).',
                [['field' => 'randevu_tarihi', 'issue' => 'invalid_format']],
                422
            );
        }

        $simdi = new \DateTimeImmutable('now');
        if ($tarih <= $simdi) {
            throw new Hata(
                'VALIDATION_ERROR',
                'Geçmiş tarihe randevu verilemez.',
                [['field' => 'randevu_tarihi', 'issue' => 'past']],
                422
            );
        }

        if ($tarih->getTimestamp() - $simdi->getTimestamp() < 24 * 3600) {
            throw new Hata(
                'VALIDATION_ERROR',
                'Randevu en az 24 saat sonrasına verilebilir.',
                [['field' => 'randevu_tarihi', 'issue' => 'too_soon']],
                422
            );
        }

        $gun = (int) $tarih->format('N');
        $saat = (int) $tarih->format('Hi');
        if ($gun === 7 || $saat < 900 || $saat > 1800) {
            throw new Hata(
                'VALIDATION_ERROR',
                'Randevu saatleri Pzt–Cmt 09:00–18:00 arasıdır.',
                [['field' => 'randevu_tarihi', 'issue' => 'outside_hours']],
                422
            );
        }

        if (isset($veri['talep_id']) && $veri['talep_id'] !== null && $veri['talep_id'] !== '') {
            if ($this->talepler->idIleGetir((int) $veri['talep_id']) === null) {
                throw new Hata(
                    'TALEP_NOT_FOUND',
                    'Bağlı talep bulunamadı.',
                    [['field' => 'talep_id', 'issue' => 'not_found']],
                    404
                );
            }
        }

        $baslangic = $tarih->modify('-30 minutes')->format('Y-m-d H:i:s');
        $bitis = $tarih->modify('+30 minutes')->format('Y-m-d H:i:s');
        if ($this->randevular->tarihAraligiIleGetir($baslangic, $bitis) !== []) {
            throw new Hata(
                'SLOT_DOLU',
                'Bu saat aralığı dolu, lütfen başka saat seçin.',
                [['field' => 'randevu_tarihi', 'issue' => 'conflict']],
                409
            );
        }

        $veri['randevu_tarihi'] = $tarih->format('Y-m-d H:i:s');

        $this->baglanti->beginTransaction();
        try {
            $id = $this->randevular->olustur($veri);
            if ($this->baglanti->inTransaction()) {
                $this->baglanti->commit();
            }
        } catch (\Throwable $hata) {
            if ($this->baglanti->inTransaction()) {
                $this->baglanti->rollBack();
            }

            throw $hata;
        }

        if ($this->bildirim !== null) {
            $this->bildirim->yeniRandevu($id, $veri);
        }

        return ['id' => $id, 'randevu_tarihi' => $veri['randevu_tarihi']];
    }

    /** Durum geçiş matrisi — tamamlandi terminaldir. */
    public const GECISLER = [
        'bekliyor' => ['devam_ediyor', 'iptal'],
        'devam_ediyor' => ['tamamlandi', 'iptal'],
        'tamamlandi' => [],
        'iptal' => ['bekliyor'],
    ];

    /** @param array<string, mixed> $veri */
    public function randevuOlustur(array $kullanici, array $veri, string $ip, string $ajan): array
    {
        $tarih = $this->tarihCoz((string) ($veri['randevu_tarihi'] ?? ''));
        $sure = isset($veri['sure_dakika']) ? (int) $veri['sure_dakika'] : 60;
        $ekip = isset($veri['ekip_uyesi_id']) && $veri['ekip_uyesi_id'] !== null && $veri['ekip_uyesi_id'] !== ''
            ? (int) $veri['ekip_uyesi_id'] : null;
        if ($ekip !== null) {
            $this->cakismaKontrol($ekip, $tarih, $sure, 0);
        }

        if (isset($veri['talep_id']) && $veri['talep_id'] !== null && $veri['talep_id'] !== '') {
            if ($this->talepler->idIleGetir((int) $veri['talep_id']) === null) {
                throw new Hata('TALEP_NOT_FOUND', 'Bağlı talep bulunamadı.', [['field' => 'talep_id', 'issue' => 'not_found']], 404);
            }
        }

        $veri['randevu_tarihi'] = $tarih->format('Y-m-d H:i:s');

        $this->baglanti->beginTransaction();
        try {
            $id = $this->randevular->olustur($veri);
            $ek = [
                'tur' => $veri['tur'] ?? 'gorusme',
                'sure_dakika' => $sure,
                'oncelik' => $veri['oncelik'] ?? 'normal',
            ];
            if ($ekip !== null) {
                $ek['ekip_uyesi_id'] = $ekip;
            }

            if (isset($veri['adres']) && $veri['adres'] !== '') {
                $ek['adres'] = $veri['adres'];
            }

            $this->randevular->guncelle($id, $ek);
            if ($this->baglanti->inTransaction()) {
                $this->baglanti->commit();
            }
        } catch (\Throwable $hata) {
            if ($this->baglanti->inTransaction()) {
                $this->baglanti->rollBack();
            }

            throw $hata;
        }

        $this->denetimKaydet($kullanici, 'create', 'randevu', $id, null, ['tur' => $ek['tur']], $ip, $ajan);
        if ($this->bildirim !== null) {
            $this->bildirim->yeniRandevu($id, $veri);
        }

        return ['id' => $id, 'randevu_tarihi' => $veri['randevu_tarihi']];
    }

    /** @param array<string, mixed> $veri */
    public function randevuGuncelle(array $kullanici, int $id, array $veri, string $ip, string $ajan): array
    {
        $eski = $this->randevular->idIleGetir($id);
        if ($eski === null) {
            throw new Hata('TALEP_NOT_FOUND', 'Randevu bulunamadı.', [['field' => 'id', 'issue' => 'not_found']], 404);
        }

        if (($eski['durum'] ?? '') === 'tamamlandi') {
            throw new Hata('KAYIT_KILITLI', 'Tamamlanan kayıt düzenlenemez.', [['field' => 'id', 'issue' => 'locked']], 422);
        }

        if (array_key_exists('durum', $veri)) {
            throw new Hata('VALIDATION_ERROR', 'Durum için durum endpointini kullanın.', [['field' => 'durum', 'issue' => 'read_only']], 422);
        }

        $tarihStr = (string) ($veri['randevu_tarihi'] ?? $eski['randevu_tarihi']);
        $tarih = $this->tarihCoz($tarihStr);
        $sure = isset($veri['sure_dakika']) ? (int) $veri['sure_dakika'] : (int) $eski['sure_dakika'];
        $ekip = array_key_exists('ekip_uyesi_id', $veri)
            ? ($veri['ekip_uyesi_id'] === null || $veri['ekip_uyesi_id'] === '' ? null : (int) $veri['ekip_uyesi_id'])
            : ($eski['ekip_uyesi_id'] === null ? null : (int) $eski['ekip_uyesi_id']);
        if ($ekip !== null) {
            $this->cakismaKontrol($ekip, $tarih, $sure, $id);
        }

        $veri['randevu_tarihi'] = $tarih->format('Y-m-d H:i:s');
        $this->randevular->guncelle($id, $veri);
        $this->denetimKaydet($kullanici, 'update', 'randevu', $id, ['durum' => $eski['durum']], ['durum' => $eski['durum']], $ip, $ajan);

        return ['id' => $id];
    }

    public function durumDegistir(array $kullanici, int $id, string $yeni, ?string $not, string $ip, string $ajan): array
    {
        $eski = $this->randevular->idIleGetir($id);
        if ($eski === null) {
            throw new Hata('TALEP_NOT_FOUND', 'Randevu bulunamadı.', [['field' => 'id', 'issue' => 'not_found']], 404);
        }

        $mevcut = (string) ($eski['durum'] ?? 'bekliyor');
        if (!in_array($yeni, self::GECISLER[$mevcut] ?? [], true)) {
            throw new Hata(
                'GECERSIZ_DURUM',
                "Geçiş yasak: {$mevcut} → {$yeni}.",
                [['field' => 'durum', 'issue' => 'invalid_transition']],
                422
            );
        }

        $guncelle = ['durum' => $yeni];
        if ($yeni === 'tamamlandi') {
            $guncelle['tamamlanma_at'] = date('Y-m-d H:i:s');
            if ($not !== null && $not !== '') {
                $guncelle['tamamlanma_notu'] = $not;
            }
        }

        $this->randevular->guncelle($id, $guncelle);
        $this->denetimKaydet($kullanici, 'durum_degisikligi', 'randevu', $id, ['durum' => $mevcut], ['durum' => $yeni], $ip, $ajan);

        return ['id' => $id, 'durum' => $yeni];
    }

    /** @return array<int, array{gun: int, toplam: int}> */
    public function aylikOzet(int $yil, int $ay): array
    {
        $bas = sprintf('%04d-%02d-01 00:00:00', $yil, $ay);
        $bit = date('Y-m-t 23:59:59', strtotime($bas));
        $toplam = [];
        foreach ($this->randevular->tarihAraligiDetayliGetir($bas, $bit) as $satir) {
            $gun = (int) substr((string) $satir['randevu_tarihi'], 8, 2);
            $toplam[$gun] = ($toplam[$gun] ?? 0) + 1;
        }

        $cikti = [];
        foreach ($toplam as $gun => $adet) {
            $cikti[] = ['gun' => $gun, 'toplam' => $adet];
        }

        return $cikti;
    }

    /** @return array<int, array<string, mixed>> */
    public function ekipIsYuku(string $baslangic, string $bitis): array
    {
        $satirlar = $this->randevular->tarihAraligiDetayliGetir($baslangic . ' 00:00:00', $bitis . ' 23:59:59');
        $toplu = [];
        foreach ($satirlar as $satir) {
            if ($satir['ekip_uyesi_id'] === null) {
                continue;
            }

            $eid = (int) $satir['ekip_uyesi_id'];
            if (!isset($toplu[$eid])) {
                $toplu[$eid] = ['ekip_uyesi_id' => $eid, 'ekip_adi' => $satir['ekip_adi'], 'is_sayisi' => 0, 'toplam_dakika' => 0];
            }

            $toplu[$eid]['is_sayisi']++;
            $toplu[$eid]['toplam_dakika'] += (int) $satir['sure_dakika'];
        }

        $gunSayisi = max(1, (int) ((strtotime($bitis) - strtotime($baslangic)) / 86400) + 1);
        $hafta = max(1 / 7, $gunSayisi / 7);
        foreach ($toplu as &$satir) {
            $satir['toplam_saat'] = round($satir['toplam_dakika'] / 60, 1);
            $satir['haftalik_saat'] = round($satir['toplam_dakika'] / 60 / $hafta, 1);
            $satir['asiri_yuk'] = $satir['haftalik_saat'] > 40;
        }

        unset($satir);

        return array_values($toplu);
    }

    private function tarihCoz(string $ham): \DateTimeImmutable
    {
        foreach (['Y-m-d H:i:s', 'Y-m-d H:i'] as $bicim) {
            $tarih = \DateTimeImmutable::createFromFormat($bicim, trim($ham));
            if ($tarih !== false) {
                return $tarih;
            }
        }

        throw new Hata('VALIDATION_ERROR', 'Tarih biçimi geçersiz (Y-m-d H:i).', [['field' => 'randevu_tarihi', 'issue' => 'invalid_format']], 422);
    }

    private function cakismaKontrol(int $ekip, \DateTimeImmutable $tarih, int $sure, int $haricId): void
    {
        $bitis = $tarih->modify('+' . $sure . ' minutes');
        if ($this->randevular->ekipCakisiyorMu($ekip, $tarih->format('Y-m-d H:i:s'), $bitis->format('Y-m-d H:i:s'), $haricId)) {
            throw new Hata('SLOT_DOLU', 'Ekip bu saatte meşgul.', [['field' => 'ekip_uyesi_id', 'issue' => 'conflict']], 409);
        }
    }

    /** @param array<string, mixed>|null $eski @param array<string, mixed>|null $yeni */
    private function denetimKaydet(array $kullanici, string $islem, string $tip, int $id, ?array $eski, ?array $yeni, string $ip, string $ajan): void
    {
        if ($this->denetim === null) {
            return;
        }

        $this->denetim->kaydet((int) $kullanici['id'], $islem, $tip, $id, $eski, $yeni, $ip, $ajan);
    }
}
