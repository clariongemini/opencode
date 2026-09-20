# YAPILACAKLAR — Kamelya

**Kaynak prompt:** "Eksik Backend ve SEO Standartlarını Oluşturma ve Entegre Etme" (geliştirici görevi, 2026-09-20) — Kamelya birincil hedefi "kusursuz web sitesi, mükemmel backend, MVC API ve kendi içinde SEO analiz yapısı" için: (1) PHP/MySQL MVC API standart dosyası + `web-developer`/`database-administrator` entegrasyonu, (2) `seo-analyzer` yeteneği + `/denetle` entegrasyonu, (3) F0–F8 faz akışı güncellemesi (AGENTS.md + opencode.json). **Kısıt: Sadece altyapı ve standartlar; Kamelya uygulama kodu yazılmayacak.**
**Durum:** initialized · 2026-09-20

## F0 — Zemin & Temel (tamamlandı)

| # | Madde | Ajan | L1 | Kabul | Durum |
|---|-------|------|----|-------|-------|
| F0.1 | MCP hazır (playwright aktif; github kapalı — `GITHUB_TOKEN` hatırlatması geliştiricide) | overmind | geliştirici | opencode.json MCP listesi okundu (satır 106–124) | tamamlandı |
| F0.2 | Governance kurulumu doğrulandı (AGENTS.md + opencode.json yüklü) | overmind | geliştirici | dosyalar okundu, kurallar aktif | tamamlandı |
| F0.3 | [EK-20260920] PHP/MySQL MVC API standart dosyası (`standards/web/PHP_MVC_API.md`): Mimari (katmanlı MVC + Service + Repository), API (RESTful), Veritabanı (MySQL 8+), Güvenlik (SQLi/XSS/CSRF/JWT), Kod Kalitesi (PSR-12/SOLID) | overmind | architect | dosya var + 5 bölüm içerik kanıtı (satır 9/59/121/150/163) | tamamlandı |
| F0.4 | [EK-20260920] Standart entegrasyonu: `web-developer` + `database-administrator` SKILL.md'lerine PHP/MySQL bölümleri; standart boşlukları kapatıldı | overmind | architect | iki SKILL.md güncel + satır kanıtı (wd:69/74, db:46/50) | tamamlandı |
| F0.5 | [EK-20260920] `seo-analyzer` yeteneği (`.opencode/skills/seo-analyzer/SKILL.md`): HTML/meta/H1-H6/keyword/internal-link/CWV/mobil denetimi + sektör rekabet analizi | overmind | cao | SKILL.md var + frontmatter kaydı (satır 2) | tamamlandı |
| F0.6 | [EK-20260920] `/denetle` komutuna SEO Analyzer denetim adımı (kod + SEO denetimi olmadan faz kapanmaz kuralı) | overmind | cao | denetle.md güncel + satır kanıtı (13/29/41) | tamamlandı |
| F0.7 | [EK-20260920] Faz akışı güncellemesi: AGENTS.md + opencode.json (instructions + references + architect promptu) + `standards/PHASE_MAP.md` — F2'ye PHP/MySQL MVC API, F3'e SEO Analyzer | overmind | geliştirici | dosyalar güncel + satır kanıtı (AGENTS:59/60/71/116/147, oj:10/76+JSON PASS, PHASE_MAP:11/12) | tamamlandı |
| F0.8 | [EK-20260920] opencode yapısının ve mimarinin github reposuna yüklenmesi (`clariongemini/opencode`): AGENTS.md, opencode.json, `.opencode/` (commands + skills), `standards/`, YAPILACAKLAR.md; `node_modules` hariç | overmind | geliştirici | push çıktısı (commit 3cc7b46, 22 dosya / 1451 satır) + remote doğrulama (git ls-remote SHA eşleşti; gh api contents listelendi) | tamamlandı |

## F1 — Kolon & Taşıyıcı (bekliyor)

| # | Madde | Ajan | L1 | Kabul | Durum |
|---|-------|------|----|-------|-------|
| F1.1 | CPO vizyon, pazar ve monetizasyon analizi — Kamelya sektörü ve hedef anahtar kelimeler bu fazda netleşir (`seo-analyzer` rekabet analizinin girdisi) | cpo | pdc | pazar raporu + kanıt (web search / MCP tarayıcı) | bekliyor |

## F2 — Kat Döşeme (bekliyor)

| # | Madde | Ajan | L1 | Kabul | Durum |
|---|-------|------|----|-------|-------|
| F2.1 | PHP/MySQL MVC API scaffold kurulumu (`standards/web/PHP_MVC_API.md` standardına göre) + domain standartları | architect | cao | iskelet + standart doğrulaması + kanıt | bekliyor |

## F3 — Duvar & Tesisat (bekliyor)

Kapsam notu: SEO Analyzer entegrasyonu — core modüller `seo-analyzer` puan kartı akışına bağlanır; her faz sonu `/denetle`'de kod + SEO denetimi zorunlu.

## F4 — Cephe & UI (bekliyor)

## F5 — Elektrik & Güvenlik (bekliyor)

## F6 — Ölçüm & Analitik (bekliyor)

## F7 — İç Mekan (bekliyor)

| # | Madde | Ajan | L1 | Kabul | Durum |
|---|-------|------|----|-------|-------|
| F7.1 | "Kusursuz web sitesi" feature WP (ön yüz sayfaları, F4 tasarım sistemiyle uyumlu) | overmind | architect | WP + kanıt | bekliyor |
| F7.2 | "Mükemmel backend / MVC API" feature WP (endpoint'ler, standards/web/PHP_MVC_API.md uyumu) | overmind | architect | WP + kanıt | bekliyor |
| F7.3 | "Kendi içinde SEO analiz yapısı" feature WP (seo-analyzer puan kartı + rapor akışı) | overmind | architect | WP + kanıt | bekliyor |

## F8 — Anahtar Teslim (bekliyor)

| # | Madde | Ajan | L1 | Kabul | Durum |
|---|-------|------|----|-------|-------|
| F8.1 | CAO + CEO denetimi, release gate (kod + SEO denetimi PASS zorunlu) | cao | ceo | L1+L2+SEO raporu | bekliyor |

## Keşifler & Dinamik Eklemeler

| Tarih | Keşif | Faz | Durum |
|-------|-------|-----|-------|
