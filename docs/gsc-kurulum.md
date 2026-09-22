# GSC Kurulum Rehberi — Kamelya (F7.2)

Bu dosya geliştirici içindir; kimlik bilgisi commitlenmez.

## 1. Service Account oluşturma

1. Google Cloud Console → yeni proje (veya mevcut) → **APIs & Services → Library** → **Google Search Console API** → Enable.
2. **IAM & Admin → Service Accounts** → Create → rol gerekmiyor (salt-okunur scope kodda).
3. Service Account → **Keys** → Add Key → JSON → indir.
4. JSON dosyasını sunucuya koy (örn. `/srv/kamelya/secrets/gsc-hesap.json`, web kökü dışı, `600` izin).

## 2. Search Console erişimi

1. Search Console → site → **Ayarlar → Kullanıcılar ve izinler** → kullanıcı ekle.
2. Service Account e-postasını (`...@....iam.gserviceaccount.com`) **Tam** izinle ekle.

## 3. Backend yapılandırma

```bash
export GSC_SERVICE_ACCOUNT_JSON=/srv/kamelya/secrets/gsc-hesap.json
export GSC_SITE_URL=https://kamelya.com/
```

(`sc-domain:` mülklerinde `site_url` önek biçiminde yazılır.)

## 4. Doğrulama

```bash
php backend/scripts/gsc-senkronize.php --gun=7
curl -H "Authorization: Bearer <JWT>" \
  "http://127.0.0.1:8000/api/v1/admin/seo/ozet?baslangic=2026-08-22&bitis=2026-09-21"
```

`GSC_YAPILANDIRILMADI` (503) → 1–3. adımları kontrol et.

## 5. Cron (günlük senkron)

Sunucuda (geliştirici kurar):

```cron
0 3 * * * /usr/bin/php /srv/kamelya/backend/scripts/gsc-senkronize.php --gun=30
```

## Kota notu

- Ücretsiz kota: ~2000 sorgu/gün. Kod 1 saat dosya-önbelleği + kalıcı `seo_analitik_verileri` tablosuyla korur.
- GSC verisi ~3 gün gecikmelidir; senkron bitişi otomatik `-3 gün` alınır.
