<?php

declare(strict_types=1);

use Kamelya\Core\Migration;

return new class implements Migration {
    public function up(PDO $baglanti): void
    {
        $baglanti->exec("CREATE TABLE urun_resimleri (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            urun_id BIGINT UNSIGNED NOT NULL,
            dosya_yolu VARCHAR(500) NOT NULL,
            kucuk_resim_yolu VARCHAR(500) NULL,
            tur VARCHAR(20) NOT NULL DEFAULT 'normal',
            kapak_mi TINYINT(1) NOT NULL DEFAULT 0,
            sira INT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_urun_resim_urun (urun_id),
            KEY idx_urun_resim_sira (urun_id, sira),
            CONSTRAINT fk_urun_resim FOREIGN KEY (urun_id)
                REFERENCES urunler (id) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function down(PDO $baglanti): void
    {
        $baglanti->exec('DROP TABLE IF EXISTS urun_resimleri');
    }
};
