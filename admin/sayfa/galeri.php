<form id="galeri-formu">
  <div class="form-alan">
    <label class="etiket" for="g-dosya">Görsel (jpg/png/webp, maks 5MB)</label>
    <input class="input" id="g-dosya" type="file" accept=".jpg,.jpeg,.png,.webp" required>
  </div>
  <div class="form-alan">
    <label class="etiket" for="g-baslik">Başlık</label>
    <input class="input" id="g-baslik" maxlength="220" required>
  </div>
  <div class="form-alan">
    <label class="etiket" for="g-hikaye">Proje Hikâyesi</label>
    <textarea class="input" id="g-hikaye"></textarea>
  </div>
  <p><button class="btn btn-birincil" type="submit">Yükle</button></p>
</form>
<div id="galeri-form-sonuc" aria-live="polite"></div>
<div class="izgara-galeri" id="galeri-alan"></div>
