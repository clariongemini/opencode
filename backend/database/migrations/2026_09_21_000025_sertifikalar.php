<?php

declare(strict_types=1);

use Kamelya\Core\Migration;

return new class implements Migration {
    public function up(PDO $baglanti): void
    {
        $baglanti->exec("CREATE TABLE sertifikalar (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            baslik VARCHAR(190) NOT NULL,
            kurum VARCHAR(30) NOT NULL DEFAULT 'Diger',
            belge_no VARCHAR(100) NULL,
            gecerlilik_tarihi DATE NULL,
            logo_yolu VARCHAR(500) NULL,
            aciklama TEXT NULL,
            sira INT NOT NULL DEFAULT 0,
            aktif TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_sertifika_sira (sira),
            KEY idx_sertifika_aktif (aktif)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $baglanti->exec("CREATE TABLE sertifika_cevirileri (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            sertifika_id BIGINT UNSIGNED NOT NULL,
            dil_kodu VARCHAR(5) NOT NULL,
            baslik VARCHAR(190) NOT NULL,
            aciklama TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_sertifika_ceviri (sertifika_id, dil_kodu),
            CONSTRAINT fk_sertifika_ceviri FOREIGN KEY (sertifika_id)
                REFERENCES sertifikalar (id) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function down(PDO $baglanti): void
    {
        $baglanti->exec('DROP TABLE IF EXISTS sertifika_cevirileri');
        $baglanti->exec('DROP TABLE IF EXISTS sertifikalar');
    }
};
