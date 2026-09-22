<?php

declare(strict_types=1);

use Kamelya\Core\Migration;

/**
 * Özellik toggle sistemi — tablo + bağımlılık ağacı seed'i tek migration'da.
 * Gerekçe: seed.php production'da yasaklı olduğundan sistem verisi burada gelir.
 */
return new class implements Migration {
    public function up(PDO $baglanti): void
    {
        $baglanti->exec("CREATE TABLE ozellik_toggle (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            anahtar VARCHAR(80) NOT NULL,
            parent_anahtar VARCHAR(80) NULL,
            baslik VARCHAR(190) NOT NULL,
            aciklama VARCHAR(500) NULL,
            aktif TINYINT(1) NOT NULL DEFAULT 1,
            zorunlu TINYINT(1) NOT NULL DEFAULT 0,
            sira INT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_ozellik_anahtar (anahtar),
            KEY idx_ozellik_parent (parent_anahtar),
            KEY idx_ozellik_aktif_sira (aktif, sira)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $agac = [
            ['anasayfa', null, 'Ana Sayfa', 1, 1, 1],
            ['urunler', null, 'Ürünler', 0, 2, 1],
            ['blog', null, 'Blog', 0, 3, 1],
            ['sss', null, 'SSS', 0, 4, 1],
            ['galeri', null, 'Galeri', 0, 5, 1],
            ['hakkimizda', null, 'Hakkımızda', 0, 6, 1],
            ['iletisim', null, 'İletişim', 0, 7, 1],
            ['rehberler', null, 'Rehberler', 0, 8, 1],
            ['pazarlama', null, 'Pazarlama', 0, 9, 1],
            ['v1_5', null, 'Gelişmiş Özellikler', 0, 10, 0],
            ['gizlilik', null, 'Gizlilik Politikası', 1, 11, 1],
            ['kvkk', null, 'KVKK Aydınlatma', 1, 12, 1],
            ['urun_kategoriler', 'urunler', 'Kategori Filtreleri', 0, 1, 1],
            ['urun_detay', 'urunler', 'Ürün Detay Sayfası', 0, 2, 1],
            ['urun_karsilastirma', 'urunler', 'Ürün Karşılaştırma', 0, 3, 1],
            ['urun_hesaplama', 'urunler', 'm² Hesaplama Aracı', 0, 4, 1],
            ['blog_listesi', 'blog', 'Blog Listesi', 0, 1, 1],
            ['blog_detay', 'blog', 'Blog Detay', 0, 2, 1],
            ['referanslar', 'galeri', 'Referanslar / Müşteri Hikayeleri', 0, 1, 1],
            ['ekip', 'hakkimizda', 'Ekip Tanıtımı', 0, 1, 1],
            ['sertifikasyon', 'hakkimizda', 'Sertifikasyon Vitrini', 0, 2, 1],
            ['atolye', 'hakkimizda', 'Atölye Fotoğrafları', 0, 3, 1],
            ['garanti', 'hakkimizda', 'Garanti Koşulları', 0, 4, 1],
            ['iletisim_formu', 'iletisim', 'İletişim Formu', 0, 1, 1],
            ['teklif_formu', 'iletisim', 'Teklif Formu', 0, 2, 1],
            ['randevu_formu', 'iletisim', 'Randevu Formu', 0, 3, 1],
            ['whatsapp_butonu', 'iletisim', 'WhatsApp Butonu', 0, 4, 1],
            ['telefon_butonu', 'iletisim', 'Telefon Butonu', 0, 5, 1],
            ['bakim_rehberi', 'rehberler', 'Kamelya Bakım Rehberi', 0, 1, 1],
            ['malzeme_rehberi', 'rehberler', 'Malzeme Karşılaştırma', 0, 2, 1],
            ['bulten', 'pazarlama', 'Bülten Aboneliği', 0, 1, 1],
            ['kampanya', 'pazarlama', 'Kampanya Duyuruları', 0, 2, 1],
            ['sanal_tur', 'v1_5', 'Sanal Tur', 0, 1, 0],
            ['viewer_360', 'v1_5', '360° Görüntüleme', 0, 2, 0],
            ['before_after', 'v1_5', 'Öncesi/Sonrası', 0, 3, 0],
            ['sicaklik_simulasyonu', 'v1_5', 'Sıcaklık Simülasyonu', 0, 4, 0],
            ['bakim_takvimi', 'v1_5', 'Mevsimsel Bakım Takvimi', 0, 5, 0],
        ];

        $ifade = $baglanti->prepare(
            'INSERT INTO ozellik_toggle (anahtar, parent_anahtar, baslik, zorunlu, sira, aktif)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        foreach ($agac as [$anahtar, $parent, $baslik, $zorunlu, $sira, $aktif]) {
            $ifade->execute([$anahtar, $parent, $baslik, $zorunlu, $sira, $aktif]);
        }
    }

    public function down(PDO $baglanti): void
    {
        $baglanti->exec('DROP TABLE IF EXISTS ozellik_toggle');
    }
};
