# AI-Çağı SEO Standartları — Kamelya

**Kaynak:** Geliştirici talebiyle oluşturuldu [EK-20260923]. Kapsam: klasik teknik SEO'nun ötesinde, arama motorlarının AI özetleri (Google AI Overviews, ChatGPT, Perplexity, Copilot) tarafından alıntılanma ve sıralanma kuralları. **Bu standartlar hem web hem AI için geçerlidir.**

**İlişki:** `seo-specialist` = strateji/keşif kanalı · `seo-analyzer` = teknik denetim kanalı · **bu dosya = AI-çağı içerik ve biçim standardı**. Üçü birbirinin yerine geçmez; `/denetle` faz kapanışında `seo-analyzer` ile birlikte okunur.

**Geçerlilik:** Tüm içerik üretiminde (kategori, ürün, blog, rehber, şehir landing, SSS) · 6 dil (TR/EN/DE/FR/IT/AR) · F16.2 içerik doldurma ve sonrası tüm fazlar.

---

## 1. Zero-Click Stratejisi

Arama sonucunun kendisi cevabı vermelidir; tıklama olmadan değer üretilir, site ikinci adımda derinlik sunar.

- **İlk 40–60 kelime tek başına cevaptır:** Sayfa girişinde soruya doğrudan, isim-fırat olmadan cevap ver. AI tarayıcılar sayfanın başını özetler; giriş paragrafı olmayan içerik alıntılmaz.
- **Özet katmanı + derinlik katmanı:** Her içerik sayfası iki katmandır — (a) üstte özet (3–5 cümle, doğrudan cevap), (b) altta detay (tablo, süreç, karşılaştırma). AI üst katmanı alıntılar, kullanıcı alt katmana geçer.
- **Bilgi sorgusu tipine uyum:** "X nedir" → tanımlayıcı giriş; "X fiyatları" → sayısal tablo ilk ekranda; "X mi Y mi" → karşılaştırma tablosu ilk ekran; "X nasıl yapılır" → numaralı adımlar.
- **Kapalı uçlu soruları sayfa içinde cevapla:** SSS bölümündeki her soru, arama sorgusuyla aynı dille yazılır (kullanıcı dili, marka dili değil).
- **Zero-click başarısı ölçümü:** GSC'de gösterim artar, tıklama oranı düşerse içerik özet için doğru, dönüşüm için zayıftır → alt katmana CTA eklenir (ölçüm maddesi: Bölüm 6).

## 2. Yapılandırılmış Veri Genişletmeleri

JSON-LD temel seti (Organization, WebSite, Product, FAQPage, LocalBusiness — mevcut `seo-kontrol-listesi.md`) AI-çağında şu alanlarla genişletilir:

| Alan | Neden | Uygulama |
|------|-------|----------|
| `sameAs` | Marka varlığı AI'a kanıtlanır | Organization JSON-LD'ye sosyal profil URL'leri (Instagram, LinkedIn, YouTube vb.) |
| `dateModified` | Eski içerik AI tarafından elenir | İçerik güncellenince JSON-LD + sitemap'e yazılır; güncelleme tarihi görünür olmalı |
| `Person` | Yazar/uzman kimliği E-E-A-T'yi besler | Blog/rehber yazarı için Person JSON-LD (ad, unvan, `sameAs`) |
| `datePublished` | İçerik yaşı sinyali | Blog/rehber yazım tarihi |
| TL;DR bloğu | AI alıntısına hazır özet | İçerik başına standart özet bloğu (Bölüm 5) |
| `aggregateRating` | Gerçek yorum varsa | Yorumlar moderasyondan geçtikten sonra Product'e; **sahte puan yasak** |

- `speakable` alanı yalnızca sesli asistan hedefleniyorsa eklenir; gereksiz alan şişirilmez.
- JSON-LD her dil sürümünde kendi dilinde yazılır (hreflang ile eşleşir).

## 3. E-E-A-T Sinyalleri

Deneyim, uzmanlık, yetkililik, güvenilirlik — AI alıntı kararının birincil filtresi.

- **Deneyim (Experience):** İçerikte saha bilgisi görünür olmalı — montaj notu, bakım gözlemi, malzeme dayanım detayı. Genel bilgi tekrarı değil, "üretimden/keşiften gelen" detay alıntılanır.
- **Uzmanlık (Expertise):** Yazar adı + unvan (ör. "Kamelya Üretim Ekibi", "Ahşap Ustası") içerik altında görünür; Person JSON-LD ile desteklenir.
- **Yetkililik (Authoritativeness):** Sertifikalar (CE/TÜV/ISO), referans projeler, medya görünürlüğü içerikten linklenir; iddialar kaynağa bağlanır.
- **Güvenilirlik (Trustworthiness):**
  - Fiyat iddiaları güncel tarihle ve koşulla yazılır (tarihli fiyat, kaynaklı aralık).
  - "En iyi/lider/ Türkiye'nin number bir" gibi **doğrulanamaz süperlatifler yasak**; yerine ölçülebilir ifade (kayıtlı üretici sayısı, garanti süresi, sertifika numarası).
  - Bilinmeyen bilgi yazılmaz (halüsinasyon sıfır); veri yoksa "keşif talebi" veya "araştırma notu" olarak işaretlenir.
