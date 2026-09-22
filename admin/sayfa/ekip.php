<table class="veri">
  <thead><tr><th>ID</th><th>Ad</th><th>Unvan</th><th>Herkese Açık</th><th>İşlemler</th></tr></thead>
  <tbody id="ekip-satirlar"></tbody>
</table>
<form id="ekip-formu">
  <input type="hidden" id="e-id">
  <div class="form-alan">
    <label class="etiket" for="e-unvan">Unvan</label>
    <input class="input" id="e-unvan" maxlength="100">
  </div>
  <div class="form-alan">
    <label class="etiket" for="e-uzmanlik">Uzmanlık Alanı</label>
    <input class="input" id="e-uzmanlik" maxlength="190">
  </div>
  <div class="form-alan">
    <label class="etiket" for="e-biyografi">Biyografi</label>
    <textarea class="input" id="e-biyografi"></textarea>
  </div>
  <div class="form-alan">
    <label><input type="checkbox" id="e-public" value="1"> Herkese açık vitrinde göster</label>
  </div>
  <p><button class="btn btn-birincil" type="submit">Kaydet</button></p>
</form>
<div id="ekip-sonuc" aria-live="polite"></div>
