---
description: APP-FABRIKA reposundan standart güncelleme — diff analizi + opencode'a yeniden haritalandırma
---

# /standart-guncelle — Standart Kaynağını Güncelle

Kaynak repo: `https://github.com/clariongemini/APP-FABRIKA` · Bu komut Cursor'a özgü yapıları (`.mdc`, `.cursorrules`, `.cursor/`) doğrudan kullanmaz; opencode formatına dönüştürür.

## Zorunlu sıra

1. **Repo klonu:** Geçici dizine klonla:

```bash
git clone --depth 1 https://github.com/clariongemini/APP-FABRIKA /tmp/APP-FABRIKA-sync
```

2. **Diff analizi:** Kamelya'daki mevcut opencode bileşenleriyle karşılaştır:
   - `AGENTS.md` + `.cursorrules` (anayasa değişiklikleri)
   - `.cursor/rules/*.mdc` (ajan kuralları — numaralı kural seti)
   - `.cursor/commands/*.md` + `.cursor/skills/*/SKILL.md` (akış değişiklikleri)
   - `governance/` (hiyerarşi, denetim zinciri, charter değişiklikleri)
   - `scripts/` (init/sync/denetim akışları — opencode'a taşınabilir kısımlar)
   - `docs/03-STANDARDS/` (standart belge güncellemeleri)

3. **Haritalandırma önerisi:** Her değişiklik için opencode karşılığını öner:

| Cursor yapısı | opencode karşılığı |
|---------------|-------------------|
| `.cursorrules` + `AGENTS.md` | `AGENTS.md` (proje kök kuralları, `instructions` ile yüklenir) |
| `.cursor/rules/*.mdc` | `opencode.json` `agent` tanımları veya skill/AGENTS.md bölümleri |
| `.cursor/commands/*.md` | `.opencode/commands/*.md` |
| `.cursor/skills/*/SKILL.md` | `.opencode/skills/<ad>/SKILL.md` |
| `.cursor/agents/*.md` (subagent) | `opencode.json` `agent` (mode: subagent) |
| `scripts/*.sh` denetim kapıları | Kanıt tabanlı manuel denetim veya opencode.json MCP/plugin |

4. **Rapor:** Değişiklik listesi + opencode haritası + riskler sun → **geliştirici onayı olmadan uygulama**.

5. Onaydan sonra değişiklikleri uygula ve `AGENTS.md` "Standart Kaynakları ve Boşluklar" bölümünü güncelle.

## Yasak

- Onaysız bileşen güncellemesi
- Cursor'a özgü dosyaları (.cursor/, .mdc, .cursorrules) kamelya dizinine kopyalamak
- Repoda var olmayan standart/script/kural uydurmak
