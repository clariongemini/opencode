# Veri Akışı Haritası — DB → API → DOM

**Kaynak:** `backend/database/migrations/` + `backend/app/Controllers/` + `frontend/sayfalar/*.php`

---

## Genel Desen

```
DB Tablosı → Migration/Seeder → Controller → API Ucu → apiGet() → Frontend Render
```

Her sayfa türü için:
1. **DB Kaynak** → hangi tablo, hangi kolonlar
2. **API Ucu** → hangi controller method'u
3. **Frontend Render** → hangi PHP sayfası, hangi satır
4. **SEO** → `seo_verileri` tablosu
5. **JSON-LD** → schema.org tipi

---

## 1. Anasayfa (Karma)

### DB Kaynak
- `urunler` (öne çıkan: `per_page=3`)
- `sss_sorulari` (SSS özet)
- `seo_verileri` (sayfa_tipi='statik', sayfa_kodu='anasayfa')

### API Uçları
- `GET /api/v1/urunler?lang=tr&per_page=3` → `oneCikan`
- `GET /api/v1/sss-sorulari?lang=tr` → `sssYanit`

### Frontend Render
- `frontend/sayfalar/anasayfa.php:7` + `:46`
- `H1: hero_baslik` → `<h1><?= t('hero_baslik') ?>`

### SEO
- `seoMeta()` → `seo_verileri` (statik, anasayfa)
- `jsonldOrganizasyon()` → @graph (Organization + WebSite)

### JSON-LD
- `Organization` — şirket bilgisi

### Feature Toggle
- `etkinMi('urunler')` → ürünler bölümü
- `etkinMi('urun_hesaplama')` → hesaplama aracı
- `etkinMi('referanslar')` → referanslar bölümü
- `etkinMi('sss')` → SSS bölümü

---

## 2. Ürünler Listesi

### DB Kaynak
- `urunler` (liste)
- `urun_cevirileri` (dil çevirileri)
- `seo_verileri`

### API Uçları
- `GET /api/v1/urunler?lang=tr&per_page=20` → `$sonuc`

### Frontend Render
- `frontend/sayfalar/urunler.php:33` + `:54`
- `H1: nav_urunler` → `<h1><?= t('nav_urunler') ?>`
- JSON-LD: `ItemList` (sayfa başına)

### SEO
- `seoMeta()` → `seo_verileri`
- `jsonld ItemList` → `@type: ItemList`

---

## 3. Ürün Detay

### DB Kaynak
- `urunler` + `urun_cevirileri` (detay)
- `yorumlar` + `yorum_cevirileri` (yorum özet)
- `seo_verileri` (sayfa_tipi='urun', referans_id={id})

### API Uçları
- `GET /api/v1/urunler?lang=tr&per_page=100` → `$liste` (ilgili ürünler)
- `GET /api/v1/urunler/{id}?lang=tr` → `$detayYanit`
- `GET /api/v1/yorumlar/ozet?lang=tr&urun_id={id}` → `$ozetYanit`

### Frontend Render
- `frontend/sayfalar/urun-detay.php:11` + `:29` + `:46` + `:79`
- `H1: $bulunan['baslik']` → `<h1><?= htmlspecialchars($bulunan['baslik']) ?>`

### SEO
- `seoMeta()` → `seo_verileri` (UNIQUE: sayfa_tipi, referans_id, dil_kodu)
- `jsonldUrun($bulunan, $dil, $derecelendirme)` → Product + AggregateRating

### JSON-LD
- `Product` schema + `AggregateRating` (yorum sayısına bağlı)

### Form
- Yorum formu → `POST /api/v1/yorumlar`
- `yorumlar.js` → modal + form

---

## 4. SSS (SSS)

### DB Kaynak
- `sss_sorulari` + `sss_cevirileri`
- `seo_verileri` (sayfa_tipi='sss')

### API Uçları
- `GET /api/v1/sss-sorulari?lang=tr` → `$yanit`

### Frontend Render
- `frontend/sayfalar/sss.php:7` + `:127`
- `H1: sss_baslik` → `<h1><?= t('sss_baslik') ?>`

### SEO
- `seoMeta()` → `seo_verileri`
- `jsonldSss($ogeler)` → FAQPage (24 giriş mainEntity)

### JSON-LD
- `FAQPage` — 24 soru/cevap çifti
- `ogeler === []` ise → `jsonld` unset edilir (satır 93)

### Feature Toggle
- `etkinMi('sss')` → kapalıysa 410 sayfası

### JS Bağımlılık
- `ana.js` → `data-akordeon` click → `aria-expanded` toggle
- SSS sekme: `data-kapsam` attribute → client-side filtre

---

## 5. Blog Listesi + Detay

### DB Kaynak
- `blog_yazilari` + `blog_yazisi_cevirileri` (dil çevirileri)
- `seo_verileri` (sayfa_tipi='blog', referans_id={id})

### API Uçları
- `GET /api/v1/blog?lang=tr&limit=20` → `$liste` (blog listesi)
- `GET /api/v1/blog/{slug}?lang=tr` → `$yanit` (detay)

