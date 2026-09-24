# Admin Kullanma Kılavuzu — Kamelya

(Ekran görüntüleri prod kurulum sonrası eklenecek; adımlar metinsel ve tamdır.)

## Giriş

1. `/index.php` açın, e-posta + şifre ile giriş yapın.
2. Token biterse panel otomatik girişe yönlendirir; Çıkış soldaki menüdedir.

## Ürünler

- **Liste:** arama kutusuyla kod/başlık arayın; Düzenle ile forma geçin; Sil soft-delete uygular.
- **Form:** ürün kodu + 3 kategori (Model/Malzeme/Kullanım) zorunludur. 6 dil sekmesi vardır;
  yalnızca doldurulan diller kaydedilir; slug boşsa başlıktan otomatik üretilir. AR sekmede
  textarea RTL'dir.

## Kategoriler / Blog / SSS / Galeri / Ayarlar

- **Kategoriler:** tür filtresi; kod deseni `^[a-z0-9_]+$` (filtrelerle uyumlu olmalı).
- **Blog:** durum taslak/yayında/arşiv; TR başlık + içerik zorunludur.
- **SSS:** kapsam (8): `fiyatlama|malzeme|bakim|montaj|garanti|teknik|kullanim|karsilastirma`
  (`AdminSssService::KAPSAMLAR` — F16.2.3); sıra sayısı; listede sıra "değiştir" ile güncellenir;
  Sil pasifleştirir. Public: `GET /api/v1/sss-sorulari?lang=` (6 dil × 40 soru).
- **Galeri:** jpg/png/webp (5MB); başlık zorunludur; yüklenen ilk ürün görseli otomatik kapaktır.
- **Ayarlar:** yalnızca listedeki anahtarlar yazılır (site adı, telefon, WhatsApp, varsayılan dil, sosyal).

## Takvim

- Ay görünümü + tür/ekip/şehir filtresi; güne tıklayınca modal, işe tıklayınca detay.
- Yeni randevu: tür, tarih-saat, süre, müşteri, adres (keşif/montaj zorunlu), ekip, öncelik.
- Durum akışı: bekliyor → devam_ediyor → tamamlandi (terminal), iptal ↔ bekliyor.
- Aynı ekipte çakışan saat uyarılır (409).

## SEO Dashboard

- Aralık seçin (7/30/90 gün); kartlar + trend + ülke/cihaz + fırsat listeleri.
- "Kelime Fırsatları": yüksek gösterim + düşük CTR kelimeler için başlık/meta önerisi.
- Senkron düğmesi GSC hesabı yoksa anlamlı hata verir (kurulum: `docs/gsc-kurulum.md`).
