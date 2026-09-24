# UI Bağımlılıkları — Kritik Harita

**Amaç:** "Şu alanı değiştirirsen şu JS kırılır" haritası
**Kanıt:** `grep -rn "<seçici>" frontend/`

---

## Bağımlılık #1: `#hesap-formu` ID

- **Kaynak (değiştirilen):** `<form id="hesap-formu">`
- **Etkilenen:** `hesaplama-araci.js` (satır 2: `document.getElementById('hesap-formu')`)
- **Semptom:** Form submit handlerı çalışmaz, hesaplama sonuç gösterilmez
- **Düzeltme:** `getElementById('hesap-formu')` çağrısını koru veya `data-form` attribute kullan

## Bağımlılık #2: `data-kapsam` Attribute

- **Kaynak (değiştirilen):** `<button ... data-kapsam="">` (sss.php:98-102)
- **Etkilenen:** `sss.php` client-side filtre + `ana.js` akordeon
- **Semptom:** SSS sekme filtresi çalışmaz, akordeon tüm soruları gösterir
- **Düzeltme:** `data-kapsam` attribute'larını silme veya rename etme

## Bağımdlık #3: `.sss-akordeon` / `data-akordeon`

- **Kaynak:** `<div data-akordeon>` (sss.php, ana.js)
- **Etkilenen:** `ana.js` (satır 3-4: `querySelectorAll('[data-akordeon]')`)
- **Semptom:** Akordeon açma/kapama çalışmaz, `aria-expanded` toggle bozulur
- **Düzeltme:** `data-akordeon` attribute'larını koru

## Bağımlılık #4: `#teklif-formu` ID

- **Kaynak:** `<form id="teklif-formu">` (teklif-al.php:24)
- **Etfilenen:** `teklif-al.js` (shared `form[data-uc]` handler)
- **Semptom:** Teklif submit çalışmaz, GA4 `teklif_formu_gonderildi` tetiklenmez
- **Düzeltme:** `id="teklif-formu"` veya `data-uc="/leads"` attribute'ı korunmalı

## Bağımlılık #5: `data-dil` Attribute (Header)

- **Kaynak:** `<header data-dil="tr">` veya `data-dil` attribute
- **Etfilenen:** `ana.js` dil değişimi, `apiGet` parametresi
- **Semptom:** Dil değiştirme çalışmaz, API çağrılarda dil parametresi yok
- **Düzeltme:** `data-dil` attribute'ı tüm formlarda ve header'da korunmalı

## Bağımlılık #6: `whatsapp-tiklandi` Event

- **Kaynak:** `<a data-event="whatsapp">` veya `.whatsapp-sabit` (ana.js:16)
- **Etfilenen:** `ana.js` click handler → `kamelyaOlay('whatsapp_tiklandi')`
- **Semptom:** WhatsApp tıklamasında GA4 event yok
- **Düzeltme:** `.whatsapp-sabit` class'ı veya `data-event` attribute'ı korunmalı

## Bağımlılık #7: `viewer-360` Modal / `data-tur="360"`

- **Kaynak:** `<div class="sanal-kap" data-kaynak="...">` (sanal-tur.php:22)
- **Etfilenen:** `viewer-360.js` → Pannellum init
- **Semptom:** 360° tur modal açılmaz, `cerceve.src` boş kalır
- **Düzeltme:** `data-kaynak` attribute'ı korunmalı

## Bağımlılık #8: `seo-dashboard` Chart Element

- **Kaynak:** `<div id="seo-dashboard">` veya Chart.js canvas
- **Etfilenen:** `seo-dashboard.js` → Chart.js
- **Semptom:** SEO dashboard chart render edilmez
- **Düzeltme:** Chart.js canvas element'i ve ID korunmalı

## Bağımlılık #9: `#arama-kutusu` Input

- **Kaynak:** `<input id="arama-girdi">` (arama.js)
- **Etfilenen:** `arama.js` autocomplete (3+ karakter → suggestion)
- **Semptom:** Arama autocomplete çalışmaz, `arama_yapildi` GA4 event tetiklenmez
- **Düzeltme:** `id="arama-girdi"` ve `id="arama-katman"` ID'leri korunmalı

## Bağımlılık #10: `#yorum-formu` Honeypot

