---
description: YAPILACAKLAR.md aktif fazından devam et; L1 doğrulaması olmadan tamamlandı deme
---

# /devam-et — Aktif Fazdan Devam

Skill: `.opencode/skills/yapilacaklar-executor/SKILL.md` · Protokol: `AGENTS.md` (Faz çalıştırma protokolü)

## Başlangıç

1. `YAPILACAKLAR.md` oku — aktif faz (`işleniyor`). Hiçbiri `işleniyor` değilse sonraki fazın ilk satırını `işleniyor` yap (önceki faz tamamen `tamamlandı` ise).
2. `zero-hallucination` skill protokolünü uygula.
3. Faz tablosunda ilk `bekliyor` satırı seç.

## Döngü (her görev)

```
seç → işleniyor (satır) → ajan/skill uygula → L1 doğrula → tamamlandı → sonraki satır
```

1. Satır durumunu tabloda `işleniyor` yap.
2. **Ajan** sütunundaki ajanı/skill'i uygula (`.opencode/skills/` veya `opencode.json` ajanları).
3. **L1** sütunundaki üst departman perspektifiyle kontrol et; gerekirse `@phase-auditor` veya ilgili subagent ile doğrula.
4. Kabul kriteri sağlanınca satırı `tamamlandı` yap.
5. Fazdaki tüm satırlar `tamamlandı` → faz başlığını `tamamlandı`, sonraki fazı `işleniyor`.

## Keşif protokolü (dinamik faz genişletme)

Uygulama sırasında eksik tespit edilirse:

1. **Keşifler & Dinamik Eklemeler** tablosuna satır ekle: `[EK-YYYYMMDD]`.
2. Uygun faz tablosuna yeni madde ekle (aynı faz veya üst faz — asla atlanmış alt faz).
3. Durum: `bekliyor`.
4. Aktif işi kesme; önce keşif maddesini plana yaz, sonra önceliğe göre uygula.

## Bitiş raporu

Her oturum sonunda:

- Tamamlanan maddeler listesi
- Aktif faz + sıradaki madde
- Eklenen `[EK-*]` maddeler
- Blokörler (geliştirici aksiyonu)

## Yasak

- Tek ajanın kendi işini nihai onaylaması (L1 doğrulaması zorunlu)
- Tamamlanmamış üst fazda alt fazı `tamamlandı` saymak
- Kanıt olmadan "tamamlandı" iddiası
