<?php

declare(strict_types=1);

use Kamelya\Core\Migration;

return new class implements Migration {
    public function up(PDO $baglanti): void
    {
        $baglanti->exec("CREATE TABLE randevular (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            talep_id BIGINT UNSIGNED NULL,
            ad_soyad VARCHAR(120) NOT NULL,
            telefon VARCHAR(40) NOT NULL,
            eposta VARCHAR(190) NULL,
            randevu_tarihi DATETIME NOT NULL,
            durum VARCHAR(20) NOT NULL DEFAULT 'bekliyor',
            notlar TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_randevu_tarih_durum (randevu_tarihi, durum),
            KEY idx_randevu_talep (talep_id),
            CONSTRAINT fk_randevu_talep FOREIGN KEY (talep_id)
                REFERENCES talepler (id) ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function down(PDO $baglanti): void
    {
        $baglanti->exec('DROP TABLE IF EXISTS randevular');
    }
};
