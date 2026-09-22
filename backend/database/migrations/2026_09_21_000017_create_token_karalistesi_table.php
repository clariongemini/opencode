<?php

declare(strict_types=1);

use Kamelya\Core\Migration;

return new class implements Migration {
    public function up(PDO $baglanti): void
    {
        $baglanti->exec("CREATE TABLE token_karalistesi (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            jti VARCHAR(64) NOT NULL,
            kullanici_id BIGINT UNSIGNED NULL,
            tur VARCHAR(20) NOT NULL DEFAULT 'refresh',
            gecerlilik_sonu DATETIME NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_token_jti (jti),
            KEY idx_token_kullanici (kullanici_id),
            KEY idx_token_son (gecerlilik_sonu),
            CONSTRAINT fk_token_kullanici FOREIGN KEY (kullanici_id)
                REFERENCES kullanicilar (id) ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function down(PDO $baglanti): void
    {
        $baglanti->exec('DROP TABLE IF EXISTS token_karalistesi');
    }
};
