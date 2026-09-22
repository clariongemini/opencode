<?php

declare(strict_types=1);

use Kamelya\Core\Migration;

return new class implements Migration {
    public function up(PDO $baglanti): void
    {
        $baglanti->exec("CREATE TABLE seo_analitik_verileri (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            veri_tipi VARCHAR(30) NOT NULL,
            tarih DATE NOT NULL,
            boyut VARCHAR(190) NULL,
            tiklama INT NOT NULL DEFAULT 0,
            gosterim INT NOT NULL DEFAULT 0,
            ctr DECIMAL(5,4) NOT NULL DEFAULT 0.0000,
            ortalama_pozisyon DECIMAL(6,2) NOT NULL DEFAULT 0.00,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_seo_analitik (veri_tipi, tarih, boyut),
            KEY idx_seo_analitik_tip_tarih (veri_tipi, tarih),
            KEY idx_seo_analitik_tip_boyut (veri_tipi, boyut)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function down(PDO $baglanti): void
    {
        $baglanti->exec('DROP TABLE IF EXISTS seo_analitik_verileri');
    }
};
