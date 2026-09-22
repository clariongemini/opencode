<?php

declare(strict_types=1);

use Kamelya\Core\Migration;

return new class implements Migration {
    public function up(PDO $baglanti): void
    {
        $baglanti->exec("CREATE TABLE yorumlar (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            musteri_adi VARCHAR(120) NOT NULL,
            eposta VARCHAR(190) NOT NULL,
            telefon VARCHAR(40) NULL,
            puan TINYINT NOT NULL,
            baslik VARCHAR(190) NULL,
            yorum TEXT NOT NULL,
            urun_id BIGINT UNSIGNED NULL,
            dil_kodu VARCHAR(5) NOT NULL,
            durum VARCHAR(20) NOT NULL DEFAULT 'bekliyor',
            red_sebebi VARCHAR(255) NULL,
            onaylayan_id BIGINT UNSIGNED NULL,
            onaylanma_at DATETIME NULL,
            one_cikan TINYINT(1) NOT NULL DEFAULT 0,
            ip_adresi VARCHAR(45) NULL,
            kvkk_onayi TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_yorum_durum_zaman (durum, created_at),
            KEY idx_yorum_urun (urun_id),
            KEY idx_yorum_dil_durum (dil_kodu, durum),
            KEY idx_yorum_puan (puan),
            CONSTRAINT chk_yorum_puan CHECK (puan BETWEEN 1 AND 5),
            CONSTRAINT fk_yorum_urun FOREIGN KEY (urun_id)
                REFERENCES urunler (id) ON DELETE SET NULL ON UPDATE CASCADE,
            CONSTRAINT fk_yorum_onaylayan FOREIGN KEY (onaylayan_id)
                REFERENCES kullanicilar (id) ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function down(PDO $baglanti): void
    {
        $baglanti->exec('DROP TABLE IF EXISTS yorumlar');
    }
};
