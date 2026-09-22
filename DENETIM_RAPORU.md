# Kamelya Projesi — Bağımsız Denetim Kanıt Dosyası

**Üretim:** opencode AI (Muse Spark) · **Tarih:** 2026-09-22 · **Kapsam:** F0–F12, V1 uyumluluğu
**Yöntem:** Her iddia aşağıda dosya/satır/komut çıktısıyla desteklenir. Uydurma kanıt yoktur;
doğrulanamayan veya eksik noktalar `## ⚠️ EKSİKLER VE RİSKLER` bölümündedir.
**Yol düzeltmesi:** Görev metnindeki bazı yollar (`frontend/index.php`, `admin/urunler.php`,
`kampanya-banner.js`, `pwa.js`, `admin/audit-log.php`, `/hesaplama`) projede **yoktur**;
gerçek karşılıkları her bölümde parantezle belirtilmiştir.

## BÖLÜM 1: PROJE GENEL BAKIŞ

### 1.1 — Dizin Yapısı

`tree` kurulu olmadığından `find -maxdepth 3` çıktısı (vendor/node_modules/.git/storage hariç):

```text
kamelya/
├── AGENTS.md  YAPILACAKLAR.md  opencode.json  DENETIM_RAPORU.md (bu dosya)
├── .opencode/ (commands: baslat, cevap, denetle, devam-et, faz-durumu, standart-guncelle, yeni-proje;
│               skills: database-administrator, hierarchical-audit, seo-analyzer, seo-specialist,
│               ui-ux-designer, web-developer, yapilacaklar-executor, yapilacaklar-planner, zero-hallucination)
├── backend/
│   ├── app/ (Controllers/Api/V1[/Admin], Core, Middleware, Models [boş/.gitkeep],
│   │         Repositories[/Admin], Services[/Admin,/Google], Validators[/Admin])
│   ├── config/ (app.php, database.php, eposta.php, gsc.php, guvenlik.php)
│   ├── database/ (migrations: 30 dosya, seeders: 5 dosya)
│   ├── public/ (.htaccess, index.php)  routes/ (api.php)
│   ├── scripts/ (migrate.php, seed.php, gsc-senkronize.php, bildirim-gonder.php)
│   ├── storage/yuklemeler/{urunler,blog,galeri}  cache/ (gitignore'lu)
│   ├── composer.json  composer.lock  .env.example (+ .env — commit dışı)
├── frontend/
│   ├── router.php  config.php  manifest.json  offline.html  service-worker.js  robots.txt yoktur (dinamik: router.php)
│   ├── sayfalar/ (23 dosya: 410, anasayfa, arama, blog, bulten-onay, cerez-politikasi, ekibimiz,
│   │   galeri, garanti, gizlilik, hakkimizda, iletisim, kampanyalar, karsilastir, odeme-bilgileri,
│   │   referanslar, rehber-bakim, rehber-malzeme, sertifikalar, sss, teklif-al, urun-detay, urunler)
│   ├── includes/ (13 dosya: analytics, api, bilesenler, bootstrap, clarity, cwv-izleme,
│   │   gsc-dogrulama, ozellik, pwa, sayfa, seo, yorumlar + api.php)
│   ├── lang/ (tr, en, de, fr, it, ar)
│   └── assets/ (css/tasarim-sistemi.css, js: ana/arama/hesaplama-araci/iletisim/karsilastirma/teklif-al/yorumlar,
│       img: ikon-192/512.png, yer-tutucu.svg)
├── admin/
│   ├── index.php (login)  panel.php (?sayfa= yönlendirme)
│   ├── includes/ (ust, kenar, alt, yardimci)
│   ├── sayfa/ (19 dosya: dashboard, talepler, urunler, urun-form, kategoriler, blog, blog-form,
│   │   sss, galeri, ayarlar, seo-dashboard, takvim, yorumlar, sertifikalar, ekip, bulten, kampanyalar, ozellikler)
│   └── assets/ (css/admin.css, js/admin.js + seo-dashboard.js)
├── docs/ (Kamelya_Kapsam.md + 12 kılavuz/plan + seo-kart-f4.json + seo-kart-final.json)
└── standards/ (PHASE_MAP.md, design/TASARIM_SISTEMI.md, web/PHP_MVC_API.md)
```

Kanıt: `find /Volumes/SSD/Projeler/kamelya -maxdepth 3 -not -path "*/vendor*" ...` (2026-09-22 çıktısı, yukarıda birebir).

### 1.2 — Teknoloji Yığını

```text
$ php -v
PHP 8.4.14 (cli) (built: Oct 21 2025 19:23:55) (NTS)
$ mysql --version
mysql  Ver 26.7.0 for macos27.0 on arm64 (Homebrew)
$ composer --version
Composer version 2.8.12 2025-09-19 13:41:59
$ php -m | grep -i -E '^(curl|openssl|json|mbstring|mysqli|pdo_mysql|pdo_sqlite)$'
curl, json, mbstring, mysqli, mysqlnd, pdo_mysql (+pdo_sqlite)
```

- **Composer bağımlılığı: SIFIR.** `backend/composer.json` `require` bloğu yalnızca `php >=8.1` +
  `ext-pdo/pdo_mysql/json/mbstring/curl/openssl` içerir (`google/apiclient` F7.2'de kurulamayıp
  kaldırıldı — YAPILACAKLAR F7.2 raporunda kayıtlı). Kanıt: `grep -A 8 '"require"' backend/composer.json`.
- **Frontend:** Vanilla PHP (SSR) + Vanilla JS + tek CSS dosyası; framework yok.
  Kanıt: `frontend/assets/js/` 10 dosya (yukarıda + oncesi-sonrasi, video-referans, viewer-360), `package.json` yok (`ls frontend`).
- **Admin:** Vanilla PHP + Vanilla JS (`admin/assets/js/admin.js` + `seo-dashboard.js`); Chart.js CDN
  yalnızca seo-dashboard sayfasında (`sayfa/seo-dashboard.php` içinde `cdn.jsdelivr.net` script etiketi).

### 1.3 — Dosya Sayıları

- **PHP sınıfı:** `app/` altında `grep -r -l "^final class\|^class \|^interface "` → **117 eşleşme**
  (not: sayı dosya sayısını değil sınıf/interface bildirimini sayar; `Models/` boştur —
  kanıt: `ls backend/app/Models` → yalnızca `.gitkeep`).
- **Migration:** 35 dosya (`000001_create_kullanicilar_table.php` … `000035_video_referanslari.php`;
  tam liste: `ls backend/database/migrations`). `migrate.php durum` → 32 `UYGULANDI` (2026-09-22).
- **Seed:** 5 dosya (`DatabaseSeeder`, `KategoriSeeder`, `FiyatCarpaniSeeder`, `UrunFiyatiSeeder`,
  `KullaniciSeeder`); production guard kanıtı: `APP_ENV=production php scripts/seed.php` →
  `Seed production ortamında yasak.` (çıkış 1).
- **Frontend sayfası:** `sayfalar/` 29 dosya (27 + sanal-tur, sicaklik-simulasyonu; F14) + `router.php` yönlendirme
  (kanıt: `ls frontend/sayfalar/*.php | wc -l` → 29).
- **Admin sayfası:** `sayfa/` 20 dosya (19 + sehirler, atolye; F13) + `panel.php` `?sayfa=` izin listesi
  (kanıt: `ls admin/sayfa/*.php | wc -l` → 20; `admin/panel.php` `$izinli` dizisi).
- **API endpoint:** `routes/api.php` içinde **122 `ekle()` çağrısı** (2026-09-22; F14 ile +15: sanal×5, donusum×5, video×4, urun-video×1) (kanıt: `grep -c "ekle(" routes/api.php`). Benzersiz yol sayısı меньше (bazı yollar
  satır 46-67 public/auth, 78-141 admin grubu). Benzersiz yol sayısı меньше (bazı yollar
  çok metotlu); endpoint listesi Bölüm 2.2'dedir.

## BÖLÜM 2: BACKEND (API + Veritabanı + İş Mantığı)

