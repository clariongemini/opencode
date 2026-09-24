# Tasarım Tokenları — CSS Sistemi

**Dosya:** `frontend/assets/css/tasarim-sistemi.css` (187 satır)
**Toplam token:** ~26 unik CSS custom property
**Desen:** `:root { --key: value; }` → `var(--key)` kullanımı

---

## Token Kategorileri

### Renk Tokenları (12 + 5 dark mode)

| Token | Değer (Light) | Değer (Dark) | Kullanım |
|-------|---------------|--------------|----------|
| `--renk-zemin` | `#FAF6EF` | `#1A1512` | `body`, `.card`, `.form-alan` |
| `--renk-yuzey` | `#FFFFFF` | `#262019` | `.card`, `.input`, `.akordeon` |
| `--renk-metin` | `#1F2937` | `#F3EDE2` | `body`, h1-h3 |
| `--renk-metin-soluk` | `#4B5563` | `#C9BFAE` | `.kirinti`, `.kisa-aciklama` |
| `--renk-ahsap` | `#8B5E34` | — | `.btn-ikincil`, `.ustbilgi a` |
| `--renk-ahsap-koyu` | `#5C3D21` | — | `.ustbilgi`, `.altbilgi` |
| `--renk-yesil` | `#2F6B3C` | — | `a`, `.btn-birincil`, `.whatsapp-sabit` |
| `--renk-yesil-koyu` | `#234E2C` | — | `a:hover`, `.btn-birincil:hover` |
| `--renk-toprak` | `#C08552` | — | `.rozet` |
| `--renk-cizgi` | `#E5DCCB` | `#4A3F30` | `.card`, `.input`, `.akordeon` |
| `--renk-hata` | `#B91C1C` | — | `.uyari-hata` |
| `--renk-basari` | `#2F6B3C` | — | `.uyari-basarili` |

### Typography Tokenları (2)

| Token | Değer | Kullanım |
|-------|-------|----------|
| `--yazitip-baslik` | `"Playfair Display", Georgia, serif` | `h1, h2, h3`, `.logo` |
| `--yazitip-govde` | `"Inter", system-ui, sans-serif` | `body`, `.input` |

### Spacing Tokenları (6)

| Token | Değer | Kullanım |
|-------|-------|----------|
| `--bosluk-1` | `8px` | `.etiket`, `.akordeon`, `.sayfalama` |
| `--bosluk-2` | `16px` | `.kapsayici`, `.card-govde`, `.uyari`, `.form-alan` |
| `--bosluk-3` | `24px` | `.izgara`, `.sayfalama`, `.hesap-araci` |
| `--bosluk-4` | `32px` | `.altbilgi-ici`, `.hesap-araci` |
| `--bosluk-6` | `48px` | `.hero`, `.altbilgi` |
| `--bosluk-8` | `64px` | `.hero`, `.altbilgi` |

### Layout Tokenları (3)

| Token | Değer | Kullanım |
|-------|-------|----------|
| `--genislik-icerik` | `1140px` | `.kapsayici` max-width |
| `--yaricap` | `12px` | `.btn`, `.card`, `.input`, `.hesap-araci` border-radius |
| `--golge` | `0 2px 12px rgba(92,61,33,0.12)` | `.card`, `.btn`, `.whatsapp-sabit`, `.hesap-araci` |

### Dark Mode Ek Tokenları

Dark mode'da `--renk-birincil`, `--renk-birincil-yumusak`, `--renk-birincil-koyu` kullanılır (`hesap-kapasite` sınıfı). Bu tokenlar `:root` dışında tanımlı olabilir (global CSS'de).

---

## Token Kullanım Haritası

### Hangi Sayfa Hangi Token Kullanıyor?

| Token | Kullanılan Sayfalar |
|-------|---------------------|
| `--renk-yesil` | TÜM sayfalar (a link color) |
| `--renk-metin` | TÜM sayfalar (body color) |
| `--renk-zemin` | TÜM sayfalar (body background) |
| `--yazitip-govde` | TÜM sayfalar (body font) |
| `--yazitip-baslik` | TÜM sayfalar (h1-h3) |
| `--renk-yuzey` | `.card`, `.input`, `.form-alan` (urunler, urun-detay, teklif-al) |
| `--bosluk-2` | `.kapsayici`, `.card`, `.uyari`, `.form-alan` (çok sayfa) |
| `--renk-cizgi` | `.card`, `.input`, `.akordeon` (urunler, sss, teklif-al) |
| `--yenik-ahsap` | `.ustbilgi`, `.altbilgi` (31 sayfa) |
| `--bosluk-8` | `.hero`, `.altbilgi` (anasayfa) |
| `--renk-hata` | `.uyari-hata` (iletisim, teklif-al, yorumlar) |
| `--yenik-basari` | `.uyari-basarili` (iletisim, teklif-al) |

