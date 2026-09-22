<?php

declare(strict_types=1);

/** Müşteri yorumları bileşenleri — 6 dil satır içi sözlük (SSS deseni). */

function yorumSozluk(): array
{
    global $dil;
    $sozluk = [
        'tr' => ['Müşteri Yorumları', 'Yorum Yap', 'Ad Soyad', 'E-posta', 'Telefon (opsiyonel)', 'Puanınız', 'Başlık (opsiyonel)', 'Yorumunuz', 'KVKK metnini okudum, onaylıyorum.', 'Gönder', 'Yorumunuz onay için alındı. En kısa sürede yayınlanacak.', 'Gönderim başarısız.', 'Henüz yorum yok. İlk yorumu siz yapın!', 'yorum', 'Kapat'],
        'en' => ['Customer Reviews', 'Write a Review', 'Full Name', 'Email', 'Phone (optional)', 'Your Rating', 'Title (optional)', 'Your Review', 'I have read the KVKK notice and consent.', 'Send', 'Your review was received for approval.', 'Submission failed.', 'No reviews yet. Be the first!', 'reviews', 'Close'],
        'de' => ['Kundenbewertungen', 'Bewertung schreiben', 'Name', 'E-Mail', 'Telefon (optional)', 'Ihre Wertung', 'Titel (optional)', 'Ihre Bewertung', 'Ich habe die Hinweise gelesen und stimme zu.', 'Senden', 'Ihre Bewertung wurde zur Prüfung erhalten.', 'Senden fehlgeschlagen.', 'Noch keine Bewertungen.', 'Bewertungen', 'Schließen'],
        'fr' => ['Avis clients', 'Donner un avis', 'Nom complet', 'E-mail', 'Téléphone (optionnel)', 'Votre note', 'Titre (optionnel)', 'Votre avis', 'J’ai lu l’avis et je consens.', 'Envoyer', 'Votre avis a été reçu pour validation.', 'Échec.', 'Aucun avis pour le moment.', 'avis', 'Fermer'],
        'it' => ['Recensioni', 'Scrivi una recensione', 'Nome e cognome', 'E-mail', 'Telefono (facoltativo)', 'Il tuo voto', 'Titolo (facoltativo)', 'La tua recensione', 'Ho letto l’informativa e acconsento.', 'Invia', 'Recensione ricevuta per l’approvazione.', 'Invio fallito.', 'Ancora nessuna recensione.', 'recensioni', 'Chiudi'],
        'ar' => ['آراء العملاء', 'اكتب رأياً', 'الاسم الكامل', 'البريد', 'الهاتف (اختياري)', 'تقييمك', 'العنوان (اختياري)', 'رأيك', 'قرأت النص وأوافق.', 'إرسال', 'تم استلام رأيك للمراجعة.', 'فشل الإرسال.', 'لا توجد آراء بعد.', 'آراء', 'إغلاق'],
    ];

    return $sozluk[$dil] ?? $sozluk['tr'];
}

function yildizlar(float $puan): string
{
    $dolu = (int) round($puan);
    $cikti = '';
    for ($i = 1; $i <= 5; $i++) {
        $cikti .= $i <= $dolu ? '★' : '☆';
    }

    return '<span aria-label="' . $puan . ' / 5">' . $cikti . '</span>';
}

