# Smoke Test Listesi — UI Sonrası Retest

**Kullanım:** UI değişiklikleri sonrasında bu listedeki tüm maddeleri kontrol et.  
**Aracı:** Playwright + `seo-analyzer` skill + `curl`

---

## A. Smoke Test — 186 URL

Her URL için kontrol maddeleri:

```
Kontrol maddeleri (her URL için):
- [ ] HTTP 200
- [ ] Title 50-60 byte
- [ ] Description 150-160 byte
- [ ] Canonical dolu
- [ ] robots index,follow
- [ ] H1 = 1 (atlamasız)
- [ ] hreflang 7 giriş
- [ ] JSON-LD geçerli (schema.org validator)
- [ ] OG + Twitter tam
- [ ] Viewport meta
- [ ] dir="rtl" (AR için)
- [ ] İç linkler 0 kırık
- [ ] Mobil taşma yok
```

### Kanıt Formatı
```bash
curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:8080/anasayfa  # → 200
grep -c "meta name=\"description\"" frontend/sayfalar/anasayfa.php  # → 1
grep -c "<title>" frontend/sayfalar/anasayfa.php  # → 1
```

---

## B. Fonksiyonel Test

Her fonksiyonel test için:

```
Test: [ ] form submit → 201 + GA4 event
Test: [ ] autocomplete → 3+ öneri
Test: [ ] dil değişimi → URL değişimi + cookie
Test: [ ] WhatsApp → GA4 event
Test: [ ] telefon → GA4 event
Test: [ ] 360° viewer modal
Test: [ ] before/after slider
Test: [ ] feature toggle → 410/200
```

### Detaylı Testler

| # | Test | Beklenen Sonuç | Aracı |
|---|------|----------------|-------|
| 1 | Hesaplama aracı: form submit → fiyat + kapasite | 201 + `hesaplama_yapildi` GA4 | Playwright |
| 2 | Autocomplete: 3+ karakter → öneri | Öneri listesi | Playwright |
| 3 | Teklif formu: submit → 201 + GA4 | `teklif_formu_gonderildi` GA4 | Playwright |
| 4 | İletişim formu: submit → 201 + GA4 | GA4 event | Playwright |
| 5 | Randevu: submit → 201 | `randevu_talebi_olusturuldu` GA4 | Playwright |
| 6 | Bülten: submit → 201 | `/bulten-onay` redirect | Playwright |
| 7 | Yorum: submit → 201 | `yorumlar.js` modal + form | Playwright |
| 8 | SSS akordeon: tıkla → aç/kapat | `aria-expanded` toggle | Playwright |
| 9 | SSS sekme: tıkla → filtre | `data-kapsam` filtre | Playwright |
| 10 | Dil değişimi: url değişimi + cookie + GA4 | `dil_degistirildi` GA4 | Playwright |
| 11 | WhatsApp butonu: tıkla → GA4 event | `whatsapp_tiklandi` GA4 | Playwright |
| 12 | Telefon butonu: tıkla → GA4 event | `telefon_tiklandi` GA4 | Playwright |
| 13 | 360° viewer modal | Pannellum viewer açılır | Playwright |
| 14 | Before/after slider | Slider hareket eder | Playwright |
| 15 | Feature toggle kapat/aç | 410/200 dönüş | Playwright |

### API Testi (curl)

```bash
# 12 public uç × 6 dil = 72 curl
for dil in tr en de fr it ar; do
  curl -s -o /dev/null -w "%{http_code}" "http://127.0.0.1:8080/api/v1/products?lang=$dil"
  curl -s -o /dev/null -w "%{http_code}" "http://127.0.0.1:8080/api/v1/sss-sorulari?lang=$dil"
  curl -s -o /dev/null -w "%{http_code}" "http://127.0.0.1:8080/api/v1/ekip?lang=$dil"
  # ...
done
```

### Hata Senaryoları

