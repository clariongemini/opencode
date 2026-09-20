# PHP/MySQL MVC API Standardı

**Kaynak:** Geliştirici talebiyle oluşturuldu [EK-20260920]. APP-FABRIKA repoda PHP/MySQL/Laravel scaffold ve kuralları **bulunmuyordu** (bkz. `AGENTS.md` — Standart Kaynakları ve Boşlukları); bu boşluk Kamelya projesinin birincil hedefi ("mükemmel backend, MVC API") için geliştirici talebiyle bu dosya ile kapatıldı. Kaynağı APP-FABRIKA değil, geliştirici talebidir; APP-FABRICA standart güncellemelerinde bu dosya korunur (`/standart-guncelle` akışı opencode bileşenlerini yeniden haritalandırır, bu dosyayı silmez).

**Kapsam:** Kamelya PHP/MySQL backend'i (F2 scaffold kurulumu + F3 core modüller). `web-developer` ve `database-administrator` skill'leri bu standarta referans verir.

---

## 1. Mimari — Katmanlı MVC + Service + Repository

### Katman sorumlulukları

| Katman | Sorumluluk | Yasak |
|--------|-----------|-------|
| **Controller** | HTTP giriş/çıkış: Request doğrulama çağrısı, Service çağrısı, Response döndürme | İş mantığı, doğrudan DB erişimi (kalın controller yasak) |
| **Service** | İş mantığı, iş kuralları, transaction sınırları, event/side-effect orkestrasyonu | HTTP objelerine (Request/Response) dokunma |
| **Repository** | Veri erişimi: SELECT/INSERT/UPDATE/DELETE, sorgu inşası; veri kaynağını üst katmandan gizler | İş mantığı, Service'ler arası çağrı |
| **Model / Entity** | Alan tanımı + alan davranışı; ORM entity veya Data Mapper entity | HTTP/DB detayları (Framework coupling) |
| **Validator** | İstek/veri doğrulama (allowlist yaklaşımı) | İş kararı verme |
| **View / Serializer** | Sunum katmanı; API'de View = JSON serializer (Model → API sözleşmesi) | Sorgu/iş mantığı |

### Bağımlılık yönü (tek yönlü)

```
Controller → Service → Repository → (PDO/ORM) → MySQL
```

- Tersine bağımlılık yasak; Repository'ler birbirini çağıramaz; Service Repository'yi interface üzerinden bilir (SOLID — Dependency Inversion).
- Bağımlılıklar constructor injection ile verilir; service container kullanımı önerilir.

### Dizin yapısı (referans iskelet)

```
app/
  Controllers/
  Services/
  Repositories/
  Models/
  Validators/
  Serializers/
  Middleware/
  Core/            # Router, Request, Response, Database, Auth (framework'e özgü)
config/
database/
  migrations/
  seeders/
public/
  index.php        # tek giriş noktası (front controller)
tests/
  Unit/            # Service testleri
  Integration/     # Repository + DB testleri
  E2E/             # API uçtan uca testleri
```

- `public/` dışındaki hiçbir dizin web'den doğrudan sunulmaz; `app/Core` dışındaki dosyalar framework detayına sahip olmaz.

---

## 2. API — RESTful Tasarım Kuralları

### Kaynak ve metot kuralları

- Kaynak isimleri: **çoğul + kebab-case** (`/api/v1/flower-orders`); fiil kullanılmaz (`/getUser` yasak).
- HTTP metotları:

| Metot | Anlam | Idempotent |
|-------|-------|-----------|
| GET | Okuma (koleksiyon/tekil) | Evet |
| POST | Oluşturma; kritik işlemler (ödeme vb.) | Hayır |
| PUT | Tam güncelleme (idempotent) | Evet |
| PATCH | Kısmi güncelleme | Hayır |
| DELETE | Silme | Evet |

### Durum kodları (zorunlu tablo)

| Kod | Kullanım |
|-----|----------|
| 200 OK | Başarılı okuma/güncelleme/silme (gövde var) |
| 201 Created | Oluşturma başarılı; `Location` başlığı önerilir |
| 204 No Content | Başarılı silme (gövde yok) |
| 400 Bad Request | Bozuk/çözümlenemeyen istek |
| 401 Unauthorized | Kimlik doğrulama yok/geçersiz |
| 403 Forbidden | Yetki yok |
| 404 Not Found | Kaynak bulunamadı |
| 405 Method Not Allowed | Metot bu kaynakta desteklenmiyor |
| 409 Conflict | Çakışma (versiyon, unique ihlali) |
| 422 Unprocessable Entity | Doğrulama hatası (Validator çıktısı) |
| 429 Too Many Requests | Rate limit aşıldı |
| 500 Internal Server Error | Beklenmeyen hata (detay sızdırılmaz) |
| 503 Service Unavailable | Bakım/bağımlılık erişilemez |

### JSON yanıt formatı (tek sözleşme)

Başarı:
```json
{
  "success": true,
  "data": { "id": 1, "name": "..." },
  "meta": { "page": 1, "per_page": 20, "total": 100 }
}
```

