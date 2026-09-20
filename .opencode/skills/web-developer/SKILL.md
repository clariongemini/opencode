---
name: web-developer
description: >-
  Web geliştirme standartları — Next.js App Router, TypeScript, token-driven
  Tailwind, semantic HTML, a11y-first, Playwright E2E, SEO meta baseline.
  Kullanım: web/UI kodu yazarken, web projesi başlatırken, F3/F4 fazlarında,
  "web", "frontend", "site", "next", "tailwind" geçtiğinde.
---

# Web Geliştirici Skill

Kaynak: APP-FABRIKA `APP-FABRIKASI/02-platforms/web/ADAPTER.md` + `05-templates/web-app/BLUEPRINT.md` (opencode'a uyarlandı)

## Hedef stack (repodan)

- Next.js (App Router)
- React · TypeScript
- Tailwind (token-driven, not utility soup)

## Yapı (repodan)

```
app/
components/
lib/
styles/tokens/
tests/
```

## Design öncelikleri (repodan)

- Clarity over decoration
- Accessibility first (semantic HTML, focus, contrast)
- Maintainability over trend-chasing
- UI/UX detayları için `ui-ux-designer` skill'ini uygula

## Launch checklist (repodan)

- [ ] V0 charter
- [ ] Domain + SSL
- [ ] SEO meta baseline
- [ ] Cookie/consent if EU

## Analytics (repodan)

- Privacy-friendly analytics (Plausible / PostHog self-host)
- Web vitals monitoring

## Testing (repodan)

- Unit: lib + components
- E2E: Playwright critical paths
- a11y: axe in CI

## Release (repodan)

- Preview deploy → production (Vercel or equivalent)

## Validation (repodan — "future" olarak işaretli)

- `tsc --noEmit`
- ESLint + a11y plugin
- Playwright critical paths
- Lighthouse CI thresholds

## PHP/MySQL Backend (Kamelya standardı — 2026-09-20)

- Kapsam: katmanlı MVC (Controller → Service → Repository), RESTful API kuralları (metotlar, durum kodları, JSON sözleşmesi, `/api/v1` versiyonlama), MySQL 8+ şema/migration/seeding, güvenlik (SQLi/XSS/CSRF/JWT), PSR-12 + SOLID.
- Detay: `standards/web/PHP_MVC_API.md` (opencode.json `references.standards` ile bağlı).
- Kamelya PHP/MySQL işlerinde F2 scaffold kurulumu ve F3 core modüller bu standarda uyar; faz kapanışında `/denetle` (kod + SEO denetimi) zorunlu.

## Standart boşluğu (kapatıldı — 2026-09-20)

- **PHP/MySQL/Laravel:** APP-FABRIKA repoda PHP/MySQL/Laravel scaffold ve kuralları **bulunmuyordu**; web/PHP/MySQL "Leo Compatibility Layer" adıyla core'un dışında tutulmuştu. Geliştirici talebiyle bu boşluk kapatıldı [EK-20260920]: PHP/MySQL MVC API standardı `standards/web/PHP_MVC_API.md` olarak oluşturuldu ve bu skill'e entegre edildi. Kaynağı APP-FABRIKA değil, geliştirici talebidir; `/standart-guncelle` akışında bu dosya korunur.
