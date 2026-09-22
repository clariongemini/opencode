<?php

declare(strict_types=1);

namespace Kamelya\Controllers\Api\V1\Admin;

use Kamelya\Core\Database;
use Kamelya\Core\Hata;
use Kamelya\Core\Request;
use Kamelya\Core\Response;
use Kamelya\Repositories\Admin\AyarYonetimRepository;
use Kamelya\Repositories\BildirimRepository;
use Kamelya\Repositories\KullaniciRepository;
use Kamelya\Repositories\RandevuRepository;
use Kamelya\Repositories\TalepRepository;
use Kamelya\Services\AuditLogService;
use Kamelya\Services\BildirimService;
use Kamelya\Services\RandevuService;
use Kamelya\Services\TakvimService;
use Kamelya\Validators\Admin\TakvimValidator;

/** Operasyon takvimi — rol: yonetici|satis. Tüm yazma audit'li. */
final class TakvimController
{
    private RandevuService $randevu;

    private TakvimService $takvim;

    private KullaniciRepository $kullanicilar;

    public function __construct()
    {
        $pdo = Database::baglanti();
        $this->randevu = new RandevuService($pdo, new RandevuRepository($pdo), new TalepRepository($pdo), new AuditLogService($pdo), new BildirimService($pdo, new BildirimRepository($pdo), new AyarYonetimRepository($pdo)));
        $this->takvim = new TakvimService($pdo, new RandevuRepository($pdo));
        $this->kullanicilar = new KullaniciRepository($pdo);
    }

    /** @param array<string, string> $rota */
    public function aylik(Request $istek, array $rota = []): void
    {
        $yil = (int) ($istek->sorgu['yil'] ?? date('Y'));
        $ay = (int) ($istek->sorgu['ay'] ?? date('n'));
        if ($yil < 2020 || $yil > 2100 || $ay < 1 || $ay > 12) {
            Response::hata('VALIDATION_ERROR', 'Geçersiz yıl/ay.', [['field' => 'ay', 'issue' => 'invalid']], 422);

            return;
        }

        $filtreler = [];
        foreach (['tur' => 'tur', 'ekip' => 'ekip_uyesi_id', 'sehir' => 'sehir'] as $sorgu => $alan) {
            if (isset($istek->sorgu[$sorgu]) && $istek->sorgu[$sorgu] !== '') {
                $filtreler[$alan] = (string) $istek->sorgu[$sorgu];
            }
        }

        Response::basari($this->takvim->aylikTakvimOlustur($yil, $ay, $filtreler));
    }

    /** @param array<string, string> $rota */
    public function gun(Request $istek, array $rota = []): void
    {
        $tarih = (string) ($rota['tarih'] ?? '');
        if (\DateTimeImmutable::createFromFormat('Y-m-d', $tarih) === false) {
            Response::hata('VALIDATION_ERROR', 'Tarih biçimi Y-m-d olmalı.', [['field' => 'tarih', 'issue' => 'invalid_format']], 422);

            return;
        }

        Response::basari($this->takvim->gunDetay($tarih));
    }

    /** @param array<string, string> $rota */
    public function yaklasan(Request $istek, array $rota = []): void
    {
        $gun = min(30, max(1, (int) ($istek->sorgu['gun'] ?? 7)));
        Response::basari($this->takvim->yaklasanIsler($gun));
    }

    /** @param array<string, string> $rota */
    public function randevuOlustur(Request $istek, array $rota = []): void
    {
        try {
            $veri = $istek->govde ?? [];
            $dogrulama = TakvimValidator::dogrula($veri);
            if (!$dogrulama['gecerli']) {
                throw new Hata('VALIDATION_ERROR', 'Doğrulama hatası.', $dogrulama['hatalar'], 422);
            }

            Response::basari(
                $this->randevu->randevuOlustur($this->kullanici($istek), $veri, $istek->istemciIp, $istek->baslik('User-Agent') ?? ''),
                null,
                201
            );
        } catch (Hata $hata) {
            Response::hata($hata->hataKodu, $hata->getMessage(), $hata->ayrintilar, $hata->httpDurum);
        }
    }

    /** @param array<string, string> $rota */
    public function randevuGuncelle(Request $istek, array $rota = []): void
    {
        try {
            $veri = $istek->govde ?? [];
            $dogrulama = TakvimValidator::dogrula($veri, true);
            if (!$dogrulama['gecerli']) {
                throw new Hata('VALIDATION_ERROR', 'Doğrulama hatası.', $dogrulama['hatalar'], 422);
            }

            Response::basari(
                $this->randevu->randevuGuncelle($this->kullanici($istek), (int) ($rota['id'] ?? 0), $veri, $istek->istemciIp, $istek->baslik('User-Agent') ?? '')
            );
        } catch (Hata $hata) {
            Response::hata($hata->hataKodu, $hata->getMessage(), $hata->ayrintilar, $hata->httpDurum);
        }
    }

    /** @param array<string, string> $rota */
    public function durumDegistir(Request $istek, array $rota = []): void
    {
        try {
            $veri = $istek->govde ?? [];
            $yeni = (string) ($veri['durum'] ?? '');
            if (!in_array($yeni, TakvimValidator::DURUMLAR, true)) {
                throw new Hata('VALIDATION_ERROR', 'Geçersiz durum.', [['field' => 'durum', 'issue' => 'invalid']], 422);
            }

            Response::basari(
                $this->randevu->durumDegistir(
                    $this->kullanici($istek),
                    (int) ($rota['id'] ?? 0),
                    $yeni,
                    isset($veri['tamamlanma_notu']) ? (string) $veri['tamamlanma_notu'] : null,
                    $istek->istemciIp,
                    $istek->baslik('User-Agent') ?? ''
                )
            );
        } catch (Hata $hata) {
            Response::hata($hata->hataKodu, $hata->getMessage(), $hata->ayrintilar, $hata->httpDurum);
        }
    }

    /** @param array<string, string> $rota */
    public function iptal(Request $istek, array $rota = []): void
    {
        try {
            Response::basari(
                $this->randevu->durumDegistir($this->kullanici($istek), (int) ($rota['id'] ?? 0), 'iptal', null, $istek->istemciIp, $istek->baslik('User-Agent') ?? '')
            );
        } catch (Hata $hata) {
            Response::hata($hata->hataKodu, $hata->getMessage(), $hata->ayrintilar, $hata->httpDurum);
        }
    }

    /** @param array<string, string> $rota */
    public function ekipYuku(Request $istek, array $rota = []): void
    {
        $bitis = (string) ($istek->sorgu['bitis'] ?? date('Y-m-d'));
        $baslangic = (string) ($istek->sorgu['baslangic'] ?? date('Y-m-d', strtotime('-7 days')));
        Response::basari($this->randevu->ekipIsYuku($baslangic, $bitis));
    }

    /** @param array<string, string> $rota */
    public function ekipUyeleri(Request $istek, array $rota = []): void
    {
        Response::basari($this->kullanicilar->ekipUyeleriGetir());
    }

    /** @return array<string, mixed> */
    private function kullanici(Request $istek): array
    {
        if ($istek->kullanici === null) {
            throw new Hata('UNAUTHORIZED', 'Kimlik doğrulama gerekli.', [], 401);
        }

        return $istek->kullanici;
    }
}
