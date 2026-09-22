<p>Şehirler API-salt-okunur listelenir; içerik ve bölgeler veritabanında yönetilir (kurulum seed'li).</p>
<table class="veri">
  <thead><tr><th>ID</th><th>Kod</th><th>Ad</th><th>Aktif</th></tr></thead>
  <tbody id="sehir-satirlar"></tbody>
</table>
<h2>İçerik (SEO + metin)</h2>
<form id="sehir-icerik-formu">
  <div class="form-alan">
    <label class="etiket" for="se-sehir">Şehir</label>
    <select class="input" id="se-sehir"></select>
  </div>
  <div class="form-alan">
    <label class="etiket" for="se-dil">Dil</label>
    <select class="input" id="se-dil">
      <option value="tr">TR</option><option value="en">EN</option><option value="de">DE</option>
      <option value="fr">FR</option><option value="it">IT</option><option value="ar">AR</option>
    </select>
  </div>
  <div class="form-alan">
    <label class="etiket" for="se-slug">Slug</label>
    <input class="input" id="se-slug" maxlength="220" required>
  </div>
  <div class="form-alan">
    <label class="etiket" for="se-baslik">SEO Başlık</label>
    <input class="input" id="se-baslik" maxlength="220">
  </div>
  <div class="form-alan">
    <label class="etiket" for="se-aciklama">SEO Açıklama</label>
    <textarea class="input" id="se-aciklama"></textarea>
  </div>
  <div class="form-alan">
    <label class="etiket" for="se-icerik">İçerik</label>
    <textarea class="input" id="se-icerik"></textarea>
  </div>
  <p><button class="btn btn-birincil" type="submit">Kaydet</button></p>
</form>
<div id="sehir-sonuc" aria-live="polite"></div>
