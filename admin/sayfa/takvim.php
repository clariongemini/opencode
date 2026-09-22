<div class="takvim-kontroller">
  <button id="takvim-onceki" type="button">←</button>
  <h2 id="takvim-baslik"></h2>
  <button id="takvim-sonraki" type="button">→</button>
  <button id="takvim-bugun" type="button">Bugüne Dön</button>
  <select class="input" id="takvim-tur" style="max-width:160px">
    <option value="">Tüm Türler</option>
    <option value="gorusme">Görüşme</option>
    <option value="kesif">Keşif</option>
    <option value="uretim">Üretim</option>
    <option value="montaj">Montaj</option>
    <option value="teslim">Teslim</option>
  </select>
  <select class="input" id="takvim-ekip" style="max-width:180px"><option value="">Tüm Ekipler</option></select>
  <input class="input" id="takvim-sehir" placeholder="Şehir" style="max-width:140px">
  <span class="rozet">Ay</span>
  <span class="rozet" title="F8'de">Hafta (F8)</span>
  <span class="rozet" title="F8'de">Gün (F8)</span>
</div>
<div class="takvim-grid" id="takvim-alan"></div>

<h2>Ekip Yükü</h2>
<div id="ekip-yuku"></div>

<div id="gun-modal" class="modal" hidden>
  <div class="modal-icerik">
    <h3 id="gun-modal-baslik"></h3>
    <div id="gun-modal-liste"></div>
    <p><button id="gun-modal-yeni" type="button" class="btn btn-birincil">Yeni Randevu</button>
    <button id="gun-modal-kapat" type="button" class="btn">Kapat</button></p>
  </div>
</div>

<div id="is-modal" class="modal" hidden>
  <div class="modal-icerik">
    <h3 id="is-modal-baslik"></h3>
    <div id="is-modal-detay"></div>
    <div id="is-modal-durum"></div>
    <form id="randevu-formu">
      <input type="hidden" id="r-id">
      <div class="form-alan"><label class="etiket">İş Türü</label>
        <label><input type="radio" name="tur" value="gorusme" checked> Görüşme</label>
        <label><input type="radio" name="tur" value="kesif"> Keşif</label>
        <label><input type="radio" name="tur" value="uretim"> Üretim</label>
        <label><input type="radio" name="tur" value="montaj"> Montaj</label>
        <label><input type="radio" name="tur" value="teslim"> Teslim</label>
      </div>
      <div class="form-alan">
        <label class="etiket" for="r-tarih">Tarih + Saat</label>
        <input class="input" id="r-tarih" type="datetime-local" required>
      </div>
      <div class="form-alan">
        <label class="etiket" for="r-sure">Süre (dakika)</label>
        <select class="input" id="r-sure">
          <option>30</option><option selected>60</option><option>90</option>
          <option>120</option><option>180</option><option>240</option>
        </select>
      </div>
      <div class="form-alan">
        <label class="etiket" for="r-ad">Ad Soyad</label>
        <input class="input" id="r-ad" maxlength="120" required>
      </div>
      <div class="form-alan">
        <label class="etiket" for="r-tel">Telefon</label>
        <input class="input" id="r-tel" inputmode="tel" required>
      </div>
      <div class="form-alan">
        <label class="etiket" for="r-adres">Adres (keşif/montaj zorunlu)</label>
        <input class="input" id="r-adres" maxlength="500">
      </div>
      <div class="form-alan">
        <label class="etiket" for="r-ekip">Ekip Üyesi</label>
        <select class="input" id="r-ekip"><option value="">—</option></select>
      </div>
      <div class="form-alan">
        <label class="etiket" for="r-oncelik">Öncelik</label>
        <select class="input" id="r-oncelik">
          <option value="dusuk">Düşük</option>
          <option value="normal" selected>Normal</option>
          <option value="yuksek">Yüksek</option>
          <option value="acil">Acil</option>
        </select>
      </div>
      <div class="form-alan">
        <label class="etiket" for="r-not">Notlar</label>
        <textarea class="input" id="r-not"></textarea>
      </div>
      <p><button class="btn btn-birincil" type="submit">Kaydet</button>
      <button id="is-modal-kapat" type="button" class="btn">Kapat</button></p>
    </form>
  </div>
</div>
