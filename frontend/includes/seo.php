<?php

declare(strict_types=1);

/**
 * SEO yardımcısı — her sayfada zorunlu meta + hreflang + JSON-LD.
 * $SEO dizisi: baslik, aciklama, yol, tip, jsonld (hazır dizi), robots?
 */

function seoMeta(array $seo): string
{
    global $AYAR, $dil;
    $baslik = htmlspecialchars($seo['baslik'], ENT_QUOTES, 'UTF-8');
    $aciklama = htmlspecialchars($seo['aciklama'], ENT_QUOTES, 'UTF-8');
    $canonical = htmlspecialchars($seo['canonical'] ?? siteUrl($seo['yol']), ENT_QUOTES, 'UTF-8');
    $robots = htmlspecialchars($seo['robots'] ?? 'index, follow', ENT_QUOTES, 'UTF-8');
    $ogTuru = htmlspecialchars($seo['og_turu'] ?? 'website', ENT_QUOTES, 'UTF-8');
    $ogGorsel = isset($seo['og_gorsel']) && $seo['og_gorsel'] !== ''
        ? "\n    " . '<meta property="og:image" content="' . htmlspecialchars($seo['og_gorsel'], ENT_QUOTES, 'UTF-8') . '">'
        : '';

    $cikti = <<<HTML
    <title>{$baslik}</title>
    <meta name="description" content="{$aciklama}">
    <link rel="canonical" href="{$canonical}">
    <meta name="robots" content="{$robots}">
    <meta property="og:title" content="{$baslik}">
    <meta property="og:description" content="{$aciklama}">
    <meta property="og:type" content="{$ogTuru}">
    <meta property="og:url" content="{$canonical}">
    <meta property="og:site_name" content="Kamelya">{$ogGorsel}
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="{$baslik}">
    <meta name="twitter:description" content="{$aciklama}">
    HTML;

    foreach ($AYAR['diller'] as $d) {
        $cikti .= "\n    " . '<link rel="alternate" hreflang="' . $d . '" href="' . htmlspecialchars(dilUrl($d), ENT_QUOTES, 'UTF-8') . '">';
    }

    $cikti .= "\n    " . '<link rel="alternate" hreflang="x-default" href="' . htmlspecialchars(dilUrl('tr'), ENT_QUOTES, 'UTF-8') . '">';

    if (isset($seo['jsonld'])) {
        $json = json_encode($seo['jsonld'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $cikti .= "\n    " . '<script type="application/ld+json">' . $json . '</script>';
    }

    return $cikti;
}

function jsonldOrganizasyon(): array
{
    return [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'Organization',
                'name' => 'Kamelya',
                'url' => siteUrl('/'),
                'logo' => siteUrl('/assets/img/logo.png'),
            ],
            [
                '@type' => 'WebSite',
                'name' => 'Kamelya',
                'url' => siteUrl('/'),
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => siteUrl('/urunler?q={arama}'),
                    'query-input' => 'required name=arama',
                ],
            ],
        ],
    ];
}

function jsonldUrun(array $urun, string $dil, ?array $derecelendirme = null): array
{
    $teklif = null;
    if (isset($urun['price_hint']) && is_array($urun['price_hint'])) {
        $teklif = [
            '@type' => 'Offer',
            'priceCurrency' => $urun['price_hint']['currency'],
            'price' => $urun['price_hint']['base_m2'],
            'description' => t('urun_fiyat_notu'),
            'availability' => 'https://schema.org/MadeToOrder',
        ];
    }

    $veri = [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $urun['baslik'],
        'description' => kisaAciklama((string) ($urun['kisa_aciklama'] ?? $urun['baslik'])),
        'brand' => ['@type' => 'Brand', 'name' => 'Kamelya'],
        'url' => siteUrl('/urun/' . $urun['slug']),
    ];
    if ($teklif !== null) {
        $veri['offers'] = $teklif;
    }

    if ($derecelendirme !== null && ($derecelendirme['toplam_yorum'] ?? 0) > 0) {
        $veri['aggregateRating'] = [
            '@type' => 'AggregateRating',
            'ratingValue' => (string) $derecelendirme['ortalama_puan'],
            'reviewCount' => (string) $derecelendirme['toplam_yorum'],
        ];
    }

    $teknik = $urun['teknik_detaylar'] ?? null;
    if (is_array($teknik)) {
        $ozellikler = [];
        foreach (['cati_tipi' => 'çatı tipi', 'korkuluk_malzeme' => 'korkuluk malzeme'] as $alan => $ad) {
            if (!empty($teknik[$alan])) {
                $ozellikler[] = ['@type' => 'PropertyValue', 'name' => $ad, 'value' => $teknik[$alan]];
            }
        }

        if ($ozellikler !== []) {
            $veri['additionalProperty'] = $ozellikler;
        }
    }

    return $veri;
}

/** @param array<int, array{soru: string, cevap: string}> $ogeler */
function jsonldSss(array $ogeler): array
{
    $sorular = [];
    foreach ($ogeler as $oge) {
        $sorular[] = [
            '@type' => 'Question',
            'name' => $oge['soru'],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $oge['cevap']],
        ];
    }

    return ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $sorular];
}

function jsonldIletisimSayfasi(): array
{
    global $AYAR;

    return [
        '@context' => 'https://schema.org',
        '@type' => 'ContactPage',
        'name' => 'Teklif ve İletişim — Kamelya',
        'url' => siteUrl('/teklif-al'),
        'about' => ['@type' => 'Organization', 'name' => 'Kamelya', 'telephone' => $AYAR['telefon']],
    ];
}

/** @param array<string, mixed> $yazi */
function jsonldMakale(array $yazi): array
{
    return [
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => $yazi['baslik'] ?? '',
        'image' => $yazi['kapak_resmi'] ?? null,
        'datePublished' => substr((string) ($yazi['yayin_tarihi'] ?? ''), 0, 10),
        'author' => ['@type' => 'Person', 'name' => $yazi['yazar'] ?? 'Kamelya'],
        'publisher' => ['@type' => 'Organization', 'name' => 'Kamelya'],
    ];
}

function jsonldYerelIsletme(): array
{
    global $AYAR;

    return [
        '@context' => 'https://schema.org',
        '@type' => 'LocalBusiness',
        'name' => 'Kamelya',
        'telephone' => $AYAR['telefon'],
        'email' => $AYAR['eposta'],
        'address' => ['@type' => 'PostalAddress', 'addressLocality' => 'İstanbul', 'addressCountry' => 'TR'],
        'url' => siteUrl('/'),
    ];
}
