<form id="urun-formu">
  <input type="hidden" id="u-id">
  <div class="form-alan">
    <label class="etiket" for="u-kod">Ürün Kodu</label>
    <input class="input" id="u-kod" maxlength="60" required>
  </div>
  <div class="izgara" style="display:flex;gap:8px">
    <div class="form-alan"><label class="etiket" for="u-model">Model</label><select class="input" id="u-model"></select></div>
    <div class="form-alan"><label class="etiket" for="u-malzeme">Malzeme</label><select class="input" id="u-malzeme"></select></div>
    <div class="form-alan"><label class="etiket" for="u-kullanim">Kullanım</label><select class="input" id="u-kullanim"></select></div>
  </div>
  <div class="sekmeler" id="u-sekmeler"></div>
  <div id="u-sekmeler-icerik"></div>
  <h2>Teknik Detaylar</h2>
  <div class="form-alan">
    <label class="etiket" for="u-cati">Çatı Tipi</label>
    <select class="input" id="u-cati">
      <option value="">—</option>
      <option value="duz">Düz</option>
      <option value="egimli">Eğimli</option>
      <option value="kubbe">Kubbe</option>
      <option value="biyoklimatik">Biyoklimatik</option>
      <option value="ahsap_kiremit">Ahşap Kiremit</option>
    </select>
  </div>
  <div class="form-alan">
    <label class="etiket" for="u-korkuluk">Korkuluk Malzeme</label>
    <select class="input" id="u-korkuluk">
      <option value="">—</option>
      <option value="ahsap">Ahşap</option>
      <option value="aluminyum">Alüminyum</option>
      <option value="kompozit">Kompozit</option>
      <option value="ferforje">Ferforje</option>
      <option value="yok">Yok</option>
    </select>
  </div>
  <div class="form-alan">
    <label class="etiket" for="u-korkuluk-yukseklik">Korkuluk Yükseklik (cm)</label>
    <input class="input" id="u-korkuluk-yukseklik" type="number" min="0" max="300">
  </div>
  <p><small>Çatı/korkuluk açıklamaları dil sekmelerindedir.</small></p>
  <div class="form-alan">
    <label class="etiket" for="u-video">Video URL (YouTube/Vimeo/Instagram, https)</label>
    <input class="input" id="u-video" maxlength="500" placeholder="https://...">
  </div>
  <h2>Görseller</h2>
  <div id="u-gorseller"></div>
  <div class="form-alan">
    <label class="etiket" for="u-dosya">Görsel Yükle (jpg/png/webp)</label>
    <input class="input" id="u-dosya" type="file" accept=".jpg,.jpeg,.png,.webp">
  </div>
  <div class="form-alan">
    <label class="etiket" for="u-tur">Görsel Türü</label>
    <select class="input" id="u-tur">
      <option value="normal">Normal</option>
      <option value="360">360°</option>
    </select>
  </div>
  <p><button class="btn" id="u-gorsel-yukle" type="button">Görsel Yükle</button></p>
  <p><button class="btn btn-birincil" type="submit">Kaydet</button></p>
</form>
<div id="urun-form-sonuc" aria-live="polite"></div>
