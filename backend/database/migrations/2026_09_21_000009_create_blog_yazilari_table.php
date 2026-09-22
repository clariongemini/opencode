<?php

declare(strict_types=1);

use Kamelya\Core\Migration;

return new class implements Migration {
    public function up(PDO $baglanti): void
    {
        $baglanti->exec("CREATE TABLE blog_yazilari (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            yazar_id BIGINT UNSIGNED NOT NULL,
            kapak_resmi VARCHAR(500) NULL,
            yayin_durumu VARCHAR(20) NOT NULL DEFAULT 'taslak',
            yayin_tarihi DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at DATETIME NULL,
            KEY idx_blog_yazar (yazar_id),
            KEY idx_blog_yayin (yayin_durumu, yayin_tarihi),
            CONSTRAINT fk_blog_yazar FOREIGN KEY (yazar_id)
                REFERENCES kullanicilar (id) ON DELETE RESTRICT ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function down(PDO $baglanti): void
    {
        $baglanti->exec('DROP TABLE IF EXISTS blog_yazilari');
    }
};
