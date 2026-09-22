<p>Mevcut katsayılar (JSON): çatı ve mevsim çarpanları. Boş bırakılırsa varsayılanlar kullanılır.</p>
<form id="sicaklik-formu-admin">
  <div class="form-alan">
    <label class="etiket" for="s-json">Katsayı JSON (örn: {"cati":{"duz":0.85},"mevsim":{"yaz":1.0}})</label>
    <textarea class="input" id="s-json" style="min-height:160px"></textarea>
  </div>
  <p><button class="btn btn-birincil" type="submit">Kaydet</button></p>
</form>
<div id="sicaklik-sonuc" aria-live="polite"></div>
