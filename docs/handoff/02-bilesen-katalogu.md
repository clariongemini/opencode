# Bileşen Kataloğu — 27 Fonksiyon

**Kaynak:** `frontend/includes/*.php`  
**Kullanım:** `grep -rn "function " frontend/includes/` ile listelenmiştir.

---

## `bilesenler.php` — Ana Bileşenler (10 fonksiyon)

### `urunKarti(array $urun): string` (satır 7)
- **Amaç:** Ürün kartı HTML üretir
- **Kullanıldığı sayfalar:** `anasayfa.php`, `urunler.php`, `karsilastir.php` (3+ sayfa)
- **Çıktı HTML:** `<div class="card">` + `<img class="card-gorsel">` + `<div class="card-govde">`
- **Bağımlılıklar:** `var(--renk-yuzey)`, `var(--renk-cizgi)`, `var(--yaricap)`
- **Değişim etkisi:** Ürün kartı değişirse 3+ sayfa etkilendi

### `kampanyaBand(): string` (satır 119)
- **Amaç:** Kampanya bantı HTML üretir
- **Kullanıldığı sayfalar:** `kampanyalar.php`, `anasayfa.php`
- **Çıktı HTML:** `<div class="rozet">`
- **Bağımlılıklar:** `var(--renk-toprak)`

### `sertifikaBand(): string` (satır 95)
- **Amaç:** Sertifika logo bandı üretir
- **Kullanıldığı sayfalar:** `sertifikalar.php`
- **Çıktı HTML:** Sertifika logo listesi

### `paylasButonlari(string $url, string $baslik): string` (satır 147)
- **Amaç:** Paylaşım butonları (WhatsApp, Twitter, vs.)
- **Kullanıldığı sayfalar:** `blog-detay.php`, `urun-detay.php`, `referanslar.php`
- **BAĞLANGIÇ:** `data-event="whatsapp"` attribute'ı

### `sosyalIkonlar(): string` (satır 168)
- **Amaç:** Sosyal medya ikonları
- **Bağımlılıklar:** SVG ikonlar

### `instagramBolumu(): string` (satır 202)
- **Amaç:** Instagram feed bölümü

### `sssAkordeon(array $ogeler): string` (satır 237)
- **Amaç:** SSS akordeonu HTML üretir
- **Kullanıldığı sayfalar:** `sss.php`
- **Çıktı HTML:** `<div data-akordeon>` + `<div class="akordeon-icerik">`
- **Bağımlılıklar:** JS `data-akordeon` click event (`ana.js`)
- **Değişim etkisi:** Akordeon değişirse tüm SSS sayfası etkilenir

### `kirinti(array $ogeler): string` (satır 255)
- **Amaç:** Breadcrumb/gezinti çubuğu
- **Kullanıldığı sayfalar:** 31 sayfa (her sayfada `sayfaUst()` çağrılır)
- **Çıktı HTML:** `<nav class="kirinti">`

### `hesapAraci(): string` (satır 36)
- **Amaç:** Hesaplama aracı HTML bileşeni
- **Kullanıldığı sayfalar:** `anasayfa.php`
- **Bağımlılıklar:** `hesaplama-araci.js`, `var(--renk-birincil)`

---

## `seo.php` — SEO Fonksiyonları (7 fonksiyon)

### `seoMeta(array $seo): string` (satır 10)
- **Amaç:** Meta etiketler üretir (Title, Description, robots, OG, Twitter)
- **Kullanıldığı sayfalar:** 31 sayfa (her sayfada `sayfaUst($SEO)` çağrılır)
- **Çıktı:** `<title>`, `<meta name="description">`, `<meta property="og:...">`, `<meta name="robots">`, `<meta name="twitter:...">`
- **Kritik alan:** `seo_baslik`, `seo_aciklama`, `og_turu`, `canonical`, `og_gorsel`
- **Değişim etkisi:** Meta değişirse TÜM sayfalar SEO'dan etkilenir

### `jsonldOrganizasyon(): array` (satır 51)
- **Amaç:** Organization JSON-LD üretir
- **Kullanıldığı sayfalar:** `anasayfa.php`, `hakkimizda.php`
- **Çıktı:** `{"@context":"https://schema.org","@type":"Organization",...}`

### `jsonldUrun(array $urun, string $dil, ?array $derecelendirme = null): array` (satır 76)
- **Amaç:** Product JSON-LD üretir
- **Kullanıldığı sayfalar:** `urun-detay.php`
- **Çıktı:** `{"@context":"https://schema.org","@type":"Product",...}` + `AggregateRating`
- **Kritik alan:** `aggregateRating` yorum sayısına bağlı

