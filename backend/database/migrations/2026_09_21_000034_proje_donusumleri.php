<?php

declare(strict_types=1);

use Kamelya\Core\Migration;

return new class implements Migration {
    public function up(PDO $baglanti): void
    {
        $baglanti->exec("CREATE TABLE proje_donusumleri (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            baslik VARCHAR(190) NOT NULL,
            oncesi_gorsel VARCHAR(500) NOT NULL,
            sonrasi_gorsel VARCHAR(500) NOT NULL,
            aciklama TEXT NULL,
            proje_id BIGINT UNSIGNED NULL,
            sira INT NOT NULL DEFAULT 0,
            aktif TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_donusum_sira (sira),
            KEY idx_donusum_aktif (aktif),
            CONSTRAINT fk_donusum_proje FOREIGN KEY (proje_id)
                REFERENCES galeri (id) ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function down(PDO $baglanti): void
    {
        $baglanti->exec('DROP TABLE IF EXISTS proje_donusumleri');
    }
};
