# API-Frontend Sözleşmesi — 13+ Public Uç

**Kaynak:** `backend/routes/api.php` + `frontend/includes/api.php`  
**Tüm uçlar:** `GET /api/v1/...` + `POST /api/v1/...`

---

## Public API Endpoints

| DB Kaynak | Tablo Adı (Türkçe) |
|-----------|---------------------|
| `urunler` + `urun_cevirileri` | (NOT `products` + `product_cevirileri`) |
| `kullanicilar` | (NOT `users` — `rol='yonetici'` = 1 admin) |
| `sss_sorulari` + `sss_cevirileri` | (NOT `fa_questions` vs.) |
| `blog_yazilari` + `blog_yazisi_cevirileri` | |
| `sehirler` + `sehir_cevirileri` + `sehir_hizmet_bolgeleri` | |
| `sertifikalar` + `sertifika_cevirileri` | |
| `yorumlar` + `yorum_cevirileri` | |
| `kampanyalar` + `kampanya_cevirileri` | |
| `ayarlar` | |
| `video_referanslari` + `sanal_turlar` + `sanal_tur_cevirileri` | |
| `proje_donusumleri` + `gocler` + `fiyat_carpanlari` + `kapasite_carpanlari` | |
| `seo_verileri` + `seo_analitik_verileri` | |
| `ozellik_toggle` | |
| `bulten_aboneleri` + `randevular` + `talepler` | |
| `token_karalistesi` + `audit_loglari` + `bildirim_kuyrugu` | |
| `galeri` + `atolye_fotograflari` | |

**Toplam tablo:** 37 (TÜM Türkçe)

**Kanıt:**
```sql
SELECT TABLE_NAME FROM information_schema.tables WHERE table_schema='kamelya' ORDER BY TABLE_NAME;
```

---

## Detaylı Sözleşmeler

### 1. GET /api/v1/urunler
- **Kullanılır:** `anasayfa.php`, `urunler.php`, `urun-detay.php`, `karsilastir.php`
- **Parametreler:** `lang` (zorunlu), `per_page` (opsiyonel)
- **Yanıt:** `{success: true, data: [{id, baslik, ...}], ...}`
- **Frontend:** `apiGet('/urunler', $dil, ['per_page' => '3'])`
- **DOM:** `.card-baslik`, `.card-govde`, `.card-gorsel`
- **Rate limit:** Yok (public)

### 2. GET /api/v1/urunler/{id}
- **Kullanılır:** `urun-detay.php`
- **Parametreler:** `lang` (zorunlu)
- **Yanıt:** `{success: true, data: {id, baslik, detayli_aciklama, ...}}`
- **Frontend:** `apiGet('/urunler/' . (int) $bulunan['id'], $dil)`
- **DOM:** `h1`, `detayli_aciklama`, `.rozet`
- **Kritik alan:** `seo_anahtar_kelimeler`, `capacity.people`

### 3. GET /api/v1/yorumlar/ozet
- **Kullanılır:** `urun-detay.php`
- **Parametreler:** `lang`, `urun_id`
- **Yanıt:** `{success: true, data: {ortalama, sayi, ...}}`
- **Frontend:** `apiGet('/yorumlar/ozet', $dil, ['urun_id' => ...])`
- **DOM:** `.yildizlar`, `.yorum-sayisi`

### 4. POST /api/v1/calculate
- **Kullanılır:** `hesaplama-araci.js`, `sicaklik-simulasyonu.php`
- **Parametreler:** `lang`, `material`, `model`, `usage` (JSON body)
- **Yanıt:** `{success: true, data: {area_m2, final_price, capacity: {people: N}}}`
- **Frontend:** `fetch(apiTaban + '/calculate', {method: 'POST', body: JSON.stringify(veri)})`
- **GA4 Event:** `hesaplama_yapildi`
- **Rate limit:** 31/10dk (hesaplama aracı)
- **Kritik alan:** `price_hint`, `capacity.people`, `final_price`

