<?php

declare(strict_types=1);

use Kamelya\Core\Migration;

return new class implements Migration {
    public function up(PDO $baglanti): void
    {
        $baglanti->exec("CREATE TABLE talepler (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            ad_soyad VARCHAR(120) NOT NULL,
            telefon VARCHAR(40) NOT NULL,
            eposta VARCHAR(190) NULL,
            sehir VARCHAR(100) NULL,
            urun_id BIGINT UNSIGNED NULL,
            genislik DECIMAL(6,2) NULL,
            derinlik DECIMAL(6,2) NULL,
            alan_m2 DECIMAL(8,2) NULL,
            dil_kodu VARCHAR(5) NOT NULL,
            para_birimi VARCHAR(8) NOT NULL,
            hesaplanan_fiyat DECIMAL(14,2) NULL,
            durum VARCHAR(20) NOT NULL DEFAULT 'yeni',
            kaynak VARCHAR(40) NULL,
            kvkk_onayi TINYINT(1) NOT NULL,
            ip_adresi VARCHAR(45) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_talep_durum_zaman (durum, created_at),
            KEY idx_talep_telefon (telefon),
            KEY idx_talep_urun (urun_id),
            KEY idx_talep_sehir (sehir),
            CONSTRAINT fk_talep_urun FOREIGN KEY (urun_id)
                REFERENCES urunler (id) ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function down(PDO $baglanti): void
    {
        $baglanti->exec('DROP TABLE IF EXISTS talepler');
    }
};
