<?php

declare(strict_types=1);

use Kamelya\Core\Migration;

return new class implements Migration {
    public function up(PDO $baglanti): void
    {
        $baglanti->exec("CREATE TABLE blog_yazisi_cevirileri (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            yazi_id BIGINT UNSIGNED NOT NULL,
            dil_kodu VARCHAR(5) NOT NULL,
            baslik VARCHAR(220) NOT NULL,
            ozet VARCHAR(500) NULL,
            icerik MEDIUMTEXT NOT NULL,
            slug VARCHAR(240) NOT NULL,
            seo_baslik VARCHAR(220) NULL,
            seo_aciklama VARCHAR(500) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_blog_ceviri (yazi_id, dil_kodu),
            UNIQUE KEY uq_blog_ceviri_slug (dil_kodu, slug),
            KEY idx_blog_ceviri_yazi (yazi_id),
            CONSTRAINT fk_blog_ceviri FOREIGN KEY (yazi_id)
                REFERENCES blog_yazilari (id) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function down(PDO $baglanti): void
    {
        $baglanti->exec('DROP TABLE IF EXISTS blog_yazisi_cevirileri');
    }
};