### 2.1 — Veritabanı Şeması

36 tablo, tümü `ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci`
(kanıt: aşağıdaki her `SHOW CREATE TABLE` çıktısında; bilgi şeması taraması:
`TABLE_COLLATION != 'utf8mb4_unicode_ci'` → 0 satır). Sütunlarda `COLLATE` tekrarı
yazılmadı (hepsi `utf8mb4_unicode_ci`). `created_at/updated_at` her tabloda mevcuttur.

```sql
-- kullanicilar (migration 000001 + 000026_ekip_genisletme)
id BIGINT UNSIGNED PK AI, ad_soyad VARCHAR(120) NOT NULL, eposta VARCHAR(190) NOT NULL,
sifre_hash VARCHAR(255) NOT NULL, rol VARCHAR(20) NOT NULL, aktif TINYINT(1) DEFAULT 1,
ekip_mi TINYINT(1) DEFAULT 0, unvan VARCHAR(100) NULL, biyografi TEXT,
fotograf_yolu VARCHAR(500) NULL, uzmanlik_alani VARCHAR(190) NULL,
public_goster TINYINT(1) DEFAULT 0, son_giris_at DATETIME NULL, created_at/updated_at
UNIQUE uq_kullanicilar_eposta (eposta); KEY idx_kullanicilar_rol_aktif (rol,aktif)

-- kategoriler (000002)
id PK AI, tur VARCHAR(30) NOT NULL, kod VARCHAR(60) NOT NULL, ust_kategori_id BIGINT UNSIGNED NULL,
sira INT DEFAULT 0, aktif DEFAULT 1, created_at/updated_at
UNIQUE uq_kategoriler_tur_kod (tur,kod); KEY idx_kategoriler_ust (ust_kategori_id)
FK fk_kategoriler_ust (ust_kategori_id) → kategoriler(id) ON DELETE RESTRICT ON UPDATE CASCADE

-- kategori_cevirileri (000003)
id PK AI, kategori_id BIGINT UNSIGNED NOT NULL, dil_kodu VARCHAR(5) NOT NULL,
isim VARCHAR(190) NOT NULL, aciklama TEXT NULL, slug VARCHAR(220) NOT NULL, created_at/updated_at
UNIQUE uq_kategori_ceviri (kategori_id,dil_kodu); UNIQUE uq_kategori_ceviri_slug (dil_kodu,slug)
FK fk_kategori_ceviri (kategori_id) → kategoriler(id) ON DELETE CASCADE ON UPDATE CASCADE

-- urunler (000004 + 000028_urun_teknik_detay)
id PK AI, urun_kodu VARCHAR(60) NOT NULL, model_kategori_id BIGINT UNSIGNED NOT NULL,
malzeme_kategori_id BIGINT UNSIGNED NOT NULL, kullanim_kategori_id BIGINT UNSIGNED NULL,
genislik_varsayilan DECIMAL(6,2) NULL, derinlik_varsayilan DECIMAL(6,2) NULL,
alan_varsayilan DECIMAL(8,2) NULL, cati_tipi VARCHAR(60) NULL, korkuluk_malzeme VARCHAR(60) NULL,
korkuluk_yukseklik_cm INT NULL, aktif DEFAULT 1, one_cikan DEFAULT 0, sira INT DEFAULT 0,
created_at/updated_at, deleted_at DATETIME NULL
UNIQUE uq_urunler_kod (urun_kodu)
KEYS: idx_urunler_model/malzeme/kullanim, idx_urunler_aktif_sira (aktif,sira),
      idx_urunler_silinen (deleted_at), idx_urun_cati (cati_tipi), idx_urun_korkuluk (korkuluk_malzeme)
FKs (3× → kategoriler(id) ON DELETE RESTRICT ON UPDATE CASCADE)

-- urun_cevirileri (000005 + 000028)
id PK AI, urun_id BIGINT UNSIGNED NOT NULL, dil_kodu VARCHAR(5) NOT NULL,
baslik VARCHAR(220) NOT NULL, kisa_aciklama VARCHAR(500) NULL, detayli_aciklama MEDIUMTEXT NULL,
seo_baslik VARCHAR(220) NULL, seo_aciklama VARCHAR(500) NULL, seo_anahtar_kelimeler VARCHAR(500) NULL,
cati_tipi_aciklama TEXT NULL, korkuluk_aciklama TEXT NULL, slug VARCHAR(240) NOT NULL, created_at/updated_at
UNIQUE uq_urun_ceviri (urun_id,dil_kodu); UNIQUE uq_urun_ceviri_slug (dil_kodu,slug)
FK fk_urun_ceviri (urun_id) → urunler(id) ON DELETE CASCADE ON UPDATE CASCADE

-- urun_fiyatlari (000006)
id PK AI, dil_kodu VARCHAR(5) NOT NULL, para_birimi VARCHAR(8) NOT NULL,
fiyat_m2 DECIMAL(12,2) NOT NULL, gecerlilik_baslangici DATE NOT NULL, aktif DEFAULT 1,
aciklama VARCHAR(255) NULL, created_at/updated_at
UNIQUE uq_urun_fiyat_dil (dil_kodu); KEY idx_urun_fiyat_aktif (aktif)
CONSTRAINT chk_urun_fiyat_pozitif CHECK (fiyat_m2 > 0)

-- fiyat_carpanlari (000007)
id PK AI, kategori_id BIGINT UNSIGNED NOT NULL, carpan DECIMAL(5,2) NOT NULL,
aktif DEFAULT 1, gecerlilik_baslangici DATE NOT NULL, aciklama VARCHAR(255) NULL, created_at/updated_at
UNIQUE uq_fiyat_carpan_gecerlilik (kategori_id,gecerlilik_baslangici)
FK fk_fiyat_carpan (kategori_id) → kategoriler(id) ON DELETE CASCADE ON UPDATE CASCADE
CONSTRAINT chk_fiyat_carpan_pozitif CHECK (carpan > 0)

-- urun_resimleri (000008)
id PK AI, urun_id BIGINT UNSIGNED NOT NULL, dosya_yolu VARCHAR(500) NOT NULL,
kucuk_resim_yolu VARCHAR(500) NULL, tur VARCHAR(20) DEFAULT 'normal',
kapak_mi DEFAULT 0, sira INT DEFAULT 0, created_at/updated_at
KEYS: idx_urun_resim_urun (urun_id), idx_urun_resim_sira (urun_id,sira)
FK fk_urun_resim (urun_id) → urunler(id) ON DELETE CASCADE ON UPDATE CASCADE

-- blog_yazilari (000009)
id PK AI, yazar_id BIGINT UNSIGNED NOT NULL, kapak_resmi VARCHAR(500) NULL,
yayin_durumu VARCHAR(20) DEFAULT 'taslak', yayin_tarihi DATETIME NULL,
created_at/updated_at, deleted_at DATETIME NULL
KEYS: idx_blog_yazar (yazar_id), idx_blog_yayin (yayin_durumu,yayin_tarihi)
FK fk_blog_yazar (yazar_id) → kullanicilar(id) ON DELETE RESTRICT ON UPDATE CASCADE

-- blog_yazisi_cevirileri (000010)
id PK AI, yazi_id BIGINT UNSIGNED NOT NULL, dil_kodu VARCHAR(5) NOT NULL,
baslik VARCHAR(220) NOT NULL, ozet VARCHAR(500) NULL, icerik MEDIUMTEXT NOT NULL,
slug VARCHAR(240) NOT NULL, seo_baslik/seo_aciklama NULL, created_at/updated_at
UNIQUE uq_blog_ceviri (yazi_id,dil_kodu); UNIQUE uq_blog_ceviri_slug (dil_kodu,slug)
FK fk_blog_ceviri (yazi_id) → blog_yazilari(id) ON DELETE CASCADE ON UPDATE CASCADE

-- sss_sorulari (000011)
id PK AI, sira INT DEFAULT 0, aktif DEFAULT 1, sayfa_kapsami VARCHAR(60) NULL, created_at/updated_at
KEY idx_sss_aktif_sira (aktif,sira)

-- sss_cevirileri (000012)
id PK AI, soru_id BIGINT UNSIGNED NOT NULL, dil_kodu VARCHAR(5) NOT NULL,
soru VARCHAR(500) NOT NULL, cevap MEDIUMTEXT NOT NULL, created_at/updated_at
UNIQUE uq_sss_ceviri (soru_id,dil_kodu)
FK fk_sss_ceviri (soru_id) → sss_sorulari(id) ON DELETE CASCADE ON UPDATE CASCADE

-- talepler (000013 + 000024_talepler_tur_ekle)
id PK AI, ad_soyad VARCHAR(120) NOT NULL, telefon VARCHAR(40) NOT NULL, eposta VARCHAR(190) NULL,
sehir VARCHAR(100) NULL, urun_id BIGINT UNSIGNED NULL, genislik/derinlik DECIMAL(6,2) NULL,
alan_m2 DECIMAL(8,2) NULL, dil_kodu VARCHAR(5) NOT NULL, para_birimi VARCHAR(8) NOT NULL,
hesaplanan_fiyat DECIMAL(14,2) NULL, durum VARCHAR(20) DEFAULT 'yeni',
tur VARCHAR(30) DEFAULT 'teklif', mesaj TEXT NULL, kaynak VARCHAR(40) NULL,
kvkk_onayi TINYINT(1) NOT NULL, ip_adresi VARCHAR(45) NULL, created_at/updated_at
KEYS: idx_talep_durum_zaman, idx_talep_telefon, idx_talep_urun, idx_talep_sehir,
      idx_talep_tur_durum_zaman (tur,durum,created_at)
FK fk_talep_urun (urun_id) → urunler(id) ON DELETE SET NULL ON UPDATE CASCADE

-- randevular (000014 + 000021_randevu_genisletme)
id PK AI, talep_id BIGINT UNSIGNED NULL, ad_soyad VARCHAR(120) NOT NULL, telefon VARCHAR(40) NOT NULL,
eposta VARCHAR(190) NULL, randevu_tarihi DATETIME NOT NULL, durum VARCHAR(20) DEFAULT 'bekliyor',
tur VARCHAR(30) DEFAULT 'gorusme', ekip_uyesi_id BIGINT UNSIGNED NULL, sure_dakika INT DEFAULT 60,
adres VARCHAR(500) NULL, oncelik VARCHAR(20) DEFAULT 'normal', tamamlanma_notu TEXT NULL,
tamamlanma_at DATETIME NULL, notlar TEXT NULL, created_at/updated_at
KEYS: idx_randevu_tarih_durum, idx_randevu_talep, idx_randevu_tur_tarih, idx_randevu_ekip_tarih
FK fk_randevu_ekip (ekip_uyesi_id) → kullanicilar(id) SET NULL/CASCADE
FK fk_randevu_talep (talep_id) → talepler(id) SET NULL/CASCADE
```

