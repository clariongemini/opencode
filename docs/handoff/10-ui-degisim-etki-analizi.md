# UI Değişim Etkisi Analizi — Blast Radius

**Veri:** `grep -rn "include.*<dosya>" frontend/sayfalar/ | wc -l`
**Amaç:** Her UI değişikliğinin etki alanını belirle

---

## Yüksek Etki (1 Dosya → Tüm Site)

| Dosya | Etkilenen Sayfa Sayısı | Etkilenen URL | Test Gerekli |
|-------|------------------------|---------------|--------------|
| `frontend/includes/bilesenler.php` | 31 | ~186 | `grep -rn "bilesenler" frontend/sayfalar/ \| wc -l` |
| `frontend/assets/css/tasarim-sistemi.css` | 31 | ~186 | CSS token change → tüm renk/spacing/font |
| `frontend/includes/seo.php` | 31 | ~186 | `grep -rn "seoMeta\|jsonld" frontend/sayfalar/` |
| `frontend/includes/api.php` | 31 | ~186 | `grep -rn "apiGet\|apiPost" frontend/sayfalar/` |
| `frontend/includes/analytics.php` | 31 | ~186 | `grep -rn "analyticsKafa\|cerezBand" frontend/` |
| `frontend/router.php` | 31 | ~186 | Tüm URL routing |
| `frontend/lang/*.php` (6 dosya) | 31 | ~186 | `grep -rn "t('" frontend/sayfalar/ | wc -l` |

### Detaylı Etkiler

**`bilesenler.php`:**
```bash
grep -rn "include.*bilesenler\|require.*bilesenler" frontend/sayfalar/ | wc -l
# Sonuç: 31 (her sayfada)
```
- `header()` → nav, dil değiştirici, logo
- `footer()` → altbilgi, sosyal ikonlar
- `urunKarti()` → ürün kartı (anasayfa, urunler, karsilastir)
- `sssAkordeon()` → SSS akordeonu (sss.php, anasayfa)
- `kirinti()` → breadcrumb (31 sayfa)
- `paylasButonlari()` → paylaşım (blog-detay, urun-detay, referanslar)
- `sosyalIkonlar()` → sosyal medya
- `instagramBolumi()` → Instagram feed
- `kampanyaBand()` → kampanya bantı
- `sertifikaBand()` → sertifika logosu
- `hesapAraci()` → hesaplama aracı (anasayfa)

**`tasarim-sistemi.css`:**
```bash
grep -rn "var(--" frontend/sayfalar/ frontend/includes/ | wc -l
# Sonuç: ~150+ kullanım
```
- Renk değişimi → TÜM sayfalar
- Font değişimi → TÜM sayfalar
- Spacing değişimi → TÜM sayfalar
- Border-radius değişimi → `.btn`, `.card`, `.input`

**`seo.php`:**
```bash
grep -rn "seoMeta\|jsonldOrganizasyon\|jsonldUrun\|jsonldSss" frontend/sayfalar/
# Sonuç: 31 sayfa × SEO fonksiyon çağrıları
```
- `seoMeta()` → meta tags (31 sayfa)
- `jsonldOrganizasyon()` → 2 sayfa (anasayfa, hakkimizda)
- `jsonldUrun()` → 1 sayfa (urun-detay)
- `jsonldSss()` → 1 sayfa (sss)
- `jsonldMakale()` → 1 sayfa (blog-detay)
- `jsonldYerelIsletme()` → 1 sayfa (iletisim)

**`api.php`:**
```bash
grep -rn "apiGet\|apiPost" frontend/sayfalar/ frontend/assets/js/
# Sonuç: ~25+ sayfada kullanım
```

**`analytics.php`:**
```bash
grep -rn "analyticsKafa\|cerezBand" frontend/
# Sonuç: tüm sayfalar
```

---

## Orta Etki (Sayfa Grubu)

