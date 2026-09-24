# JS Davranışları — 10 Dosya

**Kaynak:** `frontend/assets/js/*.js` (10 dosya)  
**Analiz:** `for f in frontend/assets/js/*.js; do ...; done`

---

## ana.js — Genel Etkileşimler (12 listener/querySelector)

**Dosya:** `frontend/assets/js/ana.js`  
**Yüklendiği sayfalar:** TÜM sayfalar (router.php üzerinden)  
**Yükleme:** `<script src="/assets/js/ana.js">` — `DOMContentLoaded`

### Tetikleyiciler
| Event | Hedef | Ne Yapar |
|-------|-------|----------|
| `DOMContentLoaded` | `[data-akordeon]` | Akordeon toggle (aria-expanded + hidden) |
| `DOMContentLoaded` | `.whatsapp-sabit` | WhatsApp tıklaması → `whatsapp_tiklandi` GA4 event |
| `DOMContentLoaded` | `a[href^="tel:"]` | Telefon tıklaması → `telefon_tiklandi` GA4 event |
| `DOMContentLoaded` | `a[href^="whatsapp:"]` | WhatsApp link → `whatsapp_tiklandi` event |
| `DOMContentLoaded` | `.dil-secici a` | Dil değişimi → `dil_degistirildi` GA4 event |
| `DOMContentLoaded` | `#cerez-kabul` | Çerez onay → consent mode güncelleme |
| `DOMContentLoaded` | `#cerez-red` | Çerez reddet → analytics devre dışı |
| `DOMContentLoaded` | `.whatsapp-sabit` | WhatsApp click GA4 |
| click | `.whatsapp-sabit` | `whatsapp_tiklandi` event |
| click | `a[href^="tel:"]` | `telefon_tiklandi` event |
| click | `a[href^="whatsapp:"]` | `whatsapp_tiklandi` event |
| `DOMContentLoaded` | GA4 consent | `gtag('consent', ...)` |

### DOM Manipülasyonu
- `[data-akordeon]` → `aria-expanded` toggle + `.icerik` hidden
- `.whatsapp-sabit` → click event listener
- `a[href^="tel:"]` → click event listener

### GA4 Events
- `whatsapp_tiklandi` — WhatsApp butonuna tıklayınca
- `telefon_tiklandi` — Telefon linkine tıklayınca
- `dil_degistirildi` — Dil değiştirildiğinde
- `hesaplama_yapildi` — Hesaplama aracı sonucunda (hesaplama-araci.js üzerinden)
- `teklif_formu_gonderildi` — Teklif form submit
- `randevu_talebi_olusturuldu` — Randevu form submit

### Dış Kütüphane
- GA4 (`gtag.js`) — consent mode integration

### RTL Etki
- `inset-inline-end` CSS kullanımı → WhatsApp butonu sağda (LTR) / solda (RTL)

---

## arama.js — Arama Autocomplete (5 listener/querySelector)

**Dosya:** `frontend/assets/js/arama.js`  
**Yüklendiği sayfalar:** `arama.php`  
**Yükleme:** Sayfa başına özel script

### Tetikleyiciler
| Event | Hedef | Ne Yapar |
|-------|-------|----------|
| `DOMContentLoaded` | `#arama-ac` | Arama autocomplete container aç |
| click | `#arama-ac` | `#arama-katman` show + `#arama-girdi` focus |
| click | `#arama-kapat` | `#arama-katman` hide |
| input | `#arama-girdi` | Autocomplete suggestion (3+ karakter) |
| `DOMContentLoaded` | `#arama-girdi` | Focus + suggestion load |

### API Çağrıları
- `GET /api/v1/arama?lang=tr&q={q}&tur=hepsi&sayfa=1&limit=20`
- Debounce: input'dan 300ms sonra

### GA4 Event
- `arama_yapildi` — `{q, tur: 'hepsi'}`

### Local Storage
- Yok

### Dış Kütüphane
- Yok (vanilla JS)

---

## hesaplama-araci.js — Hesaplama Aracı (2 listener/querySelector)

**Dosya:** `frontend/assets/js/hesaplama-araci.js`  
**Yüklendiği sayfalar:** `anasayfa.php`, `sicaklik-simulasyonu.php`  

