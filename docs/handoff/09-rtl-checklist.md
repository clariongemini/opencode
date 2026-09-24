# RTL Kontrol Listesi — Arapça (AR) Dili

**Dil:** Arapça (`ar`)
**HTML Direction:** `dir="rtl"`
**CSS Mantığı:** Mantıksal (logical) CSS properties

---

## HTML RTL Ayarı

### `<html dir="rtl" lang="ar">`

**Koşul:** `dilYonu()` fonksiyonu `ar` için `rtl` döner.

**Frontend:** `frontend/includes/bootstrap.php:34` → `dilYonu()` fonksiyonu

**Kod:**
```php
function dilYonu(): string {
    return ($_GET['lang'] ?? 'tr') === 'ar' ? 'rtl' : 'ltr';
}
```

**Doğrulama:**
```bash
grep -rn 'dilYonu' frontend/ | head -5
grep -rn 'dir="rtl"' frontend/
```

**Test:** `/ar/*` sayfalarında `dir="rtl"` ve `lang="ar"` kontrol edilmelidir.

---

## CSS Mantıksal Özellikler

### Kullanılan Mantıksal Özellikler (grep kanıtı)

```bash
grep -rn 'margin-inline\|padding-inline\|inset-inline\|text-align.*start\|text-align.*end' frontend/
```

**Sonuç:**
- `.kapsayici { margin-inline: auto }` — `frontend/assets/css/tasarim-sistemi.css:69`
- `.whatsapp-sabit { inset-inline-end: var(--bosluk-2) }` — `tasarim-sistemi.css:166`
- `.akordeon-baslik { text-align: start }` — `tasarim-sistemi.css:137`

### Kullanılmayan Fiziksel Özellikler (YASAK)

```bash
grep -rn 'margin-left\|margin-right\|padding-left\|padding-right\|left:\|right:' frontend/assets/css/
```

**Sonuç:** **0** — `margin-left/right`, `padding-left/right` kullanılmiyor ✅

**Kanıt:** CSS'te sadece `margin-inline`, `padding-inline`, `inset-inline-start/end` kullanılıyor.

---

## Yön Duyarlı Bileşenler

### Nav
- **Mevcut:** `.gezinti { display: flex; gap: var(--bosluk-2); flex-wrap: wrap }`
- **RTL davranışı:** `flex-wrap: wrap` → RTL'de düğmeler sağdan sola sıralar
- **Değişiklik riski:** DÜŞÜK — flex-wrap zaten RTL-compatible

### Footer
- **Mevcut:** `.altbilgi-ici { display: grid; gap: var(--bosluk-3); grid-template-columns: repeat(3, 1fr) }`
- **RTL davranışı:** Grid aynı — 3 sütun sağdan sola
- **Değişiklik riski:** DÜŞÜK

