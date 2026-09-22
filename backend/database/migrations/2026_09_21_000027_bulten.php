<?php

declare(strict_types=1);

use Kamelya\Core\Migration;

return new class implements Migration {
    public function up(PDO $baglanti): void
    {
        $baglanti->exec("CREATE TABLE bulten_aboneleri (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            eposta VARCHAR(190) NOT NULL,
            ad_soyad VARCHAR(120) NULL,
            dil_kodu VARCHAR(5) NOT NULL DEFAULT 'tr',
            durum VARCHAR(20) NOT NULL DEFAULT 'bekliyor',
            onay_token VARCHAR(64) NULL,
            kvkk_onayi TINYINT(1) NOT NULL DEFAULT 0,
            ip_adresi VARCHAR(45) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            onaylanma_at DATETIME NULL,
            UNIQUE KEY uq_bulten_eposta (eposta),
            KEY idx_bulten_durum (durum)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function down(PDO $baglanti): void
    {
        $baglanti->exec('DROP TABLE IF EXISTS bulten_aboneleri');
    }
};
