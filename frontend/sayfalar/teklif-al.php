<?php

declare(strict_types=1);

/** Teklif + randevu formları — API'ye fetch ile gönderilir. */

$SEO = [
    'baslik' => 'Teklif Alın — Ücretsiz Keşif ve Fiyat Teklifi — Kamelya',
    'aciklama' => 'Kamelya teklif formu: ölçü ve iletişim bilgilerinizi bırakın, ekibimiz ücretsiz keşif için sizi arasın. Şeffaf m² fiyatıyla anında ön bilgi alın. Hızlı dönüş.',
    'yol' => $MEVCUT_YOL,
    'jsonld' => jsonldIletisimSayfasi(),
];

$alanOn = htmlspecialchars($_GET['alan'] ?? '', ENT_QUOTES, 'UTF-8');
$apiTaban = htmlspecialchars($AYAR['api_taban'], ENT_QUOTES, 'UTF-8');
$mesajOk = htmlspecialchars(t('form_basarili'), ENT_QUOTES, 'UTF-8');
$mesajHata = htmlspecialchars(t('form_hata'), ENT_QUOTES, 'UTF-8');
?>
<?= kirinti([['etiket' => t('anasayfa'), 'yol' => siteUrl('/')], ['etiket' => t('teklif_baslik'), 'yol' => null]]) ?>
<h1><?= htmlspecialchars(t('teklif_baslik'), ENT_QUOTES, 'UTF-8') ?></h1>
<p><?= htmlspecialchars(t('teklif_alt'), ENT_QUOTES, 'UTF-8') ?></p>

<h2><?= htmlspecialchars(t('teklif_baslik'), ENT_QUOTES, 'UTF-8') ?></h2>
<form id="teklif-formu" data-api="<?= $apiTaban ?>" data-dil="<?= htmlspecialchars($dil, ENT_QUOTES, 'UTF-8') ?>" data-uc="/leads" data-sonuc="teklif-sonuc" data-ok="<?= $mesajOk ?>" data-hata="<?= $mesajHata ?>">
  <div class="form-alan">
    <label class="etiket" for="t-ad"><?= htmlspecialchars(t('form_ad'), ENT_QUOTES, 'UTF-8') ?></label>
    <input class="input" id="t-ad" name="ad_soyad" required minlength="3" maxlength="120">
  </div>
  <div class="form-alan">
    <label class="etiket" for="t-tel"><?= htmlspecialchars(t('form_telefon'), ENT_QUOTES, 'UTF-8') ?></label>
    <input class="input" id="t-tel" name="telefon" required inputmode="tel" aria-describedby="t-tel-hata">
  </div>
  <div class="form-alan">
    <label class="etiket" for="t-sehir"><?= htmlspecialchars(t('form_sehir'), ENT_QUOTES, 'UTF-8') ?></label>
    <input class="input" id="t-sehir" name="sehir" maxlength="100">
  </div>
  <div class="form-alan">
    <label class="etiket" for="t-alan">m²</label>
    <input class="input" id="t-alan" name="alan_m2" type="number" min="0.5" max="5000" step="0.1" value="<?= $alanOn ?>">
  </div>
  <div class="form-alan">
    <label><input type="checkbox" name="kvkk_onayi" value="1" required> <?= htmlspecialchars(t('form_kvkk'), ENT_QUOTES, 'UTF-8') ?></label>
  </div>
  <input type="hidden" name="dil_kodu" value="<?= htmlspecialchars($dil, ENT_QUOTES, 'UTF-8') ?>">
  <input type="hidden" name="kaynak" value="web">
  <p><button class="btn btn-birincil" type="submit"><?= htmlspecialchars(t('form_gonder'), ENT_QUOTES, 'UTF-8') ?></button></p>
  <div id="teklif-sonuc" aria-live="polite"></div>
</form>

<h2><?= htmlspecialchars(t('randevu_baslik'), ENT_QUOTES, 'UTF-8') ?></h2>
<p><small><?= htmlspecialchars(t('randevu_bilgi'), ENT_QUOTES, 'UTF-8') ?></small></p>
<form id="randevu-formu" data-api="<?= $apiTaban ?>" data-uc="/appointments" data-sonuc="randevu-sonuc" data-ok="<?= $mesajOk ?>" data-hata="<?= $mesajHata ?>">
  <div class="form-alan">
    <label class="etiket" for="r-ad"><?= htmlspecialchars(t('form_ad'), ENT_QUOTES, 'UTF-8') ?></label>
    <input class="input" id="r-ad" name="ad_soyad" required minlength="3" maxlength="120">
  </div>
  <div class="form-alan">
    <label class="etiket" for="r-tel"><?= htmlspecialchars(t('form_telefon'), ENT_QUOTES, 'UTF-8') ?></label>
    <input class="input" id="r-tel" name="telefon" required inputmode="tel">
  </div>
  <div class="form-alan">
    <label class="etiket" for="r-tarih"><?= htmlspecialchars(t('randevu_tarih'), ENT_QUOTES, 'UTF-8') ?></label>
    <input class="input" id="r-tarih" name="randevu_tarihi" required placeholder="YYYY-AA-GG SS:DD">
  </div>
  <p><button class="btn btn-birincil" type="submit"><?= htmlspecialchars(t('form_gonder'), ENT_QUOTES, 'UTF-8') ?></button></p>
  <div id="randevu-sonuc" aria-live="polite"></div>
</form>

<script src="/assets/js/teklif-al.js" defer></script>