### 5. POST /api/v1/leads
- **Kullanılır:** `teklif-al.php` (Form 1)
- **Parametreler:** `ad_soyad`, `telefon`, `sehir`, `alan_m2`, `kvkk_onayi`, `dil_kodu`, `kaynak`
- **Yanıt:** `{success: true, message: string}` veya `{success: false, errors: [...]}`
- **Frontend:** `teklif-al.js` — `fetch(form.getAttribute('data-api') + '/leads', ...)`
- **GA4 Event:** `teklif_formu_gonderildi`
- **Hata:** 422 → validation errors → `.uyari-hata`
- **Success:** `201` → `.uyari-basarili` + GA4 event

### 6. POST /api/v1/appointments
- **Kullanılır:** `teklif-al.php` (Form 2 — Randevu)
- **Parametreler:** `ad_soyad`, `telefon`, `randevu_tarihi`
- **Yanıt:** `{success: true}` veya `{success: false}`
- **Frontend:** `teklif-al.js` — `fetch(form.getAttribute('data-api') + '/appointments', ...)`
- **GA4 Event:** `randevu_talebi_olusturuldu`

### 7. POST /api/v1/iletisim
- **Kullanılır:** `iletisim.php`
- **Parametreler:** `ad_soyad`, `eposta`, `telefon`, `konu`, `mesaj`, `kvkk_onayi`, `tur`
- **Yanıt:** `{success: true}` veya `{success: false}`
- **Frontend:** `iletisim.js` — `fetch(form.getAttribute('data-api') + '/iletisim', ...)`
- **Honeypot:** `web_sitesi` (hidden, spam filter)
- **Kritik:** `data-api` attribute'i ve `data-dil` attribute'i formda tanımlı

### 8. POST /api/v1/yorumlar
- **Kullanılır:** `yorumlar.js`, `urun-detay.php`
- **Parametreler:** `urun_id`, `baslik`, `yorum`, `puan`
- **Hata:** 422 → `{errors: [...]}`
- **Moderasyon:** Yorumlar onay bekler

### 9. GET /api/v1/yorumlar/ozet
- **Kullanılır:** `urun-detay.php`
- **Parametreler:** `lang`, `urun_id`
- **Yanıt:** `{success: true, data: {ortalama_puan, toplam_yorum}}`

### 10. GET /api/v1/sss-sorulari
- **Kullanılır:** `anasayfa.php`, `sss.php`
- **Parametreler:** `lang`
- **Yanıt:** `{success: true, data: [{soru, cevap, ...}]}`
- **JSON-LD:** `jsonldSss()` → FAQPage (24 giriş)

### 11. GET /api/v1/arama
- **Kullanılır:** `arama.php`, `arama.js`
- **Parametreler:** `lang`, `q`, `tur`, `sayfa`, `limit`
- **Yanıt:** `{success: true, data: [{id, baslik, ...}]}`
- **GA4 Event:** `arama_yapildi` (`{q, tur}`)
- **Autocomplete:** 3+ karakter → öneri listesi

### 12. GET /api/v1/karsilastir
- **Kullanılır:** `karsilastir.php`
- **Parametreler:** `lang`
- **Yanıt:** Ürün karşılaştırma verisi

### 13. GET /api/v1/ayarlar
- **Kullanılır:** `garanti.php`, `odeme-bilgileri.php`, `iletisim.php`, `sicaklik-simulasyonu.php`
- **Parametreler:** `lang`
- **Yanıt:** `{success: true, data: {harita_url, ...}}`
- **Kullanım:** Harita embed URL, site ayarları

### 14. GET /api/v1/sehirler
- **Kullanılır:** `sehirler.php`
- **Parametreler:** `lang`
- **Yanıt:** Şehir listesi

### 15. GET /api/v1/sehirler/{slug}
- **Kullanılans:** `sehir-landing.php`
- **Parametreler:** `lang`
- **Yanıt:** Şehir detay + JSON-LD

