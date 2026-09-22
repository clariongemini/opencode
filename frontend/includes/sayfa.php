<?php

declare(strict_types=1);

/**
 * Sayfa düzeni: $SEO (baslik, aciklama, yol, jsonld?) + $sayfaBaslik + $icerikHtml.
 * Router, sayfa dosyasını tampona alıp bu düzenle sarar.
 */

function sayfaUst(array $seo): void
{
    global $AYAR, $dil;
    $meta = seoMeta($seo);    $yon = dilYonu();
    $dilKodu = htmlspecialchars($dil, ENT_QUOTES, 'UTF-8');
    $slogan = htmlspecialchars(t('site_slogan'), ENT_QUOTES, 'UTF-8');

    $gezinti = [
        ['nav_anasayfa', '/', 'anasayfa'],
        ['nav_urunler', '/urunler', 'urunler'],
        ['nav_galeri', '/galeri', 'galeri'],
        ['nav_blog', '/blog', 'blog'],
        ['nav_hakkimizda', '/hakkimizda', 'hakkimizda'],
        ['nav_iletisim', '/iletisim', 'iletisim'],
        ['nav_sss', '/sss', 'sss'],
    ];
    $baglantilar = '';
    foreach ($gezinti as [$anahtar, $yol, $ozellik]) {
        if (!etkinMi($ozellik)) {
            continue;
        }

        $baglantilar .= '<a href="' . htmlspecialchars(siteUrl($yol), ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars(t($anahtar), ENT_QUOTES, 'UTF-8') . '</a>';
    }

    $diller = '';
    foreach ($AYAR['diller'] as $d) {
        $aktif = $d === $dil ? ' class="aktif"' : '';
        $diller .= '<a href="' . htmlspecialchars(dilUrl($d), ENT_QUOTES, 'UTF-8') . '"' . $aktif . '>' . strtoupper($d) . '</a>';
    }

    $teklifButon = etkinMi('teklif_formu')
        ? '<p><a class="btn btn-birincil" href="' . $GLOBALS['teklifYolu'] . '">' . $GLOBALS['teklifMetni'] . '</a></p>'
        : '';

    $ekMeta = gscMeta() . analyticsKafa() . clarityBaslatici() . cwvBaslatici();
    $pwa = pwaKafa();
    $bantKampanya = kampanyaBand();

    echo <<<HTML
    <!DOCTYPE html>
    <html dir="{$yon}" lang="{$dilKodu}">
    <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      {$meta}
      <link rel="preconnect" href="https://fonts.googleapis.com">
      <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
      <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
      <link rel="stylesheet" href="/assets/css/tasarim-sistemi.css">
      {$ekMeta}
      {$pwa}
    </head>
    <body>
      <header class="ustbilgi">
        <div class="kapsayici ustbilgi-ici">
          <a class="logo" href="{$GLOBALS['anaYol']}">Kamelya <small>{$slogan}</small></a>
          <nav class="gezinti" aria-label="ana">{$baglantilar}</nav>
          <div class="dil-secici">{$diller}</div>
          <button id="arama-ac" type="button" aria-label="Ara">🔍</button>
          {$teklifButon}
        </div>
      </header>
      {$bantKampanya}
      <div id="arama-katman" class="modal" hidden>
        <div class="modal-icerik">
          <form id="arama-formu" action="{$GLOBALS['aramaYolu']}" method="get" data-api="{$GLOBALS['aramaApi']}">
            <div class="form-alan">
              <label class="etiket" for="arama-girdi">Ara</label>
              <input class="input" id="arama-girdi" name="q" minlength="3" autocomplete="off" required>
            </div>
            <div id="arama-oneri" aria-live="polite"></div>
          </form>
          <p><button id="arama-kapat" type="button" class="btn">Kapat</button></p>
        </div>
      </div>
      <main class="kapsayici">
    HTML;
}

function sayfaAlt(): void
{
    global $AYAR;
    $yil = date('Y');
    $wa = 'https://wa.me/' . $AYAR['whatsapp'];
    $bant = cerezBand();
    $pwaGovde = pwaGovde();
    $waButon = etkinMi('whatsapp_butonu')
        ? '<a class="whatsapp-sabit" href="' . $wa . '" aria-label="' . $GLOBALS['waMetni'] . '">✆</a>'
        : '';
    $telSatir = etkinMi('telefon_butonu')
        ? '<li><a href="tel:' . htmlspecialchars(str_replace(' ', '', $AYAR['telefon']), ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($AYAR['telefon'], ENT_QUOTES, 'UTF-8') . '</a></li>'
        : '';
    $hakkimizdaSatir = etkinMi('hakkimizda')
        ? '<a href="' . $GLOBALS['hakkimizdaYolu'] . '">' . $GLOBALS['hakkimizdaMetni'] . '</a><br>'
        : '';
    $iletisimSatir = etkinMi('iletisim')
        ? '<a href="' . $GLOBALS['iletisimYolu'] . '">' . $GLOBALS['iletisimMetni'] . '</a><br>'
        : '';
    $teklifSatir = etkinMi('teklif_formu')
        ? '<a href="' . $GLOBALS['teklifYolu'] . '">' . $GLOBALS['teklifMetni'] . '</a>'
        : '';
    $bultenFormu = etkinMi('bulten')
        ? '<form id="bulten-formu" data-api="' . $GLOBALS['bultenApi'] . '" data-dil="' . $GLOBALS['bultenDil'] . '">'
          . '<label class="etiket" for="b-eposta">Bülten</label>'
          . '<input class="input" id="b-eposta" name="eposta" type="email" required placeholder="e-posta">'
          . '<p><button class="btn btn-ikincil" type="submit">Abone Ol</button></p>'
          . '<div id="bulten-sonuc" aria-live="polite"></div></form>'
        : '';
    $telMetin = etkinMi('telefon_butonu') ? ' · ' . $AYAR['telefon'] : '';
    $sosyal = sosyalIkonlar();

    echo <<<HTML
      </main>
      <footer class="altbilgi">
        <div class="kapsayici altbilgi-ici">
          <div>
            <h2>Kamelya</h2>
            <p>{$AYAR['adres']}{$telMetin} · {$AYAR['eposta']}</p>
          </div>
          <div>
            <h2>{$GLOBALS['kurumsalBaslik']}</h2>
            <p>{$hakkimizdaSatir}
            <a href="{$GLOBALS['gizlilikYolu']}">{$GLOBALS['gizlilikMetni']}</a><br>
            <a href="{$GLOBALS['cerezYolu']}">Çerez Politikası</a><br>
            <a href="{$GLOBALS['odemeYolu']}">Ödeme Bilgileri</a></p>
          </div>
          <div>
            <h2>{$GLOBALS['iletisimBaslik']}</h2>
            <p>{$iletisimSatir}
            {$teklifSatir}</p>
            {$bultenFormu}
          </div>
        </div>
        <div class="kapsayici"><p>© {$yil} Kamelya · {$GLOBALS['haklarMetni']}</p></div>
        <div class="kapsayici">{$sosyal}</div>
      </footer>
      {$waButon}
      {$bant}
      {$pwaGovde}
      <script src="/assets/js/ana.js" defer></script>
      <script src="/assets/js/arama.js" defer></script>
      <script src="/assets/js/hesaplama-araci.js" defer></script>
      <script src="/assets/js/yorumlar.js" defer></script>
    </body>
    </html>
    HTML;
}