| Dosya | Etkilenen Sayfa Sayısı | Etkilenen URL | Test Gerekli |
|-------|------------------------|---------------|--------------|
| `frontend/includes/sayfa.php` | 31 | ~186 | `sayfaUst()`, `sayfaAlt()` |
| `frontend/assets/js/ana.js` | 31 | ~186 | nav, dil, events |
| `frontend/includes/bilesenler.php` (tek fonksiyon) | 3-5 | ~15 | `urunKarti()`, `sssAkordeon()`, `paylasButonlari()` |
| `frontend/includes/seo.php` (tek jsonld) | 1-2 | ~3 | `jsonldUrun()`, `jsonldSss()` |
| `frontend/assets/js/hesaplama-araci.js` | 2 | ~3 | anasayfa, sicaklik-simulasyonu |
| `frontend/assets/js/teklif-al.js` | 1 | ~1 | teklif-al.php |
| `frontend/assets/js/iletisim.js` | 1 | ~1 | iletisim.php |
| `frontend/assets/js/arama.js` | 1 | ~1 | arama.php |
| `frontend/assets/js/yorumlar.js` | 1 | ~1 | urun-detay.php |
| `frontend/includes/yorumlar.php` | 1 | ~1 | urun-detay.php |

---

## Düşük Etki (Tekil Sayfa)

| Dosya | Etkilenen Sayfa Sayısı | Etkilenen URL | Test Gerekli |
|-------|------------------------|---------------|--------------|
| `frontend/sayfalar/anasayfa.php` | 1 | ~1 | anasayfa |
| `frontend/sayfalar/urunler.php` | 1 | ~1 | urunler |
| `frontend/sayfalar/urun-detay.php` | 1 | ~1 | urun-detay |
| `frontend/sayfalar/blog.php` | 1 | ~1 | blog |
| `frontend/sayfalar/blog-detay.php` | 1 | ~1 | blog-detay |
| `frontend/sayfalar/sss.php` | 1 | ~1 | sss |
| `frontend/sayfalar/arama.php` | 1 | ~1 | arama |
| `frontend/sayfalar/karsilastir.php` | 1 | ~1 | karsilastir |
| `frontend/sayfalar/iletisim.php` | 1 | ~1 | iletisim |
| `frontend/sayfalar/teklif-al.php` | 1 | ~1 | teklif-al |
| `frontend/sayfalar/ekibimiz.php` | 1 | ~1 | ekibimiz |
| `frontend/sayfalar/sertifikalar.php` | 1 | ~1 | sertifikalar |
| `frontend/sayfalar/garanti.php` | 1 | ~1 | garanti |
| `frontend/sayfalar/odeme-bilgileri.php` | 1 | ~1 | odeme-bilgileri |
| `frontend/sayfalar/kampanyalar.php` | 1 | ~1 | kampanyalar |
| `frontend/sayfalar/referanslar.php` | 1 | ~1 | referanslar |
| `frontend/sayfalar/sanal-tur.php` | 1 | ~1 | sanal-tur |
| `frontend/sayfalar/sehirler.php` | 1 | ~1 | sehirler |
| `frontend/sayfalar/sehir-landing.php` | 1 | ~1 | sehir-landing |
| `frontend/sayfalar/atolye.php` | 1 | ~1 | atolye |
| `frontend/sayfalar/galeri.php` | 1 | ~1 | galeri |
| `frontend/sayfalar/rehber-*.php` | 1 | ~1 | rehberler (4) |
| `frontend/sayfalar/sicaklik-simulasyonu.php` | 1 | ~1 | sicaklik |
| `frontend/sayfalar/bulten-onay.php` | 1 | ~1 | bulten-onay |
| `frontend/sayfalar/410.php` | 1 | ~1 | 410 |
| `frontend/sayfalar/cerez-politikasi.php` | 1 | ~1 | cerez-politikasi |
| `frontend/sayfalar/gizlilik.php` | 1 | ~1 | gizlilik |
| `frontend/sayfalar/hakkimizda.php` | 1 | ~1 | hakkimizda |

---

## UI Değişim Sonrası Retest Kapsamı