### Tetikleyiciler
| Event | Hedef | Ne Yapar |
|-------|-------|----------|
| `DOMContentLoaded` | `#hesap-formu` | Form submit listener |
| submit | `#hesap-formu` | `POST /api/v1/calculate` → sonuç göster |

### API Çağrıları
- `POST /api/v1/calculate` — `{lang, material, model, usage}` JSON body

### GA4 Event
- `hesaplama_yapildi` — `{alan, malzeme, model, dil, fiyat}`

### DOM Manipülasyonu
- `#hesap-sonuc` → fiyat gösterimi
- `#hesap-kapasite` → kapasite gösterimi (people sayısı)

### Dış Kütüphane
- Yok (vanilla JS fetch)

### Kritik Dependency
- `data-dil` attribute → `data-teklif` attribute → `/teklif-al` redirect URL

---

## iletisim.js — İletişim Formu (4 listener/querySelector)

**Dosya:** `frontend/assets/js/iletisim.js`  
**Yüklendiği sayfalar:** `iletisim.php`

### Tetikleyiciler
| Event | Hedef | Ne Yapar |
|-------|-------|----------|
| DOMContentLoaded | `#iletisim-formu` | Form submit listener |
| submit | `#iletisim-formu` | `POST /api/v1/iletisim` → sonucu göster |
| `DOMContentLoaded` | `#iletisim-formu` | Form validation init |
| click | `#iletisim-formu button[type=submit]` | Submit handler |

### API Çağrıları
- `POST /api/v1/iletisim` — FormData JSON → `{ad_soyad, eposta, telefon, konu, mesaj, kvkk_onayi}`

### GA4 Event
- İletişim form submit → event tetiklenir (analytics.php üzerinden)

### DOM Manipülasyonu
- `#iletisim-formu` → form gönder
- Sonuç → `#iletisim-formu` içindeki `.uyari` div

### Dış Kütüphane
- Yok

### Form Validation
- `required`, `minlength="3"`, `maxlength="120"`, `type="email"`, `inputmode="tel"`
- Honeypot: `web_sitesi` (hidden, tabindex=-1)

---

## teklif-al.js — Teklif + Randevu Formları (5 listener/querySelector)

**Dosya:** `frontend/assets/js/teklif-al.js`  
**Yüklendiği sayfalar:** `teklif-al.php`

### Tetikleyiciler
| Event | Hedef | Ne Yapar |
|-------|-------|----------|
| DOMContentLoaded | `form[data-uc]` | Her form için submit listener |
| submit | `#teklif-formu` | `POST /api/v1/leads` → GA4 `teklif_formu_gonderildi` |
| submit | `#randevu-formu` | `POST /api/v1/appointments` → GA4 `randevu_talebi_olusturuldu` |
| submit | `form[data-uc]` | `fetch(api + uc, {method: 'POST'})` → JSON parse |
| DOMContentLoaded | `form[data-uc]` | `data-ok`, `data-hata` attribute'larını oku |

### API Çağrıları
- `POST /api/v1/leads` — Teklif formu
- `POST /api/v1/appointments` — Randevu formu

### GA4 Events
- `teklif_formu_gonderildi` — `{sehir, dil_kodu}`
- `randevu_talebi_olusturuldu` — `{}`

### DOM Manipülasyonu
- `#teklif-formu` → submit → `data-sonuc` container içine `.uyari`
- `#randevu-formu` → submit → `data-sonuc` container içine `.uyari`

### Form Validation
- `required`, `minlength="3"`, `type="number"`, `step="0.1"`, `kvkk_onayi` checkbox
- Honeypot: `web_sitesi` (hidden)
- KVKK: `kvkk_onayi` checkbox required

### Critical Attribute Dependency
- `data-api`, `data-uc`, `data-sonuc`, `data-ok`, `data-hata`, `data-dil`

---

## karsilastirma.js — Ürün Karşılaştırma (3 listener/querySelector)

**Dosya:** `frontend/assets/js/karsilastirma.js`  
**Yüklendiği sayfalar:** `karsilastir.php`

