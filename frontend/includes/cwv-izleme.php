<?php

declare(strict_types=1);

/**
 * CWV izleme — web-vitals (sabit sürüm) LCP/INP/CLS ölçer, GA4'e event gönderir.
 * F4'teki "INP ölçülmedi" notunu kapatır. CDN erişilemezse sessizce atlanır.
 */
function cwvBaslatici(): string
{
    global $AYAR;
    $ga = (string) ($AYAR['ga4_id'] ?? '');
    if ($ga === '' || str_contains($ga, 'XXX')) {
        return '';
    }

    return <<<HTML
    <script>
    window.kamelyaCwv = function(){
      var s=document.createElement('script'); s.defer=true;
      s.src='https://unpkg.com/web-vitals@4.2.4/dist/web-vitals.attribution.iife.js';
      s.onload=function(){
        if(!window.webVitals) return;
        function gonder(m){ kamelyaOlay('cwv_olcumu',{metrik:m.name,deger:Math.round(m.value)}); }
        try{
          webVitals.onLCP(gonder); webVitals.onINP(gonder); webVitals.onCLS(gonder);
        }catch(e){}
      };
      document.head.appendChild(s);
    };
    </script>
    HTML;
}
