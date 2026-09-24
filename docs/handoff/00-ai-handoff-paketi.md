# AI Handoff Paketi — Kamelya UI Uygulaması

**Oluşturuldu:** 2026-09-24  
**Web-Ready Verdict:** ✅ EVET (koşulsuz) — commit `98044c6`  
**Amaç:** Claude/Cursor UI tasarım uygulamasında hiçbir özellik/bağlantı/fonksiyon kaçırılmasın

---

## 1. Özet

- **Sistem:** 6 dil × 31 sayfa × ~276 URL
- **Backend:** PHP/MySQL MVC (dokunulmaz — 45 migration, ~90 PHP sınıfı)
- **Frontend:** 12 include + 31 sayfa + 10 JS + **75** CSS token
- **SEO:** 234 `seo_verileri` satırı (kaynak korunmalı)
- **Analytics:** 6 GA4 event (korunmalı)
- **RTL:** AR dili hazır (`margin-inline` kullanılıyor)
- **Feature toggle:** `ozellik_toggle` tablosu (fail-open; 410 sayfası koşullu)

---

## 2. Belge İndeksi

1. `01-sayfa-envanteri.md` — her sayfanın tam haritası (31 sayfa)
2. `02-bilesen-katalogu.md` — tekrar kullanılan bileşenler (27 fonksiyon)
3. `03-api-frontend-sozlesmesi.md` — API bağlantıları (13+ public uç)
4. `04-js-davranislari.md` — JS davranışları (10 dosya)
5. `05-form-akislari.md` — form akışları (6 form)
6. `06-tasarim-tokenlari.md` — tasarım tokenları (75 token)
7. `07-veri-akis-haritasi.md` — DB → API → DOM (18 sayfa türü)
8. `08-ui-bagimliliklar.md` — kritik bağımlılıklar (20+)
9. `09-rtl-checklist.md` — AR dili kontrol listesi
10. `10-ui-degisim-etki-analizi.md` — blast radius haritası
11. `11-smoke-test-listesi.md` — UI sonrası retest listesi (276 URL)
12. `00-ai-handoff-paketi.md` — bu dosya

---

## 3. Kritik Uyarılar (Okumadan Başlama)

| # | Uyarı | Neden |
|---|-------|-------|
| 1 | `frontend/includes/bilesenler.php` → 31 sayfa kullanıyor | Değiştirmeden önce tüm çağrı noktalarını gör |
| 2 | `frontend/assets/css/tasarim-sistemi.css` 75 token | Tüm renk/font buradan. Hardcoded renk YASAK |
| 3 | `apiGet`/`apiPost` fonksiyonları | Tüm API çağrıları bunlar üzerinden. Doğrudan fetch KULLANMA |
| 4 | `seo_verileri` tablosu | Meta kaynağı. UI değişirse `$SEO_HAM` değişkenleri korunmalı |
| 5 | `analytics.php` → `kamelyaOlay()` | GA4 event'leri buradan. UI değişiminde event kaybolmasın |
| 6 | `margin-inline` kullan | `margin-left/right` YASAK (RTL bozar) |
| 7 | `data-*` attribute'lar | JS/API bağlantıları. Attribute silinirse fonksiyon kırılır |
| 8 | `etkinMi()` (ozellik.php) | Feature toggle gating. `ozellikAktifMi('sss')` çağrısı korunmalı |
| 9 | Hreflang 7 giriş | 6 dil + x-default. UI değişimi SEO'yu bozmasın |
| 10 | Form `web_sitesi` hidden field | Honeypot — silinmesin (spam filtresi) |

---

## 4. Değişim Sonrası Kontrol

- [ ] 276 URL × smoke test listesi (`11-smoke-test-listesi.md`)
- [ ] SEO analyzer ≥90 her sayfa
- [ ] GA4 6 event tetikleniyor
- [ ] RTL testi (AR)
- [ ] Feature toggle testi
- [ ] Form submit testi (6 form)
- [ ] Admin CRUD çalışıyor

---

## 5. İletişim

Bu paket **Web-Ready Verdict PASS** (commit `98044c6`) sonrası oluşturuldu.  
UI uygulaması sonrası OpenCode retest yapacak (`11-smoke-test-listesi.md`).
