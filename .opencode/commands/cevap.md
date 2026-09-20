---
description: After Action Review — DIAGNOSTIC özet; dosya değiştirmez, faz ilerletmez
---

# /cevap — After Action Review (AAR)

Salt okuma DIAGNOSTIC komutu. Kaynak sözleşme: APP-FABRIKA `docs/CEVAP_REPORT_CONTRACT.md` · Skill: `hierarchical-audit`

## Katı kurallar

- `/cevap` **dosya değiştirmez**, faz ilerletmez, düzeltme çalıştırmaz, proje scaffold etmez, commit yapmaz.
- Önceki sohbet bağlamını aktif proje niyeti olarak **sayma**.
- İddialar kanıt tabanlı: terminal çıktısı veya dosya satır referansı olmadan "tamamlandı/hazır/çalışıyor" deneme.

## Rapor yapısı

```markdown
## After Action Review

### Ne yapıldı
- (oturumda tamamlanan işler — kanıt referanslı)

### Doğrulama durumu
- L1 denetim: yapıldı/yapılmadı
- Kanıt durumu: (dosya/çıktı referansları)

### Riskler ve bulgular
- (tespit edilen riskler)

### Önerilen sonraki adım
- (YAPILACAKLAR'daki sıradaki madde veya gerekirse yeni faz önerisi — dosyaya yazılmaz, yalnızca önerilir)
```
