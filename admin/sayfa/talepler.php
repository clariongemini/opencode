<div class="sekmeler" id="talep-sekmeler">
  <button type="button" data-tur="teklif" class="aktif">Teklif</button>
  <button type="button" data-tur="iletisim">İletişim</button>
  <button type="button" data-tur="kesif">Keşif</button>
  <button type="button" data-tur="sikayet">Şikayet</button>
  <button type="button" data-tur="is_basvurusu">İş Başvurusu</button>
  <button type="button" data-tur="">Tümü</button>
</div>
<table class="veri">
  <thead><tr><th>ID</th><th>Tür</th><th>Ad</th><th>Telefon</th><th>Durum</th><th>İşlemler</th></tr></thead>
  <tbody id="talep-satirlar"></tbody>
</table>
<div id="talep-modal" class="modal" hidden>
  <div class="modal-icerik">
    <h3 id="talep-modal-baslik"></h3>
    <div id="talep-modal-detay"></div>
    <p><span id="talep-modal-yanit"></span>
    <button id="talep-modal-kapat" type="button" class="btn">Kapat</button></p>
  </div>
</div>
