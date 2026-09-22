<?php

declare(strict_types=1);

use Kamelya\Core\Migration;

return new class implements Migration {
    public function up(PDO $baglanti): void
    {
        $baglanti->exec("CREATE TABLE sss_sorulari (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            sira INT NOT NULL DEFAULT 0,
            aktif TINYINT(1) NOT NULL DEFAULT 1,
            sayfa_kapsami VARCHAR(60) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_sss_aktif_sira (aktif, sira)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function down(PDO $baglanti): void
    {
        $baglanti->exec('DROP TABLE IF EXISTS sss_sorulari');
    }
};