Hata:
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Doğrulama hatası",
    "details": [ { "field": "email", "issue": "required" } ]
  }
}
```

- Hata kodları dokümante edilir; aynı hata her yerde aynı formatta döner.
- Filtre/sıralama/paginasyon query string standartı: `?page=1&per_page=20&sort=-created_at&filter[status]=active`.
- **Versiyonlama:** URL yolu versiyonu zorunlu (`/api/v1/...`). Kırıcı değişiklik = yeni versiyon; eski versiyon kaldırma planı dokümante edilir.

---

## 3. Veritabanı — MySQL 8+

### Tablo tasarımı

- Charset/collation: **utf8mb4** + `utf8mb4_unicode_ci`; Storage engine: **InnoDB** (transaction + FK zorunlu).
- Tablo isimleri çoğul (`products`, `flower_orders`); PK: `id BIGINT UNSIGNED AUTO_INCREMENT`.
- Zaman damgaları: `created_at`, `updated_at` zorunlu; soft delete gerekiyorsa `deleted_at` (NOT NULL değil, indexli).
- Enum yerine lookup tablosu veya `VARCHAR` + allowlist doğrulama tercih edilir.

### İlişkisel modelleme

- Tüm ilişkiler **FOREIGN KEY** ile tanımlanır; `ON DELETE` / `ON UPDATE` davranışı açıkça belirtilir (`RESTRICT` varsayılan; `CASCADE` yalnızca gerekçeli — ADR kaydı ile).
- Çoka-çok ilişkiler ara pivot tablo ile; pivot tablolarda bileşik PK.

### Indexing

- Zorunlu indexler: PK, tüm FK sütunları, sık `WHERE`/`ORDER BY`/`JOIN` sütunları.
- Bileşik indexlerde sütun sırası: eşitlik sütunları önce, aralık sütunları sonra.
- Redundant (tekrarlayan) index yok; her yeni index `EXPLAIN` ile doğrulanır; index değişikliği ADR kaydı ile.

### Migration ve Seeding

- Migration'lar **geri alınabilir** olmalı (`up`/`down`); şema değişikliği ADR kaydı ile; veri kaybı riski taşıyan migration'lar yedek sonrası çalışır.
- Dosya adları sıralı: `2026_09_20_000001_create_products_table.php`.
- Seeding: geliştirme/test ortamı için deterministik seeder; **production'da seed çalıştırılmaz**; gerçek veriden türetmede veri anonimleştirilir.
- `database-administrator` skill ilkeleriyle uyum: gizli anahtarlar `.env`'de, **asla commit edilmez, asla loglanmaz**; bağlantı TLS zorunlu; şema kararları mimari denetimine (L1: architect, L2: CAO) tabidir.

---

## 4. Güvenlik

| Risk | Zorunlu koruma |
|------|---------------|
| **SQL Injection** | Hazırlıklı ifadeler (PDO prepared statements / parametre bağlama) her sorguda zorunlu; ham sorgu içinde string birleştirme **yasak** |
| **XSS** | Çıktı kaçışlama (`htmlspecialchars($v, ENT_QUOTES, 'UTF-8')`) her render'da; `Content-Type` doğru; `Content-Security-Policy` başlığı önerilir; JSON API'de doğru `Content-Type: application/json` |
| **CSRF** | Stateful form akışlarında CSRF token zorunlu; token tabanlı API'de `SameSite=Lax/Strict` cookie politikası + Origin doğrulaması |
| **Kimlik doğrulama** | JWT/Token tabanlı: HTTPS zorunlu, token ömrü kısa + refresh stratejisi dokümante, imzalama algoritması asimetrik (RS256 önerilir) veya en az HS256 + güçli gizli anahtar; şifreler `password_hash()` (bcrypt/argon2id) |
| **Yetkilendirme** | Rol bazlı kontroller middleware katmanında; controller içi kontrol yalnızca ikinci savunma |
| **Diğer** | Rate limiting (özellikle auth endpoint'leri), girdi doğrulama allowlist, hata loglamasında hassas veri (token, şifre, kart) **asla loglanmaz** |

---

## 5. Kod Kalitesi

- **PSR-12** kod stili zorunlu; **PSR-4** autoload; `php-cs-fixer` / `PHP_CodeSniffer` ile doğrulama.
- **SOLID:** Single Responsibility (Controller kalınlaşmaz), Open/Closed (yeni davranış = yeni sınıf), Liskov (interface sözleşmeleri korunur), Interface Segregation (genel amaçlı dev Repository interface'i yok), Dependency Inversion (Service → Repository interface'i).
- **Temiz kod:** anlamlı isimler, kısa metotlar, tekrar yok (DRY), yorum borcu yok; magic string'ler sabitlere (enum/class constant) taşınır.
- **Statik analiz:** PHPStan (level 6+) veya Psalm; CI'da zorunlu.
- **Test:** Unit (Service), Integration (Repository + gerçek MySQL test DB'si), E2E (API critical path).

### Faz bağlantıları

- **F2 (Kat Döşeme):** Bu standart scaffold öncesi tanımlıdır — iskelet bu standarda göre kurulur.
- **F3 (Duvar & Tesisat):** Core modüller bu standartla yazılır.
- **Faz kapanış kapısı:** Her faz sonunda `/denetle` ile kod denetimi (L1+L2) **ve** SEO denetimi (`seo-analyzer`) yapılmadan faz `tamamlandı` işaretlenmez (bkz. `standards/PHASE_MAP.md`).

### Doğrulama listesi (her backend deliverable'ında)

- [ ] Katman sorumlulukları ihlal yok (Controller'da sorgu yok, Repository'de iş mantığı yok)
- [ ] Tüm sorgular hazırlıklı ifade
- [ ] JSON yanıt formatı tek sözleşmeye uyumlu + durum kodları tablosuna uyumlu
- [ ] FK + index planı + `EXPLAIN` çıktısı
- [ ] Migration geri alınabilir + ADR kaydı
- [ ] CSRF/HTTPS/JWT ayarları aktif
- [ ] PSR-12 + PHPStan çıktısı temiz
- [ ] Test kapsamı: Unit + Integration + E2E