### 186 URL × Kontrol Maddeleri
- [ ] HTTP 200
- [ ] Title 50-60 byte
- [ ] Description 150-160 byte
- [ ] Canonical dolu
- [ ] robots index,follow
- [ ] H1 = 1
- [ ] hreflang 7 giriş
- [ ] JSON-LD geçerli
- [ ] OG + Twitter tam
- [ ] Viewport meta
- [ ] dir="rtl" (AR için)
- [ ] İç linkler 0 kırık
- [ ] Mobil taşma yok

### 10 JS Dosyası × Fonksiyon Testi
- `ana.js` → nav, dil, WhatsApp, telefon, GA4 events (6 event)
- `arama.js` → autocomplete, `arama_yapildi`
- `hesaplama-araci.js` → form submit, `hesaplama_yapildi`, kapasite
- `teklif-al.js` → form submit ×2, `teklif_formu_gonderildi`, `randevu_talebi_olusturuldu`
- `iletisim.js` → form submit, GA4 event
- `yorumlar.js` → modal, form submit
- `karsilastirma.js` → comparison
- `oncesi-sonrasi.js` → before/after slider
- `video-referans.js` → video embed
- `viewer-360.js` → Pannellum 360°

### 6 Form × Submit Testi
- İletişim → `POST /api/v1/iletisim` → 201 → GA4
- Teklif → `POST /api/v1/leads` → 201 → `teklif_formu_gonderildi`
- Randevu → `POST /api/v1/appointments` → 201 → `randevu_talebi_olusturuldu`
- Bülten → POST → `/bulten-onay`
- Yorum → `POST /api/v1/yorumlar` → 201
- Arama → GET `/api/v1/arama` → autocomplete

### 12 API Ucu × Curl Testi (×6 dil = 72 curl)
- `GET /api/v1/products` → 200
- `GET /api/v1/products/{id}` → 200
- `GET /api/v1/yorumlar/ozet` → 200
- `GET /api/v1/sss-sorulari` → 200
- `GET /api/v1/blog` → 200
- `GET /api/v1/blog/{slug}` → 200
- `GET /api/v1/sehirler` → 200
- `GET /api/v1/sehirler/{slug}` → 200
- `GET /api/v1/sertifikalar` → 200
- `GET /api/v1/ekip` → 200
- `GET /api/v1/kampanyalar` → 200
- `GET /api/v1/ayarlar` → 200
- `POST /api/v1/calculate` → 201
- `POST /api/v1/leads` → 201
- `POST /api/v1/appointments` → 201
- `POST /api/v1/yorumlar` → 201
- `POST /api/v1/iletisim` → 201
- `GET /api/v1/karsilastir` → 200
- `GET /api/v1/arama` → 200

### 6 Dil × RTL Testi
- `/tr/*`, `/en/*`, `/de/*`, `/fr/*`, `/it/*`, `/ar/*`
- `dir="rtl"` (AR için)
- `margin-inline` çalışıyor
- Form input `text-align: right` (AR)

### GA4 Event Testi (6 event × 6 dil = 36 event test)
- `hesaplama_yapildi`
- `teklif_formu_gonderildi`
- `randevu_talebi_olusturuldu`
- `whatsapp_tiklandi`
- `telefon_tiklandi`
- `dil_degistirildi`

---

## Değişim Oncelik Sırası

| Öncelik | Değişiklik | Etkilenen | Risk |
|---------|------------|-----------|------|
| 1 | `bilesenler.php` değişimi | 31 sayfa | **YÜKSEK** |
| 2 | `tasarim-sistemi.css` renk değişimi | 31 sayfa | **YÜKSEK** |
| 3 | `seo.php` değişimi | 31 sayfa | **YÜKSEK** |
| 4 | `api.php` değişimi | 17+ sayfa | **YÜKSEK** |
| 5 | `ana.js` değişimi | 31 sayfa | **YÜKSEK** |
| 6 | `lang/*.php` değişimi | 31 sayfa | **YÜKSEK** |
| 7 | `sayfa.php` değişimi | 31 sayfa | **YÜKSEK** |
| 8 | `teklif-al.js` değişimi | 1 sayfa | ORTA |
| 9 | `hesaplama-araci.js` değişimi | 2 sayfa | ORTA |
| 10 | Tekil sayfa değişimi | 1 sayfa | DÜŞÜK |
