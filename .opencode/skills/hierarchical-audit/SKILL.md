---
name: hierarchical-audit
description: >-
  Hiyerarşik çok katmanlı denetim — tek ajan onayı yasak. Faz kapanışı, /denetle,
  release gate ve CAO/CEO denetimi öncesi kullan. Kaynak: APP-FABRIKA
  governance/executive/HIERARCHICAL_AUDIT_CHAIN.md.
---

# Hiyerarşik Denetim Skill

Kaynak: APP-FABRIKA `governance/executive/HIERARCHICAL_AUDIT_CHAIN.md` + `AGENT_APPROVAL_PROTOCOL.md` (opencode'a uyarlandı)

## İlke

> **Hiçbir departman kendi işini nihai doğru ilan edemez.**
> **Hiçbir denetçi kendi denetimini nihai doğru ilan edemez.**

## Denetim sırası

1. **Uygulayan ajan** — çıktı üretir (overmind)
2. **L1 üst departman** — domain doğrulama (`@architect`, `@security` veya ilgili subagent)
3. **L2 CAO** — denetim kalitesi + kanıt (`@cao`)
4. **L3 CEO** — sprint önceliği + gerçeklik (`@ceo`)
5. **L4 EGC** — periyodik şirket sağlığı (overmind + geliştirici)
6. **L5** — nihai insan kapısı: geliştirici (Mimar)

## Subagent kapıları

- `@phase-auditor` — faz L1+L2 bulgu raporu (readonly)
- `@hallucination-guard` — kanıt/kayıt doğrulama (readonly)
- `@cao` — denetim kalitesi (readonly)
- `@ceo` — gerçeklik denetimi (readonly)

## Faz kapanış kriteri

Faz `tamamlandı` sayılmaz eğer:

- L1 doğrulaması yok
- CAO kritik bulgu açık (F5, F8)
- Üst faz `bekliyor` iken alt faz tamamlandı işaretlendi

## CAO özel sorusu

Her departman için: *Denetçi gerçekten kontrol etti mi, yoksa onay mı verdi?*

Denetim boyutları: Input Sources · Output Files · Evidence Count · Freshness · Audit Quality · Cross-Department.
