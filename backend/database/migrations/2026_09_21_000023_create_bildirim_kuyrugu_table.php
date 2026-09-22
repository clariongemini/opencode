<?php

declare(strict_types=1);

use Kamelya\Core\Migration;

return new class implements Migration {
    public function up(PDO $baglanti): void
    {
        $baglanti->exec("CREATE TABLE bildirim_kuyrugu (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tur VARCHAR(30) NOT NULL,
            alici VARCHAR(190) NOT NULL,
            konu VARCHAR(255) NOT NULL,
            govde TEXT NOT NULL,
            durum VARCHAR(20) NOT NULL DEFAULT 'bekliyor',
            deneme_sayisi INT NOT NULL DEFAULT 0,
            hata TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_bildirim_durum_zaman (durum, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function down(PDO $baglanti): void
    {
        $baglanti->exec('DROP TABLE IF EXISTS bildirim_kuyrugu');
    }
};
