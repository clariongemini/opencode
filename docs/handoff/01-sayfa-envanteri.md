# Sayfa Envanteri — 31 Sayfa

**Kaynak:** `frontend/sayfalar/*.php` (31 dosya)  
**URL deseni:** `/{dil}/sayfa` veya `/sayfa` (önfiksiz)  
**Dil desteği:** tr, en, de, fr, it, ar

---

### anasayfa.php — Ana Sayfa
- **Dosya:** `frontend/sayfalar/anasayfa.php`
- **URL:** `/` · `/tr/` · `/en/` vs.
- **Kapsam:** `<h1>` hero bölüm + featured ürünler + SSS özet + blog özet
- **SEO kaynağı:** `seo_verileri` (sayfa_tipi='statik', sayfa_kodu='anasayfa')
- **API çağrıları:**
  - `GET /api/v1/products?lang=tr&per_page=3` → `oneCikan` (öne çıkan ürünler)
  - `GET /api/v1/sss-sorulari?lang=tr` → `sssYanit` (SSS özet)
- **JSON-LD:** `jsonldOrganizasyon()` (Organization schema)
- **Ana bölümler:** Hero, Öne Çıkan Ürünler, SSS Özet, Blog Özet
- **Form:** Yok
- **JS:** `ana.js` (nav, dil değişimi, WhatsApp, telefon events)
- **Özellik toggle:** `etkinMi('urunler')`, `etkinMi('urun_hesaplama')`, `etkinMi('referanslar')`, `etkinMi('sss')`
- **RTL:** Özel durum yok
- **UI değişim risk:** DÜŞÜK

---

### urunler.php — Ürün Listesi
- **Dosya:** `frontend/sayfalar/urunler.php`
- **URL:** `/urunler` · `/tr/urunler`
- **Kapsam:** Ürün listesi + filtreleme
- **SEO kaynağı:** `seo_verileri` + `jsonld` ItemList
- **API çağrıları:**
  - `GET /api/v1/products?lang=tr&per_page=20` → `$sonuc` (liste)
- **Form:** GET form (filtreleme: `apiFiltre`)
- **JS:** `ana.js` + `hesaplama-araci.js` (ürün hesaplama)
- **Özellik toggle:** `etkinMi('urunler')`
- **UI değişim risk:** ORTA (filtre mekanizması)

---

### urun-detay.php — Ürün Detay
- **Dosya:** `frontend/sayfalar/urun-detay.php`
- **URL:** `/urun-detay/{id}` · `/tr/urun-detay/{slug}`
- **Kapsam:** Ürün detay + yorumlar + ilgili ürünler
- **SEO kaynağı:** `seo_verileri` + `jsonldUrun()` (Product + AggregateRating)
- **API çağrıları:**
  - `GET /api/v1/products?lang=tr&per_page=100` → `$liste` (ilgili ürünler)
  - `GET /api/v1/products/{id}?lang=tr` → `$detayYanit`
  - `GET /api/v1/yorumlar/ozet?lang=tr&urun_id={id}` → `$ozetYanit`
- **JSON-LD:** `jsonldUrun($bulunan, $dil, $derecelendirme)` — Product + AggregateRating
- **Form:** Yorum formu (POST /api/v1/yorumlar)
- **JS:** `yorumlar.js` (modal + form)
- **Özellik toggle:** `etkinMi('urunler')`
- **UI değişim risk:** YÜKSEK (3 API çağrısı + JSON-LD)

---

### blog.php — Blog Listesi
- **Dosya:** `frontend/sayfalar/blog.php`
- **URL:** `/blog` · `/tr/blog`
- **Kapsam:** Blog yazıları listesi
- **SEO kaynağı:** `seo_verileri`
- **API çağrıları:**
  - `GET /api/v1/blog?lang=tr&limit=20` → `$liste`
- **JS:** `ana.js`
- **UI değişim risk:** DÜŞÜK

---

### blog-detay.php — Blog Detay
- **Dosya:** `frontend/sayfalar/blog-detay.php`
- **URL:** `/blog/{slug}` · `/tr/blog/{slug}`
- **Kapsam:** Blog yazısı detay + yorumlar
- **SEO kaynağı:** `seo_verileri` + `jsonldMakale()` (Article schema)
- **API çağrıları:**
  - `GET /api/v1/blog/{slug}?lang=tr` → `$yanit`
- **JSON-LD:** `jsonldMakale($bulunan)` — Article
- **Form:** Yorum formu
- **JS:** `yorumlar.js`
- **UI değişim risk:** ORTA

