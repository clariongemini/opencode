<?php

declare(strict_types=1);

use Kamelya\Core\Migration;

return new class implements Migration {
    public function up(PDO $baglanti): void
    {
        $baglanti->exec("
            CREATE TABLE `kapasite_carpanlari` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `kategori_id` BIGINT UNSIGNED NOT NULL,
                `m2_per_kisi` DECIMAL(5,2) NOT NULL,
                `aktif` TINYINT(1) NOT NULL DEFAULT 1,
                `gecerlilik_baslangici` DATE NOT NULL,
                `aciklama` VARCHAR(255) NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_kategori_baslangic` (`kategori_id`, `gecerlilik_baslangici`),
                CONSTRAINT `fk_kapasite_kategori` FOREIGN KEY (`kategori_id`)
                    REFERENCES `kategoriler` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // Deterministik seed (geliştirici onaylı değerler)
        $now = date('Y-m-d');

        // Kategori ID'lerini çek (kod bazlı) — FETCH_ASSOC + map
        $stmt = $baglanti->prepare("SELECT `id`, `kod` FROM `kategoriler` WHERE `tur` = 'kullanim_amaci' AND `aktif` = 1");
        $stmt->execute();
        $kategoriler = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $kategoriler[$row['kod']] = $row['id'];
        }

        $seedData = [
            'site_bahcesi' => ['m2' => 3.50, 'aciklama' => 'Mahremiyet ve konfor için 4 kişilik aile 14 m²'],
            'restoran'     => ['m2' => 1.80, 'aciklama' => 'Oturaklı servis standartı'],
            'otel'         => ['m2' => 2.80, 'aciklama' => 'Premium teras standardı'],
            'belediye'     => ['m2' => 1.20, 'aciklama' => 'Kamusal alan, yüksek yoğunluk'],
        ];

        $insertSql = "
            INSERT INTO `kapasite_carpanlari` (`kategori_id`, `m2_per_kisi`, `aktif`, `gecerlilik_baslangici`, `aciklama`, `created_at`, `updated_at`)
            VALUES (?, ?, 1, ?, ?, NOW(), NOW())
        ";
        $insertStmt = $baglanti->prepare($insertSql);

        foreach ($seedData as $kod => $data) {
            if (isset($kategoriler[$kod])) {
                $insertStmt->execute([
                    $kategoriler[$kod],
                    $data['m2'],
                    $now,
                    $data['aciklama'],
                ]);
            }
        }
    }

    public function down(PDO $baglanti): void
    {
        $baglanti->exec('DROP TABLE IF EXISTS `kapasite_carpanlari`');
    }
};