```bash
# 422 validation error
curl -X POST /api/v1/leads -d '{}'  # → 422

# 404 not found
curl /api/v1/products/999999  # → 404

# 500 server error (varsa)
curl /api/v1/hatali-uc  # → 500
```

### Rate Limit Testi

```bash
# calculate endpoint 31 kez → 429 veya beklenen yanıt
for i in $(seq 1 31); do
  curl -s -o /dev/null -w "%{http_code}\n" -X POST /api/v1/calculate -d '{"lang":"tr"}'
done
```

### Admin Auth

```bash
# 401 / 403
curl -I /api/v1/admin/urunler  # → 401 (auth gerekli)
curl -I -H "Authorization: Bearer invalid" /api/v1/admin/urunler  # → 403
```

---

## C. SEO Test

```
- [ ] Sitemap 264 URL (ya da güncel N)
- [ ] robots.txt
- [ ] Playwright seo-analyzer skoru ≥ 90 her sayfa
- [ ] Hreflang karşılıklı tutarlı
- [ ] Canonical doğru
```

### Sitemap
```bash
curl -s http://127.0.0.1:8080/sitemap.xml | grep -c "<url>"  # → 264
curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:8080/sitemap.xml  # → 200
```

### robots.txt
```bash
curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:8080/robots.txt  # → 200
curl -s http://127.0.0.1:8080/robots.txt | grep -c "Sitemap"  # → 1
```

### SEO Analyzer (Playwright)
```bash
# Her sayfa için: opencode /denetle veya seo-analyzer skill
# Skor ≥ 90 her sayfa için
```

### Hreflang
```bash
curl -s http://127.0.0.1:8080/anasayfa | grep -c "hreflang"  # → 7 giriş
curl -s http://127.0.0.1:8080/anasayfa | grep "x-default"  # → 1 giriş
```

### Canonical
```bash
curl -s http://127.0.0.1:8080/anasayfa | grep -c "canonical"  # → 1
```

---

## D. GA4 Event Test

```
- [ ] hesaplama_yapildi → /api/v1/calculate sonrası
- [ ] teklif_formu_gonderildi → /api/v1/leads sonrası
- [ ] randevu_talebi_olusturuldu → /api/v1/appointments sonrası
- [ ] whatsapp_tiklandi → .whatsapp-sabit click
- [ ] telefon_tiklandi → a[href^="tel:"] click
- [ ] dil_degistirildi → .dil-secici a click
```

### GA4 Verification

```bash
# GA4 debug mode ile doğrulama
# Browser dev tools → Network tab → gtag events
# veya Google Tag Assistant
```

---

## E. RTL Test (Arapça)

```
- [ ] /ar/* tüm sayfalar dir="rtl"
- [ ] Margin-inline çalışıyor
- [ ] Form input text-align: right
- [ ] Slider yönü doğru
```

### Detaylı RTL Test

```bash
# Tüm /ar/* sayfalarında kontrol
for sayfa in anasayfa urunler urun-detay blog sss arama iletisim teklif-al; do
  curl -s "http://127.0.0.1:8080/ar/$sayfa" | grep -c 'dir="rtl"'  # → 1
  curl -s "http://127.0.0.1:8080/ar/$sayfa" | grep -c 'margin-left\|margin-right'  # → 0
done
```

---

## F. Feature Toggle Test

```
- [ ] etkinMi('urunler') → AÇIK → ürünler bölümü görünür
- [ ] etkinMi('urunler') → KAPALI → 410 sayfası
- [ ] etkinMi('sss') → AÇIK → SSS bölümü görünür
- [ ] etkinMi('sss') → KAPALI → 410 sayfası
- [ ] etkinMi('referanslar') → AÇIK → referanslar görünür
- [ ] etkinMi('referanslar') → KAPALI → 410 sayfası
```

### Feature Toggle Verification

```bash
# ozellik_toggle tablosu kontrolü
mysql -u root kamelya -e "SELECT anahtar, deger FROM ozellik_toggle;"

# Tüm ürünler özelliği kapalıyken:
curl -s "http://127.0.0.1:8080/anasayfa" | grep -c "410"  # → 1 (410 sayfa)
```

