<?php

declare(strict_types=1);

namespace Kamelya\Controllers\Api\V1\Admin;

use Kamelya\Core\Database;
use Kamelya\Core\Hata;
use Kamelya\Core\Request;
use Kamelya\Core\Response;
use Kamelya\Repositories\Admin\BlogYonetimRepository;
use Kamelya\Repositories\Admin\GaleriYonetimRepository;
use Kamelya\Repositories\Admin\ResimYonetimRepository;
use Kamelya\Repositories\Admin\UrunYonetimRepository;
use Kamelya\Services\Admin\YuklemeService;
use Kamelya\Services\AuditLogService;
use Kamelya\Validators\Admin\YuklemeValidator;

/** Admin görsel yükleme (multipart) + ürün görseli silme. */
final class AdminYuklemeController
{
    private YuklemeService $service;

    public function __construct()
    {
        $pdo = Database::baglanti();
        $this->service = new YuklemeService(
            $pdo,
            new ResimYonetimRepository($pdo),
            new UrunYonetimRepository($pdo),
            new BlogYonetimRepository($pdo),
            new GaleriYonetimRepository($pdo),
            new AuditLogService($pdo)
        );
    }

    /** @param array<string, string> $rota */
    public function yukle(Request $istek, array $rota = []): void
    {
        try {
            // Multipart gövde Request JSON çözümlemez — superglobal HTTP katmanında okunur.
            $dogrulama = YuklemeValidator::dogrula($_FILES['file'] ?? [], $_POST);
            if (!$dogrulama['gecerli']) {
                throw new Hata('VALIDATION_ERROR', 'Doğrulama hatası.', $dogrulama['hatalar'], 422);
            }

            $ekstra = [
                'tur' => $_POST['tur'] ?? 'normal',
                'baslik' => $_POST['baslik'] ?? null,
                'aciklama' => $_POST['aciklama'] ?? null,
                'proje_hikayesi' => $_POST['proje_hikayesi'] ?? null,
                'dil_kodu' => $_POST['dil_kodu'] ?? 'tr',
            ];

            $sonuc = $this->service->yukle(
                $this->kullanici($istek),
                $_FILES['file'],
                (string) $_POST['hedef'],
                (int) ($_POST['hedef_id'] ?? 0),
                $ekstra,
                $istek->istemciIp,
                $istek->baslik('User-Agent') ?? ''
            );
            Response::basari($sonuc, null, 201);
        } catch (Hata $hata) {
            Response::hata($hata->hataKodu, $hata->getMessage(), $hata->ayrintilar, $hata->httpDurum);
        }
    }

    /** @param array<string, string> $rota */
    public function resimSil(Request $istek, array $rota = []): void
    {
        try {
            $this->service->resimSil($this->kullanici($istek), (int) ($rota['id'] ?? 0), $istek->istemciIp, $istek->baslik('User-Agent') ?? '');
            Response::basari(['silindi' => true]);
        } catch (Hata $hata) {
            Response::hata($hata->hataKodu, $hata->getMessage(), $hata->ayrintilar, $hata->httpDurum);
        }
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