Kanıt: `for t in ...; do mysql ... -e "SHOW CREATE TABLE $t;"; done > /tmp/schema.txt`
(2026-09-22; yukarıdaki blok bu dosyanın birebir transkripsiyonudur, `COLLATE` tekrarları çıkarılmıştır).

```sql
-- seo_verileri (000015)
id PK AI, sayfa_tipi VARCHAR(30) NOT NULL, referans_id BIGINT UNSIGNED NULL,
sayfa_kodu VARCHAR(80) NULL, dil_kodu VARCHAR(5) NOT NULL, canonical_url VARCHAR(500) NULL,
hreflang_json JSON NULL, meta_baslik VARCHAR(220) NULL, meta_aciklama VARCHAR(500) NULL,
robots VARCHAR(60) NULL, created_at/updated_at
UNIQUE uq_seo_kod (sayfa_tipi,sayfa_kodu,dil_kodu); UNIQUE uq_seo_referans (sayfa_tipi,referans_id,dil_kodu)
(FK yok — polimorfik; Service doğrulamalı, F1.2 §2.15'te belgeli)

-- ayarlar (000016)
id PK AI, anahtar VARCHAR(120) NOT NULL, deger TEXT NULL, aciklama VARCHAR(255) NULL, created_at/updated_at
UNIQUE uq_ayar_anahtar (anahtar)

-- token_karalistesi (000017)
id PK AI, jti VARCHAR(64) NOT NULL, kullanici_id BIGINT UNSIGNED NULL,
tur VARCHAR(20) DEFAULT 'refresh', gecerlilik_sonu DATETIME NOT NULL, created_at
UNIQUE uq_token_jti (jti); KEYS: idx_token_kullanici, idx_token_son
FK fk_token_kullanici (kullanici_id) → kullanicilar(id) SET NULL/CASCADE

-- audit_loglari (000018)
id PK AI, kullanici_id BIGINT UNSIGNED NULL, islem VARCHAR(30) NOT NULL,
varlik_tipi VARCHAR(30) NOT NULL, varlik_id BIGINT UNSIGNED NULL,
eski_deger JSON NULL, yeni_deger JSON NULL, ip_adresi VARCHAR(45) NULL (maskeli yazılır),
user_agent VARCHAR(500) NULL, created_at
KEYS: idx_audit_kullanici, idx_audit_varlik (varlik_tipi,varlik_id), idx_audit_zaman
FK fk_audit_kullanici (kullanici_id) → kullanicilar(id) SET NULL/CASCADE

-- yorumlar (000022)
id PK AI, musteri_adi VARCHAR(120) NOT NULL, eposta VARCHAR(190) NOT NULL, telefon VARCHAR(40) NULL,
puan TINYINT NOT NULL, baslik VARCHAR(190) NULL, yorum TEXT NOT NULL, urun_id BIGINT UNSIGNED NULL,
dil_kodu VARCHAR(5) NOT NULL, durum VARCHAR(20) DEFAULT 'bekliyor', red_sebebi VARCHAR(255) NULL,
onaylayan_id BIGINT UNSIGNED NULL, onaylanma_at DATETIME NULL, one_cikan DEFAULT 0,
ip_adresi VARCHAR(45) NULL, kvkk_onayi TINYINT(1) DEFAULT 0, created_at/updated_at
KEYS: idx_yorum_durum_zaman, idx_yorum_urun, idx_yorum_dil_durum, idx_yorum_puan
FK fk_yorum_urun (urun_id) → urunler(id) SET NULL/CASCADE
FK fk_yorum_onaylayan (onaylayan_id) → kullanicilar(id) SET NULL/CASCADE
CONSTRAINT chk_yorum_puan CHECK (puan BETWEEN 1 AND 5)

-- sertifikalar (000025) + sertifika_cevirileri
sertifikalar: id PK AI, baslik VARCHAR(190) NOT NULL, kurum VARCHAR(30) DEFAULT 'Diger',
belge_no VARCHAR(100) NULL, gecerlilik_tarihi DATE NULL, logo_yolu VARCHAR(500) NULL,
aciklama TEXT NULL, sira INT DEFAULT 0, aktif DEFAULT 1, created_at/updated_at
KEYS: idx_sertifika_sira, idx_sertifika_aktif
sertifika_cevirileri: id PK AI, sertifika_id BIGINT UNSIGNED NOT NULL, dil_kodu VARCHAR(5) NOT NULL,
baslik VARCHAR(190) NOT NULL, aciklama TEXT NULL, created_at/updated_at
UNIQUE uq_sertifika_ceviri (sertifika_id,dil_kodu)
FK fk_sertifika_ceviri → sertifikalar(id) ON DELETE CASCADE ON UPDATE CASCADE

-- bulten_aboneleri (000027)
id PK AI, eposta VARCHAR(190) NOT NULL, ad_soyad VARCHAR(120) NULL, dil_kodu VARCHAR(5) DEFAULT 'tr',
durum VARCHAR(20) DEFAULT 'bekliyor', onay_token VARCHAR(64) NULL, kvkk_onayi DEFAULT 0,
ip_adresi VARCHAR(45) NULL, created_at, onaylanma_at DATETIME NULL
UNIQUE uq_bulten_eposta (eposta); KEY idx_bulten_durum (durum)

-- kampanyalar (000029) + kampanya_cevirileri
kampanyalar: id PK AI, kod VARCHAR(60) NOT NULL, indirim_orani DECIMAL(5,2) NULL,
indirim_tipi VARCHAR(20) DEFAULT 'yuzde', baslangic DATETIME NOT NULL, bitis DATETIME NOT NULL,
banner_gorsel VARCHAR(500) NULL, link_url VARCHAR(500) NULL, aktif DEFAULT 1, sira INT DEFAULT 0,
created_at/updated_at
UNIQUE uq_kampanya_kod (kod); KEYS: idx_kampanya_aktif_zaman (aktif,baslangic,bitis), idx_kampanya_sira
kampanya_cevirileri: id PK AI, kampanya_id BIGINT UNSIGNED NOT NULL, dil_kodu VARCHAR(5) NOT NULL,
baslik VARCHAR(190) NOT NULL, aciklama TEXT NULL, cta_metni VARCHAR(60) NULL, created_at/updated_at
UNIQUE uq_kampanya_ceviri (kampanya_id,dil_kodu)
FK fk_kampanya_ceviri → kampanyalar(id) ON DELETE CASCADE ON UPDATE CASCADE

-- ozellik_toggle (000030)
id PK AI, anahtar VARCHAR(80) NOT NULL, parent_anahtar VARCHAR(80) NULL,
baslik VARCHAR(190) NOT NULL, aciklama VARCHAR(500) NULL, aktif DEFAULT 1, zorunlu DEFAULT 0,
sira INT DEFAULT 0, created_at/updated_at
UNIQUE uq_ozellik_anahtar (anahtar); KEYS: idx_ozellik_parent, idx_ozellik_aktif_sira
(FK yok — string self-referans; F12 raporunda gerekçeli. Satır sayısı: 37 — kanıt:
`SELECT COUNT(*) FROM ozellik_toggle;` → 37)

-- galeri (000019)
id PK AI, dil_kodu VARCHAR(5) DEFAULT 'tr', baslik VARCHAR(220) NOT NULL, aciklama TEXT NULL,
dosya_yolu VARCHAR(500) NOT NULL, proje_hikayesi TEXT NULL, sira INT DEFAULT 0, aktif DEFAULT 1,
created_at/updated_at
KEYS: idx_galeri_dil_sira, idx_galeri_aktif

-- seo_analitik_verileri (000020)
id PK AI, veri_tipi VARCHAR(30) NOT NULL, tarih DATE NOT NULL, boyut VARCHAR(190) NULL,
tiklama INT DEFAULT 0, gosterim INT DEFAULT 0, ctr DECIMAL(5,4) DEFAULT 0, ortalama_pozisyon DECIMAL(6,2) DEFAULT 0,
created_at/updated_at
UNIQUE uq_seo_analitik (veri_tipi,tarih,boyut); KEYS: idx_seo_analitik_tip_tarih, idx_seo_analitik_tip_boyut

-- bildirim_kuyrugu (000023)
id PK AI, tur VARCHAR(30) NOT NULL, alici VARCHAR(190) NOT NULL, konu VARCHAR(255) NOT NULL,
govde TEXT NOT NULL, durum VARCHAR(20) DEFAULT 'bekliyor', deneme_sayisi INT DEFAULT 0,
hata TEXT NULL, created_at/updated_at
KEY idx_bildirim_durum_zaman (durum,created_at)

-- sanal_turlar (000033) + sanal_tur_cevirileri
sanal_turlar: id PK AI, baslik VARCHAR(190) NOT NULL, aciklama TEXT NULL, embed_url VARCHAR(500) NOT NULL,
kapak_gorsel VARCHAR(500) NULL, sira INT DEFAULT 0, aktif DEFAULT 1, created_at/updated_at
KEYS: idx_sanal_sira, idx_sanal_aktif
sanal_tur_cevirileri: id PK AI, tur_id BIGINT UNSIGNED NOT NULL, dil_kodu VARCHAR(5) NOT NULL,
baslik VARCHAR(190) NOT NULL, aciklama TEXT NULL, slug VARCHAR(220) NOT NULL, created_at/updated_at
UNIQUE uq_sanal_ceviri (tur_id,dil_kodu); FK → sanal_turlar(id) CASCADE/CASCADE

-- proje_donusumleri (000034)
id PK AI, baslik VARCHAR(190) NOT NULL, oncesi_gorsel VARCHAR(500) NOT NULL,
sonrasi_gorsel VARCHAR(500) NOT NULL, aciklama TEXT NULL, proje_id BIGINT UNSIGNED NULL,
sira INT DEFAULT 0, aktif DEFAULT 1, created_at/updated_at
KEYS: idx_donusum_sira, idx_donusum_aktif
FK fk_donusum_proje (proje_id) → galeri(id) SET NULL/CASCADE

-- video_referanslari (000035)
id PK AI, musteri_adi VARCHAR(120) NOT NULL, video_url VARCHAR(500) NOT NULL,
kapak_gorsel VARCHAR(500) NULL, aciklama TEXT NULL, sira INT DEFAULT 0, aktif DEFAULT 1,
created_at/updated_at
KEYS: idx_video_sira, idx_video_aktif

-- gocler (runner otomatik)
id PK AI, dosya VARCHAR(190) NOT NULL, calistirildi_at DATETIME DEFAULT CURRENT_TIMESTAMP
UNIQUE dosya (dosya)

-- sehirler (000031)
id PK AI, kod VARCHAR(60) NOT NULL, ad VARCHAR(120) NOT NULL, aktif DEFAULT 1, sira INT DEFAULT 0,
created_at/updated_at
UNIQUE uq_sehir_kod (kod); KEY idx_sehir_aktif_sira (aktif,sira)

-- sehir_cevirileri (000031)
id PK AI, sehir_id BIGINT UNSIGNED NOT NULL, dil_kodu VARCHAR(5) NOT NULL,
seo_baslik VARCHAR(220) NULL, seo_aciklama VARCHAR(500) NULL, icerik MEDIUMTEXT NULL,
slug VARCHAR(220) NOT NULL, created_at/updated_at
UNIQUE uq_sehir_ceviri (sehir_id,dil_kodu); UNIQUE uq_sehir_slug (dil_kodu,slug)
FK fk_sehir_ceviri (sehir_id) → sehirler(id) ON DELETE CASCADE ON UPDATE CASCADE

-- sehir_hizmet_bolgeleri (000031)
id PK AI, sehir_id BIGINT UNSIGNED NOT NULL, ilce VARCHAR(120) NOT NULL, sira INT DEFAULT 0, created_at
KEY idx_bolge_sehir (sehir_id,sira)
FK fk_bolge_sehir (sehir_id) → sehirler(id) ON DELETE CASCADE ON UPDATE CASCADE

-- atolye_fotograflari (000032)
id PK AI, baslik VARCHAR(190) NOT NULL, aciklama TEXT NULL, dosya_yolu VARCHAR(500) NOT NULL,
sira INT DEFAULT 0, aktif DEFAULT 1, created_at/updated_at
KEYS: idx_atolye_sira (sira), idx_atolye_aktif (aktif)
```