---

### sss.php — SSS (SSS)
- **Dosya:** `frontend/sayfalar/sss.php`
- **URL:** `/sss` · `/tr/sss`
- **Kapsam:** SSS akordeonu + sekme filtresi
- **SEO kaynağı:** `seo_verileri` + `jsonldSss()` (FAQPage)
- **API çağrıları:**
  - `GET /api/v1/sss-sorulari?lang=tr` → `$yanit`
- **JSON-LD:** `jsonldSss($ogeler)` → FAQPage mainEntity (24 giriş)
- **JS:** `ana.js` (akordeon click)
- **Özellik toggle:** `etkinMi('sss')` — kapalıysa 410
- **UI değişim risk:** ORTA (akordeon + sekme filtresi)

---

### arama.php — Arama
- **Dosya:** `frontend/sayfalar/arama.php`
- **URL:** `/arama?q={sorgu}`
- **Kapsam:** Arama sonuçları + autocomplete
- **SEO kaynağı:** Hardcoded `<h1>`
- **API çağrıları:**
  - `GET /api/v1/arama?lang=tr&q={q}&tur=hepsi&sayfa=1&limit=20` → `$sonuc`
- **JS:** `arama.js` (autocomplete debounce + `arama_yapildi` GA4 event)
- **UI değişim risk:** DÜŞÜK

---

### karsilastir.php — Ürün Karşılaştırma
- **Dosya:** `frontend/sayfalar/karsilastir.php`
- **URL:** `/karsilastir`
- **Kapsam:** 2 ürün karşılaştırma
- **API çağrıları:**
  - `GET /api/v1/karsilastir?lang=tr` → JS ile karşılaştırma
- **JS:** `karsilastirma.js` (3 comparison items)
- **UI değişim risk:** ORTA

---

### iletisim.php — İletişim
- **Dosya:** `frontend/sayfalar/iletisim.php`
- **URL:** `/iletisim` · `/tr/iletisim`
- **Kapsam:** İletişim formu + harita
- **SEO kaynağı:** `seo_verileri` + `jsonldYerelIsletme()` (LocalBusiness)
- **API çağrıları:**
  - `GET /api/v1/ayarlar?lang=tr` → `$haritaAyar` (harita URL)
- **JSON-LD:** `jsonldYerelIsletme()` — LocalBusiness
- **Form:** `#iletisim-formu` → `POST /api/v1/iletisim`
  - Alanlar: ad_soyad, eposta, telefon, konu, mesaj, kvkk_onayi
  - Honeypot: `web_sitesi` (hidden)
  - Client validation: required, minlength, maxlength, email type, inputmode="tel"
- **JS:** `iletisim.js` (form submit + GA4)
- **UI değişim risk:** YÜKSEK (form + harita + JSON-LD)

---

### teklif-al.php — Teklif Al
- **Dosya:** `frontend/sayfalar/teklif-al.php`
- **URL:** `/teklif-al` · `/tr/teklif-al`
- **Kapsam:** Teklif formu + randevu formu
- **SEO kaynağı:** `seo_verileri` + `jsonldIletisimSayfasi()`
- **Form 1 — Teklif:** `#teklif-formu` → `POST /api/v1/leads`
  - Alanlar: ad_soyad, telefon, sehir, alan_m2, kvkk_onayi, dil_kodu, kaynak
  - `data-uc="/leads"` — form'un API ucu
- **Form 2 — Randevu:** `#randevu-formu` → `POST /api/v1/appointments`
  - Alanlar: ad_soyad, telefon, randevu_tarihi
  - `data-uc="/appointments"`