### Grep Kanıtı
```bash
grep -rn 'var(--' frontend/sayfalar/ frontend/includes/ | wc -l
# Sonuç: ~150+ kullanım
```

---

## Override Edilen Token'lar

### `hesap-kapasite` sınıfı (hesaplama-araci.js, anasayfa.php)
```css
.hesap-kapasite {
  background: var(--renk-birincil-yumusak);  /* override */
  border: 1px solid var(--renk-birincil);     /* override */
  color: var(--renk-birincil-koyu);           /* override */
}
```
Bu 3 token (`--renk-birincil`, `--renk-birincil-yumusak`, `--renk-birincil-koyu`) `:root` dışında tanımlı olabilir. Global CSS'de kontrol edilmeli.

### `whatsapp-sabit` (ana.js)
```css
.whatsapp-sabit {
  background: #25D366;  /* hardcoded WhatsApp green */
}
```
Hardcoded renk — override edilmeli `var(--renk-yesil)` ile.

---

## Dark Mode

### Mevcut Mu?
Evet — `@media (prefers-color-scheme: dark)` bloğu mevcut (satır 30-38).

### Nasıl Çalışıyor?
```css
@media (prefers-color-scheme: dark) {
  :root {
    --renk-zemin: #1A1512;
    --renk-yuzey: #262019;
    --renk-metin: #F3EDE2;
    --renk-metin-soluk: #C9BFAE;
    --renk-cizgi: #4A3F30;
    --golge: 0 2px 12px rgba(0, 0, 0, 0.4);
  }
}
```
6 renk token dark mode'da değişir. `prefers-reduced-motion` ayrıca desteklenir.

### `prefers-reduced-motion`
```css
@media (prefers-reduced-motion: reduce) {
  html { scroll-behavior: auto; }
  * { animation: none !important; transition: none !important; }
}
```

---

## UI Değişiminde Kritik Noktalar

### Renk Değişirse
- **Zorunlu token'lar:** `--renk-yesil`, `--renk-metin`, `--renk-zemin`, `--renk-yuzey`
- **Etkilenen:** TÜM sayfalar (link, background, text color)
- **Düşük risk:** `--renk-ahsap`, `--renk-toprak` gibi dekoratif token'lar

### Font Değişirse
- **Zorunlu:** `--yazitip-baslik`, `--yazitip-govde`
- **Etkilenen:** TÜM sayfalar (h1-h3, body)
- **Test:** Playfair Display yüklü mü? System fallback kontrol

### Spacing Değişirse
- **Zorunlu:** `--bosluk-2` (en çok kullanılan), `--bosluk-1`
- **Etkilenen:** `.kapsayici`, `.card`, `.form-alan`, `.uyari`, `.kirinti`
- **Risk:** Grid breakpoints (`--bosluk-3`, `--bosluk-4`)

### Border Radius Değişirse
- **Zorunlu:** `--yaricap`
- **Etkilenen:** `.btn`, `.card`, `.input`, `.rozet`, `.akordeon`
- **Test:** 12px → 8px veya 20px değişimi

### RTL'de Token Davranışı
- `margin-inline` (fiziksel değil mantıksal) → Renk token'ları etkilenmez
- `inset-inline-end` → WhatsApp butonu pozisyonu RTL'de değişir
- Renk token'ları = RTL'de aynı

---

## Kullanılmayan Token'lar (Dead Code)

| Token | Durum |
|-------|-------|
| `--bosluk-6` | `.hero`, `.altbilgi` — kullanılıyor |
| `--bosluk-8` | `.hero`, `.altbilgi` — kullanılıyor |
| `--renk-birincil*` | `hesap-kapasite` sınıfı — kontrol edilmeli |

---

## UI Değişim Kontrol Listesi (Token Değişimi)

```
Değiştirmeden önce:
1. grep -rn 'var(--renk-' frontend/ → kullanım haritası
2. grep -rn 'var(--bosluk-' frontend/ → spacing kullanımı
3. grep -rn 'var(--yazitip-' frontend/ → font kullanımı
4. grep -rn 'var(--yaricap' frontend/ → border-radius kullanımı
5. Her sayfada 200px+ kontrol (min-height: 44px touch)
```

---

## CSS Değişim Etkisi

| Değişiklik | Etkilenen Sayfa Sayısı | Kritiklik |
|------------|------------------------|-----------|
| `--renk-yesil` değişirse | 31 | **YÜKSEK** |
| `--yazitip-govde` değişirse | 31 | **YÜKSEK** |
| `--bosluk-2` değişirse | 31 | **YÜKSEK** |
| `--renk-yuzey` değişirse | 10+ | ORTA |
| `--yenik-ahsap` değişirse | 5+ | ORTA |
| `--yenik-cizgi` değişirse | 5+ | ORTA |
| `--yenik-hata` değişirse | 2 | DÜŞÜK |
| `--yenik-basari` değişirse | 2 | DÜŞÜK |