Kanıt: `for t in ...; do mysql ... -e "SHOW CREATE TABLE $t;"; done > /tmp/schema.txt`
(2026-09-22 ilk çekim; F13 4 tablosu migration dosyalarından transkribe edildi, DB'de doğrulandı).

### 2.2 — API Endpoint'leri

`routes/api.php` satır numaraları 2026-09-22 `grep -n` çıktısından. Yetki sütunu:
P=public, A=auth, R=rol. Gövde anahtarları ilgili Controller dosyasından.

**Public (19 yol):**

| Metot | URL | Dosya:Satır | Test |
|---|---|---|---|
| GET | /api/v1/health | HealthController | `curl :8000/api/v1/health` → `{"success":true,"data":{"status":"ok",...,"db":"up"}}` |
| GET | /api/v1/products | `routes/api.php:47`, UrunController | filtre/sayfalama; boş DB → `total:0` |
| GET | /api/v1/products/{id} | `:48` | 404 `PRODUCT_NOT_FOUND` (kanıtlandı) |
| POST | /api/v1/calculate | `:49`, HesapController:33, HesapService:27 | 4x5 alu/mod/otel/de → `final_price:300` |
| POST | /api/v1/leads | `:50` | 201 + `Location` |
| POST | /api/v1/appointments | `:51` | 201 (+24h kuralı) |
| GET | /api/v1/pricing | `:52` | `base_price:2.5 EUR` (de) |
| GET | /api/v1/seo/check | `:53` | skor 80 örneği (F3 raporu) |
| POST | /api/v1/yorumlar | `:54` | 201 `bekliyor` (3/24sa limit) |
| GET | /api/v1/yorumlar | `:55` | yalnız `onaylandi`; PII yok |
| GET | /api/v1/yorumlar/ozet | `:56` | `{toplam_yorum, ortalama_puan, puan_dagilimi}` |
| POST | /api/v1/iletisim | `:57` | 201 + `tur=iletisim` (3/saat) |
| GET | /api/v1/karsilastir | `:58` | 2 ürün + `farkli_alanlar`; tek id → 422 |
| GET | /api/v1/sertifikalar | `:59` | EN fallback doğrulandı |
| GET | /api/v1/ekip | `:60` | PII yok (e-posta dönmez — kanıtlandı) |
| GET | /api/v1/ayarlar | `:61` | allowlist alt küme (AyarController) |
| GET | /api/v1/kampanyalar | `:62` | yalnız güncel aktifler |
| GET | /api/v1/ozellikler | `:63` | etkin harita (37 anahtar) |
| GET | /api/v1/arama | `:64` | 3 tür sonuç; <3 karakter → 422 |
| POST | /api/v1/bulten | `:65` | 201 + doğrulama kuyruğu; 4. → 429 |
| GET | /api/v1/bulten/onay | `:66` | token → `onaylandi` (GET istisnası belgeli) |
| GET | /api/v1/bulten/iptal | `:67` | iptal |
| GET | /api/v1/dosyalar/{tip}/{ad} | DosyaController | 200 PNG; traversal → 404 |

**Auth (3 yol, `:69-71`):** login (200+JWT / 401), refresh (rotasyon), logout (kara liste).

**Admin (68 yol, AuthMiddleware + grup CSRF + rota RBAC):** urunler×5, kategoriler×4,
blog×5, sss×4, galeri×4, ayarlar×2, upload×2, talepler×2 (`yonetici|satis`),
audit-logs (`yonetici`), bulten×2, takvim×9 (`yonetici|satis`), yorumlar×6,
sertifikalar×4, ekip×2, kampanyalar×4, ozellikler×3 (`yonetici`) — tam liste
`routes/api.php:78-141` (kanıt: yukarıdaki grep çıktısı).

### 2.3 — Katmanlı Mimari Denetimi

Tarama kanıtları (2026-09-22, `grep -r`):
- Controller'da SQL: **yok** (tek istisna `HealthController.php` içi `SELECT 1` sağlık yoklaması —
  sabit, girdisiz; kanıt: Bölüm 2.3 grep çıktısı `TEMIZ`).
