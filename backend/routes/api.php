<?php

declare(strict_types=1);

use Kamelya\Controllers\Api\V1\Admin\AdminAyarController;
use Kamelya\Controllers\Api\V1\Admin\AdminBlogController;
use Kamelya\Controllers\Api\V1\Admin\AdminGaleriController;
use Kamelya\Controllers\Api\V1\Admin\AdminKapasiteController;
use Kamelya\Controllers\Api\V1\Admin\AdminKategoriController;
use Kamelya\Controllers\Api\V1\Admin\AdminSssController;
use Kamelya\Controllers\Api\V1\Admin\AdminUrunController;
use Kamelya\Controllers\Api\V1\Admin\AdminYuklemeController;
use Kamelya\Controllers\Api\V1\Admin\AdminYorumController;
use Kamelya\Controllers\Api\V1\Admin\AdminSertifikaController;
use Kamelya\Controllers\Api\V1\Admin\AdminEkipController;
use Kamelya\Controllers\Api\V1\Admin\AdminKampanyaController;
use Kamelya\Controllers\Api\V1\Admin\AdminOzellikController;
use Kamelya\Controllers\Api\V1\Admin\AdminSehirController;
use Kamelya\Controllers\Api\V1\Admin\AdminAtolyeController;
use Kamelya\Controllers\Api\V1\Admin\AdminSanalTurController;
use Kamelya\Controllers\Api\V1\Admin\AdminDonusumController;
use Kamelya\Controllers\Api\V1\Admin\AdminVideoRefController;
use Kamelya\Controllers\Api\V1\AtolyeController;
use Kamelya\Controllers\Api\V1\SanalTurController;
use Kamelya\Controllers\Api\V1\DonusumController;
use Kamelya\Controllers\Api\V1\VideoRefController;
use Kamelya\Controllers\Api\V1\KampanyaController;
use Kamelya\Controllers\Api\V1\OzellikController;
use Kamelya\Controllers\Api\V1\Admin\SeoDashboardController;
use Kamelya\Controllers\Api\V1\Admin\TakvimController;
use Kamelya\Controllers\Api\V1\Admin\AdminBultenController;
use Kamelya\Controllers\Api\V1\AyarController;
use Kamelya\Controllers\Api\V1\AramaController;
use Kamelya\Controllers\Api\V1\BlogController;
use Kamelya\Controllers\Api\V1\BultenController;
use Kamelya\Controllers\Api\V1\YorumController;
use Kamelya\Controllers\Api\V1\AdminController;
use Kamelya\Controllers\Api\V1\DosyaController;
use Kamelya\Controllers\Api\V1\AuthController;
use Kamelya\Controllers\Api\V1\FiyatController;
use Kamelya\Controllers\Api\V1\HealthController;
use Kamelya\Controllers\Api\V1\HesapController;
use Kamelya\Controllers\Api\V1\IletisimController;
use Kamelya\Controllers\Api\V1\EkipController;
use Kamelya\Controllers\Api\V1\KarsilastirmaController;
use Kamelya\Controllers\Api\V1\SehirController;
use Kamelya\Controllers\Api\V1\SertifikaController;
use Kamelya\Controllers\Api\V1\SssController;
use Kamelya\Controllers\Api\V1\RandevuController;
use Kamelya\Controllers\Api\V1\SeoController;
use Kamelya\Controllers\Api\V1\TalepController;
use Kamelya\Controllers\Api\V1\UrunController;
use Kamelya\Core\Router;
use Kamelya\Middleware\AuthMiddleware;
use Kamelya\Middleware\CsrfMiddleware;
use Kamelya\Middleware\RbacMiddleware;