### Form Input
- **Mevcut:** `.input { text-align: inherit }` → `body { direction: rtl }` devralır
- **RTL davranışı:** `text-align: right` (RTL'de)
- **Değişiklik riski:** DÜŞÜK — inherited direction

### Tablo
- **Mevcut:** Tablo kullanımı sınırlı
- **RTL davranışı:** Otomatik `direction: rtl`
- **Değişiklik riski:** DÜŞÜK

### Modal
- **Mevcut:** `.sanal-kap`, `.viewer-360`
- **RTL davranışı:** `inset-inline-end` → RTL'de solda
- **Değişiklik riski:** ORTA — `left`/`right` kullanımı kontrol edilmeli

### Slider (Before/After)
- **Mevcut:** `frontend/assets/js/oncesi-sonrasi.js`
- **RTL davranışı:** Slider yönü ters olabilir (before/after yer değiştirir)
- **Değişiklik riski:** **YÜKSEK** — Slider JS'de `left`/`right` pixel kullanılabilir
- **Test gerekiyor:** Arapça'da slider doğru çalışıyor mu?

### Akordeon Oku
- **Mevcut:** `.akordeon-baslik { text-align: start }` + `aria-expanded`
- **RTL davranışı:** `text-align: start` → RTL'de sağda
- **Değişiklik riski:** DÜŞÜK — `start`/`end` mantıksal

### WhatsApp Butonu
- **Mevcut:** `.whatsapp-sabit { inset-inline-end: var(--bosluk-2) }`
- **RTL davranışı:** `inset-inline-end` → RTL'de **solda** (doğru)
- **Değişiklik riski:** DÜŞÜK — `inset-inline-end` mantıksal

### Dil Değiştirici
- **Mevcut:** `.dil-secici { display: flex; gap: 4px }`
- **RTL davranışı:** Düğmeler soldan sağa sıralar
- **Değişiklik riski:** DÜŞÜK

---

## Font Değişimi

**AR için font değişimi var mı?**

Evet — Playfair Display (Latin script) Arapça için uygun olmayabilir.

**Öneri:** Arapça için ayrı font (`'Tajawal'` veya `'Cairo'`) kullanılmalı.

**Mevcut durum:** `--yazitip-baslik` → `"Playfair Display", Georgia, serif` — Arapça için fallback Google Font eklenmeli.

**Kod:**
```css
--yazitip-baslik: 'Cairo', 'Playfair Display', Georgia, serif;
```

---

## Sayı Biçimi

**AR için:** Arapça-Rakam (٠١٢٣٤٥٦٧٨٩) vs Latin-Rakam (0-9)

**Mevcut durum:** Sayılar HTML'de olduğu gibi gösterilir. PHP `date()` formatı Latin rakam kullanır.

**Değişiklik riski:** ORTA — Arapça kullanıcılar için sayı biçimi uyumlu olmalı.

**Öneri:** JavaScript'de `toLocaleString('ar')` kullanılabilir.

---

## Form Validation Mesajları

**Dil:** Arapça

**Mevcut durum:** `t('form_hata')`, `t('form_gecerli')` → `frontend/lang/ar.php` içinde mevcut

**Kontrol:**
```bash
grep -c "=>" frontend/lang/ar.php  # → 94 (her dil eşit)
grep "form_" frontend/lang/ar.php  # form mesajları
```

**Sonuç:** 94/94 dil key parity → Arapça form mesajları mevcut ✅

---

## Tarih/Saat Formatı

**Mevcut:** `placeholder="YYYY-AA-GG SS:DD"` (teklif-al.php)

**AR formatı:** `DD/MM/YYYY` veya `YYYY-MM-DD` (ISO)

**Değişiklik riski:** DÜŞÜK — placeholder değişebilir, date formatı JavaScript tarafında ayarlanabilir.

**Öneri:** `toLocaleDateString('ar')` kullanılabilir.

---

## Test Noktaları

### `/ar/*` Sayfaları × 31 Sayfa

**Kontrol maddeleri:**
1. `dir="rtl"` → `<html>` tagında var mı?
2. `lang="ar"` → `<html lang="ar">` var mı?
3. `text-align: start/end` kullanıyor mu? (fiziksel left/right yok)
4. `margin-inline` kullanıyor mu?
5. `inset-inline-end` kullanıyor mu?
6. Font (Cairo/Tajawal) yüklü mü?
7. Sayı formatı doğru mu?
8. Form validation mesajları Arapça mı?
9. Date formatı Arapça mı?
10. `flex-direction` ters mi olmalı?
11. SSS akordeon düğmesi sola mı gitmeli?
12. WhatsApp butonu sola mı?
13. Logo ve site adı sağdan sola mı okunmalı?
14. Nav menüsü ters mi sıralanmalı?
15. Breadcrumb ters mi olmalı?

**Kanıt:**
```bash
curl -s http://127.0.0.1:8080/ar/anasayfa | grep -c 'dir="rtl"'  # → 1
curl -s http://127.0.0.1:8080/ar/anasayfa | grep -c 'lang="ar"'  # → 1
curl -s http://127.0.0.1:8080/ar/anasayfa | grep -c 'margin-left\|margin-right'  # → 0
```

---

## Bilinen RTL Sorunları

### 1. Before/After Slider
- **Sorun:** `left`/`right` pixel hesaplamaları RTL'de ters olabilir
- **Çözüm:** `transform: translateX()` kullan veya `inset-inline-start/end`

### 2. Modal Animation
- **Sorun:** Slide-in animasyonu `left: 0` ile başlar → RTL'de ters
- **Çözüm:** `inset-inline-start: 0` kullan

### 3. Date Picker
- **Sorun:** Date picker grid RTL'de ters sıralanabilir
- **Çözüm:** `direction: rtl` CSS uygulama veya locale-aware date picker kullan

### 4. Input Placeholder
- **Sorun:** Placeholder metni RTL'de ters okunabilir
- **Çözüm:** `direction: ltr` input'a uygulama veya Unicode kontrol

### 5. Price Format
- **Sorun:** `1.000,00 TL` vs `1,000.00 TL`
- **Çözüm:** `toLocaleString('ar')` kullan

### 6. Icon Direction
- **Sorun:** Arrow icons RTL'de ters olabilir
- **Çözüm:** SVG `transform: scaleX(-1)` veya `data-direction` attribute

---

## UI Değişiminde RTL Testi Zorunlu Noktalar

| # | Test | Aracı |
|---|------|-------|
| 1 | `/ar/*` sayfalarında `dir="rtl"` var mı? | `curl | grep` |
| 2 | `margin-left/right` kullanılıyor mu? | `grep -rn 'margin-left\|margin-right' frontend/` |
| 3 | `margin-inline` çalışıyor mu? | `grep -rn 'margin-inline' frontend/` |
| 4 | Form input `text-align: right` var mı? | `grep -rn 'text-align' frontend/` |
| 5 | Slider yönü doğru mu? | Playwright — `/ar/referanslar` |
| 6 | WhatsApp butonu sola mı? | Playwright — `/ar/anasayfa` |
| 7 | Dil değiştirici çalışıyor mu? | Playwright — `/tr/` → `/ar/` → `/tr/` |
| 8 | SSS akordeon düğmesi sola mı? | Playwright — `/ar/sss` |
| 9 | Nav menüsü ters mi sıralanmalı? | Playwright — `/ar/anasayfa` |
| 10 | Breadcrumb ters mi? | Playwright — `/ar/blog-detay/...` |
| 11 | Font Arapça destekliyor mu? | Browser dev tools |
| 12 | Sayı formatı doğru mu? | Playwright — `/ar/teklif-al` |
| 13 | Form validation Arapça mı? | Playwright — `/ar/iletisim` |
| 14 | Tarih formatı Arapça mı? | Playwright — `/ar/teklif-al` |
| 15 | SEO hreflang `ar` var mı? | `curl | grep -c 'hreflang'` |
