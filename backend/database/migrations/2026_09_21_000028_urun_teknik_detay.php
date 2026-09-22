<?php

declare(strict_types=1);

use Kamelya\Core\Migration;

return new class implements Migration {
    public function up(PDO $baglanti): void
    {
        $baglanti->exec("ALTER TABLE urunler
            ADD COLUMN cati_tipi VARCHAR(60) NULL AFTER alan_varsayilan,
            ADD COLUMN korkuluk_malzeme VARCHAR(60) NULL AFTER cati_tipi,
            ADD COLUMN korkuluk_yukseklik_cm INT NULL AFTER korkuluk_malzeme,
            ADD KEY idx_urun_cati (cati_tipi),
            ADD KEY idx_urun_korkuluk (korkuluk_malzeme)");
        $baglanti->exec("ALTER TABLE urun_cevirileri
            ADD COLUMN cati_tipi_aciklama TEXT NULL AFTER seo_anahtar_kelimeler,
            ADD COLUMN korkuluk_aciklama TEXT NULL AFTER cati_tipi_aciklama");
    }

    public function down(PDO $baglanti): void
    {
        $baglanti->exec('ALTER TABLE urunler DROP KEY idx_urun_korkuluk');
        $baglanti->exec('ALTER TABLE urunler DROP KEY idx_urun_cati');
        $baglanti->exec('ALTER TABLE urunler DROP COLUMN korkuluk_yukseklik_cm');
        $baglanti->exec('ALTER TABLE urunler DROP COLUMN korkuluk_malzeme');
        $baglanti->exec('ALTER TABLE urunler DROP COLUMN cati_tipi');
        $baglanti->exec('ALTER TABLE urun_cevirileri DROP COLUMN korkuluk_aciklama');
        $baglanti->exec('ALTER TABLE urun_cevirileri DROP COLUMN cati_tipi_aciklama');
    }
};