- **Kaynak:** `<input type="text" name="web_sitesi" style="display:none">`
- **Etfilenen:** `yorumlar.js` form submit + spam filter
- **Semptom:** Spam filtresi devre dışı kalır, botlar yorum ekler
- **Düzeltme:** `web_sitesi` hidden field silinmemeli

## Bağımlılık #11: `jsonldSss($ogeler)` Fonksiyonu

- **Kaynak:** `frontend/includes/seo.php:127`
- **Etfilenen:** `sss.php:91` → FAQPage JSON-LD
- **Semptom:** SSS sayfasında FAQPage schema yok, Google tarafından okunmaz
- **Düzeltme:** `$ogeler !== []` kontrolü korunmalı (satır 93)

## Bağımlılık #12: `hreflang` Link'leri

- **Kaynak:** `seo_verileri` tablosu → `hreflang_json` (7 giriş)
- **Etfilenen:** `frontend/router.php` → hreflang link'leri
- **Semptom:** Hreflang tags eksik → Google tarafından dil eşleştirmesi bozulur
- **Düzeltme:** `seo_verileri` tablosundaki `hreflang_json` alan korunmalı

## Bağımlılık #13: `tasarim-sistemi.css` `--renk-birincil` Token

- **Kaynak:** `frontend/assets/css/tasarim-sistemi.css`
- **Etfilenen:** `.btn-birincil`, `.hesap-kapasite` (TÜM CTA butonları)
- **Semptom:** CTA butonları düünebilir veya görünmez olabilir
- **Düzeltme:** `--renk-birincil` token'ın tanımını koru

## Bağımlılık #14: `margin-inline` RTL Layout

- **Kaynak:** `frontend/assets/css/tasarim-sistemi.css:69` → `.kapsayici { margin-inline: auto }`
- **Etfilenen:** TÜM sayfalar (`.kapsayici`, `.whatsapp-sabit`, `.akordeon-baslik`)
- **Semptom:** RTL'de layout bozulur, `margin-left/right` kullanılırsa Arapça'da yanlış hizalama
- **Düzeltme:** `margin-left/right` YASAK — `margin-inline-start/end` kullan

## Bağımlılık #15: `bilesenler.php:header()`

- **Kaynak:** `frontend/includes/bilesenler.php:7` → `header()` fonksiyonu
- **Etfilenen:** 31 sayfa (her sayfada `include 'bilesenler.php'`)
- **Semptom:** Header (nav, dil değiştirici, logo) tüm sayfalarda kaybolur
- **Düzeltme:** `header()`, `footer()`, `nav()` fonksiyonlarını koru

## Bağımlılık #16: `seo_verileri` Tablosu

- **Kaynak:** `seo_verileri` (sayfa_tipi, referans_id, dil_kodu, meta_baslik, meta_aciklama)
- **Etfilenen:** `seoMeta()` → tüm sayfaların `<head>` meta tag'ları
- **Semptom:** Meta tags boş veya eksik → SEO çöker
- **Düzeltme:** `$SEO` değişkeni ve `$SEO['jsonld']` unset edilmemeli (satır 93)

## Bağımlılık #17: `ozellik_toggle` Feature Toggle

- **Kaynak:** `ozellik_toggle` tablosu → `etkinMi('anahtar')`
- **Etfilenen:** `anasayfa.php` (4 toggle), `410.php` (fallback)
- **Semptom:** Feature kapalıysa 410 sayfası gösterilir, içerik yok
- **Düzeltme:** `ozellikMap` ve `etkinMi()` fonksiyonlarını koru

## Bağımlılık #18: Consent Mode → GA4/Clarity

- **Kaynak:** `analytics.php:24-26` → `gtag('consent','default',{ad_storage:'denied'})`
- **Etfilenen:** `ana.js` GA4 events, `clarity.php` Clarity tracker
- **Semptom:** Consent alınmadan GA4/Clarity devre dışı, analytics yok
- **Düzeltme:** `analytics.php` consent mode init kodunu koru

## Bağımlılık #19: `apiGet('/sss-sorulari', $dil)` → Boş Sayfa

- **Kaynak:** `sss.php:7`, `anasayfa.php:46`
- **Etfilenen:** SSS bölümü boş kalır
- **Semptom:** API başarısız olursa `$yanit = null` → `sssAkordeon([])` → boş akordeon
- **Düzeltme:** `apiGet` null kontrolü + fallback mesaj

## Bağımlılık #20: `Product` JSON-LD → `aggregateRating`

