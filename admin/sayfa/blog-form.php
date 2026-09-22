<form id="blog-formu">
  <input type="hidden" id="b-id">
  <div class="form-alan">
    <label class="etiket" for="b-durum">Yayın Durumu</label>
    <select class="input" id="b-durum">
      <option value="taslak">Taslak</option>
      <option value="yayinda">Yayında</option>
      <option value="arsiv">Arşiv</option>
    </select>
  </div>
  <div class="sekmeler" id="b-sekmeler"></div>
  <div id="b-sekmeler-icerik"></div>
  <p><button class="btn btn-birincil" type="submit">Kaydet</button>
  <button class="btn" id="b-onizle" type="button">Önizleme</button></p>
</form>
<div id="blog-form-sonuc" aria-live="polite"></div>
