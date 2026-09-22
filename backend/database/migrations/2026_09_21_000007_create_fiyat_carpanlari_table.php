<?php

declare(strict_types=1);

use Kamelya\Core\Migration;

return new class implements Migration {
    public function up(PDO $baglanti): void
    {
        $baglanti->exec("CREATE TABLE fiyat_carpanlari (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            kategori_id BIGINT UNSIGNED NOT NULL,
            carpan DECIMAL(5,2) NOT NULL,
            aktif TINYINT(1) NOT NULL DEFAULT 1,
            gecerlilik_baslangici DATE NOT NULL,
            aciklama VARCHAR(255) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_fiyat_carpan_gecerlilik (kategori_id, gecerlilik_baslangici),
            KEY idx_fiyat_carpan_kategori (kategori_id),
            CONSTRAINT fk_fiyat_carpan FOREIGN KEY (kategori_id)
                REFERENCES kategoriler (id) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT chk_fiyat_carpan_pozitif CHECK (carpan > 0)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function down(PDO $baglanti): void
    {
        $baglanti->exec('DROP TABLE IF EXISTS fiyat_carpanlari');
    }
};