- **Kaynak:** `frontend/includes/seo.php:76` → `jsonldUrun()`
- **Etfilenen:** `urun-detay.php:56` → Product JSON-LD
- **Semptom:** Yorum yoksa `aggregateRating` eksik → schema invalid
- **Düzeltme:** `$derecelendirme` parametresi null kontrolü

## Bağımlılık #21: `data-teklif` Attribute

- **Kaynak:** `frontend/sayfalar/teklif-al.php` (hesaplama aracı linki)
- **Etfilenen:** `hesaplama-araci.js` (satır 4: `form.getAttribute('data-teklif') || '/teklif-al'`)
- **Semptom:** Hesaplama sonrası `/teklif-al` redirect çalışmaz
- **Düzeltme:** `data-teklif` attribute'ı korunmalı

## Bağımlılık #22: `#aranan-kutusu` Autocomplete

- **Kaynak:** `frontend/sayfalar/arama.php`, `arama.js`
- **Etfilenen:** `arama.js` autocomplete (3+ karakter → suggestion)
- **Semptom:** Arama suggestions çalışmaz, `arama_yapildi` GA4 event tetiklenmez
- **Düzeltme:** `id="arama-girdi"` ve `id="arama-oneri"` ID'leri korunmalı

## Bağımlılık #23: `#sayac` Countdown

- **Kaynak:** `frontend/sayfalar/rehber-istanbul-bakim-takvimi.php:152`
- **Etfilenen:** `data-hedef` attribute → `#sayac` countdown
- **Semptom:** Sayacı hedef tarihi boş kalır, countdown çalışmaz
- **Düzeltme:** `data-hedef="YYYY-MM-DDTHH:MM:SS"` formatını koru

## Bağımlılık #24: `data-oncesi-sonrasi` Attribute

- **Kaynak:** `frontend/sayfalar/referanslar.php:26`
- **Etfilenen:** `oncesi-sonrasi.js` → before/after slider
- **Semptom:** Slider çalışmaz, `data-oncesi-sonrasi` element yok
- **Düzeltme:** `data-oncesi-sonrasi` attribute'ı korunmalı

## Bağımlılık #25: `#karsilastirma-alan` Div

- **Kaynak:** `frontend/sayfalar/karsilastir.php:17`
- **Etfilenen:** `karsilastirma.js` → comparison items
- **Semptom:** Karşılaştırma alanı render edilmez
- **Düzeltme:** `data-api`, `data-dil` attribute'ları korunmalı

---

## Genel Bağımlılık Matrisi

| Değişiklik | Etkilenen | Etkilenen Sayfa Sayısı | Kritiklik |
|------------|-----------|------------------------|-----------|
| `data-akordeon` | `ana.js` | 1 | ORTA |
| `data-kapsam` | `sss.php` | 1 | ORTA |
| `#hesap-formu` | `hesaplama-araci.js` | 2 | ORTA |
| `#teklif-formu` | `teklif-al.js` | 1 | **YÜKSEK** |
| `data-dil` | `ana.js`, tüm formlar | 31 | **YÜKSEK** |
| `.whatsapp-sabit` | `ana.js` | 31 | ORTA |
| `data-kaynak` | `viewer-360.js` | 1 | ORTA |
| `#arama-girdi` | `arama.js` | 1 | ORTA |
| `#yorum-formu` | `yorumlar.js` | 1 | ORTA |
| `--renk-birincil` | CSS, `hesaplama-araci.js` | 31 | **YÜKSEK** |
| `margin-inline` | CSS | 31 | **YÜKSEK** |
| `seo_verileri` | `seoMeta()` | 31 | **YÜKSEK** |
| `etkinMi()` | `anasayfa.php`, `410.php` | 4 | **YÜKSEK** |
| `jsonldSss()` | `sss.php` | 1 | ORTA |
| `apiGet` | 17+ sayfa | 17+ | **YÜKSEK** |
| `data-teklif` | `hesaplama-araci.js` | 1 | ORTA |
| `data-hedef` | countdown JS | 1 | DÜŞÜK |
| `#sayac` | countdown JS | 1 | DÜŞÜK |
| `data-oncesi-sonrasi` | `oncesi-sonrasi.js` | 1 | DÜŞÜK |
| `#karsilastirma-alan` | `karsilastirma.js` | 1 | DÜŞÜK |
| `bilesenler.php` | 31 sayfa | 31 | **YÜKSEK** |
