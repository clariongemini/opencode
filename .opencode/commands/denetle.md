---
description: Hiyerarşik denetim — L1+L2 bulgu raporu, tek ajan onayı yasak
---

# /denetle — Hiyerarşik Denetim

Tek ajan onayı yasak. Skill: `.opencode/skills/hierarchical-audit/SKILL.md` · Kaynak: APP-FABRIKA `governance/executive/HIERARCHICAL_AUDIT_CHAIN.md`

## Kapsam

Aktif faz veya son tamamlanan faz — `$ARGUMENTS` ile faz belirtilmemişse aktif faz.

**Kapanış kapısı:** Her faz sonunda hem kod denetimi hem SEO denetimi yapılmadan faz `tamamlandı` işaretlenmez (bkz. `standards/PHASE_MAP.md` — Faz kapanış kapısı).

## Zorunlu sıra

1. **Skill:** `.opencode/skills/hierarchical-audit/SKILL.md` uygula.
2. `YAPILACAKLAR.md` aktif faz satırlarını oku — her satır için Ajan · L1 üst denetim · Kabul kriteri alanlarını kanıtla.
3. **Subagent:** `@phase-auditor` — L1+L2 bulgu raporu (readonly).
4. **Kanıt kontrolü:** `@hallucination-guard` — iddia edilen dosya/kayıtlar gerçekten var mı (readonly).
5. Hafızada APP-FABRIKA doğrulama script'leri kuruluysa (bkz. `/standart-guncelle` sonrası) sırayla çalıştır; kurulu değilse kanıt tabanlı manuel denetim yap:

```bash
# APP-FABRIKA tooling kuruluyssa (opsiyonel):
python3 scripts/governance/validate-yapilacaklar.py
python3 scripts/governance/validate-audit-chain.py
```

6. **SEO denetimi:** `.opencode/skills/seo-analyzer/SKILL.md` uygula — aktif faz HTML/API çıktısı üretiyorsa meta etiketler, H1-H6 hiyerarşisi, anahtar kelime yoğunluğu, iç bağlantı, Core Web Vitals ve mobil uyumluluk denetimi (readonly puan kartı); faz HTML/API çıktısı üretmiyorsa rapor `SKIP` kaydı taşır. Onay yetkisi seo-analyzer'da değildir; faz kapanışı L1+L2 (CAO) kararıdır.

## Rapor formatı

| Katman | Departman | Sonuç | Bulgu |
|--------|-----------|-------|-------|
| L1 | ... | PASS/FAIL | ... |
| L2 CAO | ... | ... | ... |

**CAO özel sorusu:** Denetçi gerçekten kontrol etti mi, yoksa onay mı verdi?
**CEO sorusu:** Hangi departman doğru çalıştı? Hangi denetim zayıf?

Faz `tamamlandı` işareti ancak **L1+L2 PASS ve SEO denetimi PASS** (veya kayıtlı `SKIP`) ise önerilir. Red → düzelt → tekrar denetle.
