<div class="form-alan">
  <label class="etiket" for="seo-aralik">Tarih Aralığı</label>
  <select class="input" id="seo-aralik" style="max-width:280px">
    <option value="7">Son 7 gün</option>
    <option value="30" selected>Son 30 gün</option>
    <option value="90">Son 90 gün</option>
  </select>
</div>
<div class="izgara-galeri" id="seo-ozet" style="grid-template-columns:repeat(auto-fill,minmax(200px,1fr))"></div>
<h2>Trend</h2>
<canvas id="seo-trend" height="120" aria-label="Tıklama ve gösterim trendi"></canvas>
<h2>En Çok Tıklanan Sorgular</h2>
<table class="veri">
  <thead><tr><th>Sorgu</th><th>Tıklama</th><th>Gösterim</th><th>CTR</th><th>Pozisyon</th></tr></thead>
  <tbody id="seo-sorgular"></tbody>
</table>
<h2>En Çok Görüntülenen Sayfalar</h2>
<table class="veri">
  <thead><tr><th>Sayfa</th><th>Tıklama</th><th>Gösterim</th><th>CTR</th></tr></thead>
  <tbody id="seo-sayfalar"></tbody>
</table>
<h2>Ülke Dağılımı</h2>
<canvas id="seo-ulke" height="120" aria-label="Ülke dağılımı"></canvas>
<h2>Cihaz Dağılımı</h2>
<canvas id="seo-cihaz" height="120" aria-label="Cihaz dağılımı"></canvas>
<h2>Kelime Fırsatları</h2>
<div id="seo-firsatlar"></div>
<p><small id="seo-kaynak">Kaynak: önbellek tablosu (mock/GSC).</small></p>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js" defer></script>
<script src="/assets/js/seo-dashboard.js" defer></script>