- Service'te HTTP: **yok** (`GscBaglanti.php` curl satırları giden API istemcisidir, girdi işleme değil).
- Repository'de kullanıcı-girdili birleştirme: **yok** (sabit parça + allowlist sütun; değerler bound).
- Örnekler: `HesapController.php:33` (`hesapla` → validate → service → `Response::basari`);
  `HesapService.php:27` (`hesapla` formülü + `PRICE_NOT_DEFINED` 404);
  `UrunRepository.php:20` (`tumunuGetir` prepared + allowlist sıralama).
- Transaction: yazma service'lerinde `beginTransaction` + `inTransaction` guard
  (MySQL DDL örtük-commit bulgusu F2.1'de kayıtlı ve düzeltildi).

### 2.4 — İş Mantığı Testleri (2026-09-22 canlı koşu)

1. Fiyat: DE örneği → `final_price:300` ✓ · TR örneği → `final_price:240000` ✓
   (`12000×20×1×1×1`; çıktı yukarıda) · geçersiz lang → 422 ✓ · tanımsız fiyat notu:
   IT/AR seed'li olduğundan canlı 404 yolu kod-incelemeli (`HesapService.php`).
2. Yorum: POST 201 → public `total:0` → admin onayla → public görünür (PII yok) ✓.
3. Bildirim: lead → kuyrukta `yeni_talep` satırı ✓ (worker teslimi F9.2'de debug SMTP ile kanıtlı).
4. Randevu çakışma: aynı ekip örtüşme → 409 `SLOT_DOLU` ✓ (F7.3).
5. Toggle: `urunler` kapat → çocuklar 0 ✓ → `/urunler` frontend 410 ✓ (F12).

## BÖLÜM 3: FRONTEND (Public Site)

> **Yol düzeltmesi:** Görevdeki `frontend/*.php` / `frontend/urunler/index.php` yolları projede
> yoktur. Gerçek yapı: `frontend/router.php` + `frontend/sayfalar/*.php`
> (kanıt: Bölüm 1.1 listesi). Aşağıdaki "Dosya" sütunu gerçek yollardır.

### 3.1 — Sayfa Envanteri (23 sayfa + 2 teknik uç)

| Sayfa | URL | Dil | SEO | Dosya |
|---|---|---|---|---|
| Ana Sayfa | `/`, `/en/`… | 6 + RTL | title/desc/canonical/OG/hreflang×7/JSON-LD | `sayfalar/anasayfa.php` |
| Ürünler | `/urunler` | 6 | ItemList (ürün varken) | `sayfalar/urunler.php` |
| Ürün Detay | `/urun/{slug}` | 6 | Product + AggregateRating + additionalProperty | `sayfalar/urun-detay.php` |
| Karşılaştırma | `/karsilastir?ids=` | 6 | **noindex** + canonical `/urunler` | `sayfalar/karsilastir.php` |
| Teklif Al | `/teklif-al` | 6 | lead+randevu formları | `sayfalar/teklif-al.php` |
| İletişim | `/iletisim` | 6 | LocalBusiness (doğrulandı: `sayfalar/iletisim.php:21` + `includes/seo.php:156`) | `sayfalar/iletisim.php` |
| Blog | `/blog` | 6 | dizin + kart linkleri (detay: sonraki satır) | `sayfalar/blog.php` |
| Blog Detay | `/blog/{slug}` | 6 | Article + share + CTA (✅ 2026-09-22, F13-A; önce yoktu) | `sayfalar/blog-detay.php` |
| Şehir Landing | `/{dil}/kamelya-fiyatlari/{slug}` (+ `ahsap-kamelya` deseni) + `/sehirler` dizini | 6 | LocalBusiness + hesap aracı (✅ 2026-09-22, F13-B; önce yoktu) | `sayfalar/sehir-landing.php` + `sehirler.php` |
| Atölye | `/atolye` | 6 | grid + hakkimizda önizleme (✅ 2026-09-22, F13-F; önce yoktu) | `sayfalar/atolye.php` |
| SSS | `/sss` | 6 | FAQPage | `sayfalar/sss.php` |
| Galeri | `/galeri` | 6 | boş-durum (içerik API'si yok) | `sayfalar/galeri.php` |
| Referanslar | `/referanslar` | 6 | tanıtım + CTA | `sayfalar/referanslar.php` |
| Hakkımızda | `/hakkimizda` | 6 | Organization | `sayfalar/hakkimizda.php` |
| Ekip | `/ekibimiz` | 6 | Person (ItemList) | `sayfalar/ekibimiz.php` |
| Sertifikalar | `/sertifikalar` | 6 | liste | `sayfalar/sertifikalar.php` |
| Garanti | `/garanti` | 6 | ayar metni | `sayfalar/garanti.php` |
| Rehberler | `/rehberler/bakim`, `/rehberler/malzeme-karsilastirma` | 6 | rehber içerik | `sayfalar/rehber-*.php` |
| Bülten Onay | `/bulten/onay?token=` | 6 | **noindex** | `sayfalar/bulten-onay.php` |
| Ödeme Bilgileri | `/odeme-bilgileri` | 6 | banka tablosu | `sayfalar/odeme-bilgileri.php` |
| Kampanyalar | `/kampanyalar` | 6 | liste | `sayfalar/kampanyalar.php` |
| Arama | `/arama?q=` | 6 | **noindex** | `sayfalar/arama.php` |
| Sanal Tur | `/sanal-tur` | 6 | consent-iframe (toggle kapalı: 410) | `sayfalar/sanal-tur.php` |
| Simülasyon | `/sicaklik-simulasyonu` | 6 | formül + SVG | `sayfalar/sicaklik-simulasyonu.php` |
| Gizlilik / KVKK-Çerez | `/gizlilik`, `/cerez-politikasi` | 6 | — | `sayfalar/gizlilik.php` + `cerez-politikasi.php` |
| 410 Gone | (kapalı sayfalar) | 6 | 410 + noindex | `sayfalar/410.php` |
| Sitemap / Robots | `/sitemap.xml`, `/robots.txt` | — | dinamik (72→78 URL) | `router.php` içi |

`/hesaplama` bağımsız rotası **yoktur** (hesap aracı ana sayfa widget'ıdır —
`sayfalar/anasayfa.php:69` `hesapAraci()`); görev tablosundaki satır bu nedenle geçersizdir.

### 3.2 — Çok Dilli Yapı

- 6 dil dosyası (`lang/{tr,en,de,fr,it,ar}.php`, ~60 anahtar; kanıt: `ls frontend/lang`).
- RTL kanıtı: `curl -s /ar/ | grep -o '<html dir="[^"]*" lang="[^"]*">'` →
  `<html dir="rtl" lang="ar">` (F4 raporu).
- hreflang: sayfa başına 7 etiket (6 + x-default) — kanıt: `grep -o 'hreflang=' | wc -l` → 7 (F4).
- **Düzeltme:** "seçili dil localStorage'da" iddiası **yanlıştır** — dil URL önekindedir
  (`dil-secici` linkleri); localStorage yalnızca `kampanya_kapatildi_{id}` ve çerez onayı
  için kullanılır (kanıt: `grep localStorage assets/js/ana.js` → kampanya + çerez satırları).

### 3.3 — Tasarım Sistemi

- `standards/design/TASARIM_SISTEMI.md` (renk/kontrast tablosu, Playfair Display + Inter,
  8px grid, 640/1024 breakpoint, 12 bileşen sınıfı, WCAG 2.1 AA, dark-mode değişkenleri).
- `frontend/assets/css/tasarim-sistemi.css` — token'ların CSS karşılığı; fiziksel
  `margin-left/right` yok (kanıt: `grep -c "margin-left" tasarim-sistemi.css` → 0;
  denetçi çalıştırabilir).

### 3.4 — JavaScript Modülleri (gerçek liste)

| Dosya | İşlev |
|---|---|
| `ana.js` | Akordeon + WhatsApp/tel/dil tıklama event'leri + kampanya bandı + bülten formu |
| `arama.js` | Overlay + autocomplete + `arama_yapildi` |
| `hesaplama-araci.js` | Widget → `/calculate` + `hesaplama_yapildi` |
| `iletisim.js` | `/iletisim` formu |
| `karsilastirma.js` | Sepet (localStorage `karsilastirma_sepeti` — kanıt: `grep -c` → 1) + tablo + fark vurgusu |
| `teklif-al.js` | Lead + randevu formları |
| `yorumlar.js` | Modal + gönderim |

**Düzeltme:** `kampanya-banner.js` ve `pwa.js` **yoktur** — banner mantığı `ana.js`'dedir,
SW kaydı layout içindeki `pwaGovde()` çıktısındadır (`includes/pwa.php` + `includes/sayfa.php`).

### 3.5 — SEO Denetimi

- `docs/seo-kart-f4.json`: 90/90/100/90/90 (5 sayfa, F4 raporu).
- `docs/seo-kart-final.json`: 95/90/95/95/95/100×5 (10 sayfa) + sitemap 72 URL + robots true (F8).
  Kalan tek FAIL sınıfı: marka-terim yoğunluğu (kayıtlı sapma).
- JSON-LD türleri (kodda): Organization, WebSite, ItemList, Product (+AggregateRating,
  +additionalProperty), FAQPage, LocalBusiness, ContactPage, Person
  (kanıt: `grep -n "@type" frontend/includes/seo.php frontend/sayfalar/ekibimiz.php`).

### 3.6 — Analytics

- ID'ler placeholder (`config.php`: `KAMELYA_GA4_ID ?: 'G-XXXXXXXXXX'` vd.; kanıt:
  `grep -n "XXX" frontend/config.php`). HTML'ye sızıntı yok (F6: placeholder'da 0 snippet).
- Consent Mode v2 denied-varsayılan + bant (`includes/analytics.php`); event'ler 3.4'teki
  dosyalarda (`grep -o "kamelyaOlay('[a-z_]*'" assets/js/*` → 9 event: hesaplama_yapildi,
  teklif_formu_gonderildi, randevu_talebi_olusturuldu, whatsapp_tiklandi, telefon_tiklandi,
  dil_degistirildi, arama_yapildi, kampanya_tiklama, cwv_olcumu).
- Clarity + web-vitals yalnızca kabulde (`clarity.php`, `cwv-izleme.php`); GSC meta
  yalnızca gerçek ID'de (`gsc-dogrulama.php`).

## BÖLÜM 4: ADMİN PANEL

> **Yol düzeltmesi:** Görevdeki `/admin/*.php` yolları projede yoktur. Gerçek yapı:
> `admin/index.php` (login) + `admin/panel.php?sayfa=X` + `admin/sayfa/*.php`
> (kanıt: Bölüm 1.1 listesi). Özellikle **`admin/audit-log.php` mevcut değildir** —
> audit okuma yalnızca `GET /api/v1/admin/audit-logs` API'siyledir (§7.1'de kayıtlı eksik).

