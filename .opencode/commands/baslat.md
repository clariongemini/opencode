---
description: Promptu hiyerarşik YAPILACAKLAR faz planına çevir, F0'dan devam et
---

# /baslat — Projeyi Hiyerarşik Faz Planıyla Başlat

`YAPILACAKLAR.md` yoksa önce `/yeni-proje` akışını uygula. Kural: `AGENTS.md` (Temel İlkeler).

Geliştiricinin verdiği promptu **kod yazmadan önce** işle. Halüsinasyon sıfır; uydurma yasak.

## Girdi

Kullanıcının mesajındaki tüm metin = **kaynak prompt**. `$ARGUMENTS` varsa onu da ekle.

## Zorunlu sıra

1. **Skill:** `.opencode/skills/zero-hallucination/SKILL.md` uygula.
2. **Oku:** `AGENTS.md` (F0–F8 faz haritası + Temel İlkeler), `YAPILACAKLAR.md` (varsa)
3. **YAPILACAKLAR oluştur/güncelle:**
   - Dosya yoksa/boşsa/uninitialized ise: yapilacaklar-planner skill'inin F0–F8 şablonuyla oluştur
   - Skill: `.opencode/skills/yapilacaklar-planner/SKILL.md`
4. Prompta göre **F1** ve **F7** tablolarına özel maddeler ekle (Ajan · L1 · Kabul · `bekliyor`).
5. **F0** fazını `işleniyor` bırak; F0.1'den başla — **henüz feature kodu yazma**.
6. Tüm maddelerde kanıt zorunlu: dosya içeriği, grep, glob, terminal çıktısı.

## F0 ilk adımlar (sırayla)

1. MCP kontrolü: `opencode.json` içindeki MCP listesi (`playwright` aktif, `github` kapalıysa aktivasyon talimatı)
2. `AGENTS.md` Standart Boşlukları bölümünü oku — PHP/MySQL/teknik SEO gibi repodan gelmeyen standartlar varsa F2 öncesi geliştiriciye bildir
3. F0 maddelerini tamamladıkça `tamamlandı` işaretle

## Çıktı formatı

```markdown
## YAPILACAKLAR durumu
- Aktif faz: F0
- Prompt kaydedildi: evet/hayır
- Eklenen özel maddeler: (liste)
- Sıradaki madde: F0.x — ...
- Blokör: (varsa)
```

## Yasak

- YAPILACAKLAR olmadan kod üretmek
- F0 bitmeden F1'e geçmek
- Tek ajanın kendi işini nihai onaylaması
