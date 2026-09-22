<?php

declare(strict_types=1);

/** GSC doğrulama meta etiketi — ID placeholder ise basılmaz. */
function gscMeta(): string
{
    global $AYAR;
    $id = (string) ($AYAR['gsc_dogrulama'] ?? '');
    if ($id === '' || str_contains($id, 'XXX')) {
        return '';
    }

    return '<meta name="google-site-verification" content="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '">';
}