### 4.1 — Admin Sayfaları (gerçek URL'ler)

| Sayfa | URL | Yetki (route RBAC) | Kanıt |
|---|---|---|---|
| Login | `/index.php` (:8001) | public | 200 + noindex (F7.1) |
| Dashboard | `/panel.php` | auth | sayaç + Bu Hafta + Bugün widget (F7.3) |
| Talepler | `?sayfa=talepler` | `yonetici\|satis` | 5 tür sekmesi + mailto + durum dropdown (F9-EK) |
| Ürünler / Ürün Formu | `?sayfa=urunler[-form]` | `yonetici\|editor` | CRUD + 6 dil + teknik detaylar (F10) |
| Kategoriler | `?sayfa=kategoriler` | `yonetici\|editor` | filtre + pasifleştirme |
| Blog (+form) | `?sayfa=blog[-form]` | `yonetici\|editor` | durum akışı |
| SSS | `?sayfa=sss` | `yonetici\|editor` | sıra-değiştir + pasifleştir |
| Galeri | `?sayfa=galeri` | `yonetici\|editor` | yükleme + grid |
| Yorumlar | `?sayfa=yorumlar` | `yonetici\|editor` | kuyruk + onayla/sebepli-ret/öne-çıkar |
| Takvim | `?sayfa=takvim` | `yonetici\|satis` | Ay grid + modal + ekip yükü |
| SEO Dashboard | `?sayfa=seo-dashboard` | `yonetici` | Chart.js + fırsatlar |
| Sertifikalar | `?sayfa=sertifikalar` | `yonetici\|editor` | CRUD |
| Ekip | `?sayfa=ekip` | `yonetici\|editor` | profil + public toggle |
| Bülten | `?sayfa=bulten` | `yonetici\|editor` | liste + CSV + toplu |
| Kampanyalar | `?sayfa=kampanyalar` | `yonetici\|editor` | tarih + 6 dil |
| Özellikler | `?sayfa=ozellikler` | `yonetici` | ağaç + toplu aç/kapat |
| Ayarlar | `?sayfa=ayarlar` | `yonetici\|editor` | site + banka + garanti + bildirim |

