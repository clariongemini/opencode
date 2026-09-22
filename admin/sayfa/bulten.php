<form id="bulten-toplu-formu">
  <div class="form-alan">
    <label class="etiket" for="b-konu">Konu</label>
    <input class="input" id="b-konu" maxlength="255" required>
  </div>
  <div class="form-alan">
    <label class="etiket" for="b-govde">Gövde</label>
    <textarea class="input" id="b-govde" required></textarea>
  </div>
  <p><button class="btn btn-birincil" type="submit">Onaylı Abonelere Gönder</button></p>
</form>
<div id="bulten-sonuc" aria-live="polite"></div>
<p><a class="btn" id="bulten-csv" href="#">CSV İndir</a></p>
<table class="veri">
  <thead><tr><th>ID</th><th>E-posta</th><th>Ad</th><th>Durum</th></tr></thead>
  <tbody id="bulten-satirlar"></tbody>
</table>