### Tetikleyiciler
| Event | Hedef | Ne Yapar |
|-------|-------|----------|
| DOMContentLoaded | `#karsilastirma-alan` | Karşılaştırma alanını başlat |
| click | comparison buttons | Ürün seçimi/takas |
| DOMContentLoaded | comparison items | 3 ürün karşılaştırma render |

### API Çağrıları
- `GET /api/v1/karsilastir` — karşılaştırma verisi

### Dış Kütüphane
- Yok

---

## oncesi-sonrasi.js — Before/After Slider (5 listener/querySelector)

**Dosya:** `frontend/assets/js/oncesi-sonrasi.js`  
**Yüklendiği sayfalar:** `referanslar.php`

### Tetikleyiciler
| Event | Hedef | Ne Yapar |
|-------|-------|----------|
| DOMContentLoaded | `[data-oncesi-sonrasi]` | Slider initialization |
| click | slider handle | Before/after görüntü değiştir |
| mousemove | slider | Drag interaction |
| touchstart | slider | Touch support |
| DOMContentLoaded | `data-video-ac` buttons | Video modal aç |

### Dış Kütüphane
- Yok (vanilla JS slider)

---

## video-referans.js — Video Referans (3 listener/querySelector)

**Dosya:** `frontend/assets/js/video-referans.js`  
**Yüklendiği sayfalar:** `referanslar.php`

### Tetikleyiciler
| Event | Hedef | Ne Yapar |
|-------|-------|----------|
| DOMContentLoaded | `data-video-ac` buttons | Video embed modal |
| click | `.video-izle-btn` | Embed URL'yi modal içine yükle |
| DOMContentLoaded | `.video-embed` | Video container init |

### Dış Kütüphane
- Yok (iframe embed)

---

## viewer-360.js — 360° Viewer (3 listener/querySelector)

**Dosya:** `frontend/assets/js/viewer-360.js`  
**Yüklendiği sayfalar:** `sanal-tur.php`

### Tetikleyiciler
| Event | Hedef | Ne Yapar |
|-------|-------|----------|
| DOMContentLoaded | `.sanal-kap[data-kaynak]` | Pannellum viewer init |
| click | modal open | 360° tur modal aç |
| DOMContentLoaded | `cerceve.src` | `data-kaynak` embed URL yükle |

### Dış Kütüphane
- **Pannellum** — 360° panorama viewer

### Data Attribute
- `data-kaynak` → embed URL (Pannellum JSON)

---

## Genel JS Özellikleri

| Özellik | Değer |
|---------|-------|
| Toplam JS dosyası | 10 |
| Ortalama listener/saya | 4.5 |
| Kullanılan Dış Kütüphane | Pannellum, GA4 (gtag.js) |
| GA4 Event Sayısı | 6 (`hesaplama_yapildi`, `teklif_formu_gonderildi`, `randevu_talebi_olusturuldu`, `whatsapp_tiklandi`, `telefon_tiklandi`, `dil_degistirildi`) |
| LocalStorage | Yok |
| SessionStorage | Yok |
| Honeypot Fields | `web_sitesi` (iletisim, teklif formlarda) |
| KVKK Fields | `kvkk_onayi` (required checkbox) |
| Consent Mode | GA4/Clarity `ad_storage:'denied'` (kabul edilene kadar) |

---

## JS Değişim Etkisi

| JS Dosyası Değişirse | Etkilenen Sayfalar | Kritiklik |
|----------------------|-------------------|-----------|
| `ana.js` | 31 sayfa | **YÜKSEK** (nav, dil, events) |
| `teklif-al.js` | 1 sayfa (2 form) | **YÜKSEK** (form submit) |
| `iletisim.js` | 1 sayfa | ORTA |
| `hesaplama-araci.js` | 2 sayfa | ORTA |
| `arama.js` | 1 sayfa | ORTA |
| `yorumlar.js` | 1 sayfa | ORTA |
| `karsilastirma.js` | 1 sayfa | DÜŞÜK |
| `oncesi-sonrasi.js` | 1 sayfa | DÜŞÜK |
| `video-referans.js` | 1 sayfa | DÜŞÜK |
| `viewer-360.js` | 1 sayfa | DÜŞÜK |
