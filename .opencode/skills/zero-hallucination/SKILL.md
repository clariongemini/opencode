---
name: zero-hallucination
description: >-
  Halüsinasyon sıfır protokolü — her dosya/API/script/kural referansından önce
  okuma/grep/glob kanıtı zorunlu. YAPILACAKLAR ve hiyerarşik denetim kapısı.
  Yeni oturum, kod yazımı, dosya referansı veya "tamamlandı/hazır/çalışıyor"
  iddiası öncesi otomatik uygula. Kullanım: /baslat, /yeni-proje, kod uygulama,
  governance ve .opencode yapılandırması.
---

# Halüsinasyon Sıfır Protokolü

Kaynak: APP-FABRIKA `.cursor/skills/zero-hallucination/SKILL.md` (opencode'a uyarlandı)

## Tetikleyiciler

- Yeni proje veya yeni oturum
- Dosya/sınıf/script adı söylenmeden önce
- "Tamamlandı", "hazır", "çalışıyor" iddiası
- Governance veya `.opencode` yapılandırması

## Zorunlu adımlar

1. **Okumadan yazma:** Referans verilen her path için Read, Glob veya Grep kanıtı.
2. **YAPILACAKLAR kapısı:** `YAPILACAKLAR.md` yoksa `/yeni-proje` veya `/baslat`.
3. **Tek aktif faz:** Birden fazla faz `işleniyor` olamaz.
4. **Komut kanıtı:** Script iddiası → terminal çıktısı veya dosya satır referansı.
5. **Uydurma yasak:** Package adı, modül listesi, JSON alanları, standart referansları — repodan/çalışma dizininden oku.

## Doğrulama checklist

- [ ] Path gerçekten var mı?
- [ ] İçerik iddia ile uyumlu mu?
- [ ] Aktif faz `YAPILACAKLAR.md` ile uyumlu mu?
- [ ] L1 denetim yapıldı mı (tek ajan onayı yok)?

## Hata durumunda

Dur, uydurmayı bırak, eksik dosyayı oluştur veya `@architect`'a / geliştiriciye sor.
