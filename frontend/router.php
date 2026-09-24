<?php

declare(strict_types=1);

/**
 * Frontend router (php -S router): statik dosya → doğrudan sun;
 * /{dil}/önekli veya öföneksiz sayfa yollarını sayfa dosyalarına eşler.
 */

$yol = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

// Statik varlıklar doğrudan sunulur.
$aday = __DIR__ . $yol;
if ($yol !== '/' && is_file($aday) && !str_ends_with($yol, '.php')) {
    return false;
}

if ($yol === '/robots.txt') {
    header('Content-Type: text/plain; charset=utf-8');
    $koku = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    echo "User-agent: *\nAllow: /\nSitemap: {$koku}/sitemap.xml\n";

    exit;
}

$parcalar = array_values(array_filter(explode('/', trim($yol, '/'))));

$AYAR_GECICI = require __DIR__ . '/config.php';
$dil = $AYAR_GECICI['varsayilan_dil'];
if (isset($parcalar[0]) && in_array($parcalar[0], $AYAR_GECICI['diller'], true)) {
    $dil = array_shift($parcalar);
}

if (isset($_GET['lang']) && in_array($_GET['lang'], $AYAR_GECICI['diller'], true)) {
    $dil = $_GET['lang'];
}

$ilk = $parcalar[0] ?? '';
$ikinci = $parcalar[1] ?? null;

$sayfa = match (true) {
    $yol === '/sitemap.xml' || ($ilk === 'sitemap.xml') => 'sitemap',
    $parcalar === [] => 'anasayfa',
    $ilk === 'hakkimizda' => 'hakkimizda',
    $ilk === 'urunler' => 'urunler',
    $ilk === 'urun' && $ikinci !== null => 'urun-detay',
    $ilk === 'galeri' => 'galeri',
    $ilk === 'atolye' => 'atolye',
    $ilk === 'sanal-tur' => 'sanal-tur',
    $ilk === 'sicaklik-simulasyonu' => 'sicaklik-simulasyonu',
    $ilk === 'blog' && $ikinci !== null => 'blog-detay',
    $ilk === 'blog' => 'blog',
    $ilk === 'kamelya-fiyatlari' && $ikinci !== null => 'sehir-landing',
    $ilk === 'ahsap-kamelya' && $ikinci !== null => 'sehir-landing',
    $ilk === 'sehirler' => 'sehirler',
    $ilk === 'iletisim' => 'iletisim',
    $ilk === 'teklif-al' => 'teklif-al',
    $ilk === 'sss' => 'sss',
    $ilk === 'rehberler' && $ikinci === 'bakim' => 'rehber-bakim',
    $ilk === 'rehberler' && $ikinci === 'malzeme-karsilastirma' => 'rehber-malzeme',
    $ilk === 'rehberler' && $ikinci === 'fark' => 'rehber-fark',
    $ilk === 'rehberler' && $ikinci === 'istanbul-bakim-takvimi' => 'rehber-istanbul-bakim-takvimi',
    $ilk === 'referanslar' => 'referanslar',
    $ilk === 'gizlilik' => 'gizlilik',
    $ilk === 'cerez-politikasi' => 'cerez-politikasi',
    $ilk === 'karsilastir' => 'karsilastir',
    $ilk === 'sertifikalar' => 'sertifikalar',
    $ilk === 'ekibimiz' => 'ekibimiz',
    $ilk === 'garanti' => 'garanti',
    $ilk === 'arama' => 'arama',
    $ilk === 'odeme-bilgileri' => 'odeme-bilgileri',
    $ilk === 'kampanyalar' => 'kampanyalar',
    $ilk === 'bulten' && $ikinci === 'onay' => 'bulten-onay',
    default => null,
};

