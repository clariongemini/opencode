<?php

declare(strict_types=1);

/** İletişim — bilgi + form (POST /api/v1/iletisim; artık /leads'e değil). */

$KONULAR = [
    'tr' => ['genel' => 'Genel', 'teklif' => 'Teklif', 'sikayet' => 'Şikayet', 'diger' => 'Diğer'],
    'en' => ['genel' => 'General', 'teklif' => 'Quote', 'sikayet' => 'Complaint', 'diger' => 'Other'],
    'de' => ['genel' => 'Allgemein', 'teklif' => 'Angebot', 'sikayet' => 'Beschwerde', 'diger' => 'Sonstiges'],
    'fr' => ['genel' => 'Général', 'teklif' => 'Devis', 'sikayet' => 'Plainte', 'diger' => 'Autre'],
    'it' => ['genel' => 'Generale', 'teklif' => 'Preventivo', 'sikayet' => 'Reclamo', 'diger' => 'Altro'],
    'ar' => ['genel' => 'عام', 'teklif' => 'عرض سعر', 'sikayet' => 'شكوى', 'diger' => 'أخرى'],
];
$konuSecenek = $KONULAR[$dil] ?? $KONULAR['tr'];

$SEO = [
    'baslik' => 'Kamelya İletişim — Adres, Telefon ve Keşif Hattı Bilgileri',
    'aciklama' => 'Kamelya iletişim: İstanbul adres, telefon ve e-posta bilgileriyle ücretsiz keşif randevusu alın. Hafta içi 09:00–18:00 arasında hizmetinizdeyiz. Hemen arayın.',
    'yol' => $MEVCUT_YOL,
    'jsonld' => jsonldYerelIsletme(),
];

$apiTaban = htmlspecialchars($AYAR['api_taban'], ENT_QUOTES, 'UTF-8');
$mesajOk = htmlspecialchars('Mesajınız alındı. 24 saat içinde dönüş yapacağız.', ENT_QUOTES, 'UTF-8');
$mesajHata = htmlspecialchars(t('form_hata'), ENT_QUOTES, 'UTF-8');
?>
<?= kirinti([['etiket' => t('anasayfa'), 'yol' => siteUrl('/')], ['etiket' => t('iletisim_baslik'), 'yol' => null]]) ?>
<h1><?= htmlspecialchars(t('iletisim_baslik'), ENT_QUOTES, 'UTF-8') ?></h1>
<ul>
  <li><a href="tel:<?= htmlspecialchars(str_replace(' ', '', $AYAR['telefon']), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($AYAR['telefon'], ENT_QUOTES, 'UTF-8') ?></a></li>
  <li><?= htmlspecialchars($AYAR['eposta'], ENT_QUOTES, 'UTF-8') ?></li>
  <li><?= htmlspecialchars($AYAR['adres'], ENT_QUOTES, 'UTF-8') ?></li>
</ul>
<?php
$haritaAyar = apiGet('/ayarlar', $dil);
$haritaUrl = trim((string) (($haritaAyar['data'] ?? [])['google_maps_embed_url'] ?? ''));
if ($haritaUrl !== ''):
?>
<div class="harita-katman" data-harita="<?= htmlspecialchars($haritaUrl, ENT_QUOTES, 'UTF-8') ?>">
  <button type="button" class="btn btn-ikincil" id="harita-ac">Haritayı Göster</button>
  <p><small>Harita Google sunucularından yüklenir; tıklayınca çerez politikası geçerlidir.</small></p>
</div>
<?php endif; ?>
<form id="iletisim-formu" data-api="<?= $apiTaban ?>" data-dil="<?= htmlspecialchars($dil, ENT_QUOTES, 'UTF-8') ?>" data-ok="<?= $mesajOk ?>" data-hata="<?= $mesajHata ?>">
  <div class="form-alan">
    <label class="etiket" for="i-ad"><?= htmlspecialchars(t('form_ad'), ENT_QUOTES, 'UTF-8') ?></label>
    <input class="input" id="i-ad" name="ad_soyad" required minlength="3" maxlength="120">
  </div>
  <div class="form-alan">
    <label class="etiket" for="i-eposta"><?= htmlspecialchars(t('form_eposta'), ENT_QUOTES, 'UTF-8') ?></label>
    <input class="input" id="i-eposta" name="eposta" type="email" required>
  </div>
  <div class="form-alan">
    <label class="etiket" for="i-tel"><?= htmlspecialchars(t('form_telefon'), ENT_QUOTES, 'UTF-8') ?></label>
    <input class="input" id="i-tel" name="telefon" required inputmode="tel">
  </div>
  <div class="form-alan">
    <label class="etiket" for="i-konu">Konu</label>
    <select class="input" id="i-konu" name="konu">
      <?php foreach ($konuSecenek as $kod => $etiket): ?>
      <option value="<?= $kod ?>"><?= htmlspecialchars($etiket, ENT_QUOTES, 'UTF-8') ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="form-alan">
    <label class="etiket" for="i-mesaj"><?= htmlspecialchars(t('form_mesaj'), ENT_QUOTES, 'UTF-8') ?></label>
    <textarea class="input" id="i-mesaj" name="mesaj" required maxlength="5000"></textarea>
  </div>
  <input type="hidden" name="tur" value="iletisim">
  <input type="text" name="web_sitesi" style="display:none" tabindex="-1" autocomplete="off" aria-hidden="true">
  <div class="form-alan">
    <label><input type="checkbox" name="kvkk_onayi" value="1" required> <?= htmlspecialchars(t('form_kvkk'), ENT_QUOTES, 'UTF-8') ?></label>
  </div>
  <p><button class="btn btn-birincil" type="submit"><?= htmlspecialchars(t('form_gonder'), ENT_QUOTES, 'UTF-8') ?></button></p>
  <div id="iletisim-sonuc" aria-live="polite"></div>
</form>
<script src="/assets/js/iletisim.js" defer></script>
