<?php

declare(strict_types=1);

use Kamelya\Core\Migration;

return new class implements Migration {
    public function up(PDO $baglanti): void
    {
        $baglanti->exec("CREATE TABLE urun_cevirileri (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            urun_id BIGINT UNSIGNED NOT NULL,
            dil_kodu VARCHAR(5) NOT NULL,
            baslik VARCHAR(220) NOT NULL,
            kisa_aciklama VARCHAR(500) NULL,
            detayli_aciklama MEDIUMTEXT NULL,
            seo_baslik VARCHAR(220) NULL,
            seo_aciklama VARCHAR(500) NULL,
            seo_anahtar_kelimeler VARCHAR(500) NULL,
            slug VARCHAR(240) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_urun_ceviri (urun_id, dil_kodu),
            UNIQUE KEY uq_urun_ceviri_slug (dil_kodu, slug),
            KEY idx_urun_ceviri_urun (urun_id),
            CONSTRAINT fk_urun_ceviri FOREIGN KEY (urun_id)
                REFERENCES urunler (id) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function down(PDO $baglanti): void
    {
        $baglanti->exec('DROP TABLE IF EXISTS urun_cevirileri');
    }
};