### `jsonldSss(array $ogeler): array` (satır 127)
- **Amaç:** FAQPage JSON-LD üretir
- **Kullanıldığı sayfalar:** `sss.php`
- **Çıktı:** `{"@type":"FAQPage","mainEntity":[24 giriş]}`

### `jsonldIletisimSayfasi(): array` (satır 141)
- **Amaç:** LocalBusiness JSON-LD üretir
- **Kullanıldığı sayfalar:** `iletisim.php`
- **Çıktı:** `{"@type":"LocalBusiness",...}`

### `jsonldMakale(array $yazi): array` (satır 155)
- **Amaç:** Article JSON-LD üretir
- **Kullanıldığı sayfalar:** `blog-detay.php`
- **Çıktı:** `{"@type":"Article",...}`

### `jsonldYerelIsletme(): array` (satır 168)
- **Amaç:** Yerel işletme JSON-LD üretir
- **Kullanıldığı sayfalar:** `iletisim.php`

---

## `sayfa.php` — Sayfa Yardımcıları (2 fonksiyon)

### `sayfaUst(array $seo): void` (satır 10)
- **Amaç:** `<head>` açar + meta render + JSON-LD output
- **Kullanıldığı sayfalar:** 31 sayfa
- **Bağımlılıklar:** `seoMeta()`, `jsonld*()` fonksiyonları
- **Kritik:** `$SEO['jsonld']` unset edilmemeli

### `sayfaAlt(): void` (satır 90)
- **Amaç:** `</body>` + `</html>` kapatır

---

## `api.php` — API İstemcisi (2 fonksiyon)

### `apiGet(string $uc, string $dil, array $sorgu = []): ?array` (satır 7)
- **Amaç:** GET API çağrısı yapılır (cURL)
- **Kullanıldığı sayfalar:** `anasayfa.php`, `urunler.php`, `urun-detay.php`, `blog.php`, `blog-detay.php`, `sss.php`, `arama.php`, `ekibimiz.php`, `sehirler.php`, `sehir-landing.php`, `sertifikalar.php`, `garanti.php`, `kampanyalar.php`, `odeme-bilgileri.php`, `sanal-tur.php`, `referanslar.php`, `iletisim.php`
- **Parametreler:** `$uc` (uç), `$dil` (dil kodu), `$sorgu` (query params)
- **Dönüş:** `?array` (null = hata)
- **Kritik:** `curl_setopt_array(CURLOPT_TIMEOUT => 5)` — 5sn timeout
- **Değişim etkisi:** Doğrudan `apiGet` değişirse 17+ sayfa etkilenir

### `apiPost(string $uc, array $veri): array` (satır 31)
- **Amaç:** POST API çağrısı yapılır (cURL)
- **Kullanıldığı sayfalar:** Form submit handler'ları
- **Kritik:** `Content-Type: application/json` header

---

## `bootstrap.php` — Başlatma (10 fonksiyon)

### `cozumlenebilirDil(string $kod, array $ayar): ?string` (satır 9)
- **Amaç:** Dil kodunu çözümler

### `t(string $anahtar): string` (satır 18)
- **Amaç:** Dil çevirisi alır (`frontend/lang/tr.php` vb.)
- **Kullanıldığı sayfalar:** 31 sayfa (her yerde `t('anahtar')`)
- **Bağımlılıklar:** `frontend/lang/{dil}.php` dosyaları (94 anahtar/her dil)

### `siteUrl(string $yol = ''): string` (satır 25)
- **Amaç:** Site URL'i üretir

### `dilYonu(): string` (satır 34)
- **Amaç:** Dil yönünü belirler (LTR/RTL)

### `mevcutYol(): string` (satır 41)
- **Amaç:** Mevcut URL yolunu döndürür

### `dilUrl(string $hedefDil): string` (satır 49)
- **Amaç:** Dil değiştirme URL'i üretir

### `kisaAciklama(string $metin, int $uzunluk = 150): string` (satır 58)
- **Amaç:** Metin kısaltır (SEO description)

---

## `analytics.php` — Analytics (2 fonksiyon)

