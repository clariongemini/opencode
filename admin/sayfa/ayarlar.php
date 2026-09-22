<form id="ayar-formu">
  <div class="form-alan">
    <label class="etiket" for="a-site">Site Adı</label>
    <input class="input" id="a-site" maxlength="120">
  </div>
  <div class="form-alan">
    <label class="etiket" for="a-tel">İletişim Telefonu</label>
    <input class="input" id="a-tel" maxlength="40">
  </div>
  <div class="form-alan">
    <label class="etiket" for="a-wa">WhatsApp Numarası</label>
    <input class="input" id="a-wa" maxlength="40">
  </div>
  <div class="form-alan">
    <label class="etiket" for="a-dil">Varsayılan Dil</label>
    <select class="input" id="a-dil">
      <option value="tr">TR</option>
      <option value="en">EN</option>
      <option value="de">DE</option>
      <option value="fr">FR</option>
      <option value="it">IT</option>
      <option value="ar">AR</option>
    </select>
  </div>
  <div class="form-alan">
    <label class="etiket" for="a-bildirim">Bildirim E-postaları (virgülle ayırın)</label>
    <input class="input" id="a-bildirim" maxlength="500" placeholder="ornek@firma.com, satis@firma.com">
  </div>
  <div class="form-alan">
    <label><input type="checkbox" id="a-bildirim-acik" value="1"> İletişim bildirimi açık</label>
  </div>
  <h2>Sosyal Medya</h2>
  <div class="form-alan">
    <label class="etiket" for="a-instagram">Instagram Kullanıcı Adı</label>
    <input class="input" id="a-instagram" maxlength="120">
  </div>
  <div class="form-alan">
    <label><input type="checkbox" id="a-instagram-acik" value="1"> Instagram gömme aktif</label>
  </div>
  <div class="form-alan">
    <label class="etiket" for="a-instagram-gonderiler">Gönderi URL'leri (satır başına bir)</label>
    <textarea class="input" id="a-instagram-gonderiler"></textarea>
  </div>
  <h2>Garanti</h2>
  <div class="form-alan">
    <label class="etiket" for="a-garanti-sure">Garanti Süresi</label>
    <input class="input" id="a-garanti-sure" maxlength="60" placeholder="5 yıl">
  </div>
  <div class="sekmeler" id="g-sekmeler"></div>
  <div id="g-sekmeler-icerik"></div>
  <h2>Ödeme Bilgileri</h2>
  <div id="banka-satirlar"></div>
  <div class="form-alan">
    <label class="etiket" for="b-yeni-banka">Banka</label>
    <input class="input" id="b-yeni-banka" maxlength="120" placeholder="Ziraat Bankası">
  </div>
  <div class="form-alan">
    <label class="etiket" for="b-yeni-iban">IBAN</label>
    <input class="input" id="b-yeni-iban" maxlength="40" placeholder="TR...">
  </div>
  <div class="form-alan">
    <label class="etiket" for="b-yeni-sahip">Hesap Sahibi</label>
    <input class="input" id="b-yeni-sahip" maxlength="120">
  </div>
  <div class="form-alan">
    <label class="etiket" for="b-yeni-sube">Şube</label>
    <input class="input" id="b-yeni-sube" maxlength="120">
  </div>
  <p><button class="btn" id="b-banka-ekle" type="button">Banka Ekle</button></p>
  <div class="sekmeler" id="o-sekmeler"></div>
  <div id="o-sekmeler-icerik"></div>
  <p><button class="btn btn-birincil" type="submit">Kaydet</button></p>
</form>
<div id="ayar-form-sonuc" aria-live="polite"></div>
