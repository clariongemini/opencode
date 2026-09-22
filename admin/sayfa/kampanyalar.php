<form id="kampanya-formu">
  <input type="hidden" id="k-id">
  <div class="form-alan">
    <label class="etiket" for="k-kod">Kod (benzersiz)</label>
    <input class="input" id="k-kod" maxlength="60" required>
  </div>
  <div class="form-alan">
    <label class="etiket" for="k-oran">İndirim Oranı</label>
    <input class="input" id="k-oran" type="number" min="0" step="0.01">
  </div>
  <div class="form-alan">
    <label class="etiket" for="k-tip">İndirim Tipi</label>
    <select class="input" id="k-tip">
      <option value="yuzde">Yüzde</option>
      <option value="sabit">Sabit</option>
    </select>
  </div>
  <div class="form-alan">
    <label class="etiket" for="k-bas">Başlangıç</label>
    <input class="input" id="k-bas" type="datetime-local" required>
  </div>
  <div class="form-alan">
    <label class="etiket" for="k-bit">Bitiş</label>
    <input class="input" id="k-bit" type="datetime-local" required>
  </div>
  <div class="sekmeler" id="k-sekmeler"></div>
  <div id="k-sekmeler-icerik"></div>
  <p><button class="btn btn-birincil" type="submit">Kaydet</button></p>
</form>
<div id="kampanya-sonuc" aria-live="polite"></div>
<table class="veri">
  <thead><tr><th>ID</th><th>Kod</th><th>Aralık</th><th>Aktif</th><th>İşlemler</th></tr></thead>
  <tbody id="kampanya-satirlar"></tbody>
</table>
