<form id="sertifika-formu">
  <div class="form-alan">
    <label class="etiket" for="s-baslik">Başlık (TR)</label>
    <input class="input" id="s-baslik" maxlength="190" required>
  </div>
  <div class="form-alan">
    <label class="etiket" for="s-kurum">Kurum</label>
    <select class="input" id="s-kurum">
      <option value="CE">CE</option>
      <option value="TUV">TÜV</option>
      <option value="ISO">ISO</option>
      <option value="Diger">Diğer</option>
    </select>
  </div>
  <div class="form-alan">
    <label class="etiket" for="s-belge">Belge No</label>
    <input class="input" id="s-belge" maxlength="100">
  </div>
  <div class="form-alan">
    <label class="etiket" for="s-gecerlilik">Geçerlilik Tarihi</label>
    <input class="input" id="s-gecerlilik" type="date">
  </div>
  <p><button class="btn btn-birincil" type="submit">Ekle</button></p>
</form>
<div id="sertifika-sonuc" aria-live="polite"></div>
<table class="veri">
  <thead><tr><th>ID</th><th>Başlık</th><th>Kurum</th><th>Belge</th><th>İşlemler</th></tr></thead>
  <tbody id="sertifika-satirlar"></tbody>
</table>
