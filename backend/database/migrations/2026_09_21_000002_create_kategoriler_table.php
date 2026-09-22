<?php

declare(strict_types=1);

use Kamelya\Core\Migration;

return new class implements Migration {
    public function up(PDO $baglanti): void
    {
        $baglanti->exec("CREATE TABLE kategoriler (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tur VARCHAR(30) NOT NULL,
            kod VARCHAR(60) NOT NULL,
            ust_kategori_id BIGINT UNSIGNED NULL,
            sira INT NOT NULL DEFAULT 0,
            aktif TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_kategoriler_tur_kod (tur, kod),
            KEY idx_kategoriler_ust (ust_kategori_id),
            CONSTRAINT fk_kategoriler_ust FOREIGN KEY (ust_kategori_id)
                REFERENCES kategoriler (id) ON DELETE RESTRICT ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function down(PDO $baglanti): void
    {
        $baglanti->exec('DROP TABLE IF EXISTS kategoriler');
    }
};
