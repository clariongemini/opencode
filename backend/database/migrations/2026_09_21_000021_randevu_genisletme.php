<?php

declare(strict_types=1);

use Kamelya\Core\Migration;

return new class implements Migration {
    public function up(PDO $baglanti): void
    {
        $baglanti->exec("ALTER TABLE randevular
            ADD COLUMN tur VARCHAR(30) NOT NULL DEFAULT 'gorusme' AFTER durum,
            ADD COLUMN ekip_uyesi_id BIGINT UNSIGNED NULL AFTER tur,
            ADD COLUMN sure_dakika INT NOT NULL DEFAULT 60 AFTER ekip_uyesi_id,
            ADD COLUMN adres VARCHAR(500) NULL AFTER sure_dakika,
            ADD COLUMN oncelik VARCHAR(20) NOT NULL DEFAULT 'normal' AFTER adres,
            ADD COLUMN tamamlanma_notu TEXT NULL AFTER oncelik,
            ADD COLUMN tamamlanma_at DATETIME NULL AFTER tamamlanma_notu,
            ADD KEY idx_randevu_tur_tarih (tur, randevu_tarihi),
            ADD KEY idx_randevu_ekip_tarih (ekip_uyesi_id, randevu_tarihi),
            ADD CONSTRAINT fk_randevu_ekip FOREIGN KEY (ekip_uyesi_id)
                REFERENCES kullanicilar (id) ON DELETE SET NULL ON UPDATE CASCADE");
        $baglanti->exec('ALTER TABLE kullanicilar ADD COLUMN ekip_mi TINYINT(1) NOT NULL DEFAULT 0 AFTER aktif');
    }

    public function down(PDO $baglanti): void
    {
        $baglanti->exec('ALTER TABLE randevular DROP FOREIGN KEY fk_randevu_ekip');
        $baglanti->exec('ALTER TABLE randevular DROP KEY idx_randevu_ekip_tarih');
        $baglanti->exec('ALTER TABLE randevular DROP KEY idx_randevu_tur_tarih');
        $baglanti->exec('ALTER TABLE randevular DROP COLUMN tamamlanma_at');
        $baglanti->exec('ALTER TABLE randevular DROP COLUMN tamamlanma_notu');
        $baglanti->exec('ALTER TABLE randevular DROP COLUMN oncelik');
        $baglanti->exec('ALTER TABLE randevular DROP COLUMN adres');
        $baglanti->exec('ALTER TABLE randevular DROP COLUMN sure_dakika');
        $baglanti->exec('ALTER TABLE randevular DROP COLUMN ekip_uyesi_id');
        $baglanti->exec('ALTER TABLE randevular DROP COLUMN tur');
        $baglanti->exec('ALTER TABLE kullanicilar DROP COLUMN ekip_mi');
    }
};
