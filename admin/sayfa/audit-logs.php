<div class="filtre">
  <label>Sayfa başına <select id="audit-limit"><option value="20">20</option><option value="50">50</option><option value="100">100</option></select></label>
  <label>İşlem <select id="audit-islem"><option value="">Tümü</option><option value="olusturma">Oluşturma</option><option value="guncelleme">Güncelleme</option><option value="silme">Silme</option><option value="giris">Giriş</option><option value="cikis">Çıkış</option><option value="yenileme">Yenileme</option></select></label>
  <label>Varlık tipi <select id="audit-varlik"><option value="">Tümü</option></select></label>
  <button id="audit-filtrele" type="button" class="btn btn-birincil">Filtrele</button>
</div>
<table class="veri">
  <thead><tr><th>ID</th><th>Zaman</th><th>Kullanıcı</th><th>İşlem</th><th>Varlık Tipi</th><th>Varlık ID</th><th>IP (maskeli)</th><th>Eski Değer</th><th>Yeni Değer</th></tr></thead>
  <tbody id="audit-satirlar"></tbody>
</table>
<div id="audit-sayfalama" class="sayfalama"></div>