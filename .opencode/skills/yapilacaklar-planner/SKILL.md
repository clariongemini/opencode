---
name: yapilacaklar-planner
description: >-
  Promptu bina metaforlu YAPILACAKLAR.md faz planına dönüştürür (F0–F8).
  Kullanım: /baslat, /yeni-proje, boş veya uninitialized YAPILACAKLAR.md durumu,
  yeni proje başlatma. Kaynak: APP-FABRIKA yapilacaklar-planner.
---

# YAPILACAKLAR Planlayıcı

Kaynak: APP-FABRIKA `.cursor/skills/yapilacaklar-planner/SKILL.md` (opencode'a uyarlandı; şablon dependency'si kaldırıldı — şablon aşağıda gömülü)

## Girdi

Geliştiricinin doğal dil promptu (ürün tanımı, hedefler, kısıtlar).

## Çıktı

Güncellenmiş `YAPILACAKLAR.md`:

- Kaynak prompt satırı dolu
- F0–F8 fazları (aşağıdaki şablondan)
- Prompta özel **F7** ve **F1** maddeleri eklenmiş
- Tüm maddeler: Ajan · L1 · Kabul · Durum (`bekliyor` / `işleniyor` / `tamamlandı`)

## Algoritma

1. Aşağıdaki şablonu kullan; prompttan türet:
   - **F1:** pazar/ürün maddeleri (CPO)
   - **F7:** feature WP satırları
   - **F6:** analytics gerekiyorsa ölçüm maddeleri
2. F0'ı `işleniyor`, diğer fazları `bekliyor` bırak.
3. `AGENTS.md` Standart Boşlukları bölümünü kontrol et — repodan gelmeyen standartlar (PHP/MySQL, teknik SEO vb.) F2 öncesi maddesi olarak ekle.
4. Plan sonunda boş **Keşifler & Dinamik Eklemeler** tablosunu koru.

## Şablon (F0–F8)

```markdown
# YAPILACAKLAR — <Proje Adı>

**Kaynak prompt:** <prompt özeti>
**Durum:** initialized · <tarih>

## F0 — Zemin & Temel (bekliyor)

| # | Madde | Ajan | L1 | Kabul | Durum |
|---|-------|------|----|-------|-------|
| F0.1 | MCP hazır (playwright aktif, github aktivasyonu) | overmind | geliştirici | opencode.json MCP listesi | bekliyor |
| F0.2 | Governance kurulumu doğrulandı | overmind | geliştirici | AGENTS.md + opencode.json yüklü | bekliyor |

## F1 — Kolon & Taşıyıcı (bekliyor)

| # | Madde | Ajan | L1 | Kabul | Durum |
|---|-------|------|----|-------|-------|
| F1.x | <prompta özel pazar/ürün maddesi> | cpo | pdc | ... | bekliyor |

## F2 — Kat Döşeme (bekliyor)

| # | Madde | Ajan | L1 | Kabul | Durum |
|---|-------|------|----|-------|-------|
| F2.x | Domain standartları (repodan gelmeyenler dahil) | architect | cao | ... | bekliyor |

## F3 — Duvar & Tesisat (bekliyor)
## F4 — Cephe & UI (bekliyor)
## F5 — Elektrik & Güvenlik (bekliyor)
## F6 — Ölçüm & Analitik (bekliyor)
## F7 — İç Mekan (bekliyor)

| # | Madde | Ajan | L1 | Kabul | Durum |
|---|-------|------|----|-------|-------|
| F7.x | <prompta özel feature satırı> | overmind | architect | ... | bekliyor |

## F8 — Anahtar Teslim (bekliyor)

## Keşifler & Dinamik Eklemeler

| Tarih | Keşif | Faz | Durum |
|-------|-------|-----|-------|
```

## Metafor (özet)

| Faz | Bina |
|-----|------|
| F0 | Temel |
| F1–F2 | Taşıyıcı |
| F3–F4 | Kabuk |
| F5–F6 | Tesisat & sayaç |
| F7 | İç mekan |
| F8 | Anahtar teslim |