### 16. GET /api/v1/ekip, /sertifikalar, /kampanyalar, /atolye, /sanal-tur, /donusumler, /video-referanslar
- **Ortak özellik:** `lang` parametresi, JSON liste dönüşü
- **Frontend:** Her sayfada `apiGet('/{uc}', $dil)`

### 17. GET /api/v1/ozellikler
- **Kullanılır:** `ozellik.php` (feature toggle)
- **Yanıt:** `{success: true, data: {ozellik_anahtari: true/false, ...}}`
- **Fail-open:** API erişilemezse TÜM özellikler AÇIK

---

## Frontend API Çağrı Haritası

```
frontend/sayfalar/
├── anasayfa.php
│   ├── apiGet('/urunler', $dil, ['per_page' => '3'])
│   └── apiGet('/sss-sorulari', $dil)
├── urunler.php
│   └── apiGet('/urunler', $dil, [...filters, 'per_page' => '20'])
├── urun-detay.php
│   ├── apiGet('/urunler', $dil, ['per_page' => '100'])
│   ├── apiGet('/urunler/' + id, $dil)
│   └── apiGet('/yorumlar/ozet', $dil, ['urun_id' => id])
├── blog.php
│   └── apiGet('/blog', $dil, ['limit' => '20'])
├── blog-detay.php
│   └── apiGet('/blog/' + slug, $dil)
├── sss.php
│   └── apiGet('/sss-sorulari', $dil)
├── arama.php
│   └── apiGet('/arama', $dil, ['q', 'tur', 'sayfa', 'limit'])
├── ekibimiz.php
│   └── apiGet('/ekip', $dil)
├── garanti.php
│   └── apiGet('/ayarlar', $dil)
├── iletisim.php
│   └── apiGet('/ayarlar', $dil)
├── kampanyalar.php
│   └── apiGet('/kampanyalar', $dil)
├── odeme-bilgileri.php
│   └── apiGet('/ayarlar', $dil)
├── referanslar.php
│   ├── apiGet('/donusumler', $dil)
│   └── apiGet('/video-referanslar', $dil)
├── sanal-tur.php
│   └── apiGet('/sanal-tur', $dil)
├── sehirler.php
│   └── apiGet('/sehirler', $dil)
├── sehir-landing.php
│   └── apiGet('/sehirler/' + slug, $dil)
├── sertifikalar.php
│   └── apiGet('/sertifikalar', $dil)
├── atolye.php
│   └── apiGet('/atolye', $dil)
└── sicaklik-simulasyonu.php
    └── fetch(apiTaban + '/ayarlar')
```

---

## Hata Durumu Dönüşü

| Hata | HTTP | Frontend Davranış |
|------|------|-------------------|
| Validation | 422 | `.uyari-hata` gösterilir, `data-hata` mesaj |
| Not Found | 404 | `404` sayfası |
| Server Error | 500 | `null` döner (apiGet), sayfa düşmez |
| Rate Limit | 429 | (belirsiz — implementation) |

---

## Kritik Alanlar (UI Değişiminde KORUNMASI GEREKEN)

| Alan | Neden |
|------|-------|
| `price_hint` | Hesaplama sonucunda gösterilir |
| `capacity.people` | Ürün kapasitesi gösterimi |
| `seo_anahtar_kelimeler` | SEO meta içerik |
| `data-api` attribute | Form submit URL'si |
| `data-dil` attribute | Form dil parametresi |
| `data-uc` attribute | Form API ucu |
| `data-sonuc` attribute | Sonuç DOM container |
| `data-ok` / `data-hata` | Başarı/hata mesajları |
| `data-event` attribute | GA4 event tetikleyici |
| `data-akordeon` attribute | SSS akordeon JS |
| `data-kapsam` attribute | SSS sekme filtresi |
| `data-harita` attribute | Harita embed URL |
| `data-hedef` attribute | Sayacı hedef tarihi |
| `data-kaynak` attribute | Sanal tur embed URL |
| `data-teklif` attribute | Hesaplama aracı yönlendirme |
