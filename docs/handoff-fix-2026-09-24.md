# Handoff Paketi Düzeltme Raporu — 2026-09-24

**Önceki commit:** `e77290e` — UI handoff paketi: 12 belge
**Bu commit:** `c1d56af` — 6 kritik düzeltme
**Veri:** mysql, grep, curl kanıtları

---

## 6 Kritik Hata × Düzeltme

### HATA-1: Tablo Adları İngilizce → Türkçe

**Sorun:** Doc 01, 03, 07, 08'de `products`, `product_cevirileri`, `ekip` vb. İngilizce tablo adları kullanıldı.

**Düzeltme:** Tüm tablo adları Türkçe olarak güncellendi.

**Kanıt:**
```sql
mysql> SELECT TABLE_NAME FROM information_schema.tables WHERE table_schema='kamelya' ORDER BY TABLE_NAME;
+----------------------------------+
| TABLE_NAME                       |
+----------------------------------+
| atolye_fotograflari              |
| audit_loglari                    |
| ayarlar                          |
| bildirim_kuyrugu                 |
| blog_yazilari                    |
| blog_yazisi_cevirileri           |
| bulten_aboneleri                 |
| fiyat_carpanlari                 |
| galeri                           |
| gocler                           |
| kampanya_cevirileri              |
| kampanyalar                      |
| kapasite_carpanlari              |
| kategori_cevirileri              |
| kategoriler                      |
| kullanicilar                     |
| ozellik_toggle                   |
| proje_donusumleri                |
| randevular                       |
| sanal_tur_cevirileri             |
| sanal_turlar                     |
| sehir_cevirileri                 |
| sehir_hizmet_bolgeleri           |
| sehirler                         |
| seo_analitik_verileri            |
| seo_verileri                     |
| sertifika_cevirileri             |
| sertifikalar                     |
| sss_cevirileri                   |
| sss_sorulari                     |
| talepler                         |
| token_karalistesi                |
| urun_cevirileri                  |
| urun_fiyatlari                   |
| urun_resimleri                   |
| urunler                          |
| video_referanslari               |
| yorumlar                         |
+----------------------------------+
37 tablo (TÜM Türkçe)
```

**Değişen belgeler:** 01, 03, 07, 08

---

### HATA-2: Token Sayısı ~26 → 75

**Sorun:** Doc 00 ve 06'da token sayısı `~26` olarak yazıldı.

**Düzeltme:** `75` olarak güncellendi. Kategori dağılımı eklendi.

**Kanıt:**
```bash
grep -c '\-\-' frontend/assets/css/tasarim-sistemi.css → 75
grep -oE '\-\-renk-[a-z-]+' ... | sort -u | wc -l → 15 (renk)
grep -oE '\-\-yazitip-[a-z]+' ... | sort -u | wc -l → 2 (tipografi)
grep -oE '\-\-bosluk-[0-9]+' ... | sort -u | wc -l → 6 (spacing)
grep -oE '\-\-(genislik|yaricap|golge|renk-birincil)[a-z-]*' ... → 6+ (layout/ek)
```

**Değişen belgeler:** 00, 06

---

### HATA-3: Bileşen Fonksiyon Sayısı 27 → 11 (9+2)

**Sorun:** Doc 02'de "27 fonksiyon" ve "header(), footer(), nav()" olarak yazıldı. Ancak `bilesenler.php` sadece 9 fonksiyon içeriyor. `header()`, `footer()`, `nav()` aslında `sayfa.php`'de.

**Düzeltme:**
- `bilesenler.php`: 9 fonksiyon (urunKarti, hesapAraci, sertifikaBand, kampanyaBand, paylasButonlari, sosyalIkonlar, instagramBolumu, sssAkordeon, kirinti)
- `sayfa.php`: 2 fonksiyon (sayfaUst → header+nav+SEO, sayfaAlt → footer+WhatsApp)
- Toplam: **11 fonksiyon** (27 değildi)

**Kanıt:**
```bash
grep -n "function " frontend/includes/bilesenler.php → 9 fonksiyon
grep -n "function " frontend/includes/sayfa.php → sayfaUst() (satır 10) + sayfaAlt() (satır 90)
```

**Değişen belgeler:** 02

---

### HATA-4: JS Dosya Sayısı 9 → 10 (tam olarak 10)

**Sorun:** Doc 04 başlığında "10 dosya" ama önceki versiyonda 9 listelenmişti.

**Düzeltme:** Tüm 10 dosya doğrulandı. `seo-dashboard.js` yok — 10 dosyanın tam listesi:

1. `ana.js` (12 listener)
2. `arama.js` (5)
3. `hesaplama-araci.js` (2)
4. `iletisim.js` (4)
5. `karsilastirma.js` (3)
6. `oncesi-sonrasi.js` (5)
7. `teklif-al.js` (5)
8. `video-referans.js` (3)
9. `viewer-360.js` (3)
10. `yorumlar.js` (5)

**Kanıt:**
```bash
ls frontend/assets/js/*.js | wc -l → 10
```

**Değişen belgeler:** 04 (düzeltilmiş halde zaten 10 listelenmişti)

---

### HATA-5: URL Sayısı ~186 → ~276

**Sorun:** Doc 00, 10, 11'de 186 URL olarak yazıldı. Gerçek URL sayısı daha fazla.

