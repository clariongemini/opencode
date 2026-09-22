<?php

declare(strict_types=1);

use Kamelya\Core\Migration;

return new class implements Migration {
    public function up(PDO $baglanti): void
    {
        $baglanti->exec("ALTER TABLE kullanicilar
            ADD COLUMN unvan VARCHAR(100) NULL AFTER ekip_mi,
            ADD COLUMN biyografi TEXT NULL AFTER unvan,
            ADD COLUMN fotograf_yolu VARCHAR(500) NULL AFTER biyografi,
            ADD COLUMN uzmanlik_alani VARCHAR(190) NULL AFTER fotograf_yolu,
            ADD COLUMN public_goster TINYINT(1) NOT NULL DEFAULT 0 AFTER uzmanlik_alani");
    }

    public function down(PDO $baglanti): void
    {
        $baglanti->exec('ALTER TABLE kullanicilar DROP COLUMN public_goster');
        $baglanti->exec('ALTER TABLE kullanicilar DROP COLUMN uzmanlik_alani');
        $baglanti->exec('ALTER TABLE kullanicilar DROP COLUMN fotograf_yolu');
        $baglanti->exec('ALTER TABLE kullanicilar DROP COLUMN biyografi');
        $baglanti->exec('ALTER TABLE kullanicilar DROP COLUMN unvan');
    }
};