return function (Router $yonlendirici): void {
    $yonlendirici->ekle('GET', '/api/v1/health', HealthController::class, 'saglik');
    $yonlendirici->ekle('GET', '/api/v1/products', UrunController::class, 'liste');
    $yonlendirici->ekle('GET', '/api/v1/products/{id}', UrunController::class, 'detay');
    $yonlendirici->ekle('POST', '/api/v1/calculate', HesapController::class, 'hesapla');
    $yonlendirici->ekle('POST', '/api/v1/leads', TalepController::class, 'olustur');
    $yonlendirici->ekle('POST', '/api/v1/appointments', RandevuController::class, 'olustur');
    $yonlendirici->ekle('GET', '/api/v1/pricing', FiyatController::class, 'liste');
    $yonlendirici->ekle('GET', '/api/v1/seo/check', SeoController::class, 'kontrol');
    $yonlendirici->ekle('POST', '/api/v1/yorumlar', YorumController::class, 'olustur');
    $yonlendirici->ekle('GET', '/api/v1/yorumlar', YorumController::class, 'liste');
    $yonlendirici->ekle('GET', '/api/v1/yorumlar/ozet', YorumController::class, 'ozet');
    $yonlendirici->ekle('POST', '/api/v1/iletisim', IletisimController::class, 'gonder');
    $yonlendirici->ekle('GET', '/api/v1/karsilastir', KarsilastirmaController::class, 'karsilastir');
    $yonlendirici->ekle('GET', '/api/v1/sertifikalar', SertifikaController::class, 'liste');
    $yonlendirici->ekle('GET', '/api/v1/ekip', EkipController::class, 'liste');
    $yonlendirici->ekle('GET', '/api/v1/ayarlar', AyarController::class, 'liste');
    $yonlendirici->ekle('GET', '/api/v1/kampanyalar', KampanyaController::class, 'liste');
    $yonlendirici->ekle('GET', '/api/v1/atolye', AtolyeController::class, 'liste');
    $yonlendirici->ekle('GET', '/api/v1/sanal-tur', SanalTurController::class, 'liste');
    $yonlendirici->ekle('GET', '/api/v1/donusumler', DonusumController::class, 'liste');
    $yonlendirici->ekle('GET', '/api/v1/video-referanslar', VideoRefController::class, 'liste');
    $yonlendirici->ekle('GET', '/api/v1/sehirler', SehirController::class, 'liste');
    $yonlendirici->ekle('GET', '/api/v1/sehirler/{slug}', SehirController::class, 'detay');
    $yonlendirici->ekle('GET', '/api/v1/ozellikler', OzellikController::class, 'harita');
    $yonlendirici->ekle('GET', '/api/v1/arama', AramaController::class, 'ara');
    $yonlendirici->ekle('GET', '/api/v1/sss-sorulari', SssController::class, 'liste');
    $yonlendirici->ekle('GET', '/api/v1/blog', BlogController::class, 'liste');
    $yonlendirici->ekle('GET', '/api/v1/blog/{slug}', BlogController::class, 'detay');
    $yonlendirici->ekle('POST', '/api/v1/bulten', BultenController::class, 'kaydol');
    $yonlendirici->ekle('GET', '/api/v1/bulten/onay', BultenController::class, 'onayla');
    $yonlendirici->ekle('GET', '/api/v1/bulten/iptal', BultenController::class, 'iptal');

    $yonlendirici->ekle('POST', '/api/v1/auth/login', AuthController::class, 'giris');
    $yonlendirici->ekle('POST', '/api/v1/auth/refresh', AuthController::class, 'yenile');
    $yonlendirici->ekle('POST', '/api/v1/auth/logout', AuthController::class, 'cikis');

    // Admin grubu: grup düzeyi auth + CSRF; rota düzeyi rol matrisi.
    // İçerik CRUD → yonetici|editor · talepler → yonetici|satis · audit-logs → yonetici.
    // (F5 GET /urunler ucu AdminUrunController::liste ile aynı sözleşmede birleştirildi.)
    $yonlendirici->grup('/api/v1/admin', [[AuthMiddleware::class], [CsrfMiddleware::class]], function (Router $r): void {
        $icerik = [[RbacMiddleware::class, ['yonetici', 'editor']]];
        $r->ekle('GET', '/urunler', AdminUrunController::class, 'liste', $icerik);
        $r->ekle('POST', '/urunler', AdminUrunController::class, 'olustur', $icerik);
        $r->ekle('GET', '/urunler/{id}', AdminUrunController::class, 'detay', $icerik);
        $r->ekle('PUT', '/urunler/{id}', AdminUrunController::class, 'guncelle', $icerik);
        $r->ekle('DELETE', '/urunler/{id}', AdminUrunController::class, 'sil', $icerik);
        $r->ekle('POST', '/urunler/{id}/video', AdminUrunController::class, 'videoEkle', $icerik);
        $r->ekle('GET', '/kategoriler', AdminKategoriController::class, 'liste', $icerik);
        $r->ekle('POST', '/kategoriler', AdminKategoriController::class, 'olustur', $icerik);
        $r->ekle('PUT', '/kategoriler/{id}', AdminKategoriController::class, 'guncelle', $icerik);
        $r->ekle('DELETE', '/kategoriler/{id}', AdminKategoriController::class, 'sil', $icerik);

        // Kapasite çarpanları (F15) — rol: yonetici|editor.
        $r->ekle('GET', '/kapasite', AdminKapasiteController::class, 'liste', $icerik);
        $r->ekle('POST', '/kapasite', AdminKapasiteController::class, 'olustur', $icerik);
        $r->ekle('PUT', '/kapasite/{id}', AdminKapasiteController::class, 'guncelle', $icerik);
        $r->ekle('DELETE', '/kapasite/{id}', AdminKapasiteController::class, 'sil', $icerik);
        $r->ekle('GET', '/blog-yazilari', AdminBlogController::class, 'liste', $icerik);
        $r->ekle('POST', '/blog-yazilari', AdminBlogController::class, 'olustur', $icerik);
        $r->ekle('GET', '/blog-yazilari/{id}', AdminBlogController::class, 'detay', $icerik);
        $r->ekle('PUT', '/blog-yazilari/{id}', AdminBlogController::class, 'guncelle', $icerik);
        $r->ekle('DELETE', '/blog-yazilari/{id}', AdminBlogController::class, 'sil', $icerik);
        $r->ekle('GET', '/sss-sorulari', AdminSssController::class, 'liste', $icerik);
        $r->ekle('POST', '/sss-sorulari', AdminSssController::class, 'olustur', $icerik);
        $r->ekle('PUT', '/sss-sorulari/{id}', AdminSssController::class, 'guncelle', $icerik);
        $r->ekle('DELETE', '/sss-sorulari/{id}', AdminSssController::class, 'sil', $icerik);
        $r->ekle('GET', '/galeri', AdminGaleriController::class, 'liste', $icerik);
        $r->ekle('POST', '/galeri', AdminGaleriController::class, 'olustur', $icerik);
        $r->ekle('PUT', '/galeri/{id}', AdminGaleriController::class, 'guncelle', $icerik);
        $r->ekle('DELETE', '/galeri/{id}', AdminGaleriController::class, 'sil', $icerik);
        $r->ekle('GET', '/ayarlar', AdminAyarController::class, 'liste', $icerik);
        $r->ekle('PUT', '/ayarlar', AdminAyarController::class, 'guncelle', $icerik);
        $r->ekle('POST', '/upload', AdminYuklemeController::class, 'yukle', $icerik);
        $r->ekle('DELETE', '/upload/{id}', AdminYuklemeController::class, 'resimSil', $icerik);
        $r->ekle('GET', '/talepler', AdminController::class, 'talepler', [[RbacMiddleware::class, ['yonetici', 'satis']]]);
        $r->ekle('PUT', '/talepler/{id}/durum', AdminController::class, 'talepDurum', [[RbacMiddleware::class, ['yonetici', 'satis']]]);
        $r->ekle('GET', '/audit-logs', AdminController::class, 'denetimKayitlari', [[RbacMiddleware::class, ['yonetici']]]);
        $r->ekle('GET', '/bulten', AdminBultenController::class, 'liste', $icerik);
        $r->ekle('POST', '/bulten/toplu', AdminBultenController::class, 'toplu', $icerik);

        // Operasyon takvimi (F7.3) — rol: yonetici|satis.
        $operasyon = [[RbacMiddleware::class, ['yonetici', 'satis']]];
        $r->ekle('GET', '/takvim/aylik', TakvimController::class, 'aylik', $operasyon);
        $r->ekle('GET', '/takvim/gun/{tarih}', TakvimController::class, 'gun', $operasyon);
        $r->ekle('GET', '/takvim/yaklasan', TakvimController::class, 'yaklasan', $operasyon);
        $r->ekle('POST', '/takvim/randevu', TakvimController::class, 'randevuOlustur', $operasyon);
        $r->ekle('PUT', '/takvim/randevu/{id}', TakvimController::class, 'randevuGuncelle', $operasyon);
        $r->ekle('PATCH', '/takvim/randevu/{id}/durum', TakvimController::class, 'durumDegistir', $operasyon);
        $r->ekle('DELETE', '/takvim/randevu/{id}', TakvimController::class, 'iptal', $operasyon);
        $r->ekle('GET', '/takvim/ekip-yuku', TakvimController::class, 'ekipYuku', $operasyon);
        $r->ekle('GET', '/takvim/ekip-uyeleri', TakvimController::class, 'ekipUyeleri', $operasyon);

        // Yorum moderasyonu (F9) — rol: yonetici|editor.
        $r->ekle('GET', '/yorumlar', AdminYorumController::class, 'kuyruk', $icerik);
        $r->ekle('GET', '/yorumlar/{id}', AdminYorumController::class, 'detay', $icerik);
        $r->ekle('PUT', '/yorumlar/{id}/onayla', AdminYorumController::class, 'onayla', $icerik);
        $r->ekle('PUT', '/yorumlar/{id}/reddet', AdminYorumController::class, 'reddet', $icerik);
        $r->ekle('PUT', '/yorumlar/{id}/one-cikan', AdminYorumController::class, 'oneCikan', $icerik);
        $r->ekle('DELETE', '/yorumlar/{id}', AdminYorumController::class, 'sil', $icerik);
        $r->ekle('GET', '/sertifikalar', AdminSertifikaController::class, 'liste', $icerik);
        $r->ekle('POST', '/sertifikalar', AdminSertifikaController::class, 'olustur', $icerik);
        $r->ekle('PUT', '/sertifikalar/{id}', AdminSertifikaController::class, 'guncelle', $icerik);
        $r->ekle('DELETE', '/sertifikalar/{id}', AdminSertifikaController::class, 'sil', $icerik);
        $r->ekle('GET', '/ekip', AdminEkipController::class, 'liste', $icerik);
        $r->ekle('PUT', '/ekip/{id}', AdminEkipController::class, 'guncelle', $icerik);
        $r->ekle('GET', '/kampanyalar', AdminKampanyaController::class, 'liste', $icerik);
        $r->ekle('POST', '/kampanyalar', AdminKampanyaController::class, 'olustur', $icerik);
        $r->ekle('PUT', '/kampanyalar/{id}', AdminKampanyaController::class, 'guncelle', $icerik);
        $r->ekle('DELETE', '/kampanyalar/{id}', AdminKampanyaController::class, 'sil', $icerik);
        $r->ekle('GET', '/ozellikler', AdminOzellikController::class, 'agac', [[RbacMiddleware::class, ['yonetici']]]);
        $r->ekle('PUT', '/ozellikler/{anahtar}', AdminOzellikController::class, 'degistir', [[RbacMiddleware::class, ['yonetici']]]);
        $r->ekle('PUT', '/ozellikler/{anahtar}/cocuklar', AdminOzellikController::class, 'toplu', [[RbacMiddleware::class, ['yonetici']]]);
        $r->ekle('GET', '/sehirler', AdminSehirController::class, 'liste', $icerik);
        $r->ekle('PUT', '/sehirler/{id}/icerik', AdminSehirController::class, 'icerikKaydet', $icerik);
        $r->ekle('POST', '/sehirler/{id}/bolge', AdminSehirController::class, 'bolgeEkle', $icerik);
        $r->ekle('DELETE', '/sehirler/bolge/{bolgeId}', AdminSehirController::class, 'bolgeSil', $icerik);
        $r->ekle('GET', '/atolye', AdminAtolyeController::class, 'liste', $icerik);
        $r->ekle('POST', '/atolye', AdminAtolyeController::class, 'yukle', $icerik);
        $r->ekle('DELETE', '/atolye/{id}', AdminAtolyeController::class, 'sil', $icerik);
        $r->ekle('GET', '/sanal-tur', AdminSanalTurController::class, 'liste', $icerik);
        $r->ekle('POST', '/sanal-tur', AdminSanalTurController::class, 'olustur', $icerik);
        $r->ekle('PUT', '/sanal-tur/{id}', AdminSanalTurController::class, 'guncelle', $icerik);
        $r->ekle('DELETE', '/sanal-tur/{id}', AdminSanalTurController::class, 'sil', $icerik);
        $r->ekle('GET', '/donusumler', AdminDonusumController::class, 'liste', $icerik);
        $r->ekle('POST', '/donusumler', AdminDonusumController::class, 'olustur', $icerik);
        $r->ekle('PUT', '/donusumler/{id}', AdminDonusumController::class, 'guncelle', $icerik);
        $r->ekle('DELETE', '/donusumler/{id}', AdminDonusumController::class, 'sil', $icerik);
        $r->ekle('GET', '/video-referanslar', AdminVideoRefController::class, 'liste', $icerik);
        $r->ekle('POST', '/video-referanslar', AdminVideoRefController::class, 'olustur', $icerik);
        $r->ekle('PUT', '/video-referanslar/{id}', AdminVideoRefController::class, 'guncelle', $icerik);
        $r->ekle('DELETE', '/video-referanslar/{id}', AdminVideoRefController::class, 'sil', $icerik);

        // SEO Dashboard (F7.2) — salt-okunur + senkron; rol: yonetici.
        $seoYonetici = [[RbacMiddleware::class, ['yonetici']]];
        $r->ekle('GET', '/seo/ozet', SeoDashboardController::class, 'ozet', $seoYonetici);
        $r->ekle('GET', '/seo/sorgular', SeoDashboardController::class, 'sorgular', $seoYonetici);
        $r->ekle('GET', '/seo/sayfalar', SeoDashboardController::class, 'sayfalar', $seoYonetici);
        $r->ekle('GET', '/seo/ulke-dagilimi', SeoDashboardController::class, 'ulkeDagilimi', $seoYonetici);
        $r->ekle('GET', '/seo/cihaz-dagilimi', SeoDashboardController::class, 'cihazDagilimi', $seoYonetici);
        $r->ekle('GET', '/seo/trend', SeoDashboardController::class, 'trend', $seoYonetici);
        $r->ekle('POST', '/seo/senkronize', SeoDashboardController::class, 'senkronize', $seoYonetici);
        $r->ekle('GET', '/seo/kelime-firsatlari', SeoDashboardController::class, 'firsatlar', $seoYonetici);
        $r->ekle('POST', '/seo/denetle', SeoDashboardController::class, 'denetle', $seoYonetici);
    });

    // Public dosya sunumu (public-dışı depo → güvenli akış).
    $yonlendirici->ekle('GET', '/api/v1/dosyalar/{tip}/{ad}', DosyaController::class, 'sun');
};
