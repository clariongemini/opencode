<?php

declare(strict_types=1);

use Kamelya\Core\Migration;

/**
 * F16.2.8 — Garanti anahtar seed (3 × 6-dil JSON).
 *
 * anahtar UNIQUE (uq_ayar_anahtar) → ON DUPLICATE KEY UPDATE idempotent.
 * migrate.php transaction sarmalar — burada beginTransaction YOK.
 *
 * down(): yalnızca bu 3 anahtar silinir (demo_icerik vb. dokunulmaz).
 */
return new class implements Migration {
    public function up(PDO $baglanti): void
    {
        $ifade = $baglanti->prepare(
            'INSERT INTO ayarlar (anahtar, deger, aciklama) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE
              deger = VALUES(deger),
              aciklama = VALUES(aciklama)'
        );

        foreach (self::icerik() as $satir) {
            $ifade->execute([$satir[0], $satir[1], $satir[2]]);
        }
    }

    public function down(PDO $baglanti): void
    {
        $ifade = $baglanti->prepare(
            "DELETE FROM ayarlar WHERE anahtar IN ('garanti_suresi','garanti_kapsami','garanti_istisnalari')"
        );
        $ifade->execute();
    }

    /** @return array<int, array{0:string,1:string,2:string}> */
    private static function icerik(): array
    {
        $suresi = (string) json_encode([
            'tr' => '5 yıl',
            'en' => '5 years',
            'de' => '5 Jahre',
            'fr' => '5 ans',
            'it' => '5 anni',
            'ar' => '5 سنوات',
        ], JSON_UNESCAPED_UNICODE);

        $kapsami = (string) json_encode([
            'tr' => 'Taşıyıcı iskelet, çatı kaplaması, korkuluk ve montaj işçiliği',
            'en' => 'Load-bearing frame, roof covering, railing and installation workmanship',
            'de' => 'Tragendes Gerüst, Dacheindeckung, Geländer und Montagearbeiten',
            'fr' => 'Structure porteuse, couverture de toit, garde-corps et main-d\'œuvre d\'installation',
            'it' => 'Struttura portante, copertura del tetto, ringhiera e manodopera di installazione',
            'ar' => 'الهيكل الحامل، تغطية السقف، الدرابزين وأعمال التركيب',
        ], JSON_UNESCAPED_UNICODE);

        $istisnalari = (string) json_encode([
            'tr' => 'Doğal afet, kullanıcı müdahalesi, bakım eksikliği, ticari yoğun kullanım',
            'en' => 'Natural disasters, user intervention, lack of maintenance, intensive commercial use',
            'de' => 'Naturkatastrophen, Eingriffe des Nutzers, mangelnde Wartung, intensive gewerbliche Nutzung',
            'fr' => 'Catastrophes naturelles, intervention de l\'utilisateur, manque d\'entretien, usage commercial intensif',
            'it' => 'Calamità naturali, intervento dell\'utente, mancanza di manutenzione, uso commerciale intensivo',
            'ar' => 'الكوارث الطبيعية، تدخل المستخدم، نقص الصيانة، الاستخدام التجاري المكثف',
        ], JSON_UNESCAPED_UNICODE);

        return [
            ['garanti_suresi', $suresi, 'Garanti süresi (6 dil JSON). F16.2.8.'],
            ['garanti_kapsami', $kapsami, 'Garanti kapsamı (6 dil JSON). F16.2.8.'],
            ['garanti_istisnalari', $istisnalari, 'Garanti istisnaları (6 dil JSON). F16.2.8.'],
        ];
    }
};
