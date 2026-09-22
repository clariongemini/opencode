<form id="sss-formu">
  <div class="form-alan">
    <label class="etiket" for="s-kapsam">Sayfa Kapsamı</label>
    <select class="input" id="s-kapsam">
      <option value="genel">Genel</option>
      <option value="fiyatlama">Fiyatlama</option>
      <option value="bakim">Bakım</option>
    </select>
  </div>
  <div class="form-alan">
    <label class="etiket" for="s-sira">Sıra</label>
    <input class="input" id="s-sira" type="number" value="0">
  </div>
  <div class="sekmeler" id="s-sekmeler"></div>
  <div id="s-sekmeler-icerik"></div>
  <p><button class="btn btn-birincil" type="submit">Ekle</button></p>
</form>
<div id="sss-form-sonuc" aria-live="polite"></div>
<table class="veri">
  <thead><tr><th>ID</th><th>Soru (TR)</th><th>Sıra</th><th>Aktif</th><th>İşlemler</th></tr></thead>
  <tbody id="sss-satirlar"></tbody>
</table>