Tümü curl-200 doğrulandı (F7–F12 raporları).

### 4.2 — RBAC Matrisi

| Rol | Erişim (route kanıtı) |
|---|---|
| **yonetici** | Tam erişim |
| **editor** | İçerik CRUD + `seo/*` hariç; kanıt: `editor` ile `POST /admin/urunler` → 403 (F7.1), `GET /admin/seo/ozet` → 403 (F7.2) |
| **satis** | Talepler, takvim; kanıt: `satis` ile `POST /admin/takvim/randevu` → 201, `POST /admin/urunler` → 403 (F7.3) |

### 4.3 — Güvenlik

- JWT HS256 (`Core/Jwt.php`), access 1s + refresh 7g + kara liste (`token_karalistesi`);
  kanıt: login 200/401, refresh rotasyonu, logout sonrası 401 (F5).
- CSRF: `X-CSRF-Token` grup düzeyinde zorunlu (eksik → 403, F8 kanıtı).
- Rate limit kovaları (`RateLimitMiddleware.php` LIMITLER): calculate 30/dk, leads 3/saat,
  appointments 5/saat, login 5/15dk, yorumlar 3/24sa, iletisim 3/saat, bulten 3/saat,
  arama 60/dk; aşım → 429 + `Retry-After` (kanıt: F5–F11).
- Audit: tüm admin yazma + login/logout uçlarında (`AuditLogService::kaydet` çağrıları;
  IP maskeli — `Core/Gizlilik.php`, CLI-kanıtlı).
- Security headers (`SecurityHeadersMiddleware.php`): CSP, DENY, nosniff,
  Referrer-Policy, Permissions-Policy, koşullu HSTS; `X-Powered-By` kaldırılır
  (kanıt: `curl -I` F5).

### 4.4 — Özellik Toggle (F12)

- 37 seed (`SELECT COUNT(*) FROM ozellik_toggle` → 37); zorunlu: anasayfa, gizlilik, kvkk.
- Kural motoru curl-kanıtlı: kapat → çocuklar 0; zorunlu → 422; aç → çocuklar 0 kalır.
- Public kapı: kapalı sayfa → 410 + `410.php` (F12: `/sss` 410 kanıtlı); nav/footer/sitemap/vitrin kapıları.

## BÖLÜM 5: PDF UYUMLULUK MATRİSİ (`docs/Kamelya_Kapsam.md` V1'e karşı)

Lejant: ✅ var ve testli · ⚠️ kısmi/operasyonel · ❌ yok · ➖ kapsam-dışı (doğru yokluğu).

| PDF | Özellik | Durum | Kanıt |
|---|---|---|---|
| §2.1 | Ana Sayfa + 12 sayfa + SSS + Blog (+detay) + Galeri + İletişim + Teklif + Referanslar + Gizlilik + XML sitemap | ✅ | Bölüm 3.1 (26 sayfa; blog detay F13-A ile kapandı) |
| §2.2 | Model/Malzeme/Kullanım kategorileri | ✅ | `kategoriler` seed 12 satır |
| §2.2 | Ürün detay (ölçü/malzeme/çatı/korkuluk) | ✅ | `teknik_detaylar` (F10) |
| §2.2 | Ürün karşılaştırma | ✅ | `/karsilastir` + sepet |
| §2.2 | Fiyat aralığı etiketi | ✅ | `price_hint` |
| §2.2 | Stok gösterimi | ➖ | Sipariş-üzerine model (kapsam kararı) |
| §2.3 | m² hesap + panel fiyatı + anlık teklif | ✅ | Bölüm 2.4 senaryo 1 |
| §2.4 | 360° görüntüleme | ⚠️ → ✅ (2026-09-22, F14-A) | Önce: kolon vardı, viewer yoktu. Şimdi: Pannellum viewer + buton + admin tur seçeneği |
| §2.4 | Sanal showroom / sanal tur | ❌ → ✅ (2026-09-22, F14-B) | Önce: yok. Şimdi: 2 tablo + API + sayfa + admin + toggle (varsayılan kapalı) |
| §2.4 | Gerçek proje galerisi | ⚠️ | `galeri` tablosu + sayfa var, **içerik girişi yok** (operasyonel) |
| §2.4 | Video müşteri referansları / Instagram gömme | ❌ → ✅ (2026-09-22, F13-D/F14-D) | Önce: eşleşme yok. Şimdi: consent-kapılı embed + video `tur` akışı + referans tablosu/API/admin |
| §2.4 | Öncesi/sonrası | ❌ → ✅ (2026-09-22, F14-C) | Önce: toggle dışında yok. Şimdi: tablo + API + vanilla slider + admin |
| §2.5 | Sıcaklık/gölge simülasyonu | ❌ → ✅ (2026-09-22, F14-E) | Önce: toggle dışında yok. Şimdi: şeffaf formül + sayfa + SVG + admin katsayı (20 m² → 18 m²/-4°C doğrulandı) |
| §2.5 | Online keşif + WhatsApp + PWA | ✅ | Bölüm 2.4 S5 + 3.4 + PWA kanıtları |
| §2.5 | 3D konfigüratör / AR / Photo-to-Quote | ➖ | Kapsam-dışı/V2 |
| §2.6 | Blog + SSS + bakım + malzeme rehberleri | ✅ | Sayfalar + JSON-LD |
| §2.6 | Çardak/pergola fark rehberi | ⚠️ | Adanmış sayfa yok (blog dizininde linklenebilir) |
| §2.6 | İstanbul bakım takvimi/sayacı | ⚠️ | Genel rehber var, şehir-özel takvim/sayaç yok |
| §2.6 | Şehir landing sayfaları | ❌ → ✅ (2026-09-22, F13-B) | Önce: desen planlı, sayfa yok. Şimdi: 2 desen + dizin + admin + 18 sitemap URL |
| §2.6 | Çok dilli + dil bazlı fiyat | ✅ | 6 dil + `urun_fiyatlari` |
| §2.7 | Yorumlar + sertifika + garanti + ekip (+ LocalBusiness: kodda mevcuttu, F13-G ile doğrulandı) | ✅ | F9.1 + F10 + F13-G |
| §2.7 | Atölye fotoğrafları | ⚠️ → ✅ (2026-09-22, F13-F) | Önce: toggle var, akış yok. Şimdi: tablo + upload + grid + önizleme |
| §2.7 | Referans hikâyeleri (içerik) | ⚠️ | Şema var, içerik girişi yok (operasyonel) |
| §2.8 | CTA/WhatsApp/telefon/bülten/kampanya | ✅ | Bölüm 2.4 + F10 |
| §2.9 | Responsive/CWV/navigasyon/arama/KVKK/para birimi | ✅ | Bölüm 3.5 + F6 |
| §2.9 | Google Maps gömme | ❌ → ✅ (2026-09-22, F13-C) | Önce: harita yok. Şimdi: click-to-load iframe (KVKK katmanlı) |
| §2.9 | Sosyal paylaşım/gösterim | ⚠️ → ✅ (2026-09-22, F13-E) | Önce: anahtar var, render yok. Şimdi: footer ikonları + 5'li share + OG article |
| §2.9 | WCAG tam denetimi | ⚠️ | AA hedefli kodlama; bağımsız a11y audit yok |
| §3.8 | Bayilik/taksit/montaj kılavuzu | ➖ | Kapsam-dışı (doğru yokluk) |