---

## G. Consent Mode Test

```
- [ ] Sayfa yüklemede GA4/Clarity devre dışı
- [ ] Çerez onay → analytics devreye girer
- [ ] Çerez reddet → analytics kapanır
```

### Consent Verification

```bash
# Consent olmadan sayfa yükleme
curl -s http://127.0.0.1:8080/anasayfa | grep -c "ad_storage.*denied"  # → 1
# Consent sonrası
curl -s http://127.0.0.1:8080/anasayfa | grep -c "ad_storage.*granted"  # → consent click sonrası
```

---

## H. Genel Smoke Test Akışı

### 1. Hazırlık
```bash
# Frontend sunucusu başlat
php -S 127.0.0.1:8080 -t frontend frontend/router.php
# Backend API başlat
php -S 127.0.0.1:8000 -t public
```

### 2. Temel Kontrol (her sayfa)
```bash
for sayfa in $(ls frontend/sayfalar/*.php | xargs -I{} basename {} .php); do
  echo "$sayfa:"
  curl -s -o /dev/null -w "%{http_code} " "http://127.0.0.1:8080/$sayfa"
done
```

### 3. SEO Kontrol
```bash
for sayfa in anasayfa urunler urun-detay blog sss iletisim teklif-al; do
  echo "$sayfa SEO:"
  curl -s "http://127.0.0.1:8080/$sayfa" | grep -c "<meta name=\"description\""
  curl -s "http://127.0.0.1:8080/$sayfa" | grep -c "hreflang"
done
```

### 4. Fonksiyonel Kontrol
```bash
# Playwright ile otomatik test
# veya:
curl -X POST http://127.0.0.1:8000/api/v1/calculate -H "Content-Type: application/json" -d '{"lang":"tr","material":"ahsap","model":"kare","usage":"site_bahcesi"}'
```

### 5. GA4 Event Kontrol
```bash
# Browser dev tools → Network tab
# veya: Google Tag Assistant browser extension
```

### 6. RTL Kontrol
```bash
for sayfa in $(ls frontend/sayfalar/*.php); do
  curl -s "http://127.0.0.1:8080/ar/$(basename $sayfa .php)" | grep -c 'dir="rtl"'
done
```

---

## I. Smoke Test Sonrası Rapor Formatı

```
SMOKE TEST RAPORU — 2026-09-24

Toplam URL: 186
HTTP 200: 185/186 (1 hata: /ar/xxx)
SEO Skoru ≥90: 180/186 (6 sayfa düşük)
GA4 Events: 6/6 tetikleniyor
RTL Testi: 31/31 PASS
Feature Toggle: 4/4 PASS
Form Submit: 6/6 PASS
API Uçları: 25/25 PASS (×6 dil = 150/150 curl)

HATALAR:
1. /ar/xxx → dir="rtl" eksik (öncelikli düzeltme)
2. /urunler → SEO skor 85 (meta desc kısa)
3. /blog-detay/... → JSON-LD missing (jsonldMakale hata)

ÖNEMLİ: Her hata → [EK-20260924] ile YAPILACAKLAR.md'ye ekle
```

---

## J. Otomasyon Önerisi

Smoke test listesi, OpenCode'un `playwright` MCP ile otomatikleştirilebilir:

```bash
# opencode /denetle sonrası
# veya özel script:
scripts/smoke-test.sh
```

Playwright ile:
```javascript
// smoke-test.spec.js
test.describe('Smoke Test', () => {
  const urls = ['/tr/anasayfa', '/en/anasayfa', '/ar/anasayfa', ...];
  urls.forEach(url => {
    test(`${url} - HTTP 200`, async ({ page }) => {
      const response = await page.goto(`http://localhost:8080${url}`);
      expect(response.status()).toBe(200);
    });
  });
});
```
