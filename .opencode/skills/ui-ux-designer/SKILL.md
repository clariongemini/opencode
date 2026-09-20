---
name: ui-ux-designer
description: >-
  Kurumsal tasarım ilkeleri ve anti-AI desenleri — tipografi, spacing,
  interaction, motion, a11y (WCAG 2.1 AA), Liquid Glass referansı. Kullanım:
  UI/UX tasarım işlerinde, F4 (Cephe & UI) fazında, "tasarım", "ui", "ux",
  "komponent", "tema", "a11y" geçtiğinde.
---

# UI/UX Tasarımcı Skill

Kaynak: APP-FABRIKA `APP-FABRIKASI/04-design/DESIGN_PRINCIPLES.md` + `ANTI_PATTERNS.md` + `docs/03-STANDARDS/LIQUID_GLASS.md` ilkeleri (opencode'a uyarlandı)

## İlke

SVOS ventures (Web, iOS, Android) **intentional** hissettirmeli — AI-generated değil. Referans kalite barı: Linear, Notion, Stripe, Apple HIG — **yalnızca ilkeler**, komponent kütüphanesi değil.

## Tipografi

| Kural | Web | iOS |
|-------|-----|-----|
| Ölçek | 12/14/16/18/24/32 — max 6 boyut | Dynamic Type — scaling'i asla engelleme |
| Aile | Bir sans + bir mono | SF Pro / system |
| Ağırlık | 400 body, 600 headings only | Regular/Medium/Semibold |
| Satır yüksekliği | 1.5 body, 1.2 headings | Apple text styles |
| **Reddet** | Inter + purple gradient hero | Her yerde rounded bubble UI |

Tek tipografik ses per venture. 3+ aile karıştırma.

## Spacing

- Temel birim: **4px** (iOS'ta 8pt)
- Layout ritmi: 8, 16, 24, 32, 48 — keyfi 13px boşluk yok
- Web content max-width: 640–1120px (bağlama göre)
- Touch targets: **44×44pt** minimum (iOS), 48dp (Android)
- Whitespace yapıdır — dense ≠ professional

## Interaction

- Ekran başına bir primary action
- Destructive actions: confirm + mümkünse reversible
- Loading: skeleton > spinner > blank
- Errors: ne oldu + ne yapılmalı — kullanıcıya raw kod yok
- Forms: inline validation, hata durumunda girdiyi koru
- Navigation: öngörülebilir geri — mystery gesture tek çıkış değil

Stripe kuralı: **her click yerini hak eder.**

## Motion

| Kullan | Kaçın |
|--------|-------|
| State change (open/close) | Dekoratif döngüler |
| 150–250ms varsayılan | >400ms blocking |
| Ease-out giriş, ease-in çıkış | Her elementte bounce |
| Reduced motion respect | Varsayılan parallax |

Motion **state** iletir, dekorasyon değil.

## Accessibility

- WCAG 2.1 AA minimum (web)
- VoiceOver / TalkBack etiketleri tüm kontrollerde
- Renk asla tek sinyal değil — icon + text
- Focus sırası mantıklı (web klavye)
- Kontrast 4.5:1 body text

## Anti-AI desenleri (zorunlu red)

| Desen | Neden |
|-------|-------|
| Purple/blue gradient hero | Anında "AI slop" |
| Glassmorphism stack'leri | Okunabilirlik kaybı |
| Generic illustration pack | Marka hafızası yok |
| Her şeyi ortalama | Hiyerarşi yok |
| Emoji ikon olarak | Profesyonel değil |
| "Dashboard" as home | Metrics ≠ user job |
| 12 eşit widget kart ızgarası | Öncelik yok |
| Ship'te lorem/filler copy | Güven yıkıcı |
| Her yerde rounded-3xl | Template görüntüsü |

## Web platform notları

- Semantic HTML first
- CSS variables token'lardan (tokens.yaml)
- Üretilen kodda inline style soup yok

## Review checklist

- [ ] ≤6 type size kullanıldı
- [ ] Spacing 4px ızgarasında
- [ ] Tek net primary CTA
- [ ] Anti-AI deseni yok
- [ ] Hedef platformda a11y pass
- [ ] Motion reduced-motion'a saygılı

**Not:** Bu checklist release gate öncesi zorunlu denetim listesidir; `@architect` ve `@security` denetim zincirine tabidir.