### Frontend Render
- `frontend/sayfalar/blog.php:7` + `blog-detay.php:9` + `:37`

### SEO
- `seoMeta()` → `seo_verileri`
- `jsonldMakale($yazi)` → Article schema (blog-detay)

### JSON-LD
- `Article` schema — `jsonldMakale()`

### Form
- Yorum formu → `POST /api/v1/yorumlar`
- `yorumlar.js` → modal

---

## 6. Şehir Landing (2 Desen × 3 Şehir)

### DB Kaynak
- `sehirler` + `sehir_cevirileri` (şehirler tablosu)
- `seo_verileri` (sayfa_tipi='sehir', referans_id={id})

### API Uçları
- `GET /api/v1/sehirler?lang=tr` → `$liste` (sehir listesi)
- `GET /api/v1/sehirler/{slug}?lang=tr` → `$yanit` (detay)

### Frontend Render
- `frontend/sayfalar/sehirler.php:7` + `sehir-landing.php:9` + `:42`

### SEO
- `seoMeta()` → `seo_verileri`
- Custom JSON-LD → `sehir-landing.php:31` (yerel işletme)

### URL Desenleri
- `/sehirler` → şehir listesi
- `/sehir/{slug}` → şehir landing sayfası

---

## 7. Sertifikalar

### DB Kaynak
- `sertifikalar` + `sertifika_cevirileri`
- `seo_verileri`

### API Uçları
- `GET /api/v1/sertifikalar?lang=tr` → `$liste`

### Frontend Render
- `frontend/sayfalar/sertifikalar.php:7` + `:17`
- `H1: Sertifikalarımız` (hardcoded)

### SEO
- `seoMeta()` → `seo_verileri`
- `sertifikaBand()` bileşeni

---

## 8. Ekip

### DB Kaynak
- `kullanicilar (rol=yonetici)`
- `seo_verileri`

### API Uçları
- `GET /api/v1/kullanicilar?lang=tr` → `$liste`

### Frontend Render
- `frontend/sayfalar/ekibimiz.php:7` + `:23`
- `H1: Ekibimiz` (hardcoded)

### SEO
- `seoMeta()` → `seo_verileri`
- `ItemList` JSON-LD (kisiListesi)

---

## 9. Garanti

### DB Kaynak
- `ayarlar` (ayarlar tablosu)
- `seo_verileri`

### API Uçları
- `GET /api/v1/ayarlar?lang=tr` → `$ayarlar`

### Frontend Render
- `frontend/sayfalar/garanti.php:7` + `:64`
- `H1: Garanti Koşulları` (hardcoded)

### SEO
- `seoMeta()` → `seo_verileri`
- No JSON-LD

---

## 10. Rehberler (4 Statik)

### DB Kaynak
- `seo_verileri` (statik sayfalar)
- `ayarlar` (harita bilgisi — bazı rehberlerde)

### API Uçları
- `GET /api/v1/ayarlar?lang=tr` (harita için)

### Frontend Render
- `rehber-bakim.php`, `rehber-fark.php`, `rehber-istanbul-bakim-takvimi.php`, `rehber-malzeme.php`
- `H1` — `t('bakim_baslik')`, `t('fark_baslik')`, `t('istanbul_bakim_baslik')`, `t('malzeme_baslik')`

### SEO
- `seoMeta()` → `seo_verileri`
- No JSON-LD

### JS Bağımlılık
- `rehber-istanbul-bakim-takvimi.php:152` → `data-hedef` attribute + countdown JS
- `rehber-istanbul-bakim-takvimi.php:185` → countdown timer

---

## 11. İletişim

### DB Kaynak
- `ayarlar` (harita URL)
- `seo_verileri`

### API Uçları
- `GET /api/v1/ayarlar?lang=tr` → `$haritaAyar`

### Frontend Render
- `frontend/sayfalar/iletisim.php:29` + `:36` + `:45`
- `H1: iletisim_baslik` → `t('iletisim_baslik')`

### SEO
- `seoMeta()` → `seo_verileri`
- `jsonldYerelIsletme()` → LocalBusiness schema

### Form
- `#iletisim-formu` → `POST /api/v1/iletisim`

---

## 12. Teklif Al

### DB Kaynak
- `seo_verileri`
- `ayarlar` (harita — formda kullanılabilir)

### API Uçları
- `POST /api/v1/leads` → Teklif oluştur
- `POST /api/v1/appointments` → Randevu oluştur

### Frontend Render
- `frontend/sayfalar/teklif-al.php:20` + `:24` + `:52`
- `H1: teklif_baslik` → `t('teklif_baslik')`

### SEO
- `seoMeta()` → `seo_verileri`
- `jsonldIletisimSayfasi()` → LocalBusiness schema

### Form
- `#teklif-formu` → `POST /api/v1/leads` (data-uc="/leads")
- `#randevu-formu` → `POST /api/v1/appointments` (data-uc="/appointments")

---

## 13. Kampanyalar

### DB Kaynak
- `kampanyalar` + `kampanya_cevirileri`
- `seo_verileri`

### API Uçları
- `GET /api/v1/kampanyalar?lang=tr` → `$liste`

