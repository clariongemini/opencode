# Analitik Panel Planı — Kamelya (F7'de uygulanacak)

**Veri kaynakları:** GA4 Data API (ziyaretçi, event, huni) + `kamelya.talepler` (lead/randevu gerçeği) + `kamelya.randevular`. GA4 sayar, `talepler` doğrular (çift kayıt önleme: GA4 event + DB satırı eşleşmesi).

## Paneller

1. **Ziyaretçi (günlük/haftalık):** GA4 `sessions`, `totalUsers`; dil kırılımı (`dil_degistirildi` + sayfa dili).
2. **Dönüşüm:** `teklif_formu_gonderildi` / `randevu_talebi_olusturuldu` sayıları + `talepler.durum` dağılımı (yeni → kazanıldı).
3. **En çok görüntülenen ürünler:** F7'de `view_item` event'i eklenecek; o zamana dek `talepler.urun_id` sayımı.
4. **Şehir bazlı lead dağılımı:** `talepler.sehir` GROUP BY (F1.1 şehir haritasıyla çapraz: İstanbul/Ankara/İzmir).
5. **Dil bazlı dönüşüm:** event `dil` parametresi × `talepler.dil_kodu`.
6. **Hesaplama kombinasyonları:** `hesaplama_yapildi` parametreleri (malzeme/model/kullanım) — en çok hesaplanan üçlü; fiyat motoru ayarına girdi.

## Erişim

- Rol: `yonetici` (tam), `satis` (kendi hunisi: talepler + randevular). RBAC F5 altyapısıyla.
- GA4 Data API anahtarı sunucu ortamında (`.env` benzeri, commit dışı); servis hesabı salt-okunur.

## [EK] GSC API entegrasyonu (V1 dışı — karar bekliyor)

- Search Console sorgu/tıklama verisini çekip `seo_verileri` yanına `seo_analitik` tablosuna yazma fikri (sorgu, tıklama, gösterim, konum, tarih).
- Gereksinim: Google Cloud servis hesabı + `opencode.json` MCP tanımı; **kimlik bilgisi geliştirici onayı olmadan açılmaz** (seo-analyzer kuralı).
- Katkı: "kendi içinde SEO analiz yapısı" otomatik raporlaması (F7+). Şimdilik plan olarak kayıtlı, kod yok.
