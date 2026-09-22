<?php

declare(strict_types=1);

use Kamelya\Core\Migration;

return new class implements Migration {
    public function up(PDO $baglanti): void
    {
        $baglanti->exec("CREATE TABLE kullanicilar (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            ad_soyad VARCHAR(120) NOT NULL,
            eposta VARCHAR(190) NOT NULL,
            sifre_hash VARCHAR(255) NOT NULL,
            rol VARCHAR(20) NOT NULL,
            aktif TINYINT(1) NOT NULL DEFAULT 1,
            son_giris_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_kullanicilar_eposta (eposta),
            KEY idx_kullanicilar_rol_aktif (rol, aktif)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function down(PDO $baglanti): void
    {
        $baglanti->exec('DROP TABLE IF EXISTS kullanicilar');
    }
};
