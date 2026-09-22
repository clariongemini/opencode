<?php

declare(strict_types=1);

use Kamelya\Core\Migration;

return new class implements Migration {
    public function up(PDO $baglanti): void
    {
        $baglanti->exec("CREATE TABLE atolye_fotograflari (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            baslik VARCHAR(190) NOT NULL,
            aciklama TEXT NULL,
            dosya_yolu VARCHAR(500) NOT NULL,
            sira INT NOT NULL DEFAULT 0,
            aktif TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_atolye_sira (sira),
            KEY idx_atolye_aktif (aktif)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function down(PDO $baglanti): void
    {
        $baglanti->exec('DROP TABLE IF EXISTS atolye_fotograflari');
    }
};
