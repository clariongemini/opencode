<?php

declare(strict_types=1);

use Kamelya\Core\Migration;

return new class implements Migration {
    public function up(PDO $baglanti): void
    {
        $baglanti->exec("CREATE TABLE sss_cevirileri (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            soru_id BIGINT UNSIGNED NOT NULL,
            dil_kodu VARCHAR(5) NOT NULL,
            soru VARCHAR(500) NOT NULL,
            cevap MEDIUMTEXT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_sss_ceviri (soru_id, dil_kodu),
            KEY idx_sss_ceviri_soru (soru_id),
            CONSTRAINT fk_sss_ceviri FOREIGN KEY (soru_id)
                REFERENCES sss_sorulari (id) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function down(PDO $baglanti): void
    {
        $baglanti->exec('DROP TABLE IF EXISTS sss_cevirileri');
    }
};
