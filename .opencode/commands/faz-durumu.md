---
description: YAPILACAKLAR.md faz özeti — aktif faz, sıradaki madde, blokörler
---

# /faz-durumu — Faz Özeti

Salt okuma komutu. Dosya değiştirme yok; reasoning bloklarından muaftır.

## Sıra

1. `YAPILACAKLAR.md` oku.
2. Aşağıdaki özeti üret:

```markdown
## Faz Durumu
- Aktif faz: Fx — <bina metaforu> (işleniyor)
- Aktif madde: Fx.y — <madde> (Ajan · L1 · Kabul)
- Sıradaki madde: Fx.z — ...
- Tamamlanan fazlar: (liste)
- Keşif maddeleri [EK-*]: (varsa liste)
- Blokörler: (varsa)
```

3. YAPILACAKLAR.md yoksa veya uninitialized ise: `/yeni-proje` veya `/baslat` öner — faz durumu uydurma.
