<?php

declare(strict_types=1);

use Kamelya\Core\Migration;

return new class implements Migration {
    public function up(PDO $baglanti): void
    {
        $baglanti->exec("CREATE TABLE seo_verileri (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            sayfa_tipi VARCHAR(30) NOT NULL,
            referans_id BIGINT UNSIGNED NULL,
            sayfa_kodu VARCHAR(80) NULL,
            dil_kodu VARCHAR(5) NOT NULL,
            canonical_url VARCHAR(500) NULL,
            hreflang_json JSON NULL,
            meta_baslik VARCHAR(220) NULL,
            meta_aciklama VARCHAR(500) NULL,
            robots VARCHAR(60) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_seo_kod (sayfa_tipi, sayfa_kodu, dil_kodu),
            UNIQUE KEY uq_seo_referans (sayfa_tipi, referans_id, dil_kodu),
            KEY idx_seo_tip_dil (sayfa_tipi, dil_kodu)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function down(PDO $baglanti): void
    {
        $baglanti->exec('DROP TABLE IF EXISTS seo_verileri');
    }
};