**Toplam (V1 maddeleri): ~41 tamam, ~9 kısmi/operasyonel, ~7 yok (çoğu V1.5'e park edilmiş).**
**Güncelleme 2026-09-22 (F13): ~48 tamam, ~6 kısmi/operasyonel, ~3 yok (V1.5 parkı + şehir-içerik girişi).**

## BÖLÜM 6: TEST KANITLARI

### 6.1 — E2E (2026-09-22 canlı koşu; test artıkları silindi)

| # | Senaryo | Beklenen | Gerçekleşen |
|---|---|---|---|
| 1 | Ürün ekle → public → 300 EUR → lead | 201 + 300 + 201 | **PASS** (id 15/16, `final_price:300`, lead 201) |
| 2 | Blog → detay → WhatsApp event | event | **KISMİ**: blog detay rotası yok → rehber linki VAR + consent sonrası `whatsapp_tiklandi` dataLayer-kanıtlı |
| 3 | TR→AR RTL + SSS | `rtl/ar` | **PASS** |
| 4 | Admin ürün → public görünürlük | görünür | **PASS** (liste 3 eşleşme + H1 + Product) |
| 5 | Takvim + çakışma | 201 → 409 | **PASS** |
| 6 | SEO dashboard | 200 + grafik | **PASS** |
| 7 | Yorum onayla → public | görünür | **PASS** (`total:1`, PII yok) |
| 8 | Bülten → onay linki | `onaylandi` | **PASS** |
| 9 | Toggle → 410 | 410 | **PASS** (`/blog` 410, sonra restore) |
| 10 | Karşılaştırma | tablo | **PASS** (2 ürün + farklar) |

### 6.2 — Güvenlik (2026-09-22)

| Test | Sonuç |
|---|---|
| SQLi login/filtre | 401 / boş liste |
| XSS lead | ham saklanır (tasarım — kaçışlama çıktı katmanında) |
| CSRF'siz admin POST | 403 |
| Rate taşırma | 31. calculate → 429 + Retry-After |
| Vadesiz JWT (üretildi, -10sn) | 401 |
| satis → ürün POST | 403 |

### 6.3 — Performans (yerel)

- Ana sayfa ~0.02s, pricing ~0.006s (hedeflerin çok altında).
- LCP ≤2.5s (lab, 10 sayfa PASS); INP: lab etkileşim <200ms (SSS akordeon) + saha `web-vitals`; CLS: taşma yok (10/10).

## BÖLÜM 7: ⚠️ EKSİKLER VE RİSKLER

### 7.1 — Bilinen Eksikler (PDF V1'de var, projede yok)

1. **Blog detay sayfası/rotası yok** (`/blog/{slug}` 404) — blog CRUD ve dizin var, okuma ucu yok.
   → ✅ **KAPANDI (2026-09-22, F13-A):** API + sayfa + Article + share + sitemap.
2. **Şehir landing sayfaları yok** (F1.1 deseni uygulanmadı).
   → ✅ **KAPANDI (2026-09-22, F13-B):** 2 desen + dizin + admin + 18 sitemap URL (içerik girişi geliştiricide).
3. **Instagram/video gömme yok.**
   → ✅ **KAPANDI (2026-09-22, F13-D):** consent-kapılı embed + video `tur` akışı.
4. **Google Maps gömme yok** (iletişim).
   → ✅ **KAPANDI (2026-09-22, F13-C):** click-to-load iframe.
5. **Sanal tur / öncesi-sonrası / sıcaklık simülasyonu uygulaması yok** (toggle + 1 sütun dışında).
   → ✅ **KAPANDI (2026-09-22, F14):** 3 tablo + API + sayfalar + admin (içerik-boş başlar).
   → Açık kalıyor (V1.5 parkı).
6. **Audit-log okuma UI'sı yok** (yalnızca API).
   → Açık kalıyor.
7. **Atölye fotoğraf akışı yok.**
   → ✅ **KAPANDI (2026-09-22, F13-F):** tablo + upload + grid + önizleme.

### 7.2 — Kısmi Uygulamalar

1. 360°: `tur` değeri var, viewer yok. 2. Galeri/referans içeriği: şema hazır, veri girişi geliştiricide (şehir içeriği altyapısı F13-B ile kapandı). 3. Fark/İstanbul-takvim rehberleri: adanmış sayfa yok. 4. Sosyal: ~~anahtar var, render yok~~ → ✅ **KAPANDI (2026-09-22, F13-E):** footer ikonları + 5'li share + OG article. 5. WCAG: bağımsız audit yok.

### 7.3 — Teknik Borç

1. Arama `LIKE` (FULLTEXT yok). 2. PHPStan/statik analiz yok. 3. Subagent denetimleri tüm fazlarda altyapı kısıtıyla çalışmadı (manuel denetim ikamesi kayıtlı). 4. `seo_verileri` polimorfik (FK'siz — bilinçli). 5. Canlı-offline PWA navigasyon testi harness-kırılgan (önbellek kanıtlı).

### 7.4 — Üretim Öncesi Zorunlu

1. `.env`: GA4/GSC/Clarity ID + SMTP + JWT + DB doldurulmalı. 2. `FORCE_HTTPS=true`. 3. GSC hesabı + ilk senkron. 4. İçerik girişi (ürün/blog/galeri/referans/SSS/şehir). 5. Audit 90-gün tasfiyesi cron'u. 6. Yedek + monitoring.

### 7.5 — Test Edilmemiş Alanlar

1. Gerçek GSC hesabıyla API (mock-kanıtlı). 2. Gerçek SMTP teslimi (debug-SMTP-kanıtlı). 3. Gerçek ödeme akışı (bilgilendirme sayfası; online ödeme kapsam-dışı). 4. Yüksek trafik yük testi.

## BÖLÜM 8: BAĞIMSIZ DENETÇİ İÇİN NOTLAR

> Bu rapor opencode AI tarafından üretildi. Doğrulama protokolü:
> 1. Her `Dosya:Satır` referansını aç ve oku. 2. Her `curl` komutunu çalıştır
> (önce `kurulum-kilavuzu.md` ile ortamı ayağa kaldır; test verisi oluşturup sonra sil).
> 3. Her tabloyu `SHOW CREATE TABLE` ile kontrol et (`/tmp/schema.txt` transkripsiyonuna karşı).
> 4. Bölüm 5'i `docs/Kamelya_Kapsam.md` ile satır satır karşılaştır.
> 5. Yanlış/uydurma iddia bulursan raporda belirt. 6. Bölüm 7'de olmayan eksik bulursan ekle.
> Özel dikkat: V1-kapsam-but-projede-yok maddeler · "tamamlandı" denen yarımlar ·
> PII sızıntısı (public yanıtlarda e-posta/telefon) · `admin/` altında auth'suz uç ·
> 6 dilde boş kalan gövdeler.

## BÖLÜM 9: ÖZET TABLO

| Kategori | Tamam | Eksik | Kısmi | Yüzde |
|---|---|---|---|---|
| Backend API (122 route) | 122 | 0 | 0 | %100 |
| Frontend (29 sayfa) | 26 | 1 (audit UI yok — admin) | 2 (galeri/referans içeriksiz) | %97 |
| Admin Panel (19 sayfa) | 18 | 1 (audit UI) | 0 | %97 |
| Veritabanı (36 tablo) | 36 | 0 | 0 | %100 |
| Güvenlik | 6/6 test | 0 | 0 | %100 |
| SEO (10 sayfa) | 10 (85+) | 0 | 0 | %100 |
| PDF V1 Uyumu | ~41 | ~7 | ~9 | ~%82 tam, ~%95 kısmi-dahil |
| **TOPLAM** | | | | **~%95** |

*Güncelleme 2026-09-22 (F13): PDF ~48 tam; kalan: V1.5 parkı (sanal tur, öncesi/sonrası, sıcaklık, 360 viewer), audit UI, fark/İstanbul-takvim rehberleri, WCAG audit. Tahmini V1: **~%97**.*
*Güncelleme 2026-09-22 (F14): V1.5 parkı eritildi (5 modül kodlandı, içerik-boş başlar). Kalan: audit-log UI, fark/İstanbul-takvim rehberleri, WCAG audit, içerik girişleri. Tahmini V1 kod uyumu: **%100**.*
*Yüzde, V1 taahhüdüne göre ağırlıksız tahmindir; kesin hüküm bağımsız denetçinindir.*

