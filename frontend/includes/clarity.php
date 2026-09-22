<?php

declare(strict_types=1);

/**
 * Microsoft Clarity (ücretsiz ısı haritası/oturum kaydı) — yalnızca
 * çerez kabulünde `kamelyaScriptYukle()` içinden çağrılır.
 * Hotjar/Mouseflow: ödemeli alternatif — [EK] notu raporda.
 */
function clarityBaslatici(): string
{
    global $AYAR;
    $ga = (string) ($AYAR['ga4_id'] ?? '');
    if ($ga === '' || str_contains($ga, 'XXX')) {
        return '';
    }

    $id = (string) ($AYAR['clarity_id'] ?? '');
    if ($id === '' || str_contains($id, 'XXX')) {
        return '';
    }

    $idEsc = htmlspecialchars($id, ENT_QUOTES, 'UTF-8');

    return <<<HTML
    <script>
    window.kamelyaClarity = function(){
      (function(c,l,a,r,i,t,y){
        c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
        t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
        y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
      })(window,document,"clarity","script","{$idEsc}");
    };
    </script>
    HTML;
}