- 6 dilde de aynı güven kültürü geçerlidir; çeviride iddia güçlendirilmez (TR "iddia" → DE/FR'de abartılı iddia dönüşü yasak).

## 4. Information Gain

Aynı bilgiyi tekrarlayan içerik AI tarafından değerlenmez; **mevcut arama sonuçlarına yeni bilgi** ekleyen içerik yükselir.

- Her içerik sayfası en az bir **özgün veri katmanı** taşımalıdır: kendi fiyat aralığı tablosu, kendi karşılaştırma ölçütleri, kendi bakım takvimi, kendi kullanım senaryosu matrisi.
- Rakip sayfalarda zaten var olan genel tanım tek başına yayın konusu değildir → tanım + özgün katman birlikte yazılır.
- Bilgi boşluğu kuralı: pazar verisi olmayan iddia üretilmez (F1.1 kuralı); veri toplama adımı `[EK]` ile işaretlenir.
- İçerik tekrarı denetimi: aynı anahtar kelimeye yazılan ikinci sayfa, ilkinden ölçülebilir derecede farklı olmalıdır (farklı JTBD, farklı şehir, farklı dil hedefi) — değilse birleştirilir.
- Öncelik sırası: (1) boşluğu olan sorgu, (2) sayısal/tablosal içerik, (3) mevcut sayfanın bilgi katmanı eksiği.

## 5. AI Alıntı Formatı

AI motorlarının doğrudan alıntıdığı biçim kalıpları — her içerik sayfasında uygulanır:

1. **TL;DR bloğu:** Sayfanın en üstünde, başlığın hemen altında 2–4 cümlelik özet. Doğrudan cevap, sıfat şişirmesi yok. (Hem insan hem AI için ilk ekran.)
2. **Soru-formatlı H2:** Bölüm başlıkları gerçek arama sorgusu diliyle yazılır ("Altıgen kamelya ne kadar yer kaplar?" — başlık ≠ etiket jargonu).
3. **Sayısal veriler:** Fiyat, ölçü, süre, garanti süresi sayı olarak tablo/madde içinde geçer (metin içinde kaybolmuş sayılar alıntılmaz).
4. **Karşılaştırma tabloları:** "X mi Y mi" niyetli içeriklerde tablo zorunlu (malzeme, model, kullanım amacı karşılaştırmaları — Kamelya çarpan matrisi doğal girdidir).
5. **Alternatif senaryolar:** Farklı ihtiyaçlar için seçenek sunulur (bütçe/ölçü/kullanım amacına göre) — tek doğru dayatması AI özetlerinde kaybedilir.
6. **Numaralı süreçler:** Bakım, keşif, sipariş akışı adım adım yazılır (adım = alıntı birimi).
7. **Madde listeleri:** Her madde tek başına anlamlı olacak şekilde yazılır (alıntı kırpmasına dayanıklı).

## 6. AI Performans Takibi

- **GSC AI Report:** Search Console'da AI Overviews kaynaklı gösterim/tıklama ayrımı izlenir; AI görünürlüğü ayrı metrik olarak raporlanır (F16.6 döngüsüne işlenir).
- **ChatGPT / Perplexity sorguları:** Hedef anahtar kelimeler (F1.1 seti + kategori anahtar kelimeleri) bu araçlarda düzenli olarak denenir; marka/kategori yanıtlarında görünür olup olmadığı kaydedilir.
- **Takip döngüsü:** T+14 / T+30 (F16.6) — seo-analyzer taraması + GSC raporu + AI sorgu testi tek raporda toplanır.
- **Başarı ölçütü:** (a) AI özetlerinde site adı/kategori anılması, (b) "site:" ve marka sorgularında tutarlı bilgi, (c) gösterim ↑ + tıklama oranı düşüşünün dönüşümle dengelenmesi.
- Ölçüm altyapısı: `seo_analitik_verileri` tablosu + `scripts/gsc-senkronize.php` (mevcut); AI sorgu testleri elle yapıldıkça rapora eklenir.

## 7. Karakter Bantları (Kesin)

Bu bantlar **byte** ölçülür (MySQL `LENGTH()`), karakter değil. TR/DE/FR/IT özel harfleri 2 byte, AR 3 byte/klasik ASCII 1 byte sayılır.

| Alan | Bant | Üst sınır uygulaması |
|------|------|----------------------|
| `<title>` / `meta_baslik` | **50–60 byte** | Kırp + uygun sonraki kelime; marka suffix varsa (`| Kamelya`) suffix dahil bant içinde kalır |
| `<meta description>` / `meta_aciklama` | **150–160 byte** | 160'ta kelime sınırında kes; 150 altındaysa doğal ifadeyle tamamla |
| Slug | ≤ 220 karakter | Türkçe karakterler ASCII'ye maplenir |

- Bant ihlali `seo-analyzer` denetiminde bulgudur; F16.2.1-ek düzeltmesi (72/72) bu standardın uygulamasıdır.
- 6 dilde de bant geçerlidir; dil bazlı byte farkı dikkate alınır (AR desc'ler byte olarak kırpılırken çok-karakterli Arapça dizileri bölünmez — multibyte kesim zorunlu).

---

**Denetim zinciri:** Bu standart `seo-analyzer` denetim girdisidir; faz kapanışı yine L1 (phase-auditor) + L2 (CAO) `/denetle` PASS'ine tabidir. Değişiklik talebi geliştirici onayıyla yapılır.