- **JS:** `teklif-al.js` (form submit + GA4 event'leri)
- **GA4 events:** `teklif_formu_gonderildi`, `randevu_talebi_olusturuldu`
- **UI değişim risk:** YÜKSEK (2 form + 2 API ucu)

---

### ekibimiz.php — Ekip
- **Dosya:** `frontend/sayfalar/ekibimiz.php`
- **URL:** `/ekibimiz`
- **Kapsam:** Ekip üyeleri listesi
- **SEO kaynağı:** `seo_verileri` + `ItemList` JSON-LD
- **API çağrıları:**
  - `GET /api/v1/ekip?lang=tr` → `$liste`
- **JSON-LD:** `ItemList` (kisiListesi)
- **UI değişim risk:** DÜŞÜK

---

### sertifikalar.php — Sertifikalar
- **Dosya:** `frontend/sayfalar/sertifikalar.php`
- **URL:** `/sertifikalar`
- **Kapsam:** Sertifika logosu listesi
- **SEO kaynağı:** `seo_verileri`
- **API çağrıları:**
  - `GET /api/v1/sertifikalar?lang=tr` → `$liste`
- **JS:** `sertifikaBand()` bileşeni
- **UI değişim risk:** DÜŞÜK

---

### garanti.php — Garanti
- **Dosya:** `frontend/sayfalar/garanti.php`
- **URL:** `/garanti`
- **Kapsam:** Garanti koşulları
- **SEO kaynağı:** `seo_verileri`
- **API çağrıları:**
  - `GET /api/v1/ayarlar?lang=tr` → `$ayarlar`
- **UI değişim risk:** DÜŞÜK

---

### odeme-bilgileri.php — Ödeme Bilgileri
- **Dosya:** `frontend/sayfalar/odeme-bilgileri.php`
- **URL:** `/odeme-bilgileri`
- **Kapsam:** Ödeme yöntemleri
- **SEO kaynağı:** `seo_verileri`
- **API çağrıları:**
  - `GET /api/v1/ayarlar?lang=tr` → `$ayarlar`
- **UI değişim risk:** DÜŞÜK

---

### kampanyalar.php — Kampanyalar
- **Dosya:** `frontend/sayfalar/kampanyalar.php`
- **URL:** `/kampanyalar`
- **Kapsam:** Aktif kampanyalar
- **SEO kaynağı:** `seo_verileri`
- **API çağrıları:**
  - `GET /api/v1/kampanyalar?lang=tr` → `$liste`
- **UI değişim risk:** DÜŞÜK

---

### hakkimizda.php — Hakkımızda
- **Dosya:** `frontend/sayfalar/hakkimizda.php`
- **URL:** `/hakkimizda`
- **Kapsam:** Şirket bilgisi
- **SEO kaynağı:** `seo_verileri` + `jsonldOrganizasyon()`
- **JSON-LD:** `jsonldOrganizasyon()` — Organization schema
- **UI değişim risk:** DÜŞÜK

---

### referanslar.php — Referanslar
- **Dosya:** `frontend/sayfalar/referanslar.php`
- **URL:** `/referanslar`
- **Kapsam:** Proje referansları + video referanslar
- **SEO kaynağı:** `seo_verileri`
- **API çağrıları:**
  - `GET /api/v1/donusumler?lang=tr` → `$donusumler`
  - `GET /api/v1/video-referanslar?lang=tr` → `$videolar`
- **JS:** `oncesi-sonrasi.js` (before/after slider), `video-referans.js`
- **Özellik toggle:** `etkinMi('referanslar')` — kapalıysa 410
- **UI değişim risk:** ORTA (slider + embed)

---

### sanal-tur.php — Sanal Tur
- **Dosya:** `frontend/sayfalar/sanal-tur.php`
- **URL:** `/sanal-tur`
- **Kapsam:** 360° sanal tur listesi
- **SEO kaynağı:** `seo_verileri`
- **API çağrıları:**
  - `GET /api/v1/sanal-tur?lang=tr` → `$liste`
- **JS:** `viewer-360.js` (Pannellum integration)
- **Data attribute:** `data-kaynak` (embed URL)
- **UI değişim risk:** ORTA

---

### sehirler.php — Hizmet Bölgelerimiz
- **Dosya:** `frontend/sayfalar/sehirler.php`
- **URL:** `/sehirler`
- **Kapsam:** Şehir listesi
- **SEO kaynağı:** `seo_verileri`
- **API çağrıları:**
  - `GET /api/v1/sehirler?lang=tr` → `$liste`
- **UI değişim risk:** DÜŞÜK

---

### sehir-landing.php — Şehir Landing
- **Dosya:** `frontend/sayfalar/sehir-landing.php`
- **URL:** `/sehir/{slug}` · `/tr/sehir/{slug}`
- **Kapsam:** Şehir özel landing sayfası
- **SEO kaynağı:** `seo_verileri` + custom JSON-LD
- **API çağrıları:**
  - `GET /api/v1/sehirler/{slug}?lang=tr` → `$yanit`
- **JSON-LD:** Custom schema (yerel işletme)
- **UI değişim risk:** ORTA

---

### atolye.php — Atölyemiz
- **Dosya:** `frontend/sayfalar/atolye.php`
- **URL:** `/atolye`
- **Kapsam:** Atölye bilgi + liste
- **SEO kaynağı:** `seo_verileri`
- **API çağrıları:**
  - `GET /api/v1/atolye?lang=tr` → `$liste`
- **UI değişim risk:** DÜŞÜK

---

### galeri.php — Galeri
- **Dosya:** `frontend/sayfalar/galeri.php`
- **URL:** `/galeri`
- **Kapsam:** Galeri fotoğrafları
- **SEO kaynağı:** `seo_verileri`
- **UI değişim risk:** DÜŞÜK

---

### rehber-bakim.php — Bakım Rehberi
- **Dosya:** `frontend/sayfalar/rehber-bakim.php`
- **URL:** `/rehber/bakim`
- **Kapsam:** Bakım ipuçları
- **SEO kaynağı:** `seo_verileri`
- **UI değişim risk:** DÜŞÜK

---

### rehber-fark.php — Malzeme Farkı
- **Dosya:** `frontend/sayfalar/rehber-fark.php`
- **URL:** `/rehber/fark`
- **Kapsam:** Malzeme karşılaştırması
- **SEO kaynağı:** `seo_verileri`
- **UI değişim risk:** DÜŞÜK

---

### rehber-istanbul-bakim-takvimi.php — İstanbul Bakım Takvimi
- **Dosya:** `frontend/sayfalar/rehber-istanbul-bakim-takvimi.php`
- **URL:** `/rehber/istanbul-bakim-takvimi`
- **Kapsam:** İstanbul bakım takvimi + sayaç
- **SEO kaynağı:** `seo_verileri`
- **JS:** `sayac` countdown (data-hedef attribute)
- **UI değişim risk:** ORTA (countdown JS)

---

### rehber-malzeme.php — Malzeme Rehberi
- **Dosya:** `frontend/sayfalar/rehber-malzeme.php`
- **URL:** `/rehber/malzeme`
- **Kapsam:** Malzeme özellikleri
- **SEO kaynağı:** `seo_verileri`
- **UI değişim risk:** DÜŞÜK

---

### sicaklik-simulasyonu.php — Sıcaklık Simülasyonu
- **Dosya:** `frontend/sayfalar/sicaklik-simulasyonu.php`
- **URL:** `/sicaklik-simulasyonu`
- **Kapsam:** Sıcaklık ve gölge simülasyonu
- **SEO kaynağı:** `seo_verileri`
- **API çağrıları:**
  - `GET /api/v1/ayarlar?lang=tr` → form data
- **Form:** `#sicaklik-formu` (client-side simulation)
- **JS:** `hesaplama-araci.js` (form submit)
- **UI değişim risk:** ORTA

---

### bulten-onay.php — Bülten Onayı
- **Dosya:** `frontend/sayfalar/bulten-onay.php`
- **URL:** `/bulten-onay`
- **Kapsam:** Bülten abonelik onay message
- **SEO kaynağı:** `seo_verileri`
- **UI değişim risk:** DÜŞÜK

---

### 410.php — Sayfa Yayında Değil
- **Dosya:** `frontend/sayfalar/410.php`
- **URL:** `/410` (özellik toggle kapalıysa yönlendirilir)
- **Kapsam:** 410 Gone mesajı
- **Özellik toggle:** `etkinMi()` false → 410 sayfası
- **UI değişim risk:** DÜŞÜK

---

### cerez-politikasi.php — Çerez Politikası
- **Dosya:** `frontend/sayfalar/cerez-politikasi.php`
- **URL:** `/cerez-politikasi`
- **Kapsam:** Çerez onay metinleri (6 dil)
- **SEO kaynağı:** Hardcoded (6 dil inline)
- **UI değişim risk:** DÜŞÜK

---

### gizlilik.php — Gizlilik
- **Dosya:** `frontend/sayfalar/gizlilik.php`
- **URL:** `/gizlilik`
- **Kapsam:** Gizlilik politikası (6 dil inline)
- **SEO kaynağı:** `seo_verileri`
- **UI değişim risk:** DÜŞÜK

---

### odeme-bilgileri.php — Ödeme Bilgileri
- **Dosya:** `frontend/sayfalar/odeme-bilgileri.php`
- **URL:** `/odeme-bilgileri`
- **Kapsam:** Ödeme yöntemleri
- **SEO kaynağı:** `seo_verileri`
- **API çağrıları:** `GET /api/v1/ayarlar?lang=tr`
- **UI değişim risk:** DÜŞÜK