if ($sayfa === null) {
    http_response_code(404);
    $dil = $dil;
    $MEVCUT_YOL = '/';
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/ozellik.php';
    require __DIR__ . '/includes/api.php';
    require __DIR__ . '/includes/seo.php';
    require __DIR__ . '/includes/analytics.php';
    require __DIR__ . '/includes/gsc-dogrulama.php';
    require __DIR__ . '/includes/clarity.php';
    require __DIR__ . '/includes/cwv-izleme.php';
    require __DIR__ . '/includes/sayfa.php';
    $SEO = ['baslik' => 'Sayfa Bulunamadı — Kamelya', 'aciklama' => 'Aradığınız sayfa bulunamadı.', 'yol' => '/', 'robots' => 'noindex, follow'];
    $GLOBALS['anaYol'] = siteUrl('/');
    $GLOBALS['teklifYolu'] = siteUrl('/teklif-al');
    $GLOBALS['teklifMetni'] = t('nav_teklif');
    $GLOBALS['kurumsalBaslik'] = t('footer_kurumsal');
    $GLOBALS['hakkimizdaYolu'] = siteUrl('/hakkimizda');
    $GLOBALS['hakkimizdaMetni'] = t('nav_hakkimizda');
$GLOBALS['gizlilikYolu'] = siteUrl('/gizlilik');
$GLOBALS['cerezYolu'] = siteUrl('/cerez-politikasi');
    $GLOBALS['odemeYolu'] = siteUrl('/odeme-bilgileri');
    $GLOBALS['gizlilikMetni'] = t('footer_kvkk');
    $GLOBALS['iletisimYolu'] = siteUrl('/iletisim');
    $GLOBALS['iletisimMetni'] = t('nav_iletisim');
    $GLOBALS['iletisimBaslik'] = t('footer_iletisim');
    $GLOBALS['haklarMetni'] = t('footer_haklar');
    $GLOBALS['waMetni'] = t('whatsapp_yazi');
    $GLOBALS['bultenApi'] = $AYAR['api_taban'];
    $GLOBALS['bultenDil'] = $dil;
    $GLOBALS['aramaYolu'] = siteUrl('/arama');
    $GLOBALS['aramaApi'] = $AYAR['api_taban'];
    sayfaUst($SEO);
    echo '<h1>404</h1><p><a href="' . htmlspecialchars(siteUrl('/'), ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars(t('anasayfa'), ENT_QUOTES, 'UTF-8') . '</a></p>';
    sayfaAlt();

    exit;
}

$MEVCUT_YOL = '/' . implode('/', $parcalar);
$SLUG = ($sayfa === 'urun-detay' || $sayfa === 'blog-detay' || $sayfa === 'sehir-landing') ? ($ikinci ?? '') : null;

require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/ozellik.php';
require __DIR__ . '/includes/api.php';
require __DIR__ . '/includes/seo.php';
    require __DIR__ . '/includes/bilesenler.php';
    require __DIR__ . '/includes/yorumlar.php';
require __DIR__ . '/includes/analytics.php';
require __DIR__ . '/includes/gsc-dogrulama.php';
require __DIR__ . '/includes/clarity.php';
require __DIR__ . '/includes/cwv-izleme.php';
require __DIR__ . '/includes/pwa.php';
require __DIR__ . '/includes/sayfa.php';

$GLOBALS['anaYol'] = siteUrl('/');
$GLOBALS['teklifYolu'] = siteUrl('/teklif-al');
$GLOBALS['teklifMetni'] = t('nav_teklif');
$GLOBALS['kurumsalBaslik'] = t('footer_kurumsal');
$GLOBALS['hakkimizdaYolu'] = siteUrl('/hakkimizda');
$GLOBALS['hakkimizdaMetni'] = t('nav_hakkimizda');
$GLOBALS['gizlilikYolu'] = siteUrl('/gizlilik');
$GLOBALS['gizlilikMetni'] = t('footer_kvkk');
$GLOBALS['cerezYolu'] = siteUrl('/cerez-politikasi');
$GLOBALS['odemeYolu'] = siteUrl('/odeme-bilgileri');
$GLOBALS['iletisimYolu'] = siteUrl('/iletisim');
$GLOBALS['iletisimMetni'] = t('nav_iletisim');
$GLOBALS['iletisimBaslik'] = t('footer_iletisim');
$GLOBALS['haklarMetni'] = t('footer_haklar');
$GLOBALS['waMetni'] = t('whatsapp_yazi');
$GLOBALS['inceleMetni'] = t('urun_incele');
$GLOBALS['urunListeYolu'] = siteUrl('/urun');
$GLOBALS['hesapBaslik'] = t('hesap_baslik');
$GLOBALS['hesapGenislik'] = t('hesap_genislik');
$GLOBALS['hesapDerinlik'] = t('hesap_derinlik');
$GLOBALS['hesapAlan'] = t('hesap_alan');
$GLOBALS['hesapMalzeme'] = t('hesap_malzeme');
$GLOBALS['hesapModel'] = t('hesap_model');
$GLOBALS['hesapKullanim'] = t('hesap_kullanim');
$GLOBALS['hesapButon'] = t('hesap_buton');
$GLOBALS['hesapTeklifAl'] = t('hesap_teklif_al');
$GLOBALS['hesapHata'] = t('hesap_hata');
$GLOBALS['bultenApi'] = $AYAR['api_taban'];
$GLOBALS['bultenDil'] = $dil;
$GLOBALS['aramaYolu'] = siteUrl('/arama');
$GLOBALS['aramaApi'] = $AYAR['api_taban'];

if ($sayfa === 'sitemap') {
    header('Content-Type: application/xml; charset=utf-8');
    $taban = rtrim($AYAR['site_taban'], '/');
    $bolumler = ['', '/hakkimizda', '/atolye', '/urunler', '/galeri', '/blog', '/iletisim', '/teklif-al', '/sss', '/rehberler/bakim', '/rehberler/malzeme-karsilastirma', '/rehberler/fark', '/rehberler/istanbul-bakim-takvimi', '/referanslar', '/gizlilik', '/cerez-politikasi', '/sertifikalar', '/ekibimiz', '/garanti', '/odeme-bilgileri', '/kampanyalar', '/sanal-tur', '/sicaklik-simulasyonu'];
    $bolumAnahtar = [
        '' => 'anasayfa', '/hakkimizda' => 'hakkimizda', '/urunler' => 'urunler',
        '/galeri' => 'galeri', '/blog' => 'blog', '/iletisim' => 'iletisim',
        '/teklif-al' => 'teklif_formu', '/sss' => 'sss',
        '/rehberler/bakim' => 'bakim_rehberi', '/rehberler/malzeme-karsilastirma' => 'malzeme_rehberi',
        '/rehberler/fark' => null, '/rehberler/istanbul-bakim-takvimi' => null,
        '/referanslar' => 'referanslar', '/gizlilik' => 'gizlilik', '/cerez-politikasi' => 'kvkk',
        '/sertifikalar' => 'sertifikasyon', '/ekibimiz' => 'ekip', '/garanti' => 'garanti',
        '/odeme-bilgileri' => null, '/kampanyalar' => 'kampanya',
        '/sanal-tur' => 'sanal_tur', '/sicaklik-simulasyonu' => null,
    ];
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach ($bolumler as $bolum) {
        $anahtar = $bolumAnahtar[$bolum] ?? null;
        if ($anahtar !== null && !etkinMi($anahtar)) {
            continue;
        }

        foreach ($AYAR['diller'] as $d) {
            $onek = $d === 'tr' ? '' : '/' . $d;
            echo '  <url><loc>' . htmlspecialchars($taban . $onek . ($bolum === '' ? '/' : $bolum), ENT_QUOTES, 'UTF-8') . '</loc></url>' . "\n";
        }
    }

    foreach ($AYAR['diller'] as $d) {
        $liste = apiGet('/blog', $d, ['limit' => '100']);
        foreach ($liste['data'] ?? [] as $yazi) {
            $onek = $d === 'tr' ? '' : '/' . $d;
            echo '  <url><loc>' . htmlspecialchars($taban . $onek . '/blog/' . $yazi['slug'], ENT_QUOTES, 'UTF-8') . '</loc></url>' . "\n";
        }
    }

    foreach ($AYAR['diller'] as $d) {
        $liste = apiGet('/sehirler', $d);
        foreach ($liste['data'] ?? [] as $sehir) {
            $onek = $d === 'tr' ? '' : '/' . $d;
            $slug = $sehir['slug'] ?? $sehir['kod'];
            echo '  <url><loc>' . htmlspecialchars($taban . $onek . '/kamelya-fiyatlari/' . $slug, ENT_QUOTES, 'UTF-8') . '</loc></url>' . "\n";
        }
    }

    foreach ($AYAR['diller'] as $d) {
        $liste = apiGet('/products', $d, ['per_page' => '100']);
        foreach ($liste['data'] ?? [] as $urun) {
            $onek = $d === 'tr' ? '' : '/' . $d;
            echo '  <url><loc>' . htmlspecialchars($taban . $onek . '/urun/' . $urun['slug'], ENT_QUOTES, 'UTF-8') . '</loc></url>' . "\n";
        }
    }

    echo '</urlset>';

    exit;
}

// Özellik kapısı (bootstrap sonrası — $AYAR gerekli): eşleşen anahtar
// kapalıysa 410 + 410 sayfası. Eşleşmeyen yollar her zaman açıktır.
$ozellikMap = [
    'anasayfa' => 'anasayfa', 'hakkimizda' => 'hakkimizda', 'urunler' => 'urunler',
    'urun-detay' => 'urun_detay', 'karsilastir' => 'urun_karsilastirma',
    'galeri' => 'galeri', 'blog' => 'blog', 'blog-detay' => 'blog_detay', 'iletisim' => 'iletisim',
    'sanal-tur' => 'sanal_tur', 'sicaklik-simulasyonu' => null,
    'sehir-landing' => null, 'sehirler' => null,
    'teklif-al' => 'teklif_formu', 'sss' => 'sss',
    'rehber-bakim' => 'bakim_rehberi', 'rehber-malzeme' => 'malzeme_rehberi',
    'rehber-fark' => null, 'rehber-istanbul-bakim-takvimi' => null,
    'referanslar' => 'referanslar', 'gizlilik' => 'gizlilik',
    'cerez-politikasi' => 'kvkk', 'sertifikalar' => 'sertifikasyon',
    'ekibimiz' => 'ekip', 'garanti' => 'garanti',
    'odeme-bilgileri' => null, 'arama' => null, 'bulten-onay' => 'bulten',
    'kampanyalar' => 'kampanya',
];
if ($sayfa !== null && $sayfa !== 'sitemap' && array_key_exists($sayfa, $ozellikMap)
    && $ozellikMap[$sayfa] !== null && !etkinMi($ozellikMap[$sayfa])) {
    http_response_code(410);
    $sayfa = '410';
}

ob_start();
require __DIR__ . '/sayfalar/' . $sayfa . '.php';
$icerikHtml = (string) ob_get_clean();

sayfaUst($SEO ?? ['baslik' => 'Kamelya', 'aciklama' => t('site_slogan'), 'yol' => $MEVCUT_YOL]);
echo $icerikHtml;
sayfaAlt();
