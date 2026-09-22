# Veritabanı Şeması — Kamelya (mermaid ER)

```mermaid
erDiagram
    kullanicilar ||--o{ blog_yazilari : yazar
    kullanicilar ||--o{ randevular : ekip
    kullanicilar ||--o{ audit_loglari : islem
    kullanicilar ||--o{ token_karalistesi : jeton
    kategoriler ||--o{ kategori_cevirileri : ceviri
    kategoriler ||--o{ fiyat_carpanlari : carpan
    kategoriler ||--o{ urunler : model
    kategoriler ||--o{ urunler : malzeme
    kategoriler ||--o{ urunler : kullanim
    urunler ||--o{ urun_cevirileri : ceviri
    urunler ||--o{ urun_resimleri : gorsel
    urunler ||--o{ talepler : talep
    talepler ||--o{ randevular : randevu
    blog_yazilari ||--o{ blog_yazisi_cevirileri : ceviri
    sss_sorulari ||--o{ sss_cevirileri : ceviri
    urun_fiyatlari { string dil_kodu PK decimal fiyat_m2 }
    fiyat_carpanlari { int kategori_id FK decimal carpan }
    seo_verileri { string sayfa_tipi int referans_id }
    seo_analitik_verileri { string veri_tipi date tarih }
    galeri { string baslik string dosya_yolu }
    ayarlar { string anahtar UK }
    gocler { string dosya UK }
```

İlkeler: InnoDB + `utf8mb4_unicode_ci`; PK `id BIGINT UNSIGNED AUTO_INCREMENT`;
`created_at/updated_at` her tabloda; soft delete (`deleted_at`) ürün/blog;
`RESTRICT` varsayılan FK, `CASCADE` çeviri/görsel/çarpan, `SET NULL` iş izleri.
