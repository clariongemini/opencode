# Analitik Dönüşüm Hunisi — Kamelya (F6)

**Kural:** Hedef oranlar başlangıç varsayımıdır ("başlangıç" ibaresiyle); gerçek veri GA4 + `talepler` tablosundan 4–6 hafta sonra kalibre edilir. Event adları `frontend/assets/js/` ile birebir eşleşir.

## Huni 1 — Ürün İlgisi

Ana sayfa → Ürünler → Ürün Detay → Hesaplama Aracı → Teklif Formu

| Adım | GA4 olayı | Kaynak |
|---|---|---|
| Ürün listesi görüntüleme | `view_item_list` (öneri; F7'de) | — |
| Ürün detay | `view_item` (öneri; F7'de) | — |
| Hesaplama | `hesaplama_yapildi` (alan, malzeme, model, dil, fiyat) | `hesaplama-araci.js` ✓ aktif |
| Teklif | `teklif_formu_gonderildi` (şehir, dil) | `teklif-al.js` ✓ aktif |

## Huni 2 — Doğrudan Dönüşüm

Herhangi sayfa → Hesaplama Aracı → Teklif Formu (yukarıdaki iki event ile ölçülür).

## Huni 3 — İletişim

Herhangi sayfa → WhatsApp/Telefon: `whatsapp_tiklandi`, `telefon_tiklandi` (`ana.js` ✓ aktif).

## Dil değişimi (segment)

`dil_degistirildi` (hedef_dil) — dil bazlı dönüşüm oranları için kırılım boyutu.

## Hedef dönüşüm oranları (başlangıç)

- Huni 1 uçtan uca: **%2–3** (başlangıç hedefi).
- Huni 2 (hesap → teklif): **%8–12** (başlangıç hedefi; niyetli trafik).
- Huni 3 (sayfa → tıklama): **%5–8** (başlangıç hedefi).

## GA4 Funnel Exploration kurulumu

1. GA4 → Explore → Funnel exploration → yeni huni.
2. Adımları yukarıdaki event adlarıyla sırayla ekle (Huni 1: `view_item_list` → `view_item` → `hesaplama_yapildi` → `teklif_formu_gonderildi`).
3. Kırılım (breakdown): `dil` parametresi + `sehir` (teklif eventinden).
4. Dönem: son 28 gün; ilk kalibrasyon 4–6 hafta veri sonrası.