### Frontend Render
- `frontend/sayfalar/kampanyalar.php:7` + `:17`

---

## 14. Ödeme Bilgileri

### DB Kaynak
- `ayarlar`
- `seo_verileri`

### API Uçları
- `GET /api/v1/ayarlar?lang=tr` → `$ayarlar`

### Frontend Render
- `frontend/sayfalar/odeme-bilgileri.php:7` + `:24`

---

## 15. Sanal Tur

### DB Kaynak
- `sanal_turlar` + `sanal_tur_cevirileri`
- `seo_verileri`

### API Uçları
- `GET /api/v1/sanal-tur?lang=tr` → `$liste`

### Frontend Render
- `frontend/sayfalar/sanal-tur.php:7` + `:17`
- `H1: Sanal Tur` (hardcoded)

### JS Bağımlılık
- `viewer-360.js` → `data-kaynak` attribute → Pannellum

---

## 16. Sıcaklık Simülasyonu

### DB Kaynak
- `ayarlar`
- `seo_verileri`

### API Uçları
- `GET /api/v1/ayarlar?lang=tr` → form data

### Frontend Render
- `frontend/sayfalar/sicaklik-simulasyonu.php:14` + `:54`
- `#sicaklik-formu` → `data-api` attribute

### JS Bağımlılık
- `hesaplama-araci.js` → form submit

---

## 17. Referanslar

### DB Kaynak
- `donusumler` + `donusum_cevirileri`
- `video_referanslar` + `video_referans_cevirileri`
- `seo_verileri`

### API Uçları
- `GET /api/v1/donusumler?lang=tr` → `$donusumler`
- `GET /api/v1/video-referanslar?lang=tr` → `$videolar`

### Frontend Render
- `frontend/sayfalar/referanslar.php:18` + `:19` + `:26`
- `H1: referans_baslik` → `t('referans_baslik')`

### JS Bağımlılık
- `oncesi-sonrasi.js` → `data-oncesi-sonrasi` attribute
- `video-referans.js` → `data-video-ac` attribute

### Feature Toggle
- `etkinMi('referanslar')` → kapalıysa 410

---

## 18. Arama Sonuçları

### DB Kaynak
- `urunler` + `blog` + `sss` (çoklu tablo arama)
- `seo_verileri`

### API Uçları
- `GET /api/v1/arama?lang=tr&q={q}&tur=hepsi&sayfa=1&limit=20` → `$sonuc`

### Frontend Render
- `frontend/sayfalar/arama.php:20` + `:25`
- `H1: Arama: {q}` (hardcoded + dynamic)

### JS Bağımlılık
- `arama.js` → autocomplete + `arama_yapildi` GA4 event

---

## DB Tablo — Schema Referansı

| Tablo | API | Sayfa Türü |
|-------|-----|------------|
| `urunler` + `urun_cevirileri` | `/api/v1/urunler` | urunler, urun-detay |
| `sss_sorulari` + `sss_cevirileri` | `/api/v1/sss-sorulari` | sss, anasayfa |
| `blog_yazilari` + `blog_yazisi_cevirileri` | `/api/v1/blog` | blog, blog-detay |
| `sehirler` + `sehir_cevirileri` | `/api/v1/sehirler` | sehirler, sehir-landing |
| `sertifikalar` + `sertifika_cevirileri` | `/api/v1/sertifikalar` | sertifikalar |
| `kullanicilar (rol=yonetici)` | `/api/v1/kullanicilar` | ekibimiz |
| `kampanyalar` + `kampanya_cevirileri` | `/api/v1/kampanyalar` | kampanyalar |
| `ayarlar` | `/api/v1/ayarlar` | garanti, odeme-bilgileri, iletisim, sanal-tur, sicaklik |
| `donusumler` + `donusum_cevirileri` | `/api/v1/donusumler` | referanslar |
| `video_referanslar` + `video_referans_cevirileri` | `/api/v1/video-referanslar` | referanslar |
| `yorumlar` + `yorum_cevirileri` | `/api/v1/yorumlar` | urun-detay |
| `seo_verileri` | — | TÜM sayfalar |
| `kullanicilar` | — | Admin CRUD |
| `ozellik_toggle` | `/api/v1/ozellikler` | Feature toggle |

---

## SEO Verileri Tablosu

**Tablo:** `seo_verileri`  
**UNIQUE:** `(sayfa_tipi, sayfa_kodu, dil_kodu)` + `(sayfa_tipi, referans_id, dil_kodu)`

| sayfa_tipi | sayfa_kodu | referans_id | dil_kodu |
|------------|------------|-------------|----------|
| statik | anasayfa | NULL | tr/en/de/fr/it/ar |
| urun | {product_id} | {id} | tr/en/de/fr/it/ar |
| blog | {slug} | {id} | tr/en/de/fr/it/ar |
| sss | sss | NULL | tr/en/de/fr/it/ar |
| sehir | {slug} | {id} | tr/en/de/fr/it/ar |
| ... | ... | ... | ... |

**Sayfa değişiminde:** `$SEO` değişkeni güncellenmeli, `$SEO['jsonld']` unset edilmemeli (satır 93-94).
