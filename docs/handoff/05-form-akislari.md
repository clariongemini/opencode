# Form Akışları — 6 Form

**Kaynak:** `frontend/sayfalar/*.php` (formlar) + `frontend/assets/js/*.js` (handler'lar)

---

## 1. İletişim Formu

### Sayfa
- `frontend/sayfalar/iletisim.php`

### HTML
```html
<form id="iletisim-formu" data-api="<?= $apiTaban ?>" data-dil="<?= $dil ?>" data-ok="<?= $mesajOk ?>" data-hata="<?= $mesajHata ?>">
  <input id="i-ad" name="ad_soyad" required minlength="3" maxlength="120">
  <input id="i-eposta" name="eposta" type="email" required>
  <input id="i-tel" name="telefon" required inputmode="tel">
  <select id="i-konu" name="konu">...</select>
  <textarea id="i-mesaj" name="mesaj" required maxlength="5000"></textarea>
  <input type="hidden" name="tur" value="iletisim">
  <input type="text" name="web_sitesi" style="display:none" tabindex="-1" autocomplete="off" aria-hidden="true"> <!-- honeypot -->
  <label><input type="checkbox" name="kvkk_onayi" value="1" required> KVKK</label>
  <button class="btn btn-birincil" type="submit">Gönder</button>
</form>
```

### Client Validation
- `required`, `minlength="3"`, `maxlength="120"`, `type="email"`, `inputmode="tel"`, `required` (textarea), `required` (KVKK checkbox)

### Submit Handler
- **JS:** `iletisim.js` (lines 4+)
- **API:** `POST /api/v1/iletisim`
- **Body:** `FormData` → JSON (`{ad_soyad, eposta, telefon, konu, mesaj, kvkk_onayi, tur}`)

### Başarı
- `data-ok` mesajı → `.uyari-basarili` div gösterilir
- GA4: iletişim formu gönderildi event
- `data-sonuc` container içeriği güncellenir

### Hata
- 422 → `data-hata` mesajı → `.uyari-hata` div gösterilir
- KVKK onay yoksa → `required` browser validation

### Honeypot
- `web_sitesi` (hidden, tabindex=-1, aria-hidden=true) — botlar doldurursa server tarafından reddedilir

### KVKK
- `kvkk_onayi` checkbox required — kabul edilmeden form submit edilemez

### Feature Toggle
- Kapalıysa form gizlenebilir (özellik toggle kontrolü)

---

## 2. Teklif Formu

### Sayfa
- `frontend/sayfalar/teklif-al.php`

### HTML
```html
<form id="teklif-formu" data-api="..." data-dil="..." data-uc="/leads" data-sonuc="teklif-sonuc" data-ok="..." data-hata="...">
  <input id="t-ad" name="ad_soyad" required minlength="3" maxlength="120">
  <input id="t-tel" name="telefon" required inputmode="tel" aria-describedby="t-tel-hata">
  <input id="t-sehir" name="sehir" maxlength="100">
  <input id="t-alan" name="alan_m2" type="number" min="0.5" max="5000" step="0.1">
  <label><input type="checkbox" name="kvkk_onayi" value="1" required> KVKK</label>
  <input type="hidden" name="dil_kodu" value="tr">
  <input type="hidden" name="kaynak" value="web">
  <button class="btn btn-birincil" type="submit">Teklif Al</button>
</form>
```

### Client Validation
- `required`, `minlength="3"`, `type="number"`, `min="0.5"`, `max="5000"`, `step="0.1"`, `required` (KVKK)

### Submit Handler
- **JS:** `teklif-al.js` (lines 1-30+)
- **API:** `POST /api/v1/leads` (`data-uc="/leads"`)
- **Body:** `FormData` → JSON + `kvkk_onayi` (checked state)

### Başarı
- `data-ok` mesajı → `#teklif-sonuc` div içine `.uyari-basarili`
- GA4: `teklif_formu_gonderildi` (`{sehir, dil_kodu}`)

### Hata
- 422 → `data-hata` mesajı → `.uyari-hata`
- `aria-describedby="t-tel-hata"` → telefon hata mesajı

### Özgün Özellik
- `data-teklif` attribute (hesaplama-araci.js tarafından kullanılır, `/teklif-al` redirect)
- `alan_m2` → `min="0.5"` `max="5000"` `step="0.1"` — hassas sayı girişi

### Feature Toggle
- `etkinMi('urun_hesaplama')` — hesap aracı feature toggle

---

## 3. Randevu Formu

### Sayfa
- `frontend/sayfalar/teklif-al.php` (aynı sayfa, ikinci form)

### HTML
```html
<form id="randevu-formu" data-api="..." data-uc="/appointments" data-sonuc="randevu-sonuc" data-ok="..." data-hata="...">
  <input id="r-ad" name="ad_soyad" required minlength="3" maxlength="120">
  <input id="r-tel" name="telefon" required inputmode="tel">
  <input id="r-tarih" name="randevu_tarihi" required placeholder="YYYY-AA-GG SS:DD">
  <button class="btn btn-birincil" type="submit">Randevu Al</button>
</form>
```

### Client Validation
- `required`, `minlength="3"`, `inputmode="tel"`, `required` (tarih)
- `placeholder="YYYY-AA-GG SS:DD"` — tarih formatı

### Submit Handler
- **JS:** `teklif-al.js` (shared handler — `form[data-uc]`)
- **API:** `POST /api/v1/appointments` (`data-uc="/appointments"`)

### Başarı
- GA4: `randevu_talebi_olusturuldu`

### Hata
- 422 → `.uyari-hata` → `data-hata` mesajı

---

## 4. Bülten Formu (Footer)

### Sayfa
- `frontend/sayfalar/bulten-onay.php` (onay sayfası)
- Footer'da form — `frontend/includes/bilesenler.php` veya `footer` bileşeni

### HTML
- Footer'da `<form>` — `bulten` bileşeni
- `name="eposta"` (email)
- `type="submit"`

### Submit Handler
- **API:** `POST /api/v1/bulten` veya benzeri
- **Yönlendirme:** `/bulten-onay` sayfasına yönlendirme

### KVKK
- Footer'da KVKK onayı kontrol edilmeli

### GA4
- Bülten aboneliği event'leri (tüm bülten formları için)

---

## 5. Yorum Formu

### Sayfa
- `frontend/sayfalar/urun-detay.php`

### HTML
- Modal içerisindeki form
- `name="urun_id"` (hidden), `name="baslik"`, `name="yorum"`, `name="puan"`

### Submit Handler
- **JS:** `yorumlar.js`
- **API:** `POST /api/v1/yorumlar`
- **Body:** `{urun_id, baslik, yorum, puan}`

### Başarı
- 201 → yorum eklendi → modal kapanır → sayfa yeniden yüklenir (yorumları görmek için)
- Moderasyon: Yorumlar onay bekler (`yorumBolumu()` — `oneCikanYorumlar()`)

### Hata
- 422 → validation errors
- Puan aralığı: 1-5 arası (örn)

### Honeypot
- `web_sitesi` hidden field

---

## 6. Arama Formu (Header Search)

### Sayfa
- `frontend/sayfalar/arama.php`
- Header'da arama ikonu/input

### HTML
```html
<input id="arama-girdi" type="search" placeholder="Ara...">
<div id="arama-katman">...</div>
<button id="arama-kapat">Kapat</button>
```

### Client Validation
- `required` (arama girdisi)
- `minlength="3"` (autocomplete için minimum karakter)

### Submit Handler
- **JS:** `arama.js`
- **API:** `GET /api/v1/arama?lang=tr&q={q}&tur=hepsi&sayfa=1&limit=20`
- **Debounce:** 300ms

### Başarı
- Autocomplete suggestion listesi gösterilir
- GA4: `arama_yapildi` (`{q, tur: 'hepsi'}`)

### Hata
- Boş sonuç → "Sonuç bulunamadı" mesajı

---

## Form Karşılaştırma Tablosu

| Form | ID | API Ucu | Method | GA4 Event | Honeypot | KVKK |
|------|-----|---------|--------|-----------|----------|------|
| İletişim | `iletisim-formu` | `/iletisim` | POST | — | `web_sitesi` | ✅ |
| Teklif | `teklif-formu` | `/leads` | POST | `teklif_formu_gonderildi` | `web_sitesi` | ✅ |
| Randevu | `randevu-formu` | `/appointments` | POST | `randevu_talebi_olusturuldu` | Yok | Yok |
| Bülten | footer form | `/bulten` | POST | — | Yok | Yok |
| Yorum | modal form | `/yorumlar` | POST | — | `web_sitesi` | Yok |
| Arama | `arama-girdi` | `/arama` | GET | `arama_yapildi` | Yok | Yok |
| Sıcaklık | `sicaklik-formu` | `/ayarlar` | GET | Yok | Yok | Yok |

---

## Önemli Notlar

1. **`data-api`, `data-dil`, `data-uc`, `data-sonuc`, `data-ok`, `data-hata`** — Bu attribute'lar form submit handler'larının temelini oluşturur. Her formda aynı pattern kullanılır. UI değişiminde bu attribute'lar silinmez.

2. **`web_sitesi` honeypot** — İletişim ve teklif formlarında mevcut. Silinirse spam filtresi devre dışı kalır.

3. **`kvkk_onayi`** — İletişim ve teklif formlarında zorunlu checkbox. KVKK yasaya uygunluğu için kritik.

4. **`data-teklif`** — `teklif-al.php`'de `data-teklif="/teklif-al"` attribute'u, `hesaplama-araci.js` tarafından hesaplama sonrası yönlendirme için kullanılır.

5. **`data-hedef`** — `rehber-istanbul-bakim-takvimi.php`'de countdown için kullanılır (`data-hedef="2026-10-01T09:00:00"`).

6. **`data-kaynak`** — `sanal-tur.php`'de Pannellum viewer için embed URL.

7. **`data-kapsam`** — `sss.php`'de SSS sekme filtresi için kullanılır.

8. **`data-akordeon`** — SSS akordeonlarında `js/ana.js` tarafından kullanılır.
