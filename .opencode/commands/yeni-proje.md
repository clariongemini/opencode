---
description: Tam bootstrap — YAPILACAKLAR.md oluştur, F0 fazını başlat (kod yazma yok)
---

# /yeni-proje — Tam Fabrika Bootstrap

Geliştiricinin verdiği promptu **kod yazmadan önce** işle. Halüsinasyon sıfır; uydurma yasak. Kural: `AGENTS.md` (Temel İlkeler + Faz çalıştırma protokolü).

## Girdi

- Kullanıcı proje adı/açıklaması vermişse onu kullan; yoksa sor:
  - Proje/app adı
  - Kısa ürün açıklaması (kaynak prompt)
  - Hedef platform/teknoloji (web, PHP/MySQL vs. — varsayım yapma, sor)

## Zorunlu sıra

1. **Skill:** `.opencode/skills/zero-hallucination/SKILL.md` uygula.
2. **Oku:** `AGENTS.md` (F0–F8 faz haritası), `.opencode/skills/yapilacaklar-planner/SKILL.md`
3. **YAPILACAKLAR oluştur:** `YAPILACAKLAR.md` yoksa veya boşsa, yapilacaklar-planner skill'inin F0–F8 şablonuyla oluştur; kaynak promptu dosyanın üstündeki "Kaynak prompt" satırına yaz.
4. Prompta göre **F1** ve **F7** tablolarına özel maddeler ekle (Ajan · L1 · Kabul · `bekliyor`).
5. **F0** fazını `işleniyor` bırak; F0.1'den başla — **henüz feature kodu yazma**.
6. MCP kontrolü: `opencode.json` içindeki MCP listesini oku. `playwright` aktif mi? `github` kapalıysa kullanıcıya `GITHUB_TOKEN` tanımlamasını hatırlat.

## Çıktı formatı

```markdown
## YAPILACAKLAR oluşturuldu
- Aktif faz: F0
- Prompt kaydedildi: evet/hayır
- Eklenen özel maddeler: (liste)
- Sıradaki madde: F0.x — ...
- Blokör: (varsa)
```

## Yasak

- YAPILACAKLAR olmadan kod üretmek
- F0 bitmeden F1'e geçmek
- Kullanıcıya sormadan platform/dil varsayımı yapmak