### `analyticsKafa(): string` (satır 11)
- **Amaç:** GA4 gtag.js script bloğu üretir
- **Çıktı:** `gtag('consent','default',...)` + `gtag('js', new Date())` + `gtag('config', '{$idEsc}')`
- **Kritik:** `consent` mode — `ad_storage:'denied'` (Kabul Et'e kadar analytics engelli)
- **GA4 ID:** `$AYAR['ga4_id']`

### `cerezBand(): string` (satır 56)
- **Amaç:** Çerez onay bandı üretir
- **Çıktı:** Kabul Et / Reddet butonları
- **Bağımlılıklar:** `analytics.php` consent mode

---

## `clarity.php` — Clarity (1 fonksiyon)

### `clarityBaslatici(): string` (satır 10)
- **Amaç:** Clarity tracking script üretir
- **Koşul:** Consent onayı sonrası yüklenir
- **Bağımlılıklar:** `analytics.php` consent mode

---

## `cwv-izleme.php` — CWV (1 fonksiyon)

### `cwvBaslatici(): string` (satır 9)
- **Amaç:** Core Web Vitals ölçüm script üretir
- **Bağımlılıklar:** Consent mode

---

## `gsc-dogrulama.php` — GSC (1 fonksiyon)

### `gscMeta(): string` (satır 6)
- **Amaç:** Google Search Console doğrulama meta etiketi üretir

---

## `ozellik.php` — Feature Toggle (2 fonksiyon)

### `ozellikHarita(): array` (satır 10)
- **Amaç:** `ozellik_toggle` tablosundan harita üretir
- **Bağımlılıklar:** `GET /api/v1/ozellikler` veya veri tabanı
- **Fail-open:** API erişilemezse TÜM özellikler AÇIK

### `etkinMi(string $anahtar): bool` (satır 36)
- **Amaç:** Özellik etkin mi kontrol eder
- **Kullanıldığı sayfalar:** `anasayfa.php` (`etkinMi('urunler')`, `etkinMi('urun_hesaplama')`, `etkinMi('referanslar')`, `etkinMi('sss')`)
- **Kritik:** False → 410 sayfası (410.php)

---

## `pwa.php` — PWA (2 fonksiyon)

### `pwaKafa(): string` (satır 7)
- **Amaç:** PWA manifest link üretir

### `pwaGovde(): string` (satır 14)
- **Amaç:** PWA service worker script üretir

---

## `yorumlar.php` — Yorumlar (4 fonksiyon)

### `yorumSozluk(): array` (satır 7)
- **Amaç:** Yorum durum sözlüğü

### `yildizlar(float $puan): string` (satır 22)
- **Amaç:** Yıldız rating HTML üretir
- **Kullanıldığı sayfalar:** `urun-detay.php`

### `yorumBolumu(?int $urunId): string` (satır 34)
- **Amaç:** Yorum bölümü HTML üretir
- **Kullanıldığı sayfalar:** `urun-detay.php`
- **Bağımlılıklar:** `apiGet('/yorumlar/ozet')`, `yorumlar.js`

### `oneCikanYorumlar(): string` (satır 119)
- **Amaç:** Öne çıkan yorumlar

---

## `api.php` — Yardımcı Fonksiyonlar

### `apiGet(string $uc, string $dil, array $sorgu = []): ?array`
### `apiPost(string $uc, array $veri): array`

---

## Kullanım Haritası (Sayfa → Bileşen Bağımlılığı)

| Sayfa | Bileşenler |
|-------|-----------|
| anasayfa | `urunKarti()`, `sssAkordeon()`, `seoMeta()`, `jsonldOrganizasyon()`, `analyticsKafa()`, `etkinMi()` |
| urunler | `urunKarti()`, `seoMeta()`, `jsonld ItemList` |
| urun-detay | `seoMeta()`, `jsonldUrun()`, `yorumBolumu()`, `yildizlar()`, `apiGet ×3` |
| sss | `sssAkordeon()`, `seoMeta()`, `jsonldSss()`, `etkinMi('sss')` |
| iletisim | `seoMeta()`, `jsonldYerelIsletme()`, `form` |
| teklif-al | `seoMeta()`, `jsonldIletisimSayfasi()`, `form ×2` |
| blog-detay | `seoMeta()`, `jsonldMakale()`, `yorumBolumu()` |
| referanslar | `oncesi-sonrasi.js`, `video-referans.js`, `etkinMi('referanslar')` |
| tüm sayfalar | `kirinti()`, `sayfaUst()`, `sayfaAlt()`, `analyticsKafa()`, `t()` |

---

## Değişim Etkisi Tablosu

| Fonksiyon Değişirse | Etkilenen Sayfa Sayısı | Kritiklik |
|---------------------|------------------------|-----------|
| `seoMeta()` | 31 | **YÜKSEK** |
| `apiGet()` | 17+ | **YÜKSEK** |
| `jsonldUrun()` | 1 | ORTA |
| `jsonldSss()` | 1 | ORTA |
| `jsonldOrganizasyon()` | 2 | ORTA |
| `jsonldYerelIsletme()` | 1 | ORTA |
| `jsonldMakale()` | 1 | ORTA |
| `sssAkordeon()` | 1 | ORTA |
| `urunKarti()` | 3+ | ORTA |
| `yorumBolumu()` | 1 | ORTA |
| `etkinMi()` | 4 sayfa | **YÜKSEK** |
| `t()` | 31 | **YÜKSEK** |
| `kirinti()` | 31 | ORTA |
| `analyticsKafa()` | 31 | ORTA |
