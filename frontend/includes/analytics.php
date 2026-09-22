<?php

declare(strict_types=1);

/**
 * GA4 + Consent Mode v2 (KVKK uyumlu).
 * Kural: GA4 ID gerçek değilse (placeholder) hiçbir şey basılmaz.
 * Kullanıcı kabul etmeden üçüncü-taraf script yüklenmez.
 */

function analyticsKafa(): string
{
    global $AYAR;
    $id = (string) ($AYAR['ga4_id'] ?? '');
    if ($id === '' || str_contains($id, 'XXX')) {
        return '';
    }

    $idEsc = htmlspecialchars($id, ENT_QUOTES, 'UTF-8');

    return <<<HTML
    <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('consent','default',{ad_storage:'denied',ad_user_data:'denied',ad_personalization:'denied',analytics_storage:'denied'});
    function kamelyaOlay(ad,veri){ if(typeof gtag==='function'){ gtag('event',ad,veri||{}); } }
    window.kamelyaYuklendi = false;
    window.kamelyaScriptYukle = function(){
      if(window.kamelyaYuklendi) return; window.kamelyaYuklendi = true;
      var s=document.createElement('script'); s.async=true;
      s.src='https://www.googletagmanager.com/gtag/js?id={$idEsc}';
      document.head.appendChild(s);
      gtag('js', new Date());
      gtag('config', '{$idEsc}');
      if(window.kamelyaClarity) window.kamelyaClarity();
      if(window.kamelyaCwv) window.kamelyaCwv();
    };
    window.kamelyaOnay = function(kabul){
      gtag('consent','update',{ad_storage:kabul?'granted':'denied',ad_user_data:kabul?'granted':'denied',ad_personalization:kabul?'granted':'denied',analytics_storage:kabul?'granted':'denied'});
      try{localStorage.setItem('kamelya-cerez',kabul?'kabul':'ret');}catch(e){}
      try{document.dispatchEvent(new CustomEvent('kamelya:onay',{detail:{kabul:kabul}}));}catch(e){}
      var b=document.getElementById('cerez-bandi'); if(b) b.hidden = true;
      if(kabul) window.kamelyaScriptYukle();
    };
    document.addEventListener('DOMContentLoaded',function(){
      var durum=null; try{durum=localStorage.getItem('kamelya-cerez');}catch(e){}
      var b=document.getElementById('cerez-bandi');
      if(durum==='kabul'){ if(b) b.hidden = true; window.kamelyaScriptYukle(); }
      else if(durum==='ret'){ if(b) b.hidden = true; }
      else if(b){ b.hidden = false; }
    });
    </script>
    HTML;
}

function cerezBand(): string
{
    global $AYAR, $dil;
    $id = (string) ($AYAR['ga4_id'] ?? '');
    if ($id === '' || str_contains($id, 'XXX')) {
        return '';
    }

    $metin = [
        'tr' => ['Çerezleri kabul ediyor musunuz? Analitik yalnızca onay verirseniz çalışır.', 'Kabul Et', 'Reddet'],
        'en' => ['Do you accept cookies? Analytics runs only with your consent.', 'Accept', 'Reject'],
        'de' => ['Stimmen Sie Cookies zu? Analyse läuft nur mit Zustimmung.', 'Annehmen', 'Ablehnen'],
        'fr' => ['Acceptez-vous les cookies ? La mesure exige votre consentement.', 'Accepter', 'Refuser'],
        'it' => ['Accetti i cookie? L’analisi richiede il consenso.', 'Accetta', 'Rifiuta'],
        'ar' => ['هل تقبل ملفات الارتباط؟ لا يعمل التحليل إلا بموافقتك.', 'قبول', 'رفض'],
    ];
    [$mesaj, $kabul, $ret] = $metin[$dil] ?? $metin['tr'];

    return '<div id="cerez-bandi" class="uyari" hidden>'
        . '<p>' . htmlspecialchars($mesaj, ENT_QUOTES, 'UTF-8') . '</p>'
        . '<p><button class="btn btn-birincil" type="button" onclick="kamelyaOnay(true)">'
        . htmlspecialchars($kabul, ENT_QUOTES, 'UTF-8')
        . '</button> <button class="btn btn-ikincil" type="button" onclick="kamelyaOnay(false)">'
        . htmlspecialchars($ret, ENT_QUOTES, 'UTF-8') . '</button></p></div>';
}
