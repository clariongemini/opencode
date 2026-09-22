<?php

declare(strict_types=1);

use Kamelya\Core\Migration;

return new class implements Migration {
    public function up(PDO $baglanti): void
    {
        $baglanti->exec("CREATE TABLE sehirler (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            kod VARCHAR(60) NOT NULL,
            ad VARCHAR(120) NOT NULL,
            aktif TINYINT(1) NOT NULL DEFAULT 1,
            sira INT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_sehir_kod (kod),
            KEY idx_sehir_aktif_sira (aktif, sira)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $baglanti->exec("CREATE TABLE sehir_cevirileri (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            sehir_id BIGINT UNSIGNED NOT NULL,
            dil_kodu VARCHAR(5) NOT NULL,
            seo_baslik VARCHAR(220) NULL,
            seo_aciklama VARCHAR(500) NULL,
            icerik MEDIUMTEXT NULL,
            slug VARCHAR(220) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_sehir_ceviri (sehir_id, dil_kodu),
            UNIQUE KEY uq_sehir_slug (dil_kodu, slug),
            CONSTRAINT fk_sehir_ceviri FOREIGN KEY (sehir_id)
                REFERENCES sehirler (id) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $baglanti->exec("CREATE TABLE sehir_hizmet_bolgeleri (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            sehir_id BIGINT UNSIGNED NOT NULL,
            ilce VARCHAR(120) NOT NULL,
            sira INT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_bolge_sehir (sehir_id, sira),
            CONSTRAINT fk_bolge_sehir FOREIGN KEY (sehir_id)
                REFERENCES sehirler (id) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $sehirler = [
            ['istanbul', 'İstanbul', 1, ['Bahçelievler', 'Ümraniye', 'Pendik']],
            ['ankara', 'Ankara', 2, ['Çankaya']],
            ['izmir', 'İzmir', 3, ['Bornova']],
        ];
        $sIfade = $baglanti->prepare('INSERT INTO sehirler (kod, ad, sira) VALUES (?, ?, ?)');
        $bIfade = $baglanti->prepare('INSERT INTO sehir_hizmet_bolgeleri (sehir_id, ilce, sira) VALUES (?, ?, ?)');
        foreach ($sehirler as [$kod, $ad, $sira, $ilceler]) {
            $sIfade->execute([$kod, $ad, $sira]);
            $sehirId = (int) $baglanti->lastInsertId();
            $i = 1;
            foreach ($ilceler as $ilce) {
                $bIfade->execute([$sehirId, $ilce, $i++]);
            }
        }
    }

    public function down(PDO $baglanti): void
    {
        $baglanti->exec('DROP TABLE IF EXISTS sehir_hizmet_bolgeleri');
        $baglanti->exec('DROP TABLE IF EXISTS sehir_cevirileri');
        $baglanti->exec('DROP TABLE IF EXISTS sehirler');
    }
};
