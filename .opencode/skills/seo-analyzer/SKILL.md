---
name: seo-analyzer
description: >-
  SEO Analyzer — backend'den gelen HTML çıktısını denetler: meta etiketler
  (Title, Description, canonical, Open Graph), H1-H6 hiyerarşisi, anahtar
  kelime yoğunluğu, iç bağlantı (internal linking) yapısı, Core Web Vitals
  ve mobil uyumluluk önerileri, sektör rekabet analizi. Kullanım: /denetle
  faz kapanış denetimlerinde (kod + SEO denetimi zorunlu), HTML/sayfa
  analizinde, "seo analizi", "meta", "anahtar kelime", "web vitals",
  "mobil uyumluluk", "hız" geçtiğinde.
---

# SEO Analyzer Skill

Kaynak: Geliştirici talebiyle oluşturuldu [EK-20260920] — Kamelya'nın "kendi içinde SEO analiz yapısı" hedefi için. `seo-specialist` skill'iyle ilişki: **seo-specialist = strateji/keşif kanalı** (meta baseline, long-tail, JTBD), **seo-analyzer = teknik denetim kanalı** (bu skill). İkisi birbirinin yerine geçmez.

## Denetim kapsamı (7 alan)

### 1. HTML çıktı analizi

- Backend'den gelen **render edilmiş HTML** denetlenir (API'lerde JSON yanıtının içindeki HTML parçaları da kapsanır).
- HTML kaynağı: Playwright MCP (aktif) ile canlı sayfadan alınır; **MCP tarayıcı çıktısı dosyaya yazılır, sohbete güvenilmez** (`AGENTS.md` kuralı).
- Analiz statik kaynakta + gerçek render sonucunda yapılır (JS ile enjekte edilen içerik ayrıca doğrulanır).

### 2. Meta etiketler

| Etiket | Zorunlu kural |
|--------|--------------|
| `<title>` | 50–60 karakter bandı, sayfa başına benzersiz, anahtar kelime başa yakın |
| `<meta name="description">` | 150–160 karakter bandı, benzersiz, kullanıcı dili |
| `<link rel="canonical">` | Her sayfada; çift içerik (duplicate) riskini kapatır |
| `<meta name="robots">` | İndeksleme niyeti açık (index/follow veya sayfa bazlı karar) |
| Open Graph / Twitter Card | Paylaşım görünümü tanımlı |
| `<meta name="viewport">` | Responsive için zorunlu |

### 3. H1–H6 hiyerarşisi

- Sayfada **tek H1**; seviye atlaması yasak (H1 → H3 atlaması bulgudur).
- Başlık metinleri anahtar kelime/arama niyetiyle uyumlu; başlıklar semantik (`<h1>`–`<h6>` etiketleri, stil için değil).
- Başlık yapısı sayfa iskeletiyle (F3 core modüller) tutarlı.

### 4. Anahtar kelime yoğunluğu

- Hedef bant: **%1–2** (sayfa metninde hedef anahtar kelime payı); üzerindeki yoğunluk **keyword stuffing** bulgusudur.
- Long-tail dağılımı `seo-specialist` stratejisiyle uyumlu (kullanıcı dili, arama niyeti).
- Hedef anahtar kelimeler F1'de CPO pazar analiziyle netleşir; **veri yoksa varsayım yapılmaz** (halüsinasyon sıfır).

### 5. İç bağlantı (internal linking)

- Kırık link yok (4xx/5xx tespiti bulgu); anchor text açıklayıcı ("buraya tıkla" yasak).
- Derinlik: her önemli sayfa ana sayfadan **3 tık içinde** erişilebilir.
- Orphan sayfa yok (hiçbir sayfadan linklenmeyen sayfa bulgudur); sitemap.xml ile iç link yapısı uyumlu.

### 6. Sayfa hızı (Core Web Vitals)

Google'ın resmi eşikleri (güvenilir genel standart):

| Metrik | İyi eşiği | Öneri alanları |
|--------|-----------|----------------|
| LCP | ≤ 2.5s | Görsel optimizasyonu, kritik kaynak önceliği, CDN |
| INP | ≤ 200ms | JS boyutu, uzun görev bölme |
| CLS | ≤ 0.1 | Görsel boyut rezervi, font yüklemesi |

- Ölçüm: Playwright MCP performans verisi veya Lighthouse; çıktı dosyaya yazılır.
- Backend önerileri: HTTP cache başlıkları, gzip/brotli, sorgu optimizasyonu (`standards/web/PHP_MVC_API.md` — EXPLAIN zorunluluğu).

### 7. Mobil uyumluluk

- Viewport meta zorunlu; yatay kaydırma yok; içerik viewport genişliğine sığar.
- Dokunma hedefleri ≥ 48×48px önerisi (a11y uyumu — `ui-ux-designer` skill'iyle çapraz kontrol).
- Responsive kırılım noktaları F4 tasarım sistemi token'larıyla uyumlu.

## Kamelya sektörü rekabet analizi

- Sektöre özel anahtar kelimeler **F1'de CPO pazar analiziyle** netleşir; seo-analyzer bu girdiyle rekabet analizi çalıştırır.
- Araçlar: **Playwright MCP (aktif)** ile rakip sayfa meta/içerik analizi; harici API ihtiyacı için (Search Console API, SerpAPI vb.) `opencode.json` MCP tanımına öneri — **kimlik bilgisi gerektiren entegrasyon geliştirici onayı olmadan açılmaz**.
- Pazar verisi yoksa varsayım yapılmaz; veri toplama adımı önerilir.

## Denetim protokolü (hierarşiye entegre)

1. Denetim **kanıt zorunlu**: Playwright/Lighthouse çıktısı dosyaya yazılır; bulgular satır/dosya referansıyla raporlanır.
2. Rapor formatı: puan kartı (7 alan × PASS/FAIL/SKIP + bulgu + öneri).
3. `/denetle` komutunun zorunlu sırasında SEO denetim adımı bu skill'i çalıştırır (bkz. `.opencode/commands/denetle.md`).
4. **Onay yetkisi bu skill'de değildir:** seo-analyzer raporu L1 girdisidir; faz `tamamlandı` kararı L1+L2 (CAO) katmanından geçer (bkz. `standards/PHASE_MAP.md` — Faz kapanış kapısı).
5. Faz HTML/API çıktısı üretmiyorsa rapor `SKIP` kaydı taşır — atlanan değil, kayıtlı.

## Standart boşlukları (varsayım yapılmaz)

- **Harici SEO API entegrasyonu:** Search Console/SerpAPI gibi harici servisler opsiyoneldir; tanım `opencode.json` MCP bölümünde yapılır (geliştirici onayı ile). Şu an tanımlı değildir.
- **Teknik SEO detayları** (sitemap, structured data, canonical, CWV eşikleri) bu skill'de tanımlıdır; kaynağı geliştirici talebidir, APP-FABRIKA repoda yoktu.
