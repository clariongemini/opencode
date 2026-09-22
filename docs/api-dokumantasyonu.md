# API Dokümantasyonu — Kamelya (v1)

Baz: `/api/v1`. Yanıt sözleşmesi: başarı `{success:true, data, meta?}` · hata `{success:false, error:{code,message,details}}`. İçerik/fiyat uçlarında `lang` zorunludur.

## Public

| Metot | Uç | Açıklama |
|---|---|---|
| GET | `/health` | Sistem + DB durumu (200/503) |
| GET | `/products?lang=&page=&per_page=&sort=&filter[material|model|usage]=&q=` | Liste + `price_hint` |
| GET | `/products/{id}?lang=` | Detay (çeviri + resim + SEO) |
| POST | `/calculate` `{width,length|area_m2,material,model,usage,lang}` | Fiyat motoru (30/dk limit) |
| POST | `/leads` | Teklif talebi 201 (3/saat; KVKK zorunlu) |
| POST | `/appointments` | Randevu 201 (5/saat; +24h, Pzt–Cmt) |
| GET | `/pricing?lang=` | Temel fiyat + çarpanlar |
| GET | `/seo/check?url=&lang=` | Meta bant kontrolü + skor |
| GET | `/dosyalar/{tip}/{ad}` | Güvenli dosya sunumu (traversal korumalı) |

## Auth (`/auth`)

| Metot | Uç | Açıklama |
|---|---|---|
| POST | `/auth/login` `{eposta,sifre}` | JWT çifti + csrf_token (5/15dk limit) |
| POST | `/auth/refresh` `{refresh_token}` | Rotasyon (eski kara liste) |
| POST | `/auth/logout` | Access kara listeye |

## Admin (`Authorization: Bearer`, yazmalarda `X-CSRF-Token`)

- İçerik CRUD: `urunler|kategoriler|blog-yazilari|sss-sorulari|galeri` (GET/POST[/{id}]/PUT/DELETE) + `ayarlar` (GET/PUT) — rol `yonetici|editor`.
- `upload` (POST multipart, DELETE `upload/{id}`) — `yonetici|editor`.
- `talepler`, `audit-logs` (GET) — talepler `yonetici|satis`, audit `yonetici`.
- SEO: `seo/ozet|sorgular|sayfalar|ulke-dagilimi|cihaz-dagilimi|trend|kelime-firsatlari` (GET), `seo/senkronize|seo/denetle` (POST) — `yonetici`.
- Takvim: `takvim/aylik|gun/{tarih}|yaklasan|ekip-yuku|ekip-uyeleri` (GET), `takvim/randevu` (POST), `takvim/randevu/{id}` (PUT/DELETE=iptal), `takvim/randevu/{id}/durum` (PATCH) — `yonetici|satis`.

## Hata kodları

`VALIDATION_ERROR` 422 · `PRODUCT_NOT_FOUND|TALEP_NOT_FOUND|PRICE_NOT_DEFINED|SEO_NOT_FOUND` 404 ·
`UNSUPPORTED_LANG` 422 · `UNAUTHORIZED` 401 · `FORBIDDEN` 403 · `CONFLICT|SLOT_DOLU` 409 ·
`RATE_LIMITED` 429 (+Retry-After) · `GSC_*` 403/429/503 · `INTERNAL_ERROR` 500.
