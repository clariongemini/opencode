<form id="atolye-formu">
  <div class="form-alan">
    <label class="etiket" for="a-dosya">Fotoğraf (jpg/png/webp, maks 5MB)</label>
    <input class="input" id="a-dosya" type="file" accept=".jpg,.jpeg,.png,.webp" required>
  </div>
  <div class="form-alan">
    <label class="etiket" for="a-baslik">Başlık</label>
    <input class="input" id="a-baslik" maxlength="190" required>
  </div>
  <div class="form-alan">
    <label class="etiket" for="a-aciklama">Açıklama</label>
    <textarea class="input" id="a-aciklama"></textarea>
  </div>
  <p><button class="btn btn-birincil" type="submit">Yükle</button></p>
</form>
<div id="atolye-sonuc" aria-live="polite"></div>
<div class="izgara-galeri" id="atolye-alan"></div>
