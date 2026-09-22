<?php

declare(strict_types=1);

/** Paylaşılan bileşenler: kart, akordeon, kırıntı, dil seçici, hesap aracı. */

function urunKarti(array $urun): string
{
    $baslik = htmlspecialchars($urun['baslik'] ?? '', ENT_QUOTES, 'UTF-8');
    $slug = htmlspecialchars($urun['slug'] ?? '', ENT_QUOTES, 'UTF-8');
    $ozet = htmlspecialchars(kisaAciklama((string) ($urun['kisa_aciklama'] ?? ''), 110), ENT_QUOTES, 'UTF-8');
    $gorsel = htmlspecialchars($urun['kapak_resmi'] ?? '/assets/img/yer-tutucu.svg', ENT_QUOTES, 'UTF-8');
    $kartId = (int) ($urun['id'] ?? 0);

    $rozet = '';
    if (isset($urun['price_hint']) && is_array($urun['price_hint'])) {
        $fiyat = htmlspecialchars((string) $urun['price_hint']['base_m2'], ENT_QUOTES, 'UTF-8');
        $birim = htmlspecialchars((string) $urun['price_hint']['currency'], ENT_QUOTES, 'UTF-8');
        $rozet = '<span class="rozet">' . $fiyat . ' ' . $birim . '/m²</span> <small>' . t('urun_fiyat_notu') . '</small>';
    }

    return <<<HTML
    <article class="card">
      <img class="card-gorsel" src="{$gorsel}" alt="{$baslik}" loading="lazy">
      <div class="card-govde">
        <h3>{$baslik}</h3>
        <p>{$ozet}</p>
        <p>{$rozet}</p>
        <p><a class="btn btn-ikincil" href="{$GLOBALS['urunListeYolu']}/{$slug}">{$GLOBALS['inceleMetni']}</a>
        <label><input type="checkbox" data-karsilastir="{$kartId}"> Karşılaştır</label></p>
      </div>
    </article>
    HTML;
}

function hesapAraci(): string
{
    $apiTaban = htmlspecialchars($GLOBALS['AYAR']['api_taban'], ENT_QUOTES, 'UTF-8');

    return <<<HTML
    <section class="hesap-araci" aria-label="{$GLOBALS['hesapBaslik']}">
      <h2>{$GLOBALS['hesapBaslik']}</h2>
      <form id="hesap-formu" data-api="{$apiTaban}" data-dil="{$GLOBALS['dil']}" data-teklif="{$GLOBALS['teklifYolu']}" data-teklif-metin="{$GLOBALS['hesapTeklifAl']}" data-hata="{$GLOBALS['hesapHata']}">
        <div class="izgara izgara-2">
          <div class="form-alan">
            <label class="etiket" for="h-genislik">{$GLOBALS['hesapGenislik']}</label>
            <input class="input" id="h-genislik" name="width" type="number" min="0.5" max="100" step="0.1" inputmode="decimal">
          </div>
          <div class="form-alan">
            <label class="etiket" for="h-derinlik">{$GLOBALS['hesapDerinlik']}</label>
            <input class="input" id="h-derinlik" name="length" type="number" min="0.5" max="100" step="0.1" inputmode="decimal">
          </div>
        </div>
        <div class="form-alan">
          <label class="etiket" for="h-alan">{$GLOBALS['hesapAlan']}</label>
          <input class="input" id="h-alan" name="area_m2" type="number" min="0.5" max="5000" step="0.1" inputmode="decimal">
        </div>
        <div class="izgara izgara-3">
          <div class="form-alan">
            <label class="etiket" for="h-malzeme">{$GLOBALS['hesapMalzeme']}</label>
            <select class="input" id="h-malzeme" name="material">
              <option value="ahsap">Ahşap</option>
              <option value="aluminyum">Alüminyum</option>
              <option value="kompozit">Kompozit</option>
            </select>
          </div>
          <div class="form-alan">
            <label class="etiket" for="h-model">{$GLOBALS['hesapModel']}</label>
            <select class="input" id="h-model" name="model">
              <option value="kare">Kare</option>
              <option value="altigen">Altıgen</option>
              <option value="dikdortgen">Dikdörtgen</option>
              <option value="modern">Modern</option>
              <option value="klasik">Klasik</option>
            </select>
          </div>
          <div class="form-alan">
            <label class="etiket" for="h-kullanim">{$GLOBALS['hesapKullanim']}</label>
            <select class="input" id="h-kullanim" name="usage">
              <option value="site_bahcesi">Site Bahçesi</option>
              <option value="restoran">Restoran</option>
              <option value="otel">Otel</option>
              <option value="belediye">Belediye</option>
            </select>
          </div>
        </div>
        <p><button class="btn btn-birincil" type="submit">{$GLOBALS['hesapButon']}</button></p>
      </form>
      <div id="hesap-sonuc" class="hesap-sonuc" aria-live="polite"></div>
    </section>
    HTML;
}

