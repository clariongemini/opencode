<?php

declare(strict_types=1);

/** Tek giriş noktası (front controller) — public/ dışı web'den sunulmaz. */

use Kamelya\Core\Config;
use Kamelya\Core\Request;
use Kamelya\Core\Router;
use Kamelya\Middleware\CorsMiddleware;
use Kamelya\Middleware\ErrorHandlerMiddleware;
use Kamelya\Middleware\HttpsMiddleware;
use Kamelya\Middleware\RateLimitMiddleware;
use Kamelya\Middleware\SecurityHeadersMiddleware;

$kokuDizin = dirname(__DIR__);
$otomatikYukleyici = $kokuDizin . '/vendor/autoload.php';
if (!is_file($otomatikYukleyici)) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(
        [
            'success' => false,
            'error' => [
                'code' => 'BOOTSTRAP_ERROR',
                'message' => 'Otomatik yükleyici yok: backend/ dizininde `composer dump-autoload` çalıştırın.',
                'details' => [],
            ],
        ],
        JSON_UNESCAPED_UNICODE
    );

    exit(1);
}

require $otomatikYukleyici;

Config::yukle($kokuDizin);

// Oturum çerezi sıkılaştırma (gelecekteki panel oturumları için şimdiden kilitli).
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_only_cookies', '1');
if ((($_SERVER['HTTPS'] ?? '') === 'on') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')) {
    ini_set('session.cookie_secure', '1');
}

$yonlendirici = new Router();
$rotaTanimi = require $kokuDizin . '/routes/api.php';
$rotaTanimi($yonlendirici);

$istek = Request::yakala();

$hataYakalayici = new ErrorHandlerMiddleware();
$https = new HttpsMiddleware();
$cors = new CorsMiddleware();
$guvenlikBaslik = new SecurityHeadersMiddleware();
$hiz = new RateLimitMiddleware();

// Halka sırası: ErrorHandler → HTTPS → CORS → SecurityHeaders → RateLimit → Router.
$hataYakalayici->isle($istek, function (Request $birinci) use ($https, $cors, $guvenlikBaslik, $hiz, $yonlendirici): void {
    $https->isle($birinci, function (Request $ikinci) use ($cors, $guvenlikBaslik, $hiz, $yonlendirici): void {
        $cors->isle($ikinci, function (Request $ucuncu) use ($guvenlikBaslik, $hiz, $yonlendirici): void {
            $guvenlikBaslik->isle($ucuncu, function (Request $dorduncu) use ($hiz, $yonlendirici): void {
                $hiz->isle($dorduncu, function (Request $besinci) use ($yonlendirici): void {
                    $yonlendirici->dagit($besinci);
                });
            });
        });
    });
});