/** Ürün yorum bölümü: liste + ortalama + modal tetikleyici. */
function yorumBolumu(?int $urunId): string
{
    global $dil;
    [$baslik, $yap, $ad, $eposta, $tel, $puanEt, $baslikEt, $yorumEt, $kvkk, $gonder, $ok, $hata, $bos, $birim, $kapat] = yorumSozluk();

    $sorgu = ['limit' => '10'];
    if ($urunId !== null) {
        $sorgu['urun_id'] = (string) $urunId;
    }

    $sonuc = apiGet('/yorumlar', $dil, $sorgu);
    $satirlar = $sonuc['data'] ?? [];
    $ust = $sonuc['meta'] ?? [];
    $ortalama = (float) ($ust['ortalama_puan'] ?? 0);
    $toplam = (int) ($ust['total'] ?? 0);

    $liste = '';
    foreach ($satirlar as $y) {
        $liste .= '<article class="card"><div class="card-govde">'
            . '<p>' . yildizlar((float) $y['puan']) . ' <strong>'
            . htmlspecialchars($y['musteri_adi'] ?? '', ENT_QUOTES, 'UTF-8') . '</strong></p>'
            . '<p>' . htmlspecialchars($y['yorum'] ?? '', ENT_QUOTES, 'UTF-8') . '</p>'
            . '</div></article>';
    }

    if ($liste === '') {
        $liste = '<p>' . htmlspecialchars($bos, ENT_QUOTES, 'UTF-8') . '</p>';
    }

    $apiTaban = htmlspecialchars($GLOBALS['AYAR']['api_taban'], ENT_QUOTES, 'UTF-8');

    return <<<HTML
    <section aria-label="{$baslik}">
      <h2>{$baslik} ({$toplam} {$birim}) — {$ortalama}/5</h2>
      <p>{$liste}</p>
      <p><button class="btn btn-birincil" id="yorum-ac" type="button">{$yap}</button></p>
      <div id="yorum-modal" class="modal" hidden>
        <div class="modal-icerik">
          <h3>{$yap}</h3>
          <form id="yorum-formu" data-api="{$apiTaban}" data-dil="{$dil}" data-urun="{$urunId}">
            <div class="form-alan">
              <label class="etiket">{$ad}</label>
              <input class="input" name="musteri_adi" required minlength="2" maxlength="120">
            </div>
            <div class="form-alan">
              <label class="etiket">{$eposta}</label>
              <input class="input" name="eposta" type="email" required>
            </div>
            <div class="form-alan">
              <label class="etiket">{$tel}</label>
              <input class="input" name="telefon" inputmode="tel">
            </div>
            <div class="form-alan">
              <label class="etiket">{$puanEt}</label>
              <select class="input" name="puan">
                <option value="5">★★★★★ (5)</option>
                <option value="4">★★★★ (4)</option>
                <option value="3">★★★ (3)</option>
                <option value="2">★★ (2)</option>
                <option value="1">★ (1)</option>
              </select>
            </div>
            <div class="form-alan">
              <label class="etiket">{$baslikEt}</label>
              <input class="input" name="baslik" maxlength="190">
            </div>
            <div class="form-alan">
              <label class="etiket">{$yorumEt}</label>
              <textarea class="input" name="yorum" required maxlength="5000"></textarea>
            </div>
            <input type="text" name="web_sitesi" style="display:none" tabindex="-1" autocomplete="off" aria-hidden="true">
            <div class="form-alan">
              <label><input type="checkbox" name="kvkk_onayi" value="1" required> {$kvkk}</label>
            </div>
            <p><button class="btn btn-birincil" type="submit">{$gonder}</button>
            <button class="btn" type="button" id="yorum-kapat">{$kapat}</button></p>
            <div id="yorum-sonuc" aria-live="polite" data-ok="{$ok}" data-hata="{$hata}"></div>
          </form>
        </div>
      </div>
    </section>
    HTML;
}

/** Ana sayfa vitrini: öne çıkan onaylı yorumlar (en fazla 5). */
function oneCikanYorumlar(): string
{
    global $dil;
    [$baslik] = yorumSozluk();

    $sonuc = apiGet('/yorumlar', $dil, ['one_cikan' => '1', 'limit' => '5']);
    $satirlar = $sonuc['data'] ?? [];
    if ($satirlar === []) {
        return '';
    }

    $liste = '';
    foreach ($satirlar as $y) {
        $liste .= '<div class="card"><div class="card-govde"><p>'
            . yildizlar((float) $y['puan']) . ' <strong>'
            . htmlspecialchars($y['musteri_adi'] ?? '', ENT_QUOTES, 'UTF-8') . '</strong></p><p>'
            . htmlspecialchars($y['yorum'] ?? '', ENT_QUOTES, 'UTF-8') . '</p></div></div>';
    }

    return '<section><h2>' . htmlspecialchars($baslik, ENT_QUOTES, 'UTF-8') . '</h2>'
        . '<div class="izgara izgara-3">' . $liste . '</div></section>';
}