/** Ana sayfa sertifika bandı — ilk 4 aktif sertifika logosu. */
function sertifikaBand(): string
{
    if (!etkinMi('sertifikasyon')) {
        return '';
    }

    global $dil;
    $sonuc = apiGet('/sertifikalar', $dil);
    $satirlar = array_slice($sonuc['data'] ?? [], 0, 4);
    if ($satirlar === []) {
        return '';
    }

    $cikti = '<section aria-label="Sertifikalar"><div class="izgara izgara-4">';
    foreach ($satirlar as $s) {
        $cikti .= '<div class="card"><div class="card-govde"><p><strong>'
            . htmlspecialchars($s['kurum'] ?? '', ENT_QUOTES, 'UTF-8') . '</strong><br>'
            . htmlspecialchars($s['baslik'] ?? '', ENT_QUOTES, 'UTF-8') . '</p></div></div>';
    }

    return $cikti . '</div></section>';
}

/** Kampanya bandı — ilk aktif kampanya; kapatma 24 saat saklanır (JS). */
function kampanyaBand(): string
{
    global $dil;
    $sonuc = apiGet('/kampanyalar', $dil);
    $satirlar = $sonuc['data'] ?? [];
    if ($satirlar === []) {
        return '';
    }

    $k = $satirlar[0];
    $baslik = htmlspecialchars($k['baslik'] ?? '', ENT_QUOTES, 'UTF-8');
    $aciklama = htmlspecialchars($k['aciklama'] ?? '', ENT_QUOTES, 'UTF-8');
    $cta = htmlspecialchars($k['cta_metni'] ?? '', ENT_QUOTES, 'UTF-8');
    $id = (int) $k['id'];
    $gorsel = '';
    if (!empty($k['banner_gorsel'])) {
        $gorsel = '<img src="' . htmlspecialchars($k['banner_gorsel'], ENT_QUOTES, 'UTF-8') . '" alt="' . $baslik . '" loading="lazy" style="max-height:60px">';
    }

    return <<<HTML
    <div id="kampanya-band" class="uyari" data-kampanya="{$id}" style="margin:0;border-radius:0;text-align:center">
      {$gorsel}<strong>{$baslik}</strong> — {$aciklama}
      <button id="kampanya-kapat" type="button" aria-label="Kapat">×</button>
    </div>
    HTML;
}

/** Paylaşım butonları (FB, X, LinkedIn, WhatsApp). */
function paylasButonlari(string $url, string $baslik): string
{
    $u = urlencode($url);
    $b = urlencode($baslik);
    $baglantilar = [
        'Facebook' => 'https://www.facebook.com/sharer/sharer.php?u=' . $u,
        'X' => 'https://twitter.com/intent/tweet?url=' . $u . '&text=' . $b,
        'LinkedIn' => 'https://www.linkedin.com/sharing/share-offsite/?url=' . $u,
        'WhatsApp' => 'https://wa.me/?text=' . $b . '%20' . $u,
        'Pinterest' => 'https://pinterest.com/pin/create/button/?url=' . $u . '&description=' . $b,
    ];

    $cikti = '<p>Paylaş: ';
    foreach ($baglantilar as $ad => $href) {
        $cikti .= '<a href="' . $href . '" target="_blank" rel="noopener">' . $ad . '</a> ';
    }

    return $cikti . '</p>';
}