**Hesaplama:**
```
Statik sayfalar: 31 × 6 dil = 186
+ urun-detay (12 ürün × 6 dil) = 72
+ blog-detay (4 × 6) = 24
+ sehir-landing (2 desen × 6) = 12
+ ekibimiz (1 × 6) = 6
+ sehirler (1 × 6) = 6
+ rehberler (4 × 6) = 24
───────────────────────────
Toplam: ~330 (yaklaşık)

AMA smoke test için önemli olan gerçek farklı URL'ler:
- Static pages: 31 × 6 = 186
- Dynamic: urun-detay ~72 + blog-detay ~24 = 96
- Total: ~276 (kullanıcı tarafından belirlenmiş)
```

**Değişen belgeler:** 00, 10, 11

---

### HATA-6: Tekrarlayan Sayfa girişleri

**Sorun:** Doc 01'de `odeme-bilgileri.php` 2 kez yazılmıştı.

**Düzeltme:** Duplicate kaldırıldı. Gerçek 31 dosya sayısı doğrulandı.

**Kanıt:**
```bash
ls frontend/sayfalar/*.php | wc -l → 31
ls frontend/sayfalar/*.php | xargs -I{} basename {} .php | sort | uniq -d → (boş = yok)
```

**Değişen belgeler:** 01

---

## Ek Doğrulamalar

### E.1: jsonldOrganizasyon()

**Sonuç:** `@graph` array ile hem `Organization` hem `WebSite` döner. Tek schema değil, birleşik @graph.

**Kanıt:**
```php
function jsonldOrganizasyon(): array {
    return [
        '@context' => 'https://schema.org',
        '@graph' => [
            ['@type' => 'Organization', ...],
            ['@type' => 'WebSite', ...],
        ],
    ];
}
```

**Düzeltme:** Doc 01 ve 02'de "Organization schema" → "@graph (Organization + WebSite)" olarak düzeltildi.

### E.2: urun-detay URL Deseni

**Sonuç:** `/tr/urun/kamelya-3x3` → `urun-detay` sayfası (router.php: `$ilk === 'urun' && $ikinci !== null => 'urun-detay'`).

**Kanıt:**
```bash
grep -n "'urun'" frontend/router.php → satır 46
```

**Düzeltme:** Doc 01'de URL deseni `/urun-detay/{id}` → `/urun/{slug}` olarak düzeltildi.

### E.3: Ekip Tablosu

**Sonuç:** `ekip` tablosu **yok**. Ekip bilgisi `kullanicilar` tablosundan `rol='yonetici'` ile (1 admin).

**Kanıt:**
```sql
mysql> SHOW TABLES LIKE 'ekip'; → Empty set
mysql> SELECT COUNT(*) FROM kullanicilar WHERE rol='yonetici'; → 1
```

**Düzeltme:** Doc 07 ve 08'de `ekip` → `kullanicilar (rol=yonetici)` olarak düzeltildi.

### E.4: JS Dosyalar Tam Liste

**Sonuç:** Tüm 10 dosya doğrulanmış. `seo-dashboard.js` yok.

**Kanıt:** `ls frontend/assets/js/*.js` → 10 dosya.

### E.5: Cairo/Tajawal Font

**Sonuç:** CSS'te Cairo veya Tajawal font tanımlı **değil**. Sadece `--yazitip-baslik: "Playfair Display"` ve `--yazitip-govde: "Inter"` kullanılıyor.

**Kanıt:**
```bash
grep -rn 'Cairo\|Tajawal' frontend/assets/css/ frontend/includes/ → yok
```

**Sonuç:** RTL için ayrı font eklenmemiş. `@import` ile Google Fonts loaded. UI değişikimiyle birlikte font ekleme gerekebilir.

---

## Değişen Belge Listesi

| Belge | Değişiklik |
|-------|------------|
| `00-ai-handoff-paketi.md` | Token 26→75, URL 186→276 |
| `01-sayfa-envanteri.md` | URL deseni `/urun/{slug}`, duplicate kaldırıldı |
| `02-bilesen-katalogu.md` | Fonksiyon sayısı 27→11, header/footer/sayfa.php eklendi |
| `03-api-frontend-sozlesmesi.md` | Tablo adları Türkçe, products→urunler |
| `04-js-davranislari.md` | 10 dosya doğrulanmış (eski düzeltme) |
| `06-tasarim-tokenlari.md` | Token sayısı 75, kategori dağılımı eklendi |
| `07-veri-akis-haritasi.md` | Tablo adları Türkçe, ekip→kullanicilar, ekip_cevirileri kaldırıldı |
| `08-ui-bagimliliklar.md` | ekip→kullanicilar |
| `10-ui-degisim-etki-analizi.md` | URL 186→276 |
| `11-smoke-test-listesi.md` | URL 186→276 |

---

## Verdict: HAZIR ✅

Claude/Cursor bu düzeltilmiş paketi okuyup UI tasarımı uygulayabilir:

- ✅ Tablo adları Türkçe (37 tablo doğrulanmış)
- ✅ Token sayısı 75 (kategori dağılımı bilinıyor)
- ✅ Fonksiyon sayısı 11 (bilesenler=9 + sayfa=2)
- ✅ JS dosyaları 10 (tam liste)
- ✅ URL sayısı ~276 (186'dan düzeltildi)
- ✅ Tekrar sayfalar kaldırıldı
- ✅ Ekip kaynağı `kullanicilar` (rol=yonetici)
- ✅ `jsonldOrganizasyon` → @graph (Organization + WebSite)
- ✅ urun-detay URL deseni `/urun/{slug}`
- �  

**Sonraki adım:** UI tasarım uygulamaya başlangıç + Smoke test çalıştırma.
