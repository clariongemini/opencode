<?php

declare(strict_types=1);

use Kamelya\Core\Migration;

/**
 * F16.2.4-fix — Dil kodu tip normalizasyonu: varchar(20) → varchar(5).
 *
 * Sebep: 000044 migration sırasında manuel ALTER ile dil_kodu sütunu
 * varchar(5) → varchar(20) genişletilmişti. Gerçekten gerekli değildi:
 * x-default yalnızca hreflang_json JSON anahtarıydı, dil_kodu sütununa
 * asla değer girilmiyordu (tüm değerler tr/en/de/fr/it/ar, 2-3 char).
 *
 * Bu migration, şema tutarlılığını geri getirir: 14/14 dil_kodu sütunu
 * varchar(5). Fresh deploy güvenliği sağlanır.
 *
 * up(): blog_yazisi_cevirileri + seo_verileri → varchar(5)
 * down(): varchar(5) → varchar(20) (geçici geri alma)
 * Nested transaction YOK (ALTER DDL örtük commit).
 */
return new class implements Migration
{
    public function up(PDO $baglanti): void
    {
        $baglanti->exec('ALTER TABLE blog_yazisi_cevirileri MODIFY COLUMN dil_kodu VARCHAR(5) COLLATE utf8mb4_unicode_ci NOT NULL');
        $baglanti->exec('ALTER TABLE seo_verileri MODIFY COLUMN dil_kodu VARCHAR(5) COLLATE utf8mb4_unicode_ci NOT NULL');
    }

    public function down(PDO $baglanti): void
    {
        $baglanti->exec('ALTER TABLE blog_yazisi_cevirileri MODIFY COLUMN dil_kodu VARCHAR(20) COLLATE utf8mb4_unicode_ci NOT NULL');
        $baglanti->exec('ALTER TABLE seo_verileri MODIFY COLUMN dil_kodu VARCHAR(20) COLLATE utf8mb4_unicode_ci NOT NULL');
    }
};
