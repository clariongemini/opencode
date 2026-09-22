<?php

declare(strict_types=1);

use Kamelya\Core\Migration;

return new class implements Migration {
    public function up(PDO $baglanti): void
    {
        $baglanti->exec("CREATE TABLE audit_loglari (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            kullanici_id BIGINT UNSIGNED NULL,
            islem VARCHAR(30) NOT NULL,
            varlik_tipi VARCHAR(30) NOT NULL,
            varlik_id BIGINT UNSIGNED NULL,
            eski_deger JSON NULL,
            yeni_deger JSON NULL,
            ip_adresi VARCHAR(45) NULL,
            user_agent VARCHAR(500) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_audit_kullanici (kullanici_id),
            KEY idx_audit_varlik (varlik_tipi, varlik_id),
            KEY idx_audit_zaman (created_at),
            CONSTRAINT fk_audit_kullanici FOREIGN KEY (kullanici_id)
                REFERENCES kullanicilar (id) ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function down(PDO $baglanti): void
    {
        $baglanti->exec('DROP TABLE IF EXISTS audit_loglari');
    }
};
