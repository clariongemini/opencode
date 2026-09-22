# Kamelya Tasarım Sistemi Standardı

**Kaynak:** Geliştirici talebiyle oluşturuldu [EK-20260921] (F4 — Cephe & UI). F4 sayfaları ve `frontend/assets/css/tasarim-sistemi.css` bu dosyaya tabidir. `ui-ux-designer` skill ilkeleri (kurumsal tipografi, anti-AI desenler, WCAG 2.1 AA) ile uyumludur.

## 1. Renk paleti (doğa/ahşap teması)

| Token | Değer | Kullanım |
|---|---|---|
| `--renk-zemin` | `#FAF6EF` (krem) | Sayfa zemini |
| `--renk-yuzey` | `#FFFFFF` | Kart, modal yüzeyi |
| `--renk-metin` | `#1F2937` | Gövde metni (krem üstünde kontrast ~14:1, AAA) |
| `--renk-metin-soluk` | `#4B5563` | İkincil metin (beyaz üstünde ~7:1, AAA) |
| `--renk-ahsap` | `#8B5E34` | Vurgu, başlık çizgileri |
| `--renk-ahsap-koyu` | `#5C3D21` | Footer, hero overlay |
| `--renk-yesil` | `#2F6B3C` | Birincil buton (beyaz metinle ~5.9:1, AA) |
| `--renk-yesil-koyu` | `#234E2C` | Buton hover |
| `--renk-toprak` | `#C08552` | İkincil vurgu, rozet |
| `--renk-cizgi` | `#E5DCCB` | Kart kenarlığı, ayraç |
| `--renk-hata` | `#B91C1C` | Form/uyarı (beyazla ~5.9:1) |
| `--renk-basarili` | `#2F6B3C` | Başarı bildirimi |

**Dark mode hazırlığı:** Tüm renkler CSS değişkenidir; `prefers-color-scheme: dark` bloğu koyu karşılıkları tanımlar (F4'te temel set aktif, tam tema F7 ince işçilikte).

## 2. Tipografi

- Başlık: `Playfair Display`, serif yedeği (`Georgia, serif`) — Google Fonts.
- Gövde: `Inter`, sistem yedeği (`system-ui, sans-serif`) — Google Fonts.
- Ölçek (mobile-first, `clamp`): H1 2–3rem · H2 1.5–2rem · H3 1.25rem · gövde 1rem/1.6 satır.
- Tek H1 kuralı (seo-analyzer §3); başlıklar semantik sırayla.

## 3. Spacing / grid

- 8px taban (`--bosluk-1: 8px` … `--bosluk-8: 64px`); içerik genişliği `--genislik-icerik: 1140px`.
- Breakpoint'ler: `640px` (tablet), `1024px` (masaüstü); mobile-first akış.
- Yön bağımsızlığı: fiziksel `margin-left/right` yasak; `margin-inline-start/end`, `padding-inline`, `inset-inline` kullanılır (AR/RTL).

## 4. Bileşenler (CSS sınıfları İngilizce — DB kuralı dışı)

`btn`, `btn-birincil`, `btn-ikincil`, `card`, `card-gorsel`, `form-alan`, `input`, `etiket`, `modal`, `uyari` (`uyari-hata`, `uyari-basarili`), `kirinti` (breadcrumb), `sayfalama`, `rozet` (fiyat etiketi), `akordeon` (SSS), `dil-secici`, `whatsapp-sabit`.

## 5. Erişilebilirlik (WCAG 2.1 AA)

- Metin kontrastı ≥ 4.5:1 (tablodaki oranlarla doğrulanır); odak halkası `:focus-visible` ile görünür (2px `--renk-yesil` dış çizgi).
- Form etiketleri `label` ile eşleşir; hata mesajları `aria-describedby` ile bağlanır.
- Dokunma hedefi ≥ 44px; `alt` metinsiz görsel yasak; `lang`/`dir` her sayfada doğru.
- Hareket azaltma: `prefers-reduced-motion` bloğu animasyonları kapatır.