/** Footer sosyal ikonları — boş bırakılan ağlar gösterilmez. */
function sosyalIkonlar(): string
{
    $sonuc = apiGet('/ayarlar', $GLOBALS['dil'] ?? 'tr');
    $veri = $sonuc['data'] ?? [];
    $aglar = [
        'sosyal_facebook' => 'Facebook',
        'sosyal_instagram' => 'Instagram',
        'sosyal_twitter' => 'X',
        'sosyal_x' => 'X',
        'sosyal_linkedin' => 'LinkedIn',
        'sosyal_youtube' => 'YouTube',
        'sosyal_pinterest' => 'Pinterest',
    ];

    $cikti = '';
    $gorulen = [];
    foreach ($aglar as $anahtar => $ad) {
        $url = trim((string) ($veri[$anahtar] ?? ''));
        if ($url === '' || isset($gorulen[$ad])) {
            continue;
        }

        $gorulen[$ad] = true;
        $cikti .= '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener">' . $ad . '</a> ';
    }

    if ($cikti === '') {
        return '';
    }

    return '<p>Bizi takip edin: ' . $cikti . '</p>';
}

/** Instagram gömme bölümü — toggle + hesap + gönderi URL'leri ayarlardan. */
function instagramBolumu(): string
{
    $sonuc = apiGet('/ayarlar', $GLOBALS['dil'] ?? 'tr');
    $veri = $sonuc['data'] ?? [];
    if (trim((string) ($veri['instagram_embed_aktif'] ?? '')) !== '1') {
        return '';
    }

    $gonderiler = json_decode((string) ($veri['instagram_gonderiler'] ?? '[]'), true);
    if (!is_array($gonderiler)) {
        $gonderiler = [];
    }

    $gonderiler = array_slice(array_filter(array_map('trim', $gonderiler)), 0, 6);
    if ($gonderiler === []) {
        return '';
    }

    $cikti = '<section aria-label="Instagram"><h2>Instagram</h2>';
    foreach ($gonderiler as $url) {
        $cikti .= '<blockquote class="instagram-media" data-instgrm-permalink="'
            . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '"></blockquote>';
    }

    $cikti .= '</section>'
        . '<script>function kamelyaInstagram(){if(window.kamelyaInstagramYuklendi)return;'
        . 'window.kamelyaInstagramYuklendi=true;var s=document.createElement("script");'
        . 's.async=true;s.src="https://www.instagram.com/embed.js";document.body.appendChild(s);}'
        . 'try{if(localStorage.getItem("kamelya-cerez")==="kabul")kamelyaInstagram();}catch(e){}'
        . 'document.addEventListener("kamelya:onay",kamelyaInstagram);</script>';

    return $cikti;
}

/** @param array<int, array{soru: string, cevap: string}> $ogeler */
function sssAkordeon(array $ogeler): string
{
    $cikti = '';
    foreach ($ogeler as $i => $oge) {
        $soru = htmlspecialchars($oge['soru'], ENT_QUOTES, 'UTF-8');
        $cevap = htmlspecialchars($oge['cevap'], ENT_QUOTES, 'UTF-8');
        $cikti .= <<<HTML
        <div class="akordeon">
          <button class="akordeon-baslik" aria-expanded="false" data-akordeon="{$i}">{$soru}</button>
          <div class="akordeon-icerik" hidden>{$cevap}</div>
        </div>
        HTML;
    }

    return $cikti;
}

/** @param array<int, array{etiket: string, yol: string|null}> $ogeler */
function kirinti(array $ogeler): string
{
    $parcalar = [];
    foreach ($ogeler as $oge) {
        $etiket = htmlspecialchars($oge['etiket'], ENT_QUOTES, 'UTF-8');
        $parcalar[] = $oge['yol'] === null ? $etiket : '<a href="' . htmlspecialchars($oge['yol'], ENT_QUOTES, 'UTF-8') . '">' . $etiket . '</a>';
    }

    return '<nav class="kirinti" aria-label="breadcrumb">' . implode(' / ', $parcalar) . '</nav>';
}
