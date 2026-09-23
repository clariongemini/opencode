# Kamelya — opencode Proje Anayasası

**Kaynak:** [APP-FABRIKA](https://github.com/clariongemini/APP-FABRIKA) (Ulaş Kaşıkcı) — Cursor ekosistemi için geliştirilen fabrika paketinin opencode'a uyarlanmış hali.

Bu kurallar `opencode.json` içindeki `instructions` ile her oturuma otomatik yüklenir. Geliştirici uyarı yazmasa bile geçerlidir.

---

## Temel İlkeler

1. **Halüsinasyon sıfır:** Var olmayan dosya, sınıf, API, script veya governance kaydı **uydurma**. Okumadan referans verme. Dosya içeriği, grep, glob, terminal çıktısı = kanıt; tahmin değil.
2. **Plan önce (YAPILACAKLAR kapısı):** Kod/dosya değişikliği öncesi `YAPILACAKLAR.md` oku. Yoksa `/yeni-proje` veya `/baslat` akışını uygula. Aktif faz bitmeden alt faza geçme.
3. **Hiyerarşik denetim (tek ajan onayı yasak):** Hiçbir ajan kendi işini nihai doğru ilan edemez. Her çıktı en az L1 (üst departman) + L2 (CAO) katmanından geçer.
4. **Kanıt zorunluluğu:** "Tamamlandı / hazır / çalışıyor" iddiası yalnızca terminal çıktısı veya dosya satır referansı ile.
5. **Tek faz kuralı:** Aynı anda yalnızca **bir** faz `işleniyor` olabilir.
6. **Sorumluluk ayrımı:** Ürün kararları ve öncelikler geliştiriciye aittir; teknik yürütme ve koordinasyon ajanların işidir.

## Zorunlu İş Akışı

```
Geliştirici talebi → YAPILACAKLAR (/baslat) → Hafıza Oku → Departman/Ajan Seç → Planla → Uygula → L1 Doğrula → Denetle (/denetle) → Kaydet → Raporla (/cevap)
```

**ASLA körlemesine kod yazmaya başlama.**

---

## Ajan Hiyerarşisi (opencode.json)

| Ajan | Mod | Rol | Denetleyen |
|------|-----|-----|------------|
| **overmind** | primary | Merkez koordinasyon, teknik yürütme | Mimar (insan, L5) |
| **ceo** | subagent | Teslimat gerçekliği, sprint önceliği (readonly) | EGC |
| **cao** | subagent | Denetim kalitesi, denetçileri denetler (readonly) | CEO |
| **pdc** | subagent | Ürün karar kurulu, roadmap (readonly) | CAO → CEO |
| **cpo** | subagent | Ürün/vizyon/pazar analizi (readonly) | PDC |
| **architect** | subagent | Mimari kararlar, modül haritası (denetimli düzenleme) | CEC → CAO |
| **security** | subagent | Güvenlik ve QA denetimi (readonly) | Architect → CAO |
| **phase-auditor** | subagent | Faz L1+L2 bulgu raporu (readonly) | CAO |
| **hallucination-guard** | subagent | Kanıt/kayıt doğrulama (readonly) | Architect |

İlke: *"Hiçbir departman kendi işini nihai doğru ilan edemez; hiçbir denetçi kendi denetimini nihai doğru ilan edemez."* Faz tamamlanması ≠ proje tamamlandı.

---

## YAPILACAKLAR (zorunlu plan)

| Dosya | Rol |
|-------|-----|
| `YAPILACAKLAR.md` (kök) | F0–F8 faz planı — `/yeni-proje` veya `/baslat` ile oluşturulur |
| Durum ibareleri | **`bekliyor`** · **`işleniyor`** · **`tamamlandı`** |

### Faz haritası (bina metaforu)

| Faz | Bina | Kapsam |
|-----|------|--------|
| F0 | Temel & zemin | Governance, MCP, hafıza, plan kilidi |
| F1 | Kolon & taşıyıcı | CPO vizyon, pazar, monetizasyon |
| F2 | Kat döşeme | Mimari, modül haritası, bağımlılıklar, domain standartları, PHP/MySQL MVC API scaffold (`standards/web/PHP_MVC_API.md`) |
| F3 | Duvar & tesisat | Uygulama iskeleti, core modüller, SEO Analyzer entegrasyonu (`seo-analyzer` skill) |
| F4 | Cephe & UI | UI/UX, i18n, tasarım sistemi |
| F5 | Elektrik & güvenlik | Denetim, güvenlik, uyumluluk |
| F6 | Ölçüm & analitik | Analytics, ölçüm |
| F7 | İç mekan & detay | Feature WP'ler, ince işçilik |
| F8 | Anahtar teslim | CAO + CEO denetimi, release gate |

### Faz çalıştırma protokolü

1. Fazı `işleniyor` yap → `YAPILACAKLAR.md` güncelle.
2. İlgili ajan/skill'i uygula (aşağıdaki tablolar).
3. İş bitince **L1 ajan** ile doğrula (`@phase-auditor` veya departman subagent); faz HTML/API çıktısı üretiyorsa `seo-analyzer` denetimi de uygula.
4. Satırı `tamamlandı` yap — **yalnızca `/denetle` (kod + SEO denetimi) PASS ise**; sonraki fazın ilk satırını `işleniyor` yap.
5. **Keşif kuralı:** Uygulama sırasında eksik/güncelleme gerekirse aynı faza veya uygun üst faza **`[EK-YYYYMMDD]`** ile yeni madde ekle; asla atlama.

---

## Reasoning Disiplini (kod değişikliği öncesi)

Kod veya core dosya değişikliğinden **önce** (muafiyet hariç), chat çıktısının başında **sırayla** üç bloğu aç:

1. `<thinking>` — faz, modül, standart, kanıt (**max 150–200 kelime**)
2. `<architecture_check>` — katman uyumu, bağımlılık sırası (**max 150–200 kelime**)
3. `<negative_constraints>` — katman bypass yok, mock bırakma yok, uydurma yok (**max 150–200 kelime**)

Etiketleri **kapatmadan** koda geçme.

**Zorunlu:** faz `işleniyor`, core/DB/network değişikliği, build-loop/quality-gate debug.
**Muaf:** doc typo, `/faz-durumu`, salt okuma, AAR.

---

## Komutlar (.opencode/commands/)

| Komut | İşlev |
|-------|-------|
| `/yeni-proje` | Tam bootstrap: YAPILACAKLAR.md oluştur, F0 başlat |
| `/baslat` | Promptu hiyerarşik faz planına çevir, F0'dan devam |
| `/devam-et` | Aktif fazdan devam (yapilacaklar-executor) |
| `/denetle` | Hiyerarşik denetim (L1+L2 raporu, kod + SEO denetimi) |
| `/faz-durumu` | Faz özeti |
| `/cevap` | After Action Review (DIAGNOSTIC; dosya değiştirmez) |
| `/standart-guncelle` | APP-FABRIKA reposundan standart güncelleme |

---

## Skill'ler (.opencode/skills/)

| Skill | Kapsam |
|-------|--------|
| `zero-hallucination` | Kanıt protokolü, YAPILACAKLAR kapısı |
| `hierarchical-audit` | L1→L4 denetim zinciri |
| `yapilacaklar-planner` | Prompt → F0–F8 faz planı |
| `yapilacaklar-executor` | Aktif faz uygulama + keşif protokolü |
| `web-developer` | Web geliştirme standartları (Next.js/TS/Tailwind/a11y) |
| `seo-specialist` | SEO/keşif standartları (meta baseline, long-tail) |
| `seo-analyzer` | Teknik SEO denetimi (HTML/meta/H1-H6/keyword/internal link/CWV/mobil) — `/denetle` faz kapanışında zorunlu |
| `database-administrator` | Veritabanı standartları (offline-first, sync, şifreleme) |
| `ui-ux-designer` | Kurumsal tasarım ilkeleri + anti-AI desenleri |

---

## MCP (opencode.json)

| MCP | Durum | Amaç |
|-----|-------|------|
| `playwright` | **aktif** | Tarayıcı otomasyonu, rakip analizi, UI doğrulama (P0) |
| `github` | **kapalı** | Repo/Issues/PR/CI yönetimi (P0) — `GITHUB_TOKEN` ortam değişkeni tanımlanıp `enabled: true` yapılmalı |
| MySQL MCP | **tanımsız** | Veritabanı erişimi gerektiğinde F2 fazında tanımlanacak (bkz. Standart Boşlukları) |

MCP tarayıcı çıktısı dosyaya yazılır, sohbete güvenilmez.

---

## Standart Kaynakları ve Boşluklar

### Repodan alınan standartlar

- **Web (blueprint + adapter):** Next.js App Router, TypeScript, token-driven Tailwind, semantic HTML, a11y-first, Playwright E2E, SEO meta baseline, privacy-friendly analytics, Lighthouse CI.
- **UI/UX (04-design + Liquid Glass):** Kurumsal tasarım ilkeleri, anti-AI desen listesi, tokens, a11y (WCAG 2.1 AA), tipografi/spacing/interaction/motion kuralları.
- **Veritabanı (mimari katmanlar):** Offline-first + sync queue, şifreleme (SQLCipher/EncryptedSharedPreferences), dağıtık V2 (PostgreSQL/Redis).
- **Denetim/governance:** Hiyerarşik denetim zinciri, intent gate, YAPILACAKLAR sistemi, freeze disiplini, AAR sözleşmesi.

### Repoda OLMAYAN standartlar — geliştirici talebiyle kapatıldı [EK-20260920]

Aşağıdaki standartlar APP-FABRICA repoda **yoktu**; geliştirici talebiyle bu oturumda tanımlandı. Kaynağı APP-FABRICA değildir — `/standart-guncelle` akışında bu dosyalar korunur, silinmez.

- **AI-Çağı SEO (AI SEO standardı) [EK-20260923]:** `standards/seo/AI_CAĞI_SEO_STANDARTLARI.md` — zero-click stratejisi, yapılandırılmış veri genişletmeleri (sameAs, dateModified, Person, TL;DR), E-E-A-T sinyalleri, information gain, AI alıntı formatı, AI performans takibi (GSC AI Report + ChatGPT/Perplexity), kesin karakter bantları (title 50–60 byte, desc 150–160 byte). `seo-analyzer` + `seo-specialist` SKILL.md'lerinden ve `docs/seo-kontrol-listesi.md`'den referanslanır.

- **PHP/MySQL MVC API (backend standartı):** `standards/web/PHP_MVC_API.md` — katmanlı MVC (Controller → Service → Repository), RESTful API kuralları (metotlar, durum kodları, JSON sözleşmesi, `/api/v1` versiyonlama), MySQL 8+ (utf8mb4/InnoDB, Foreign Keys, indexing, geri alınabilir migration + seeding), güvenlik (SQL Injection/XSS/CSRF/JWT), PSR-12 + SOLID. F2 (Kat Döşeme) scaffold kurulumu ve F3 (Duvar & Tesisat) core modüller bu standarda tabidir; `web-developer` + `database-administrator` skill'lerine entegre edildi.
- **Teknik SEO denetimi (SEO Analyzer):** `seo-analyzer` skill (`.opencode/skills/seo-analyzer/`) — backend HTML çıktı denetimi, meta etiketler, H1-H6 hiyerarşisi, anahtar kelime yoğunluğu (%1–2 bant), iç bağlantı yapısı, Core Web Vitals (LCP ≤ 2.5s / INP ≤ 200ms / CLS ≤ 0.1), mobil uyumluluk, sektör rekabet analizi. `/denetle` ile her faz sonunda zorunlu; strateji/keşif kanalı `seo-specialist`'te kalır. **AI-çağı uzantısı [EK-20260923]:** `standards/seo/AI_CAĞI_SEO_STANDARTLARI.md` — bu standartlar hem web hem AI için geçerlidir (zero-click, JSON-LD genişletmeleri, E-E-A-T, information gain, AI alıntı formatı, AI performans takibi, kesin karakter bantları).

### Kalan boşluklar (geliştirici onayı bekliyor)

- **MySQL MCP tanımı:** `opencode.json` içinde yok; F2+ fazında geliştirici onayıyla tanımlanacak.
- **Harici SEO API entegrasyonu** (Search Console/SerpAPI vb.): opsiyonel; geliştirici onayıyla `opencode.json` MCP bölümüne eklenecek.

---

## Standart Güncelleme Akışı

```
git clone https://github.com/clariongemini/APP-FABRIKA (geçici dizin)
→ diff analizi (repodaki kurallar/scripts/templates vs kamelya)
→ opencode bileşenlerine yeniden haritalandır (.opencode/ + opencode.json + AGENTS.md)
→ değişiklik raporu sun → geliştirici onayı → uygula
```

Komut: `/standart-guncelle` · Kural: Cursor'a özgü yapılar (`.mdc`, `.cursorrules`, `.cursor/`) doğrudan kullanılmaz; opencode formatına dönüştürülür.

---

## Yasaklar

- YAPILACAKLAR olmadan kod üretmek
- Tamamlanmamış üst fazda alt fazı `tamamlandı` saymak
- Tek ajanın kendi işini nihai onaylaması
- Kanıt olmadan "tamamlandı/hazır/çalışıyor" iddiası
- Repoda var olmayan standart/script/kural referansı vermek
