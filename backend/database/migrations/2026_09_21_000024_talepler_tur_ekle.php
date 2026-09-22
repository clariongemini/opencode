<?php

declare(strict_types=1);

use Kamelya\Core\Migration;

/**
 * [F9-EK] talepler.tur ayrımı — görevde 000025 yazıldı; sıra bütünlüğü için
 * 000024 verildi (000024 atlanmıştı). Geri alınabilir.
 */
return new class implements Migration {
    public function up(PDO $baglanti): void
    {
        $baglanti->exec("ALTER TABLE talepler
            ADD COLUMN tur VARCHAR(30) NOT NULL DEFAULT 'teklif' AFTER durum,
            ADD COLUMN mesaj TEXT NULL AFTER tur,
            ADD KEY idx_talep_tur_durum_zaman (tur, durum, created_at)");
        $baglanti->exec("UPDATE talepler SET tur = 'teklif' WHERE tur = '' OR tur IS NULL");
    }

    public function down(PDO $baglanti): void
    {
        $baglanti->exec('ALTER TABLE talepler DROP KEY idx_talep_tur_durum_zaman');
        $baglanti->exec('ALTER TABLE talepler DROP COLUMN mesaj');
        $baglanti->exec('ALTER TABLE talepler DROP COLUMN tur');
    }
};
