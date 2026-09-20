# Faz Haritası — Kamelya (F0–F8)

Faz tanımlarının merkezi standart kaydı. `opencode.json` içindeki `instructions` ile her oturuma yüklenir; `AGENTS.md`'deki faz haritası özetiyle senkron tutulur.

## Fazlar

| Faz | Bina | Kapsam |
|-----|------|--------|
| F0 | Temel & zemin | Governance, MCP, hafıza, plan kilidi, standart kurulumu |
| F1 | Kolon & taşıyıcı | CPO vizyon, pazar, monetizasyon (sektör + hedef anahtar kelimeler netleşir) |
| F2 | Kat döşeme | Mimari, modül haritası, bağımlılıklar, domain standartları, **PHP/MySQL MVC API scaffold (`standards/web/PHP_MVC_API.md`)** |
| F3 | Duvar & tesisat | Uygulama iskeleti, core modüller, **SEO Analyzer entegrasyonu (`seo-analyzer` skill)** |
| F4 | Cephe & UI | UI/UX, i18n, tasarım sistemi |
| F5 | Elektrik & güvenlik | Denetim, güvenlik, uyumluluk |
| F6 | Ölçüm & analitik | Analytics, ölçüm |
| F7 | İç mekan & detay | Feature WP'ler, ince işçilik |
| F8 | Anahtar teslim | CAO + CEO denetimi, release gate |

## Faz kapanış kapısı ("geri dönüş yok")

Her faz sonunda faz `tamamlandı` işareti **yalnızca** şu üçü sağlanırsa konur:

1. **L1 (üst departman) doğrulaması PASS** — `@phase-auditor` veya departman subagent.
2. **L2 (CAO) denetimi PASS** — `/denetle` (`@phase-auditor` + `@hallucination-guard` zinciri).
3. **SEO denetimi PASS** — `seo-analyzer` skill: faz HTML/API çıktısı üretiyorsa zorunlu; üretmiyorsa raporda `SKIP` kaydıyla geçilir.

Kod denetimi veya SEO denetimi eksikken sonraki faza **geçilmez**.

## Faz içi sıra

1. Fazı `işleniyor` yap → `YAPILACAKLAR.md` güncelle.
2. Ajan/skill uygula (standardlar: `standards/`, `AGENTS.md`).
3. L1 doğrula → `/denetle` (kod + SEO).
4. `tamamlandı` → sonraki fazın ilk satırı `işleniyor`.
5. Keşif: `[EK-YYYYMMDD]` ile aynı faza veya uygun üst faza ekle; asla atlama.

## Standart dosyaları

| Dosya | Kapsam |
|-------|--------|
| `standards/web/PHP_MVC_API.md` | PHP/MySQL MVC API: katmanlı MVC + Service + Repository, RESTful API, MySQL 8+ (FK/indexing/migration/seeding), güvenlik (SQLi/XSS/CSRF/JWT), PSR-12 + SOLID |
| `standards/PHASE_MAP.md` | Bu dosya — faz haritası ve kapanış kapısı |
