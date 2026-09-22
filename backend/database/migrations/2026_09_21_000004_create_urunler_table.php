<?php

declare(strict_types=1);

use Kamelya\Core\Migration;

return new class implements Migration {
    public function up(PDO $baglanti): void
    {
        $baglanti->exec("CREATE TABLE urunler (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            urun_kodu VARCHAR(60) NOT NULL,
            model_kategori_id BIGINT UNSIGNED NOT NULL,
            malzeme_kategori_id BIGINT UNSIGNED NOT NULL,
            kullanim_kategori_id BIGINT UNSIGNED NULL,
            genislik_varsayilan DECIMAL(6,2) NULL,
            derinlik_varsayilan DECIMAL(6,2) NULL,
            alan_varsayilan DECIMAL(8,2) NULL,
            aktif TINYINT(1) NOT NULL DEFAULT 1,
            one_cikan TINYINT(1) NOT NULL DEFAULT 0,
            sira INT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at DATETIME NULL,
            UNIQUE KEY uq_urunler_kod (urun_kodu),
            KEY idx_urunler_model (model_kategori_id),
            KEY idx_urunler_malzeme (malzeme_kategori_id),
            KEY idx_urunler_kullanim (kullanim_kategori_id),
            KEY idx_urunler_aktif_sira (aktif, sira),
            KEY idx_urunler_silinen (deleted_at),
            CONSTRAINT fk_urunler_model FOREIGN KEY (model_kategori_id)
                REFERENCES kategoriler (id) ON DELETE RESTRICT ON UPDATE CASCADE,
            CONSTRAINT fk_urunler_malzeme FOREIGN KEY (malzeme_kategori_id)
                REFERENCES kategoriler (id) ON DELETE RESTRICT ON UPDATE CASCADE,
            CONSTRAINT fk_urunler_kullanim FOREIGN KEY (kullanim_kategori_id)
                REFERENCES kategoriler (id) ON DELETE RESTRICT ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function down(PDO $baglanti): void
    {
        $baglanti->exec('DROP TABLE IF EXISTS urunler');
    }
};
