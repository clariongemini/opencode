<?php

declare(strict_types=1);

use Kamelya\Core\Migration;

return new class implements Migration {
    public function up(PDO $baglanti): void
    {
        $baglanti->exec("CREATE TABLE urun_fiyatlari (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            dil_kodu VARCHAR(5) NOT NULL,
            para_birimi VARCHAR(8) NOT NULL,
            fiyat_m2 DECIMAL(12,2) NOT NULL,
            gecerlilik_baslangici DATE NOT NULL,
            aktif TINYINT(1) NOT NULL DEFAULT 1,
            aciklama VARCHAR(255) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_urun_fiyat_dil (dil_kodu),
            KEY idx_urun_fiyat_aktif (aktif),
            CONSTRAINT chk_urun_fiyat_pozitif CHECK (fiyat_m2 > 0)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function down(PDO $baglanti): void
    {
        $baglanti->exec('DROP TABLE IF EXISTS urun_fiyatlari');
    }
};
