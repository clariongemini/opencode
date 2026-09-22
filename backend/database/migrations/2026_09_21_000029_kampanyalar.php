<?php

declare(strict_types=1);

use Kamelya\Core\Migration;

return new class implements Migration {
    public function up(PDO $baglanti): void
    {
        $baglanti->exec("CREATE TABLE kampanyalar (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            kod VARCHAR(60) NOT NULL,
            indirim_orani DECIMAL(5,2) NULL,
            indirim_tipi VARCHAR(20) NOT NULL DEFAULT 'yuzde',
            baslangic DATETIME NOT NULL,
            bitis DATETIME NOT NULL,
            banner_gorsel VARCHAR(500) NULL,
            link_url VARCHAR(500) NULL,
            aktif TINYINT(1) NOT NULL DEFAULT 1,
            sira INT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_kampanya_kod (kod),
            KEY idx_kampanya_aktif_zaman (aktif, baslangic, bitis),
            KEY idx_kampanya_sira (sira)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $baglanti->exec("CREATE TABLE kampanya_cevirileri (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            kampanya_id BIGINT UNSIGNED NOT NULL,
            dil_kodu VARCHAR(5) NOT NULL,
            baslik VARCHAR(190) NOT NULL,
            aciklama TEXT NULL,
            cta_metni VARCHAR(60) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_kampanya_ceviri (kampanya_id, dil_kodu),
            CONSTRAINT fk_kampanya_ceviri FOREIGN KEY (kampanya_id)
                REFERENCES kampanyalar (id) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function down(PDO $baglanti): void
    {
        $baglanti->exec('DROP TABLE IF EXISTS kampanya_cevirileri');
        $baglanti->exec('DROP TABLE IF EXISTS kampanyalar');
    }
};
