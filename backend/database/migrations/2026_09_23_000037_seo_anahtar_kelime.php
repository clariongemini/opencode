<?php

declare(strict_types=1);

use Kamelya\Core\Migration;

/**
 * B.1 — seo_verileri.anahtar_kelime kolonu + 72 kategori anahtar kelimesi.
 * Kaynak: F1.1 SEO/anahtar kelime stratejisi (YAPILACAKLAR.md F1.1 Adım 3)
 * + kategori_cevirileri dil bazlı isimler + geliştirici TR/EN örnekleri.
 * Geri alınabilir: down() kolonu kaldırır.
 */
return new class implements Migration {
    public function up(PDO $baglanti): void
    {
        $baglanti->exec(
            'ALTER TABLE seo_verileri ADD COLUMN anahtar_kelime VARCHAR(500) NULL AFTER meta_aciklama'
        );

        // [dil_kodu][referans_id] => virgülle ayrılmış anahtar kelimeler
        $soz = [
            'tr' => [
                1 => 'kare kamelya, kare kamelya fiyatları, kamelya modelleri',
                2 => 'altıgen kamelya, altıgen kamelya fiyatları, bahçe kamelyası',
                3 => 'dikdörtgen kamelya, dikdörtgen kamelya fiyatları, kamelya modelleri',
                4 => 'modern kamelya, modern kamelya modelleri, kamelya fiyatları',
                5 => 'klasik kamelya, klasik kamelya modelleri, çardak imalatı',
                6 => 'ahşap kamelya, ahşap kamelya fiyatları, çardak imalatı',
                7 => 'alüminyum kamelya, alüminyum kamelya fiyatları, kamelya fiyatları',
                8 => 'kompozit kamelya, kompozit kamelya fiyatları, kamelya fiyatları',
                9 => 'site bahçesi kamelyası, bahçe kamelyası, kamelya fiyatları',
                10 => 'restoran kamelyası, restoran çardak, kamelya fiyatları',
                11 => 'otel kamelyası, otel bahçe çardak, kamelya fiyatları',
                12 => 'belediye kamelyası, kamu alanı kamelya, çardak imalatı',
            ],
            'en' => [
                1 => 'square gazebo, gazebo prices, garden gazebo',
                2 => 'hexagonal gazebo, gazebo prices, garden gazebo',
                3 => 'rectangular gazebo, gazebo prices, garden gazebo',
                4 => 'modern gazebo, modern gazebo designs, gazebo prices',
                5 => 'classic gazebo, traditional gazebo, gazebo prices',
                6 => 'wooden gazebo, wood gazebo prices, garden gazebo',
                7 => 'aluminium gazebo, aluminium gazebo prices, gazebo prices',
                8 => 'composite gazebo, composite gazebo prices, gazebo prices',
                9 => 'residential complex gazebo, garden gazebo, gazebo prices',
                10 => 'restaurant gazebo, outdoor dining gazebo, gazebo prices',
                11 => 'hotel gazebo, hotel garden gazebo, gazebo prices',
                12 => 'municipal gazebo, public park gazebo, gazebo prices',
            ],
            'de' => [
                1 => 'quadratischer Pavillon, Pavillon Preise, Gartenpavillon',
                2 => 'sechseckiger Pavillon, Pavillon Preise, Gartenpavillon',
                3 => 'rechteckiger Pavillon, Pavillon Preise, Gartenpavillon',
                4 => 'moderner Pavillon, moderne Pavillon Modelle, Pavillon Preise',
                5 => 'klassischer Pavillon, klassische Pavillon Modelle, Pavillon Preise',
                6 => 'Holzpavillon, Holzpavillon Preise, Gartenpavillon',
                7 => 'Aluminium-Pavillon, Aluminium Pavillon Preise, Pavillon Preise',
                8 => 'Komposit-Pavillon, Komposit Pavillon Preise, Pavillon Preise',
                9 => 'Pavillon für Wohnanlagen, Gartenpavillon, Pavillon Preise',
                10 => 'Restaurant-Pavillon, Außengastronomie Pavillon, Pavillon Preise',
                11 => 'Hotel-Pavillon, Hotelgarten Pavillon, Pavillon Preise',
                12 => 'Kommunal-Pavillon, Pavillon für öffentliche Bereiche, Pavillon Preise',
            ],
            'fr' => [
                1 => 'gazebo carré, gazebo prix, tonnelle de jardin',
                2 => 'gazebo hexagonal, gazebo prix, tonnelle de jardin',
                3 => 'gazebo rectangulaire, gazebo prix, tonnelle de jardin',
                4 => 'gazebo moderne, gazebo moderne prix, gazebo prix',
                5 => 'gazebo classique, tonnelle classique, gazebo prix',
                6 => 'gazebo en bois, gazebo bois prix, tonnelle de jardin',
                7 => 'gazebo en aluminium, gazebo aluminium prix, gazebo prix',
                8 => 'gazebo composite, gazebo composite prix, gazebo prix',
                9 => 'gazebo pour copropriété, tonnelle de jardin, gazebo prix',
                10 => 'gazebo pour restaurant, tonnelle de restaurant, gazebo prix',
                11 => 'gazebo pour hôtel, tonnelle d\'hôtel, gazebo prix',
                12 => 'gazebo municipal, tonnelle espace public, gazebo prix',
            ],
            'it' => [
                1 => 'gazebo quadrato, gazebo prezzi, pergola da giardino',
                2 => 'gazebo esagonale, gazebo prezzi, pergola da giardino',
                3 => 'gazebo rettangolare, gazebo prezzi, pergola da giardino',
                4 => 'gazebo moderno, gazebo moderni prezzi, gazebo prezzi',
                5 => 'gazebo classico, gazebo classici prezzi, gazebo prezzi',
                6 => 'gazebo in legno, gazebo legno prezzi, pergola da giardino',
                7 => 'gazebo in alluminio, gazebo alluminio prezzi, gazebo prezzi',
                8 => 'gazebo composito, gazebo composito prezzi, gazebo prezzi',
                9 => 'gazebo per condomini, pergola da giardino, gazebo prezzi',
                10 => 'gazebo per ristoranti, gazebo ristorante prezzi, gazebo prezzi',
                11 => 'gazebo per hotel, gazebo hotel prezzi, gazebo prezzi',
                12 => 'gazebo comunali, gazebo spazio pubblico, gazebo prezzi',
            ],
            'ar' => [
                1 => 'كوش مربع, أسعار الكوش, كوش حدائق',
                2 => 'كوش سداسي, أسعار الكوش, كوش حدائق',
                3 => 'كوش مستطيل, أسعار الكوش, كوش حدائق',
                4 => 'كوش عصري, موديلات الكوش العصرية, أسعار الكوش',
                5 => 'كوش كلاسيكي, موديلات الكوش الكلاسيكية, أسعار الكوش',
                6 => 'كوش خشبي, أسعار الكوش الخشبي, كوش حدائق',
                7 => 'كوش ألمنيوم, أسعار الكوش الألمنيوم, أسعار الكوش',
                8 => 'كوش مركّب,أسعار الكوش المركّب, أسعار الكوش',
                9 => 'كوش حدائق المجمعات, كوش حدائق, أسعار الكوش',
                10 => 'كوش المطاعم, كوش خارجية للمطاعم, أسعار الكوش',
                11 => 'كوش الفنادق, كوش حدائق الفنادق, أسعار الكوش',
                12 => 'كوش البلدية, كوش المساحات العامة, أسعار الكوش',
            ],
        ];

        $ifade = $baglanti->prepare(
            'UPDATE seo_verileri SET anahtar_kelime = :kelime
             WHERE sayfa_tipi = :tip AND referans_id = :id AND dil_kodu = :dil'
        );
        foreach ($soz as $dil => $kategoriler) {
            foreach ($kategoriler as $katId => $kelime) {
                $ifade->execute([
                    ':kelime' => $kelime,
                    ':tip' => 'kategori',
                    ':id' => $katId,
                    ':dil' => $dil,
                ]);
            }
        }
    }

    public function down(PDO $baglanti): void
    {
        $baglanti->exec('ALTER TABLE seo_verileri DROP COLUMN anahtar_kelime');
    }
};
