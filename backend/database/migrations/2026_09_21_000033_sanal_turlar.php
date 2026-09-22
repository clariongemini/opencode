<?php

declare(strict_types=1);

use Kamelya\Core\Migration;

return new class implements Migration {
    public function up(PDO $baglanti): void
    {
        $baglanti->exec("CREATE TABLE sanal_turlar (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            baslik VARCHAR(190) NOT NULL,
            aciklama TEXT NULL,
            embed_url VARCHAR(500) NOT NULL,
            kapak_gorsel VARCHAR(500) NULL,
            sira INT NOT NULL DEFAULT 0,
            aktif TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_sanal_sira (sira),
            KEY idx_sanal_aktif (aktif)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $baglanti->exec("CREATE TABLE sanal_tur_cevirileri (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tur_id BIGINT UNSIGNED NOT NULL,
            dil_kodu VARCHAR(5) NOT NULL,
            baslik VARCHAR(190) NOT NULL,
            aciklama TEXT NULL,
            slug VARCHAR(220) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_sanal_ceviri (tur_id, dil_kodu),
            CONSTRAINT fk_sanal_ceviri FOREIGN KEY (tur_id)
                REFERENCES sanal_turlar (id) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function down(PDO $baglanti): void
    {
        $baglanti->exec('DROP TABLE IF EXISTS sanal_tur_cevirileri');
        $baglanti->exec('DROP TABLE IF EXISTS sanal_turlar');
    }
};
