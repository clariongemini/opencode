<?php

declare(strict_types=1);

use Kamelya\Core\Migration;

return new class implements Migration {
    public function up(PDO $baglanti): void
    {
        $baglanti->exec("CREATE TABLE kategori_cevirileri (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            kategori_id BIGINT UNSIGNED NOT NULL,
            dil_kodu VARCHAR(5) NOT NULL,
            isim VARCHAR(190) NOT NULL,
            aciklama TEXT NULL,
            slug VARCHAR(220) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_kategori_ceviri (kategori_id, dil_kodu),
            UNIQUE KEY uq_kategori_ceviri_slug (dil_kodu, slug),
            KEY idx_kategori_ceviri_kategori (kategori_id),
            CONSTRAINT fk_kategori_ceviri FOREIGN KEY (kategori_id)
                REFERENCES kategoriler (id) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function down(PDO $baglanti): void
    {
        $baglanti->exec('DROP TABLE IF EXISTS kategori_cevirileri');
    }
};
