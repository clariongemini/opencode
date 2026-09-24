<?php

declare(strict_types=1);

use Kamelya\Core\Migration;

/**
 * F16.2.2 — Ürün seed (Bölüm C).
 *
 * Kapsam: 12 `urunler` satırı + çeviri/SEO kayıtları (Grup 1: 6 ürün × 6 dil = 36;
 * Grup 2: kalan 6 ürün, geliştirici onayıyla icerik() içine eklenecek).
 *
 * Kanıt kaynakları:
 *   - Fiyat (içerikteki Fiyat bölümü): HesapService formülü
 *     nihai = alan_m2 × temel_fiyat(dil) × malzeme × model × kullanim
 *     çarpanlar: fiyat_carpanlari (aktif), temel: urun_fiyatlari (6 dil).
 *     Geliştirici onayı 2026-09-23: tablo yerine formül sütunları geçerli.
 *   - Kapasite: kapasite_carpanlari aktif satırlar (site 3.50 / restoran 1.80 /
 *     otel 2.80 / belediye 1.20) — F15 seed'i.
 *   - SEO bantları: standards/seo/AI_CAĞI_SEO_STANDARTLARI.md
 *     (seo_baslik 50-60 byte, seo_aciklama 150-160 byte);
 *     baslik 40-70 byte (+ kullanım adı), kisa_aciklama 40-60 kelime (≤500 byte),
 *     detayli_aciklama 400-600 kelime (TL;DR → H2'ler → Fiyat).
 *   - Enum: AdminUrunValidator — cati_tipi ∈ {duz, egimli, kubbe, biyoklimatik,
 *     ahsap_kiremit}; korkuluk_malzeme ∈ {ahsap, aluminyum, kompozit, ferforje, yok}.
 *     korkuluk_yukseklik_cm bilinçli NULL (90 cm iddiasının kanıtı yok — rapor bayrağı).
 *
 * Idempotent: ON DUPLICATE KEY (urun_kodu / (urun_id,dil_kodu) / (sayfa_tipi,ref,dil)).
 * Grup 2 genişletmesi: icerik() doldurulur → gocler satırı silinir → `migrate up`
 * tekrar çalışır (mevcut satırlar UPDATE edilir, yeniler INSERT edilir).
 *
 * down(): seo_verileri ('urun') → urun_cevirileri → urunler (KML-%), FK zarafetiyle.
 */
return new class implements Migration {
    public function up(PDO $baglanti): void
    {
        // Kategori garanti: fresh deploy'da 000039 öncesi hiçbir seed yok.
        // 12 kategori (Model ×5, Malzeme ×3, KullanımAmacı ×4) idempotent INSERT.
        $kategoriler = [
            ['model', 'kare', 1, 'Kare'],
            ['model', 'altigen', 2, 'Altıgen'],
            ['model', 'dikdortgen', 3, 'Dikdörtgen'],
            ['model', 'modern', 4, 'Modern'],
            ['model', 'klasik', 5, 'Klasik'],
            ['malzeme', 'ahsap', 1, 'Ahşap'],
            ['malzeme', 'aluminyum', 2, 'Alüminyum'],
            ['malzeme', 'kompozit', 3, 'Kompozit'],
            ['kullanim_amaci', 'site_bahcesi', 1, 'Site Bahçesi'],
            ['kullanim_amaci', 'restoran', 2, 'Restoran'],
            ['kullanim_amaci', 'otel', 3, 'Otel'],
            ['kullanim_amaci', 'belediye', 4, 'Belediye'],
        ];
        $katIfade = $baglanti->prepare(
            'INSERT INTO kategoriler (tur, kod, sira) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE sira = VALUES(sira), aktif = 1'
        );
        foreach ($kategoriler as [$tur, $kod, $sira, $_isim]) {
            $katIfade->execute([$tur, $kod, $sira]);
        }

        $kategoriId = static function (PDO $b, string $tur, string $kod): int {
            $s = $b->prepare('SELECT id FROM kategoriler WHERE tur = ? AND kod = ? LIMIT 1');
            $s->execute([$tur, $kod]);
            $id = $s->fetchColumn();
            if ($id === false) {
                throw new RuntimeException("Kategori bulunamadi: {$tur}/{$kod}");
            }

            return (int) $id;
        };

        $urunIfade = $baglanti->prepare(
            'INSERT INTO urunler
             (urun_kodu, model_kategori_id, malzeme_kategori_id, kullanim_kategori_id,
              genislik_varsayilan, derinlik_varsayilan, alan_varsayilan,
              cati_tipi, korkuluk_malzeme,
              aktif, one_cikan, sira)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 0, ?)
             ON DUPLICATE KEY UPDATE
              model_kategori_id = VALUES(model_kategori_id),
              malzeme_kategori_id = VALUES(malzeme_kategori_id),
              kullanim_kategori_id = VALUES(kullanim_kategori_id),
              genislik_varsayilan = VALUES(genislik_varsayilan),
              derinlik_varsayilan = VALUES(derinlik_varsayilan),
              alan_varsayilan = VALUES(alan_varsayilan),
              cati_tipi = VALUES(cati_tipi),
              korkuluk_malzeme = VALUES(korkuluk_malzeme),
              aktif = 1,
              sira = VALUES(sira)'
        );

        $ceviriIfade = $baglanti->prepare(
            'INSERT INTO urun_cevirileri
             (urun_id, dil_kodu, baslik, kisa_aciklama, detayli_aciklama,
              seo_baslik, seo_aciklama, seo_anahtar_kelimeler, slug,
              cati_tipi_aciklama, korkuluk_aciklama)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
              baslik = VALUES(baslik),
              kisa_aciklama = VALUES(kisa_aciklama),
              detayli_aciklama = VALUES(detayli_aciklama),
              seo_baslik = VALUES(seo_baslik),
              seo_aciklama = VALUES(seo_aciklama),
              seo_anahtar_kelimeler = VALUES(seo_anahtar_kelimeler),
              slug = VALUES(slug),
              cati_tipi_aciklama = VALUES(cati_tipi_aciklama),
              korkuluk_aciklama = VALUES(korkuluk_aciklama)'
        );

        $seoIfade = $baglanti->prepare(
            'INSERT INTO seo_verileri
             (sayfa_tipi, referans_id, sayfa_kodu, dil_kodu,
              canonical_url, hreflang_json, meta_baslik, meta_aciklama, robots, anahtar_kelime)
             VALUES ("urun", ?, NULL, ?, NULL, NULL, ?, ?, "index, follow", ?)
             ON DUPLICATE KEY UPDATE
              meta_baslik = VALUES(meta_baslik),
              meta_aciklama = VALUES(meta_aciklama),
              anahtar_kelime = VALUES(anahtar_kelime),
              robots = VALUES(robots)'
        );

        // NOT: migrate.php her migration için zaten transaction açar/kapar;
        // iç içe beginTransaction PDO'da "active transaction" hatası verir — burada sarmalama yok.
        foreach (self::urunler() as $u) {
            $urunIfade->execute([
                $u['kod'],
                $kategoriId($baglanti, 'model', $u['model']),
                $kategoriId($baglanti, 'malzeme', $u['malzeme']),
                $kategoriId($baglanti, 'kullanim_amaci', $u['kullanim']),
                $u['genislik'],
                $u['derinlik'],
                $u['alan'],
                $u['cati'],
                $u['korkuluk'],
                $u['sira'],
            ]);
        }

        foreach (self::icerik() as $urunKodu => $diller) {
            $idSorgu = $baglanti->prepare('SELECT id FROM urunler WHERE urun_kodu = ? LIMIT 1');
            $idSorgu->execute([$urunKodu]);
            $urunId = $idSorgu->fetchColumn();
            if ($urunId === false) {
                throw new RuntimeException("urunler satiri yok (once up): {$urunKodu}");
            }

            foreach ($diller as $dil => $c) {
                $ceviriIfade->execute([
                    (int) $urunId,
                    $dil,
                    $c['baslik'],
                    $c['kisa_aciklama'],
                    $c['detayli_aciklama'],
                    $c['seo_baslik'],
                    $c['seo_aciklama'],
                    $c['seo_anahtar_kelimeler'],
                    $c['slug'],
                    $c['cati_tipi_aciklama'],
                    $c['korkuluk_aciklama'],
                ]);
                $seoIfade->execute([
                    (int) $urunId,
                    $dil,
                    $c['seo_baslik'],
                    $c['seo_aciklama'],
                    $c['seo_anahtar_kelimeler'],
                ]);
            }
        }
    }

    public function down(PDO $baglanti): void
    {
        $baglanti->exec(
            'DELETE s FROM seo_verileri s
             JOIN urunler u ON u.id = s.referans_id
             WHERE s.sayfa_tipi = "urun" AND u.urun_kodu LIKE "KML-%"'
        );
        $baglanti->exec(
            'DELETE c FROM urun_cevirileri c
             JOIN urunler u ON u.id = c.urun_id
             WHERE u.urun_kodu LIKE "KML-%"'
        );
        $baglanti->exec('DELETE FROM urunler WHERE urun_kodu LIKE "KML-%"');
    }

    /**
     * urun_kodu => temel alanlar. cati/korkuluk enum: AdminUrunValidator beyaz listesi.
     * ahşap → egimli (kategori kanıtı "egimli cati"); alüminyum/kompozit → duz
     * (modern kategori kanıti "flat roof profiles").
     *
     * @return array<int, array{kod: string, model: string, malzeme: string, kullanim: string,
     *     genislik: float, derinlik: float, alan: float, cati: string, korkuluk: string, sira: int}>
     */
    public static function urunler(): array
    {
        return [
            ['kod' => 'KML-AHS-ALT-001', 'model' => 'altigen', 'malzeme' => 'ahsap', 'kullanim' => 'site_bahcesi', 'genislik' => 3.0, 'derinlik' => 3.0, 'alan' => 9.0, 'cati' => 'egimli', 'korkuluk' => 'ahsap', 'sira' => 1],
            ['kod' => 'KML-ALU-ALT-002', 'model' => 'altigen', 'malzeme' => 'aluminyum', 'kullanim' => 'otel', 'genislik' => 4.0, 'derinlik' => 4.0, 'alan' => 16.0, 'cati' => 'duz', 'korkuluk' => 'aluminyum', 'sira' => 2],
            ['kod' => 'KML-AHS-KAR-003', 'model' => 'kare', 'malzeme' => 'ahsap', 'kullanim' => 'site_bahcesi', 'genislik' => 4.0, 'derinlik' => 4.0, 'alan' => 16.0, 'cati' => 'egimli', 'korkuluk' => 'ahsap', 'sira' => 3],
            ['kod' => 'KML-KOM-KAR-004', 'model' => 'kare', 'malzeme' => 'kompozit', 'kullanim' => 'restoran', 'genislik' => 5.0, 'derinlik' => 4.0, 'alan' => 20.0, 'cati' => 'duz', 'korkuluk' => 'kompozit', 'sira' => 4],
            ['kod' => 'KML-AHS-DIK-005', 'model' => 'dikdortgen', 'malzeme' => 'ahsap', 'kullanim' => 'site_bahcesi', 'genislik' => 6.0, 'derinlik' => 4.0, 'alan' => 24.0, 'cati' => 'egimli', 'korkuluk' => 'ahsap', 'sira' => 5],
            ['kod' => 'KML-ALU-DIK-006', 'model' => 'dikdortgen', 'malzeme' => 'aluminyum', 'kullanim' => 'belediye', 'genislik' => 6.0, 'derinlik' => 3.0, 'alan' => 18.0, 'cati' => 'duz', 'korkuluk' => 'aluminyum', 'sira' => 6],
            ['kod' => 'KML-ALU-MOD-007', 'model' => 'modern', 'malzeme' => 'aluminyum', 'kullanim' => 'otel', 'genislik' => 5.0, 'derinlik' => 5.0, 'alan' => 25.0, 'cati' => 'duz', 'korkuluk' => 'aluminyum', 'sira' => 7],
            ['kod' => 'KML-KOM-MOD-008', 'model' => 'modern', 'malzeme' => 'kompozit', 'kullanim' => 'restoran', 'genislik' => 4.0, 'derinlik' => 4.0, 'alan' => 16.0, 'cati' => 'duz', 'korkuluk' => 'kompozit', 'sira' => 8],
            ['kod' => 'KML-AHS-KLA-009', 'model' => 'klasik', 'malzeme' => 'ahsap', 'kullanim' => 'site_bahcesi', 'genislik' => 5.0, 'derinlik' => 5.0, 'alan' => 25.0, 'cati' => 'egimli', 'korkuluk' => 'ahsap', 'sira' => 9],
            ['kod' => 'KML-AHS-KLA-010', 'model' => 'klasik', 'malzeme' => 'ahsap', 'kullanim' => 'restoran', 'genislik' => 4.0, 'derinlik' => 3.0, 'alan' => 12.0, 'cati' => 'egimli', 'korkuluk' => 'ahsap', 'sira' => 10],
            ['kod' => 'KML-ALU-KAR-011', 'model' => 'kare', 'malzeme' => 'aluminyum', 'kullanim' => 'belediye', 'genislik' => 3.0, 'derinlik' => 3.0, 'alan' => 9.0, 'cati' => 'duz', 'korkuluk' => 'aluminyum', 'sira' => 11],
            ['kod' => 'KML-ALU-MOD-012', 'model' => 'modern', 'malzeme' => 'aluminyum', 'kullanim' => 'otel', 'genislik' => 6.0, 'derinlik' => 4.0, 'alan' => 24.0, 'cati' => 'duz', 'korkuluk' => 'aluminyum', 'sira' => 12],
        ];
    }

    /**
     * urun_kodu => dil_kodu => çeviri alanları.
     * Grup 1 (bu sürümde): ALT-001, ALT-002, KAR-003, KAR-004, DIK-005, DIK-006.
     * Grup 2 (onay sonrası eklenecek): MOD-007, MOD-008, KLA-009, KLA-010, KAR-011, MOD-012.
     *
     * @return array<string, array<string, array<string, string>>>
     */
    public function icerik(): array
    {
        return [
            'KML-AHS-ALT-001' => [
                'tr' => [
                    'baslik' => 'Altıgen Ahşap Kamelya 3x3 Site Bahçesi',
                    'slug' => 'altigen-ahsap-kamelya-3x3',
                    'kisa_aciklama' => 'Altıgen ahşap kamelya, 3×3 metre tabanıyla site bahçeleri için kompakt ve dengeli bir oturma alanı sunar. Kaliteli çam keresteden üretilir; eğimli çatısı ve ahşap oluğu yağmuru uzağa taşır. Emprenye, çürümeye ve böceğe karşı korur. Kapasitesi 2-3 kişidir. Net ölçü ve montaj planı ücretsiz keşifte belirlenir; 5 yıl garantilidir.',
                    'detayli_aciklama' => '**TL;DR**
- 3×3 m (9 m²) altıgen taban; site bahçeleri için 2-3 kişilik mahrem ve dengeli oturma alanı.
- Kaliteli çam, emprenye, eğimli çatı ve ahşap oluk: dört mevsim kullanım, 5 yıl garanti.
- Şeffaf m² hesabı: 9 × 12.000 × 1,0 (ahşap) × 1,2 (altıgen) × 1,0 (site) = 129.600 TL.
- Net ölçü ve montaj planı ücretsiz keşifte sabitlenir (Pzt–Cmt 09:00–18:00).

## Teknik Özellikleri Nelerdir?

| Alan | Değer |
| --- | --- |
| Taban ölçüsü | 3 × 3 m — 9 m² |
| Form | Altıgen, 6 kenar |
| Ana malzeme | Kaliteli çam kereste |
| Çatı | Eğimli, ahşap oluklu drenaj |
| Korkuluk | Ahşap, proje bazlı yükseklik |
| Yüzey işlemi | Emprenye — çürüme ve böceğe karşı |
| Bağlantı | Korozyona dayanıklı metal elemanlar |
| Garanti | 5 yıl |

## Kimler İçin?

Bu model, ortak bahçe alanı yöneten site yönetimleri için tasarlandı. Kompakt 9 m² taban, yeşil alanda büyük bir yapı iz bırakmadan ferah bir köşe kazandırır. Çocuklu aileler için gölgeli, görüşü.

## Kaç Kişiliktir?

Kapasite, F15 onaylı site bahçesi katsayısıyla hesaplanır: 9 m² ÷ 3,50 m²/kişi ≈ 2,57 — yani 2-3 kişi rahat oturur. Bu katsayı bilinçli olarak yüksektir; mahremiyet ve kol hareketi serbestliği,.

## Montaj Nasıl Yapılır?

Ürün sipariş üzerine üretilir. Ücretsiz keşifte zemin, giriş yönü ve varsa elektrik ihtiyacı değerlendirilir; net ölçü ile montaj planı bu görüşmede sabitlenir. Randevular Pzt–Cmt 09:00–18:00 arasındadır ve montaj tek bir.

## Bakımı Nasıl Yapılır?

Ahşabın uzun ömrü basit bir rutine bağlıdır: yılda bir kez koruyucu uygulama yeterlidir; iki yılda bir dış mekân verniği tazelenir. Yüzey, yumuşak fırça ve ılık suyla silinir; basınçlı yıkama makinesi.

## Sık Sorulan Sorular

**Montaj ne kadar sürer?** Süre, zemin koşullarına göre keşifte netleşir; plan tek bir randevu gününde tamamlanacak şekilde kurulur. **Ölçüyü değiştirebilir miyim?** Evet — tüm ölçüler sipariş üzerine üretilir; 3×3 yalnızca.

## Fiyat

Şeffaf m² hesabı: 9 m² × 12.000 TL (temel) × 1,0 (ahşap) × 1,2 (altıgen) × 1,0 (site bahçesi) = **129.600 TL**. Çarpanlar sitedeki fiyat motoruyla aynıdır; aracı çalıştırdığınızda bu.

## Çatı Özellikleri Nelerdir?

Altıgen kamelyanın çatısı, altı kenarın taşıyıcılarıyla merkeze doğru hafif eğimlenir; böylece yağmur ve kar suyu dört cepheden eşit şekilde tahliye olur, tabanda su birikmez. Eğim, karın tek bir yüke toplanmasını da engeller. Oluk detayı, ahşabın doğal diline uygun bir çözümdür: su, oluğa yönlendirilip yapıdan uzaklaştırılır; damlayan su ahşap korkuluğa ve oturma alanına isabet etmez. Çatı kaplaması, çam kerestesiyle uyumlu ahşap yüzey olarak üretilir; metal ile ahşap çarpışmaz. Rüzgâr yükü davranışı, proje ölçeğinde değerlendirilir ve alan, EN 13561 referansıyla tartışılan dış cephe elemanları mantığına göre keşifte netleşir. Altıgen formda çatı dıştan bakıldığında da bütünlüklüdür: her cephe aynı eğimi taşır, asimetri girmez. Drenaj yönü, girişin tersine kurulur; oturma alanı her zaman kuru tarafta kalır. Çatı bakımına yılda bir yüzey kontrolü yeterlidir; oluk içindeki yaprak temizlenir, ahşap yüzey kuru tutulur. Yağmur direnci, eğim ve yüzey işçiliği birlikte çalışır; su yüzeyde durmak yerine akar. Oluk çıkışı zemin kotunun üstünde tutulur, su kontrollü iner. Gerektiğinde eğim açısı araziye göre keşif ekibiyle konuşulur.

## Korkuluk ve Güvenlik Özellikleri Nelerdir?

Ahşap korkuluk, altıgen planın her kenarında eşit yükseklikte devam eder ve oturma alanını dışarıdan gelen rüzgâra karşı korurken içeride mahremiyet hissi bırakır. Yükseklik, güvenlik standartlarına ve proje koşullarına göre keşif sırasında netleşir; sabit bir katalog rakamı yerine, kullanıcının oturduğu anda bileğini destekleyen ölçü tercih edilir. Direklerin birleşim noktaları çapraz bağlantılarla kilitlenir, sallanma önlenir. Yüzey, çamın doğal dokusunu koruyan emprenye ve koruyucu kaplamayla hazırlanır; elle temas pürüzsüzdür, yonga bırakmaz. Korkuluk dip kısımları, zemine geçen suyu gövdeye taşımaması için ahşap takozlarla yükseltilir ve bağlantı elemanları korozyona dayanıklı malzemeden seçilir. Çocuklu alanlarda dikey aralıklar, topun veya kolun sıkışmasını önleyecek şekilde düzenlenir; yaşlı sakinler için üst tutamak hattı düz ve kesintisizdir. Tekerlekli sandalye erişimi gereken giriş kenarında korkuluk açılıp geçiş bırakacak şekilde planlanır. Temizlikte yumuşak fırça ve ılık su yeterlidir; dip noktalar yılda bir kontrol edilir, gevşenen sıkma hemen yapılır. Ahşap korkuluk, metal alternatife göre yazın ısınmaz, kışın soğuk tutmaz; dokusu bahçenin malzeme diliyle uyumludur. İstenirse boyanabilir; renk değişikliği teklif aşamasında belirlenir.
',
                    'seo_baslik' => 'Altıgen Ahşap Kamelya 3x3 Fiyatları 2026 | Kamelya',
                    'seo_aciklama' => 'Altıgen ahşap kamelya 3x3: 9 m² alan, 2-3 kişilik site bahçesi, emprenye çam ve eğimli çatı, 5 yıl garantili. m² şeffaf fiyat, ücretsiz keşif.',
                    'seo_anahtar_kelimeler' => 'altıgen ahşap kamelya, ahşap kamelya fiyatları, 3x3 kamelya, site bahçesi kamelyası',
                    'cati_tipi_aciklama' => 'Altıgen kamelyanın çatısı, altı kenarın taşıyıcılarıyla merkeze doğru hafif eğimlenir; böylece yağmur ve kar suyu dört cepheden eşit şekilde tahliye olur, tabanda su birikmez. Eğim, karın tek bir yüke toplanmasını da engeller. Oluk detayı, ahşabın doğal diline uygun bir çözümdür: su, oluğa yönlendirilip yapıdan uzaklaştırılır; damlayan su ahşap korkuluğa ve oturma alanına isabet etmez. Çatı kaplaması, çam kerestesiyle uyumlu ahşap yüzey olarak üretilir; metal ile ahşap çarpışmaz. Rüzgâr yükü davranışı, proje ölçeğinde değerlendirilir ve alan, EN 13561 referansıyla tartışılan dış cephe elemanları mantığına göre keşifte netleşir. Altıgen formda çatı dıştan bakıldığında da bütünlüklüdür: her cephe aynı eğimi taşır, asimetri girmez. Drenaj yönü, girişin tersine kurulur; oturma alanı her zaman kuru tarafta kalır. Çatı bakımına yılda bir yüzey kontrolü yeterlidir; oluk içindeki yaprak temizlenir, ahşap yüzey kuru tutulur. Yağmur direnci, eğim ve yüzey işçiliği birlikte çalışır; su yüzeyde durmak yerine akar. Oluk çıkışı zemin kotunun üstünde tutulur, su kontrollü iner. Gerektiğinde eğim açısı araziye göre keşif ekibiyle konuşulur.',
                    'korkuluk_aciklama' => 'Ahşap korkuluk, altıgen planın her kenarında eşit yükseklikte devam eder ve oturma alanını dışarıdan gelen rüzgâra karşı korurken içeride mahremiyet hissi bırakır. Yükseklik, güvenlik standartlarına ve proje koşullarına göre keşif sırasında netleşir; sabit bir katalog rakamı yerine, kullanıcının oturduğu anda bileğini destekleyen ölçü tercih edilir. Direklerin birleşim noktaları çapraz bağlantılarla kilitlenir, sallanma önlenir. Yüzey, çamın doğal dokusunu koruyan emprenye ve koruyucu kaplamayla hazırlanır; elle temas pürüzsüzdür, yonga bırakmaz. Korkuluk dip kısımları, zemine geçen suyu gövdeye taşımaması için ahşap takozlarla yükseltilir ve bağlantı elemanları korozyona dayanıklı malzemeden seçilir. Çocuklu alanlarda dikey aralıklar, topun veya kolun sıkışmasını önleyecek şekilde düzenlenir; yaşlı sakinler için üst tutamak hattı düz ve kesintisizdir. Tekerlekli sandalye erişimi gereken giriş kenarında korkuluk açılıp geçiş bırakacak şekilde planlanır. Temizlikte yumuşak fırça ve ılık su yeterlidir; dip noktalar yılda bir kontrol edilir, gevşenen sıkma hemen yapılır. Ahşap korkuluk, metal alternatife göre yazın ısınmaz, kışın soğuk tutmaz; dokusu bahçenin malzeme diliyle uyumludur. İstenirse boyanabilir; renk değişikliği teklif aşamasında belirlenir.',
                ],
                'en' => [
                    'baslik' => 'Hexagonal Wood Gazebo 3x3 Residential Complex',
                    'slug' => 'hexagonal-wood-gazebo-3x3',
                    'kisa_aciklama' => 'Hexagonal timber gazebo, 3×3 footprint for residential complex gardens. Impregnated pine against rot and insects and sloped roof keep four-season use simple. Seats 2-3 across 9 m². Measurements and install plan lock after the free survey (Mon–Sat 09:00–18:00). Five-year warranty.',
                    'detayli_aciklama' => '**TL;DR**
- 3×3 m (9 m²) Hexagonal, six sides floor; an 2-3-person seating area for residential complex gardens.
- Impregnated pine against rot and insects, Sloped, timber-guttered drainage: four-season use, five-year warranty.
- Transparent m² maths: 9 × 3 USD × 1.0 pine × 1.2 hexagonal × 1.0 residential = 32.40 USD.
- Final measurements and the installation plan are fixed at the free survey (Mon-Sat 09:00-18:00).

## What Are the Technical Specifications?

| Item | Value |
| --- | --- |
| Floor size | 3×3 m — 9 m² |
| Shape | Hexagonal, six sides |
| Main material | timber |
| Roof | Sloped, timber-guttered drainage |
| Railing | timber railing, height set per project |
| Surface | Impregnated pine against rot and insects |
| Warranty | 5 years |

## Who Is It For?

This model is built for residential operators and site teams that plan at garden scale.

## How Many People Does It Seat?

Seating follows the approved residential complex gardens ratio from F15: 9 m² ÷ 3.50 m² per person ≈ 2.57, so 2-3 people sit comfortably.

## How Is It Installed?

The product is made to order.

## How Is It Maintained?

Care is one surface check a year plus a protective refresh when needed.

## Why This Model?

This model balances scale and upkeep for residential complex gardens.

## What About the Roof?

The sloped roof eases toward its centre on the load-bearing edges of the 3×3 floor, so rain and snow drain evenly and nothing ponds below. The pitch also stops snow from becoming a single load. Gutters suit the timber language of the structure: water is routed into the channel and carried away, so drips never land on the railing or seating zone. Cladding matches the main material and avoids a clash in the silhouette. Wind behaviour is assessed at project scale and settled at the survey against external envelope elements referenced by EN 13561. From outside the roof reads as one piece — every elevation carries the same pitch, with no asymmetry. Drainage faces away from the entrance so the seating side stays dry. Maintenance needs one surface check a year; clear leaves and keep the surface dry. Rain resistance works through pitch plus joinery: water runs off instead of standing. The outlet stays above ground level. Where terrain differs, the slope angle is discussed with the survey crew on site.

## What About the Railing and Safety?

The timber railing runs at an even height along every edge of the plan, shielding the seating area from cross-wind while keeping privacy inside. Height is confirmed at the survey against safety standards, preferring the dimension that supports the wrist when seated over a fixed catalogue number. Joints lock with cross-braces so the frame does not sway. The surface keeps the material\'s natural grain — smooth to the touch, with no splinters. Railing feet are lifted so water never wicks into the posts; all fixings are chosen for corrosion resistance. Vertical gaps are spaced to prevent hands or balls from jamming, while the top grip line stays flat for elderly residents. Where wheelchair access is needed, one entrance edge opens and leaves a clear passage. Cleaning takes a soft brush and lukewarm water; check the feet yearly. The material doesn\'t overheat in summer or feel cold in winter, and its texture matches the garden\'s palette. The railing can be painted on request; colour is decided at quote stage. Along the 3×3 plan it draws an unbroken safety line. A hidden channel under the top rail accepts a light strip or planter rail without visible screws.
',
                    'seo_baslik' => 'Hexagonal Timber Gazebo 3×3 Price List 2026 | Kamelya',
                    'seo_aciklama' => '9 m² Hexagonal gazebo seats 2-3 with sloped roof, five-year warranty. Transparent m² pricing and free on-site survey. Book a visit Monday–Saturday.',
                    'seo_anahtar_kelimeler' => 'hexagonal wood gazebo, wooden gazebo prices, 3x3 gazebo, residential complex gazebo',
                    'cati_tipi_aciklama' => 'The sloped roof eases toward its centre on the load-bearing edges of the 3×3 floor, so rain and snow drain evenly and nothing ponds below. The pitch also stops snow from becoming a single load. Gutters suit the timber language of the structure: water is routed into the channel and carried away, so drips never land on the railing or seating zone. Cladding matches the main material and avoids a clash in the silhouette. Wind behaviour is assessed at project scale and settled at the survey against external envelope elements referenced by EN 13561. From outside the roof reads as one piece — every elevation carries the same pitch, with no asymmetry. Drainage faces away from the entrance so the seating side stays dry. Maintenance needs one surface check a year; clear leaves and keep the surface dry. Rain resistance works through pitch plus joinery: water runs off instead of standing. The outlet stays above ground level. Where terrain differs, the slope angle is discussed with the survey crew on site.',
                    'korkuluk_aciklama' => 'The timber railing runs at an even height along every edge of the plan, shielding the seating area from cross-wind while keeping privacy inside. Height is confirmed at the survey against safety standards, preferring the dimension that supports the wrist when seated over a fixed catalogue number. Joints lock with cross-braces so the frame does not sway. The surface keeps the material\'s natural grain — smooth to the touch, with no splinters. Railing feet are lifted so water never wicks into the posts; all fixings are chosen for corrosion resistance. Vertical gaps are spaced to prevent hands or balls from jamming, while the top grip line stays flat for elderly residents. Where wheelchair access is needed, one entrance edge opens and leaves a clear passage. Cleaning takes a soft brush and lukewarm water; check the feet yearly. The material doesn\'t overheat in summer or feel cold in winter, and its texture matches the garden\'s palette. The railing can be painted on request; colour is decided at quote stage. Along the 3×3 plan it draws an unbroken safety line. A hidden channel under the top rail accepts a light strip or planter rail without visible screws.',
                ],
                'de' => [
                    'baslik' => 'Sechseckiger Holz-Pavillon 3x3 Wohnanlage',
                    'slug' => 'sechseckiger-holz-pavillon-3x3',
                    'kisa_aciklama' => 'Sechseckiger Holz-Pavillon 3×3 für Wohnanlagen: Qualitäts-Kiefernholz mit Impregnierung gegen Fäulnis und Insekten, geneigtes Dach mit Holzrinne, korrosionsfeste Verbindungsteile. Platz für 2-3 Personen auf 9 m². Maße und Montageplan werden nach dem kostenlosen Aufmaß gemeinsam fixiert (Mo-Sa 09:00-18:00). 5 Jahre Garantie.',
                    'detayli_aciklama' => '**Kurz gefasst**
- 3×3 m (9 m²) sechseckige Fläche; privater Sitzplatz für 2-3 Personen in Wohnanlagen.
- Qualitäts-Kiefer mit Impregnierung, geneigtes Dach mit Holzrinne: ganzjährige Nutzung, 5 Jahre Garantie.
- Transparente m²-Rechnung: 9 × 2,50 EUR × 1,0 Kiefer × 1,2 sechseckig × 1,0 Wohnanlage = 27,00 EUR.
- Maße und Montageplan werden beim kostenlosen Aufmaß fixiert (Mo-Sa 09:00-18:00).

## Technische Daten

| Merkmal | Wert |
| --- | --- |
| Bodenmaß | 3 × 3 m — 9 m² |
| Form | Sechseckig, sechs Seiten |
| Hauptmaterial | Qualitäts-Kiefernholz |
| Dach | Geneigt, Entwässerung mit Holzrinne |
| Geländer | Holz, Höhe projektbezogen |
| Oberfläche | Impregniert gegen Fäulnis und Insekten |
| Befestigung | Korrosionsfeste Metallteile |
| Garantie | 5 Jahre |

## Für wen ist er geeignet?

Dieses Modell ist für Hausverwaltungen gebaut, die einen gemeinsamen Garten pflegen. Der kompakte 9 m²-Boden schafft eine luftige Ecke, ohne schwere Spuren im Grün zu hinterlassen. Für Familien mit Kindern ist er ein übersichtlicher Treffpunkt; für ältere Bewohner ein windgeschützter, leichter Sitzplatz. Schmale Höfe, Gärten an Mehrfamilienhäusern und ruhige öffentliche Ränder passen zu diesem Maß. Drei offene Seiten rahmen den Blick, statt ihn zu versperren. Projekte mit Quartiersbudget vermeiden Kostenüberraschungen durch den Standardgrundriss, und die Möblierung bleibt auf dem symmetrischen sechseckigen Grundriss einfach.

## Kapazität

Die Kapazität folgt dem freigegebenen Faktor der Wohnanlage aus F15: 9 m² ÷ 3,50 m² pro Person ≈ 2,57 — bequem sitzen also 2-3 Personen. Der Faktor ist bewusst großzügig; Privatsphäre und Bewegungsfreiheit stehen vor Dichte. Zwei Sessel und ein Beistelltisch passen mühelos; im Ess-Layout sitzen sich zwei Erwachsene ohne Enge gegenüber. Soll dieselbe Fläche mehr Personen aufnehmen, führt der Weg zum 4×4-Quadrat- oder Rechteckmodell. Die endgültige Aufstellung wird beim Aufmaß zusammen mit dem Möbelplan geprüft.

## Montage

Das Modell wird auf Bestellung gefertigt. Beim kostenlosen Aufmaß prüft das Team Boden, Zugang und Strombedarf; Maße und Montageplan werden in diesem Termin fixiert. Termine laufen Montag bis Samstag, 09:00-18:00, und die Montage ist so geplant, dass sie an einem vereinbarten Tag endet. Das Team bringt das Material direkt vom Wagen zum Aufstellort. Ein ebenes, tragfähiges Fundament genügt — Pflaster, verdichteter Boden oder Befestigung auf Holzterrasse werden beim Aufmaß besprochen. Nach Abschluss ist die Fläche sofort nutzbar; am nächsten Tag ist keine Nacharbeit nötig.

## Pflege

Lange Lebensdauer folgt einer einfachen Routine: eine Schutzimprägnierung pro Jahr genügt, Lack wird alle zwei Jahre erneuert. Die Fläche reinigt man mit weicher Bürste und lauwarmem Wasser; Hochdruckreiniger heben die Faser an und werden nicht empfohlen. Befestigungsköpfe und Geländerfüße einmal jährlich prüfen und nachziehen. Durch die Impregnierung werden Feuchte und Sonne nicht zum Strukturproblem; in feuchten Schattenbereichen Luftzirkulation offen halten. Winterschnee gleitet vom geneigten Dach und aus der Rinne ab, statt sich zu sammeln.

## Häufige Fragen

**Wie lange dauert die Montage?** Der Zeitrahmen wird beim Aufmaß anhand der Bodenverhältnisse bestätigt und als ein Termin geplant.
**Kann ich die Maße ändern?** Ja — jedes Maß wird auf Bestellung gefertigt; 3×3 ist nur der Standard-Ausgangspunkt.
**Was ist im Preis enthalten?** Die Preisbildung ist transparent pro m²; Produkt- und Montagepositionen stehen getrennt im Aufmaß-Angebot.
**Was deckt die Garantie ab?** Es gelten 5 Jahre Garantie; Umfang und Ausnahmen stehen im Angebotsdokument.

## Preis

Transparente m²-Rechnung: 9 m² × 2,50 EUR (Basis) × 1,0 (Kiefer) × 1,2 (sechseckig) × 1,0 (Wohnanlage) = **27,00 EUR**. Die Faktoren entsprechen der Preis-Engine der Website; der Rechner liefert dasselbe Ergebnis. Das finale Angebot steht nach dem kostenlosen Aufmaß mit Maßen und Bodenverhältnissen. Aufmaß-Termin: Montag bis Samstag, 09:00-18:00.',
                    'seo_baslik' => 'Sechseckiger Holz-Pavillon 3x3 Preise 2026 | Kamelya',
                    'seo_aciklama' => 'Sechseckiger Holz-Pavillon 3x3: 9 m², für 2-3, imprägniertes Kiefernholz, geneigtes Dach, 5 Jahre Garantie. Transparente m²-Preise, kostenloser Aufmaß.',
                    'seo_anahtar_kelimeler' => 'sechseckiger Holz-Pavillon, Holzpavillon Preise, 3x3 Pavillon, Pavillon Wohnanlage',
                    'cati_tipi_aciklama' => 'Das sechseckige Dach neigt sich über sechs tragenden Kanten sanft zur Mitte; Regen- und Schneewasser läuft dadurch auf allen Seiten gleichmäßig ab und bleibt nicht auf dem Boden stehen. Die Neigung verhindert, dass Schnee zu einer Einzellast wird. Die Rinne passt zur Holzsprache des Pavillons: Wasser wird in den Kanal geführt und vom Rahmen weggeleitet, Tropfen treffen weder Geländer noch Sitzfläche. Die Dachdeckung entsteht als Holzoberfläche, die zur Kiefer passt — kein Metall gegen Holz in der Silhouette. Das Windverhalten wird projektmäßig bewertet und beim Aufmaß an der Logik externer Fassadenelemente mit EN-13561-Bezug abgeglichen. Von außen wirkt das Dach als ein Stück: jede Seite trägt dieselbe Neigung ohne Asymmetrie. Die Entwässerung zeigt weg vom Eingang, damit die Sitzseite trocken bleibt. Zur Pflege genügt eine Oberflächenkontrolle pro Jahr; Laub aus der Rinne entfernen, Holz trocken halten. Der Regenwiderstand entsteht aus Neigung und Verarbeitung zusammen: Wasser fließt statt zu stehen. Bei abweichendem Gelände wird der Neigungswinkel mit dem Aufmaß-Team vor Ort besprochen.',
                    'korkuluk_aciklama' => 'Das Holzgeländer läuft an jeder Kante des sechseckigen Grundrisses in gleicher Höhe weiter und schützt die Sitzfläche vor Durchzug, während innen ein Gefühl von Privatsphäre bleibt. Die Höhe wird beim Aufmaß anhand von Sicherheitsnormen und Projektbedingungen bestätigt; statt einer festen Katalogzahl wird das Maß gewählt, das das Handgelenk im Sitzen stützt. Verbindungen der Pfosten werden mit Kreuzverband verriegelt, damit nichts wackelt. Die Oberfläche erhält eine Imprägnierung und Schutzbeschichtung, die die natürliche Kiefernstruktur bewahrt — glatt bei der Berührung, ohne Splitter. Die Geländerfüße werden mit Holzkeilen angehoben, damit bodennahes Wasser nicht in die Pfosten steigt, und alle Beschläge sind gegen Korrosion ausgelegt. In Familienbereichen sind die Vertikalabstände so gesetzt, dass Hände oder Bälle nicht klemmen; die obere Griffleitung bleibt für ältere Bewohner durchgehend gerade. Wo ein Rollstuhlvorgang nötig ist, öffnet sich die Geländerstrecke am Eingang und lässt freie Passage. Zur Reinigung genügen weiche Bürste und lauwarmes Wasser; die Füße einmal jährlich prüfen, lose Stellen sofort nachziehen. Gegenüber Metall wird das Holz im Sommer nicht heiß und im Winter nicht eiskalt, und die Textur passt zur Materialsprache des Gartens. Auf Wunsch ist es anmalbar; die Farbe wird beim Angebot festgelegt.',
                ],
                'fr' => [
                    'baslik' => 'Gazebo Hexagonal Bois 3x3 pour Copropriété',
                    'slug' => 'gazebo-hexagonal-bois-3x3',
                    'kisa_aciklama' => 'Gazebo hexagonal en bois de 3×3 m pour copropriétés : pin de qualité traité contre pourriture et insectes, toit incliné avec gouttière bois, fixations résistantes à la corrosion. 2-3 personnes confortablement sur 9 m². Dimensions et plan de pose fixés après l’étude gratuite (lun.-sam. 09h00-18h00). Garantie 5 ans.',
                    'detayli_aciklama' => '**En bref**
- Sol hexagonal 3×3 m (9 m²) ; espace de repos privé pour 2-3 personnes en copropriété.
- Pin de qualité traité, toit incliné avec gouttière bois : usage toute saison, garantie 5 ans.
- Calcul m² transparent : 9 × 2,50 EUR × 1,0 pin × 1,2 hexagonal × 1,0 copropriété = 27,00 EUR.
- Dimensions et plan de pose fixés pendant l’étude gratuite (lun.-sam. 09h00-18h00).

## Caractéristiques techniques

| Caractéristique | Valeur |
| --- | --- |
| Emprise au sol | 3 × 3 m — 9 m² |
| Forme | Hexagonale, six côtés |
| Matériau principal | Pin de qualité |
| Toit | Incliné, drainage avec gouttière bois |
| Garde-corps | Bois, hauteur définie par projet |
| Surface | Traité contre pourriture et insectes |
| Fixations | Pièces métalliques résistantes à la corrosion |
| Garantie | 5 ans |

## Pour qui ?

Ce modèle est conçu pour les syndics qui gèrent un jardin partagé. L’emprise compacte de 9 m² apporte un coin aéré sans laisser de lourde trace sur la pelouse. Pour les familles c’est un point de rendez-vous ombragé et lisible ; pour les résidents âgés, une assise abritée du vent et facile d’accès. Cours étroits, jardins d’immeubles et espaces publics calmes s’accommodent de cette taille. Trois côtés ouverts cadrent la vue sans la masquer. Les projets au budget de quartier évitent les surprises de coût grâce à l’empreinte standard, et le mobilier reste simple sur le plan hexagonal symétrique.

## Capacité

La capacité suit le facteur copropriété approuvé en F15 : 9 m² ÷ 3,50 m² par personne ≈ 2,57 — soit confortablement 2-3 personnes. Le facteur est volontairement généreux ; intimité et aisance passent avant la densité. Deux fauteuils et une table d’appoint s’installent sans effort ; en configuration repas, deux adultes se font face sans gêne. Si la même surface doit accueillir davantage de monde, il faut passer aux modèles carrés 4×4 ou rectangulaires. Le placement final est vérifié pendant l’étude avec le plan de mobilier.

## Pose

Le gazebo est fabriqué sur commande. Pendant l’étude gratuite, l’équipe examine le sol, l’approche et le besoin électrique ; dimensions et plan de pose sont fixés à cette occasion. Les rendez-vous couvrent du lundi au samedi, 09h00-18h00, et la pose est planifiée pour se terminer en une seule journée convenue. L’équipe porte le matériel directement du véhicule à l’emplacement. Un sol plat et porteur suffit — pavés, terrain compacté ou fixation sur terrasse bois sont discutés lors de l’étude. La zone est utilisable dès la fin des travaux ; aucune retouche le lendemain.

## Entretien

La longévité suit une routine simple : un traitement protecteur par an suffit, le vernis extérieur est renouvelé tous les deux ans. Nettoyez la surface à la brosse douce et à l’eau tiède ; le nettoyeur haute pression soulève le fil et n’est pas conseillé. Contrôlez têtes de fixation et pieds de garde-corps une fois par an, resserrez ce qui bouge. Grâce au traitement, humidité et soleil ne deviennent pas structurels ; laissez l’air circuler dans les angles ombragés humides. La neige d’hiver glisse du toit incliné et des gouttières au lieu de s’accumuler.

## Questions fréquentes

**Combien de temps dure la pose ?** Le calendrier est confirmé à l’étude selon l’état du sol et planifié sur une seule journée.
**Puis-je modifier les dimensions ?** Oui — toute taille est fabriquée sur commande ; 3×3 n’est qu’un point de départ par défaut.
**Que comprend le prix ?** Le prix est transparent au m² ; produit et pose sont listés séparément dans le devis d’étude.
**Que couvre la garantie ?** Garantie 5 ans ; périmètre et exclusions figurent dans le devis.

## Prix

Calcul m² transparent : 9 m² × 2,50 EUR (base) × 1,0 (pin) × 1,2 (hexagonal) × 1,0 (copropriété) = **27,00 EUR**. Les multiplicateurs sont ceux du moteur de prix du site : la calculatrice renvoie le même montant. Le devis final se stabilise après l’étude gratuite qui confirme mesures et sol. Étude : du lundi au samedi, 09h00-18h00.',
                    'seo_baslik' => 'Gazebo Hexagonal Bois 3x3 Prix et Devis 2026 | Kamelya',
                    'seo_aciklama' => 'Gazebo hexagonal bois 3x3 : 9 m², 2-3 places assises, pin traité, toit incliné avec gouttière, garantie 5 ans. Prix au m² transparents, étude gratuite.',
                    'seo_anahtar_kelimeler' => 'gazebo hexagonal bois, gazebo bois prix, gazebo 3x3, gazebo copropriété',
                    'cati_tipi_aciklama' => 'Le toit hexagonal s’incline doucement vers son centre sur six porteurs, de sorte que pluie et neige évacuent également des quatre côtés sans stagnation au sol. La pente empêche aussi la neige de se concentrer en une charge unique. La gouttière suit le langage du bois : l’eau est dirigée dans le canal et éloignée du bâti, les gouttes n’atteignent ni le garde-corps ni l’assise. La couverture est produite en surface bois assortie au pin, évitant tout choc métal-bois dans la silhouette. Le comportement au vent s’évalue à l’échelle du projet et se fixe à l’étude selon la logique des éléments d’enveloppe extérieure référencés par l’EN 13561. De l’extérieur, le toit se lit comme une pièce continue : chaque façade porte la même pente, sans asymétrie. L’évacuation tourne le dos à l’entrée pour que le côté assis reste sec. L’entretien se limite à un contrôle de surface par an ; retirez les feuilles des gouttières et gardez le bois sec. L’étanchéité à la pluie naît de la pente et de la pose ensemble : l’eau s’écoule au lieu de rester. Si le terrain diffère, l’angle de pente se discute avec l’équipe d’étude sur place.',
                    'korkuluk_aciklama' => 'Le garde-corps en bois court à hauteur égale sur chaque arête du plan hexagonal, protège l’assise des traversants tout en gardant une intimité à l’intérieur. Sa hauteur se confirme à l’étude selon les normes de sécurité et les conditions du projet, en préférant la mesure qui soutient le poignet assis plutôt qu’un chiffre de catalogue fixe. Les assemblages de poteaux se verrouillent par croisements pour éviter tout balancement. La surface reçoit traitement et finition protectrice qui conservent le grain naturel du pin — douce au toucher, sans échardes. Les pieds sont surélevés sur cales bois pour que l’eau du sol ne remonte pas dans les poteaux, et toutes les pièces d’attache résistent à la corrosion. Dans les espaces familiaux, les entraxes verticaux empêchent les mains ou les balles de se coincer ; la ligne de saisie supérieure reste droite et continue pour les résidents âgés. Là où le passage en fauteuil est requis, un côté d’entrée s’ouvre et libère une trajectoire libre. Le nettoyage demande une brosse douce et une eau tiède ; contrôlez les pieds une fois par an et resserrez aussitôt ce qui bouge. Contrairement au métal, le bois ne chauffe pas en été ni ne givre en hiver, et sa texture s’accorde au langage matériel du jardin. Peignable sur demande ; la couleur se décide à l’étape du devis.',
                ],
                'it' => [
                    'baslik' => 'Gazebo Esagonale in Legno 3x3 Condominio',
                    'slug' => 'gazebo-esagonale-legno-3x3',
                    'kisa_aciklama' => 'Gazebo esagonale in legno da 3×3 m per condomini: pino di qualità trattato contro putridume e insetti, tetto inclinato con gronda in legno, fissaggi resistenti alla corrosione. 2-3 persone comode su 9 m². Misure e piano di posa fissati dopo il sopralluogo gratuito (lun.-sab. 09:00-18:00). Garanzia 5 anni.',
                    'detayli_aciklama' => '**In breve**
- Pavimento esagonale 3×3 m (9 m²); zona seduta riservata per 2-3 persone nei condomini.
- Pino di qualità trattato, tetto inclinato con gronda in legno: uso tutto l’anno, garanzia 5 anni.
- Calcolo m² trasparente: 9 × 2,50 EUR × 1,0 pino × 1,2 esagonale × 1,0 condominio = 27,00 EUR.
- Misure e piano di posa fissati durante il sopralluogo gratuito (lun.-sab. 09:00-18:00).

## Specifiche tecniche

| Voce | Valore |
| --- | --- |
| Impronta a terra | 3 × 3 m — 9 m² |
| Forma | Esagonale, sei lati |
| Materiale principale | Pino di qualità |
| Tetto | Inclinato, scarico con gronda in legno |
| Parapetto | Legno, altezza definita dal progetto |
| Superficie | Trattata contro putridume e insetti |
| Fissaggi | Parti metalliche resistenti alla corrosione |
| Garanzia | 5 anni |

## Per chi è?

Questo modello è pensato per gli amministratori che gestiscono un giardino condiviso. L’impronta compatta di 9 m² regala un angolo arioso senza lasciare impronte pesanti sul verde. Per le famiglie con bambini è un punto d’incontro ombreggiato e leggibile; per gli anziani una seduta riparata dal vento e facile da raggiungere. Cortili stretti, giardini di condomini e spazi pubblici tranquilli si adattano bene a questa misura. Tre lati aperti inquadrano la vista invece di bloccarla. I progetti con budget di quartiere evitano sorprese di costo grazie all’impronta standard, e l’arredamento resta semplice sul piano esagonale simmetrico.

## Capienza

La capienza segue il fattore condominio approvato in F15: 9 m² ÷ 3,50 m² a persona ≈ 2,57 — quindi 2-3 persone comodamente. Il fattore è volutamente generoso; privacy e libertà di movimento vengono prima della densità. Due poltrone e un tavolino entrano senza sforzo; in configurazione pranzo due adulti si fronteggiano senza affollamento. Se la stessa superficie deve ospitare più persone, si passa ai modelli quadrati 4×4 o rettangolari. La posa finale si verifica durante il sopralluogo insieme al piano d’arredo.

## Posa

Il gazebo è prodotto su ordinazione. Durante il sopralluogo gratuito l’equipaggio valuta terreno, accesso ed eventuali esigenze elettriche; misure e piano di posa si fissano in quella visita. Gli appuntamenti vanno da lunedì a sabato, 09:00-18:00, e i lavori sono pianificati per concludersi in un solo giorno concordato. Il team trasporta il materiale direttamente dal mezzo al punto di installazione. Basta una base piatta e portante — piastrelle, terreno compatto o fissaggio su terrazza in legno si discutono al sopralluogo. L’area è subito utilizzabile a fine lavori; nessuna ritocchi il giorno dopo.

## Manutenzione

La lunga vita segue una routine semplice: una trattazione protettiva all’anno basta, la vernice esterna si rinnova ogni due anni. La superficie si pulisce con spazzola morbida e tiepida; il lavapressione solleva la fibra e non è consigliato. Controllare teste di fissaggio e piedi del parapetto una volta l’anno, stringere subito ciò che muove. Grazie al trattamento, umidità e sole non diventano problemi strutturali; negli angoli umidi e ombrosi lasciare aperta la circolazione d’aria. La neve inverno scivola dal tetto inclinato e dalle gronde invece di accumularsi.

## Domande frequenti

**Quanto dura la posa?** Il calendario si conferma al sopralluogo, in base allo stato del terreno, e si pianifica su un solo giorno d’appuntamento.
**Posso cambiare le misure?** Sì — ogni misura è su ordinazione; 3×3 è solo il punto di partenza predefinito.
**Cosa comprende il prezzo?** Il prezzo è trasparente al m²; prodotto e posa sono elencati separatamente nel preventivo di sopralluogo.
**Cosa copre la garanzia?** Vale 5 anni di garanzia; ambito ed esclusioni sono scritti nel documento di offerta.

## Prezzo

Calcolo m² trasparente: 9 m² × 2,50 EUR (base) × 1,0 (pino) × 1,2 (esagonale) × 1,0 (condominio) = **27,00 EUR**. I moltiplicatori sono quelli del motore di prezzo del sito: la calcolatrice restituisce la stessa cifra. Il preventivo definitivo si stabilisce dopo il sopralluogo gratuito che conferma misure e terreno. Sopralluogo: da lunedì a sabato, 09:00-18:00.',
                    'seo_baslik' => 'Gazebo Esagonale Legno 3x3 Prezzi nel 2026 | Kamelya',
                    'seo_aciklama' => 'Gazebo esagonale legno 3x3 per condomini: 9 m², 2-3 posti, pino trattato, tetto inclinato, garanzia 5 anni. Prezzi al m² trasparenti, sopralluogo gratuito.',
                    'seo_anahtar_kelimeler' => 'gazebo esagonale legno, gazebo legno prezzi, gazebo 3x3, gazebo condominio',
                    'cati_tipi_aciklama' => 'Il tetto esagonale si inclina dolcemente verso il centro su sei portanti, così pioggia e neve scaricano in modo uniforme su tutti i lati senza ristagni a terra. La pendenza impedisce anche alla neve di concentrarsi in un carico unico. La gronda segue il linguaggio del legno: l’acqua viene convogliata nel canale e allontanata dalla struttura, le gocce non colpiscono né parapetto né zona seduta. La copertura è prodotta come superficie in legno abbinata al pino, evitando uno scontro metallo-legno nella silhouette. Il comportamento al vento si valuta a scala di progetto e si definisce al sopralluogo secondo la logica degli elementi di involucro esterno richiamati dalla EN 13561. Dall’esterno il tetto si legge come un pezzo continuo: ogni prospetto porta la stessa pendenza, senza asimmetrie. Lo scarico guarda lontano dall’ingresso perché il lato seduto resti asciutto. La manutenzione richiede un controllo di superficie all’anno; togliere foglie dalle gronde e tenere il legno asciutto. La resistenza alla pioggia nasce da pendenza e posa insieme: l’acqua scorre invece di restare. Se il terreno differisce, l’angolo di pendenza si discute con l’equipaggio del sopralluogo in loco.',
                    'korkuluk_aciklama' => 'Il parapetto in legno prosegue ad altezza uguale su ogni spigolo del piano esagonale, ripara la seduta dai venti traversi e mantiene una sensazione di riservatezza dentro. L’altezza si conferma al sopralluogo secondo norme di sicurezza e condizioni di progetto, preferendo la misura che sorregge il polso da seduti a un numero fisso di catalogo. Le giunzioni dei pali si bloccano con incroci che eliminano l’oscillazione. La superficie riceve trattamento e finitura protettiva che conservano la venatura naturale del pino — liscia al tatto, senza scaglie. I piedi sono sollevati su cunei di legno perché l’acqua del terreno non risalga nei pali, e tutte le ferramenta sono a prova di corrosione. Negli spazi familiari gli interassi verticali impediscono che mani o palline restino incastrate; la linea di presa superiore resta dritta e continua per gli anziani. Dove serve il passaggio in sedia a rotelle, un lato d’ingresso si apre e lascia il transito libero. La pulizia richiede spazzola morbida e acqua tiepida; controllare i piedi una volta l’anno e stringere subito ciò che è mosso. Rispetto al metallo, il legno non scalda d’estate né gela d.inverno, e la texture si accorda al linguaggio materiale del giardino. Colorabile a richiesta; la tinta si sceglie in fase di preventivo.',
                ],
                'ar' => [
                    'baslik' => 'كوش خشبي سداسي 3x3 للمجمعات السكنية',
                    'slug' => 'hexagonal-wood-kush-3x3',
                    'kisa_aciklama' => 'كوش خشبي سداسي بمقاس 3×3 متر لمجتمعات المجمعات السكنية: خشب صنوبر معالج ضد التحلل والحشرات، سقف مائل بمزارب خشبية، تثبيتات مقاومة للصدأ. يتسع لـ 2-3 أشخاص على 9 م². تُحدَّد المقاسات وخطة التركيب بعد الاستشارة المجانية (الاثنين–السبت 09:00–18:00). ضمان 5 سنوات.',
                    'detayli_aciklama' => '**باختصار**
- أرضية سداسية 3×3 م (9 م²)؛ مساحة جلوس خاصة لـ 2-3 أشخاص في المجمعات السكنية.
- صنوبر معالج، سقف مائل بمرزة خشبية: استخدام على مدار السنة، ضمان 5 سنوات.
- حساب شفاف للمتر: 9 × 3 USD × 1.0 صنوبر × 1.2 سداسي × 1.0 سكني = 32.40 USD.
- تُثبَّت المقاسات وخطة التركيب في الاستشارة المجانية (الاثنين–السبت 09:00–18:00).

## المواصفات التقنية

| البند | القيمة |
| --- | --- |
| مقاس الأرضية | 3 × 3 م — 9 م² |
| الشكل | سداسي، ستة أضلاع |
| المادة الأساسية | خشب صنوبر عالي الجودة |
| السقف | مائل، تصريف بمرزة خشبية |
| السور | خشبي، الارتفاع حسب المشروع |
| السطح | معالج ضد التحلل والحشرات |
| التثبيت | قطع معدنية مقاومة للصدأ |
| الضمان | 5 سنوات |

## لمن صُمم؟

هذا الموديل لإدارات المجمعات التي تدير حديقة مشتركة. أرضية 9 م² المدمجة تمنح ركنًا منتعشًا دون أثر ثقيل على الخضرة. للأسر ذات الأطفال نقطة لقاء مظلولة وواضحة الرؤية؛ لكبار السن جلسة محمية من الرياح يسهل الجلوس فيها. الأفنية الضيقة وحدائق العمارات والمساحات الهادئة تتسع لهذا المقاس بسهولة. ثلاثة وجوه مفتوحة تؤطر المشهد ولا تحجبه. المشاريع ذات ميزانية الحي تتجنّب مفاجآت التكلفة بفضل المقاس القياسي، ويبقى توزيع الأثاث بسيطًا على الأرضية السداسية المتماثلة.

## السعة

تُحسب السعة ومعامل المجمع السكني المعتمد من F15: 9 م² ÷ 3.50 م² للشخص ≈ 2.57 — أي 2-3 أشخاص براحة. المعامل مقصود أن يكون سخيًا؛ الخصوصية وحرية الحركة تأتي قبل الازدحام. كرسيان وطاولة جانبية تدخلان بيسر؛ في ترتيب الطعام يجلس شخصان متقابلان دون ضغط. إن احتاجت المساحة نفسها لعدد أكبر، فالانتقال إلى موديلات مربعة 4×4 أو مستطيلة ضروري. يُتحقق من التوضع النهائي أثناء الاستشارة مع خطة الأثاث.

## التركيب

يُصنع الموديل عند الطلب. في الاستشارة المجانية يفحص الفريق الأرضية واتجاه الدخول وحاجة الكهرباء إن وُجدت؛ وتُثبَّت المقاسات وخطة التركيب في ذلك الموعد. المواعيد من الاثنين إلى السبت 09:00–18:00، ويُخطط العمل لإنهاء اليوم في يوم واحد متّفق عليه. ينقل الفريق المواد مباشرة من السيارة إلى مكان التركيب. كفاية أرضية مستوية وحاملة — بلاط خرساني أو تربة مضغوطة أو تثبيت على سطح خشبي تُناقش في الاستشارة. تصبح المساحة جاهزة فور انتهاء التركيب دون أعمال لاحقة في اليوم التالي.

## الصيانة

العمر الطويل يتبع روتينًا بسيطًا: معالجة واقية مرة في السنة تكفي، وتجديد الورنيش الخارجي كل سنتين. يُنظَّف السطح بفرشاة ناعمة ومياه فاترة؛ ماكسة الضغط العالي ترفع الألياف ولا يُنصح بها. تُفحص رؤوس التثبيت وقواعد السور مرة في السنة ويُشدّ ما يتمايل فورًا. بفضل المعالجة لا يتحول الرطوبة والشمس إلى مشكلة إنشائية؛ أبقِ حركة الهواء مفتوحة في الزوايا الرطبة المظللة. تنزلق ثلوج الشتاء عن السقف المائل والمرزبات بدل أن تتراكم.

## أسئلة شائعة

**كم يستغرق التركيب؟** يُحدَّد الجدول في الاستشارة حسب حالة الأرضية ويُخطط كيوم موعد واحد.
**هل يمكن تغيير المقاسات؟** نعم — كل المقاسات تُصنع عند الطلب؛ 3×3 نقطة انطلاق افتراضية فقط.
**ما الذي يشمله السعر؟** السعر شفاف للمتر؛ بند المنتج والتركيب يُذكران منفصلين في عرض الاستشارة.
**ماذا يغطي الضمان؟** ضمان 5 سنوات؛ النطاق والاستثناءات مكتوبان في وثيقة العرض.

## السعر

حساب متر شفاف: 9 م² × 3 USD (أساسي) × 1.0 (صنوبر) × 1.2 (سداسي) × 1.0 (سكني) = **32.40 USD**. المضاعفات مطابقة لمحرك سعر الموقع؛ الآلة تعطي النفس الرقم. يستقر العرض النهائي بعد الاستشارة المجانية التي تؤكد المقاسات وحالة الأرضية. احجز الاستشارة: الاثنين–السبت 09:00–18:00.',
                    'seo_baslik' => 'كوش سداسي خشبي 3x3 أسعار 2026 | Kamelya',
                    'seo_aciklama' => 'كوش سداسي خشبي 3x3: 9 م²، يتسع لـ 2-3 أشخاص، ضمان 5 سنوات. أسعار متر شفافة، استشارة مجانية.',
                    'seo_anahtar_kelimeler' => 'كوش خشبي سداسي, أسعار الكوش الخشبي, كوش 3x3, كوش المجمعات السكنية',
                    'cati_tipi_aciklama' => 'يتجه سقف الكوش السداسي بانحدار خفيف نحو مركزه على ست حاملات، فيتصريف مياه المطر والثلج بالتساوي على جميع الوجوه دون تجمّع على الأرضية. الانحدار يمنع أيضًا تركّز الثلج في حمل واحد. المرزة تناسب لغة الخشب في الهيكل: توجَّه المياه إلى القناة وتُبعد عن الإطار، فلا تصل القطرة إلى السور ولا إلى منطقة الجلوس. تُنتج تغطية السقف كسطح خشبي متناغم مع الصنوبر، فيتجنَّب التعارض بين المعدن والخشب في السيلويت. يُقيَّم سلوك الرياح على مستوى المشروع ويُحسم في الاستشارة وفق منطق عناصر الغلاف الخارجي المرجعية (EN 13561). من الخارج يُقرأ السقف قطعة واحدة: كل وجه يحمل الميل نفسه بلا عدم تماثل. يتجه التصريف بعيدًا عن المدخل فيبقى جانب الجلوس جافًا. الصيانة سطر فحص سطحي سنوي؛ تنظيف أوراق المرزبات وإبقاء الخشب جافًا. مقاومة المطر تولَّد من الانحدار والتركيب معًا: تنزلق المياه بدل أن تثبت. ومع تباين الأرضية يُناقش زاوية الانحدار مع فريق الاستشارة في الموقع. تُفحص المرزة مرة في السنة وتُنظَّف من الأوراق بانتظام.',
                    'korkuluk_aciklama' => 'يستمر السور الخشبي بارتفاع متساوٍ على كل حافة من حواف الخطة السداسية، يحمي منطقة الجلوس من الرياح المارّة مع إبقاء إحساس بالخصوصية في الداخل. يُثبَّت الارتفاع في الاستشارة وفق معايير السلامة وظروف المشروع، مع تفضيل المقاس الذي يسند المعصم أثناء الجلوس على رقم كتالوج ثابت. تُقفل نقاط التقاء الأعمدة بتثبيتات متقاطعة تمنع الاهتزاز. يُجهَّز السطح بمعالجة واقية تحافظ على نسيج الصنوبر الطبيعي — ناعم عند اللمس دون شظايا. تُرفع قواعد السور على أخماس خشبية كي لا يصعد ماء الأرض إلى الجذوع، وتُختار كل القطع المعدنية مقاومة للصدأ. في الأماكن العائلية تُضبط المسافات الرأسية بحيث لا تعلق اليد أو الكرة، وتبقى خط القبضة العلوي مستقيمًا ومتصلًا لكبار السن. حيث يلزم مرور الكرسي المتحرك، يُخطَّط أحد حواف المدخل ليُفتح السور ويترك ممرًا حرًا. التنظيف بفرشاة ناعمة ومياه فاترة يكفي؛ فحص القواعد سنويًا وشدّ المحكم فورًا. مقارنةً بالمعدن لا يسخن الصنوبر صيفًا ولا يُثلج شتاءً، ونسجه ينسجم مع لغة مواد الحديقة. يمكن طلبه باللون المطلوب عند الطلب، ويُحدَّد اللون في مرحلة العرض.',
                ],
            ],
            'KML-ALU-ALT-002' => [
                'tr' => [
                    'baslik' => 'Altıgen Alüminyum Kamelya 4x4 Otel Bahçesi',
                    'slug' => 'altigen-aluminyum-kamelya-4x4',
                    'kisa_aciklama' => 'Altıgen alüminyum kamelya, 4×4 metre tabanıyla otel bahçeleri ve terasları için ferah, bakımı kolay bir yapı sunar. Korozyona dirençli alüminyum profil, toz boya ve prefabrik panel sistemiyle üretilir; yıkanması yeterlidir. Kapasitesi 5-6 kişidir. Net ölçü ve montaj planı ücretsiz keşifte belirlenir; 5 yıl garantilidir.',
                    'detayli_aciklama' => '**TL;DR**
- 4×4 m (16 m²) altıgen taban; otel bahçeleri için 5-6 kişilik ferah teras yapısı.
- Korozyona dirençli alüminyum, toz boya, prefabrik panel, EN 13561 referansı.
- Şeffaf m² hesabı: 16 × 12.000 × 2,5 (alüminyum) × 1,2 (altıgen) × 1,6 (otel) = 921.600 TL.
- Net ölçü ve montaj planı ücretsiz keşifte sabitlenir (Pzt–Cmt 09:00–18:00).

## Teknik Özellikleri Nelerdir?

| Alan | Değer |
| --- | --- |
| Taban ölçüsü | 4 × 4 m — 16 m² |
| Form | Altıgen, 6 kenar |
| Ana malzeme | Alüminyum profil |
| Çatı | Düz, prefabrik panel drenajı |
| Korkuluk | Alüminyum, proje bazlı yükseklik |
| Yüzey işlemi | Elektrostatik toz boya, UV stabil |
| Rüzgâr | EN 13561 referanslı değerlendirme |
| Garanti | 5 yıl |

## Kimler İçin?

Bu model, misafir deneyimini dış mekâna taşıyan oteller için tasarlandı. 16 m² taban, iki oturma grubunu veya bir yemek düzenini yan yana taşıyabilecek kadar geniştir; boutique otellerden zincir tesislerin bahçe teraslarına kadar ölçeklenir.

## Kaç Kişiliktir?

Kapasite, F15 onaylı otel katsayısıyla hesaplanır: 16 m² ÷ 2,80 m²/kişi ≈ 5,71 — yani 5-6 kişi rahat oturur. Bu değer premium teras standardıdır; servis koridoru ve masa aralıkları için pay bırakır.

## Montaj Nasıl Yapılır?

Ürün sipariş üzerine üretilir. Ücretsiz keşifte zemin, giriş yönü, servis akışı ve elektrik ihtiyacı değerlendirilir; net ölçü ile montaj planı bu görüşmede sabitlenir. Randevular Pzt–Cmt 09:00–18:00 arasındadır. Prefabrik panel sistemi, malzemenin araçtan doğrudan kurulum alanına.

## Bakımı Nasıl Yapılır?

Alüminyumun bakımı neredeyse sıfırdır: yüzeyi yıkanması yeterlidir. Toz boya kaplama UV ışınlarına karşı solmaz; korozyon direnci, nemli ve tuzlu sahil koşullarında ek güvence sağlar. Korozyona dayanıklı bağlantı elemanları yılda bir gözle kontrol edilir; gevşen sıkma.

## Sık Sorulan Sorular

**Montaj ne kadar sürer?** Prefabrik panel sayesinde kurulum hızlıdır; kesin gün keşifte zemin koşullarıyla netleşir. **Ölçüyü değiştirebilir miyim?** Evet — tüm ölçüler sipariş üzerine üretilir; 4×4 yalnızca varsayılan bir başlangıç noktasıdır. **Fiyata neler dahil?** Fiyat.

## Fiyat

Şeffaf m² hesabı: 16 m² × 12.000 TL (temel) × 2,5 (alüminyum) × 1,2 (altıgen) × 1,6 (otel) = **921.600 TL**. Çarpanlar sitedeki fiyat motoruyla aynıdır; aracı çalıştırdığınızda bu sonucu birebir görürsünüz.

## Çatı Özellikleri Nelerdir?

Düz çatı, altıgen gövdenin üzerinde temiz ve modern bir hat çizer; su, panel aralarından cepheye doğru kontrollü yönlendirilir, oturma alanına damlamaz. Prefabrik panel yapısı, çatının araçtan inip kısa sürede yerine oturmasını sağlar; ek yerleri su sızdırmazlığına göre birleştirilir. Alüminyum yüzey, çatıda da korozyon direnci sağlar — kiremit veya ahşap kaplamaya gerek kalmadan bakım döngüsü kapanır. Rüzgâr yükü davranışı, EN 13561 referansıyla değerlendirilir ve alan keşifte netleşir. Düz form, otel siluetine oturur ve üstten bakıldığında altıgen planı okunaklı tutar. Kar yükü, proje konumuna göre keşif ekibiyle ele alınır; gerekirse eğim detayı konuşulur. Kenar detayları, suyun tek noktada toplanıp uzaklaştırılmasını sağlar. Yüzey yıkanırken çatı da aynı ritimde temizlenir; yaprak ve toz panel arasından akar. Gizli sabitleme noktaları, dışarıdan bakıldığında görünmez; profil dili gövdeyle aynıdır. Şeffaf veya opak panel seçeneği, keşifte ışık tercihine göre değerlendirilir. Gizli sabitlemeler profilin içine gömülür; dış yüzeyde vida başı kalmaz. Bakım, yılda bir görsel kontrol ve olası derz kontrolünden ibarettir.

## Korkuluk ve Güvenlik Özellikleri Nelerdir?

Alüminyum korkuluk, altıgen planın altı kenarında kesintisiz devam eder ve teras misafirlerini rüzgârdan korurken hafif bir çerçeve çizgisi bırakır. Yükseklik, güvenlik standartlarına ve otelin proje koşullarına göre keşif sırasında netleşir; sabit katalog rakamı yerine misafir konforunu destekleyen ölçü tercih edilir. Toz boya kaplama, güneşte solmaz ve tuzlu sahil havasında korozyona karşı korur. Dikey aileler, parmakların sıkışmasını önleyecek aralıkla düzenlenir; üst tutamak hattı düz ve kesintisizdir, servis personeli için de tutunma noktası oluşturur. Tekerlekli sandalye erişimi gereken kenarlarda korkuluk açılıp geçiş bırakacak şekilde planlanır. Kayar panel uyumu sayesinde korkuluk ile cephe arasında boşluk kalmaz; toz ve yaprak birikmez. Temizlikte sabunlu su ve yumuşak bez yeterlidir; metal fırça kullanılmaz. Birleşim elemanları gizli bağlantı kutularında saklanır, dışarıdan vida başı görünmez. Ahşap korkulukla kıyasla bakım gerektirmez; boyaya, emprenyeye ve zımparaya gerek yoktur. İstenirse renk, toz boya skalasından teklif aşamasında seçilir. Korkuluk dip kısımları su biriktirmez; profil içi boşluk kapalıdır. Akşam aydınlatması entegrasyonu için profil arasında kablo kanalı bırakılabilir.
',
                    'seo_baslik' => 'Altıgen Alüminyum Kamelya 4x4 Fiyatları 2026 | Kamelya',
                    'seo_aciklama' => 'Altıgen alüminyum kamelya 4x4: 16 m² alan, 5-6 kişilik otel terası, toz boyalı profil, düz çatı, EN 13561. m² şeffaf fiyat, ücretsiz keşif.',
                    'seo_anahtar_kelimeler' => 'altıgen alüminyum kamelya, alüminyum kamelya fiyatları, otel kamelyası, 4x4 kamelya',
                    'cati_tipi_aciklama' => 'Düz çatı, altıgen gövdenin üzerinde temiz ve modern bir hat çizer; su, panel aralarından cepheye doğru kontrollü yönlendirilir, oturma alanına damlamaz. Prefabrik panel yapısı, çatının araçtan inip kısa sürede yerine oturmasını sağlar; ek yerleri su sızdırmazlığına göre birleştirilir. Alüminyum yüzey, çatıda da korozyon direnci sağlar — kiremit veya ahşap kaplamaya gerek kalmadan bakım döngüsü kapanır. Rüzgâr yükü davranışı, EN 13561 referansıyla değerlendirilir ve alan keşifte netleşir. Düz form, otel siluetine oturur ve üstten bakıldığında altıgen planı okunaklı tutar. Kar yükü, proje konumuna göre keşif ekibiyle ele alınır; gerekirse eğim detayı konuşulur. Kenar detayları, suyun tek noktada toplanıp uzaklaştırılmasını sağlar. Yüzey yıkanırken çatı da aynı ritimde temizlenir; yaprak ve toz panel arasından akar. Gizli sabitleme noktaları, dışarıdan bakıldığında görünmez; profil dili gövdeyle aynıdır. Şeffaf veya opak panel seçeneği, keşifte ışık tercihine göre değerlendirilir. Gizli sabitlemeler profilin içine gömülür; dış yüzeyde vida başı kalmaz. Bakım, yılda bir görsel kontrol ve olası derz kontrolünden ibarettir.',
                    'korkuluk_aciklama' => 'Alüminyum korkuluk, altıgen planın altı kenarında kesintisiz devam eder ve teras misafirlerini rüzgârdan korurken hafif bir çerçeve çizgisi bırakır. Yükseklik, güvenlik standartlarına ve otelin proje koşullarına göre keşif sırasında netleşir; sabit katalog rakamı yerine misafir konforunu destekleyen ölçü tercih edilir. Toz boya kaplama, güneşte solmaz ve tuzlu sahil havasında korozyona karşı korur. Dikey aileler, parmakların sıkışmasını önleyecek aralıkla düzenlenir; üst tutamak hattı düz ve kesintisizdir, servis personeli için de tutunma noktası oluşturur. Tekerlekli sandalye erişimi gereken kenarlarda korkuluk açılıp geçiş bırakacak şekilde planlanır. Kayar panel uyumu sayesinde korkuluk ile cephe arasında boşluk kalmaz; toz ve yaprak birikmez. Temizlikte sabunlu su ve yumuşak bez yeterlidir; metal fırça kullanılmaz. Birleşim elemanları gizli bağlantı kutularında saklanır, dışarıdan vida başı görünmez. Ahşap korkulukla kıyasla bakım gerektirmez; boyaya, emprenyeye ve zımparaya gerek yoktur. İstenirse renk, toz boya skalasından teklif aşamasında seçilir. Korkuluk dip kısımları su biriktirmez; profil içi boşluk kapalıdır. Akşam aydınlatması entegrasyonu için profil arasında kablo kanalı bırakılabilir.',
                ],
                'en' => [
                    'baslik' => 'Hexagonal Aluminium Gazebo 4x4 Hotel Garden',
                    'slug' => 'hexagonal-aluminium-gazebo-4x4',
                    'kisa_aciklama' => 'Hexagonal aluminium gazebo, 4×4 footprint for hotel gardens. Electrostatic powder coat, UV-stable and flat roof keep four-season use simple. Seats 5-6 across 16 m². Measurements and install plan lock after the free survey (Mon–Sat 09:00–18:00). Five-year warranty. Built to order for gardens and terraces that need reliable shade.',
                    'detayli_aciklama' => '**TL;DR**
- 4×4 m (16 m²) Hexagonal, six sides floor; an 5-6-person seating area for hotel gardens.
- Electrostatic powder coat, UV-stable, Flat, prefab panel drainage: four-season use, five-year warranty.
- Transparent m² maths: 16 × 3 USD × 2.5 aluminium × 1.2 hexagonal × 1.6 hotel = 230.40 USD.
- Final measurements and the installation plan are fixed at the free survey (Mon-Sat 09:00-18:00).

## What Are the Technical Specifications?

| Item | Value |
| --- | --- |
| Floor size | 4×4 m — 16 m² |
| Shape | Hexagonal, six sides |
| Main material | aluminium |
| Roof | Flat, prefab panel drainage |
| Railing | aluminium railing, height set per project |
| Surface | Electrostatic powder coat, UV-stable |
| Warranty | 5 years |

## Who Is It For?

This model is built for hotel operators and site teams that plan at garden scale.

## How Many People Does It Seat?

Seating follows the approved hotel gardens ratio from F15: 16 m² ÷ 2.80 m² per person ≈ 5.71, so 5-6 people sit comfortably.

## How Is It Installed?

The product is made to order.

## How Is It Maintained?

Care is one surface check a year plus a protective refresh when needed.

## Why This Model?

This model balances scale and upkeep for hotel gardens.

## What About the Roof?

The flat roof meets the modern silhouette and garden line on the same plane; rainwater drains through controlled channels between panels and never drips onto the seating zone. A tiny slope, invisible to the eye, is planned into the mould so drainage is guaranteed and no ponding forms. Wind behaviour is assessed at project scale and settled at the survey against external envelope logic referenced by EN 13561. The plane leaves room for lighting or menu-board mounts. Roof care needs one visual check a year — keep the holes open and rinse with a hose. Edge profiles guide water down the facade and leave no stain marks. Where needed, the drainage plan is discussed with the survey crew on site. The outlet stays above ground level so the surface remains dry. In winter the snow load spreads evenly across the plane instead of concentrating on one point. Cable runs for evening lighting hide under the ceiling; the drip line stays outside the seating circle. Dust and limescale rinse off with a hose; no chemicals are required.

## What About the Railing and Safety?

The aluminium railing runs at an even height along every edge of the plan, shielding the seating area from cross-wind while keeping privacy inside. Height is confirmed at the survey against safety standards, preferring the dimension that supports the wrist when seated over a fixed catalogue number. Joints lock with cross-braces so the frame does not sway. The surface keeps the material\'s natural grain — smooth to the touch, with no splinters. Railing feet are lifted so water never wicks into the posts; all fixings are chosen for corrosion resistance. Vertical gaps are spaced to prevent hands or balls from jamming, while the top grip line stays flat for elderly residents. Where wheelchair access is needed, one entrance edge opens and leaves a clear passage. Cleaning takes a soft brush and lukewarm water; check the feet yearly. The material doesn\'t overheat in summer or feel cold in winter, and its texture matches the garden\'s palette. The railing can be painted on request; colour is decided at quote stage. Along the 4×4 plan it draws an unbroken safety line. A hidden channel under the top rail accepts a light strip or planter rail without visible screws.
',
                    'seo_baslik' => 'Hexagonal aluminium Gazebo 4×4 Prices 2026 | Kamelya',
                    'seo_aciklama' => '16 m² Hexagonal gazebo seats 5-6 with flat roof, five-year warranty. Transparent m² pricing and free on-site survey. Book a visit Monday–Saturday.',
                    'seo_anahtar_kelimeler' => 'hexagonal aluminium gazebo, aluminium gazebo prices, hotel gazebo, 4x4 gazebo',
                    'cati_tipi_aciklama' => 'The flat roof meets the modern silhouette and garden line on the same plane; rainwater drains through controlled channels between panels and never drips onto the seating zone. A tiny slope, invisible to the eye, is planned into the mould so drainage is guaranteed and no ponding forms. Wind behaviour is assessed at project scale and settled at the survey against external envelope logic referenced by EN 13561. The plane leaves room for lighting or menu-board mounts. Roof care needs one visual check a year — keep the holes open and rinse with a hose. Edge profiles guide water down the facade and leave no stain marks. Where needed, the drainage plan is discussed with the survey crew on site. The outlet stays above ground level so the surface remains dry. In winter the snow load spreads evenly across the plane instead of concentrating on one point. Cable runs for evening lighting hide under the ceiling; the drip line stays outside the seating circle. Dust and limescale rinse off with a hose; no chemicals are required.',
                    'korkuluk_aciklama' => 'The aluminium railing runs at an even height along every edge of the plan, shielding the seating area from cross-wind while keeping privacy inside. Height is confirmed at the survey against safety standards, preferring the dimension that supports the wrist when seated over a fixed catalogue number. Joints lock with cross-braces so the frame does not sway. The surface keeps the material\'s natural grain — smooth to the touch, with no splinters. Railing feet are lifted so water never wicks into the posts; all fixings are chosen for corrosion resistance. Vertical gaps are spaced to prevent hands or balls from jamming, while the top grip line stays flat for elderly residents. Where wheelchair access is needed, one entrance edge opens and leaves a clear passage. Cleaning takes a soft brush and lukewarm water; check the feet yearly. The material doesn\'t overheat in summer or feel cold in winter, and its texture matches the garden\'s palette. The railing can be painted on request; colour is decided at quote stage. Along the 4×4 plan it draws an unbroken safety line. A hidden channel under the top rail accepts a light strip or planter rail without visible screws.',
                ],
                'de' => [
                    'baslik' => 'Sechseckiger Aluminium-Pavillon 4x4 Hotelgarten',
                    'slug' => 'sechseckiger-aluminium-pavillon-4x4',
                    'kisa_aciklama' => 'Sechseckiger Aluminium-Pavillon 4×4 für Hotelgärten und Terrassen: korrosionsbeständiges Aluminiumprofil, Pulverbeschichtung und Prefab-Panel — Waschen genügt als Pflege, kein Lackieren, kein Schleifen. Platz für 5-6 Personen auf 16 m². Maße und Montageplan werden beim kostenlosen Aufmaß fixiert (Mo-Sa 09:00-18:00). 5 Jahre Garantie.',
                    'detayli_aciklama' => '**Kurz gefasst**
- 4×4 m (16 m²) sechseckige Fläche; luftige Terrassenstruktur für 5-6 Personen im Hotelgarten.
- Korrosionsbeständiges Aluminium, Pulverbeschichtung, Prefab-Panels, EN-13561-Bezug.
- Transparente m²-Rechnung: 16 × 2,50 EUR × 2,5 Aluminium × 1,2 sechseckig × 1,6 Hotel = 192,00 EUR.
- Maße und Montageplan werden beim kostenlosen Aufmaß fixiert (Mo-Sa 09:00-18:00).

## Technische Daten

| Merkmal | Wert |
| --- | --- |
| Bodenmaß | 4 × 4 m — 16 m² |
| Form | Sechseckig, sechs Seiten |
| Hauptmaterial | Aluminiumprofil |
| Dach | Flach, Entwässerung über Prefab-Panel |
| Geländer | Aluminium, Höhe projektbezogen |
| Oberfläche | Elektrostatische Pulverbeschichtung, UV-stabil |
| Wind | Bewertung mit EN-13561-Bezug |
| Garantie | 5 Jahre |

## Für wen ist er geeignet?

Dieses Modell ist für Hotels gebaut, die das Gästeerlebnis nach draußen tragen. Der 16 m²-Boden trägt zwei Sitzgruppen oder ein Ess-Setup nebeneinander — von Boutique-Häusern bis zur Kettenterrasse. Bei hoher Auslastung ist der Vorteil am größten: Waschen genügt, kein Lackieren, kein Schleifen. An der Küste treffen Salz und Feuchte auf die natürliche Widerstandsfähigkeit des Aluminiums. Plaza-Höfe und Wintergarten-Öffnungen bevorzugen dieselbe Form. Die ruhige Silhouette gibt Schatten, ohne den Garten zu überdecken, und Panelzwischenräume lassen Platz für Abendbeleuchtung.

## Kapazität

Die Kapazität folgt dem freigegebenen Hotel-Faktor aus F15: 16 m² ÷ 2,80 m² pro Person ≈ 5,71 — bequem sitzen also 5-6 Personen. Das ist der Premium-Terrassen-Standard mit Platz für Servicewege und Tischabstände. Das Layout trägt zwei Vierer-Tische oder eine Lounge für vier. Für größere Event-Setups führt der Weg zum 5×5-Modern- oder 6×4-Rechteckmodell. Die endgültige Aufstellung wird beim Aufmaß mit dem Möbelplan geprüft.

## Montage

Das Modell wird auf Bestellung gefertigt. Beim kostenlosen Aufmaß prüft das Team Boden, Zugang, Servicefluss und Strombedarf; Maße und Montageplan werden dort fixiert. Termine laufen Montag bis Samstag, 09:00-18:00. Das Prefab-Panel-System erlaubt schnellen Aufbau direkt vom Fahrzeug, sodass der Hotelbetrieb nicht lange stockt. Ein ebenes, tragfähiges Fundament genügt; Befestigungen werden beim Aufmaß besprochen. Nach Abschluss ist die Fläche sofort nutzbar.

## Pflege

Die Pflege liegt nahe bei null: Waschen genügt. Die Pulverbeschichtung bleicht in der UV-Sonne nicht aus, und Korrosionsbeständigkeit gibt an salzigen Küsten Sicherheit. Korrosionsfeste Beschläge einmal jährlich prüfen und nachziehen. Schiebetürchen und verdeckte Scharniere einmal jährlich mit weichem Tuch abwischen. Gegenüber Holz gibt es keine Imprägnierung, keinen Lackierzyklus, keinen Schleifgang. Im Winter entfernen Seifenwasser und Wasser Schmutz und Salz; die Struktur bleibt geschützt.

## Häufige Fragen

**Wie lange dauert die Montage?** Prefab-Panels halten den Bau kurz; der genaue Tag wird beim Aufmaß bestätigt.
**Kann ich die Maße ändern?** Ja — jedes Maß wird auf Bestellung gefertigt; 4×4 ist nur der Standard-Ausgangspunkt.
**Was ist im Preis enthalten?** Transparente Bildung pro m²; Produkt- und Montagepositionen stehen getrennt im Angebot.
**Ist die Pflege wirklich so einfach?** Ja — Waschen genügt; kein Lackier-, Imprägnier- oder Schleifzyklus.

## Preis

Transparente m²-Rechnung: 16 m² × 2,50 EUR (Basis) × 2,5 (Aluminium) × 1,2 (sechseckig) × 1,6 (Hotel) = **192,00 EUR**. Die Faktoren entsprechen der Preis-Engine der Website; der Rechner liefert dasselbe Ergebnis. Das finale Angebot steht nach dem kostenlosen Aufmaß. Aufmaß-Termin: Montag bis Samstag, 09:00-18:00.',
                    'seo_baslik' => 'Sechseckiger Aluminium-Pavillon 4x4 Preise 2026 | Kamelya',
                    'seo_aciklama' => 'Sechseckiger Aluminium-Pavillon 4x4: 16 m², Platz für 5-6, pulverbeschichtetes Profil, flaches Dach, EN 13561. Transparente m²-Preise, kostenloser Aufmaß.',
                    'seo_anahtar_kelimeler' => 'sechseckiger Aluminium-Pavillon, Aluminium Pavillon Preise, Hotel-Pavillon, 4x4 Pavillon',
                    'cati_tipi_aciklama' => 'Das flache Dach zieht eine saubere, moderne Linie über den sechseckigen Körper; Wasser wird über Panelfugen zur Fassade geführt und tropft nicht auf die Sitzfläche. Das Prefab-Panel-System bringt das Dach schnell vom Fahrzeug an seinen Ort, mit fugendichter Versiegelung. Aluminium gibt dem Dach denselben Korrosionsschutz wie dem Rahmen — keine Ziegel, kein Holz, dadurch kein Pflegezyklus. Das Windverhalten wird mit EN-13561-Bezug bewertet und beim Aufmaß abgeglichen. Die flache Form passt zur Hotelsilhouette und hält den sechseckigen Grundriss von oben lesbar. Schneelast wird mit dem Aufmaß-Team am Projektstandort behandelt; bei Bedarf wird der Neigungswinkel besprochen. Kanten Details sammeln Wasser an kontrollierten Punkten und führen es ab. Das Waschen des Dachs folgt dem Rhythmus der Körperoberfläche; Laub und Staub spülen aus den Fugen. Verdeckte Befestigungen verschwinden von außen; die Profilsprache bleibt gleich. Klar- oder Opakpanel wird beim Aufmaß nach Lichtwunsch gewählt. Die Pflege ist eine jährliche Sichtprüfung plus gelegentliche Fugenkontrolle.',
                    'korkuluk_aciklama' => 'Das Aluminiumgeländer läuft an allen sechs Kanten des sechseckigen Grundrisses ununterbrochen weiter, schützt Terrassengäste vor Wind und lässt eine leichte Rahmenlinie. Die Höhe wird beim Aufmaß an Sicherheitsnormen und Hotelprojektbedingungen bestätigt, mit Präferenz für Gästekomfort statt Katalogzahl. Pulverbeschichtung bleicht in der Sonne nicht aus und schützt an salziger Küste vor Korrosion. Vertikalstäbe sind so gesetzt, dass Finger nicht klemmen; die obere Griffleitung bleibt gerade und durchgehend und gibt dem Serviceteam gleich Halt. Wo ein Rollstuhlvorgang nötig ist, öffnet sich eine Kante und lässt freie Passage. Die Schiebe-Panel-Passt ohne Spiegel zwischen Geländer und Fassade; Staub und Laub sammeln sich nicht. Seifenwasser und weiches Tuch genügen; Metallbürsten bleiben außen vor. Verbindungen stecken in Anschlussdosen, von außen sieht man keine Schraubenköpfe. Gegenüber Holz entfallen Lack, Imprägnierung und Schleifen. Die Füße stehen auf stoßfesten Auflagen; Kratzer im Boden bleiben aus. Die Farbe wählt man aus der Pulverbeschichtungs-Palette beim Angebot. Für Abendbeleuchtung bleibt zwischen den Profilen ein Kabelkanal frei.',
                ],
                'fr' => [
                    'baslik' => 'Gazebo Hexagonal Aluminium 4x4 Jardin d\'Hôtel',
                    'slug' => 'gazebo-hexagonal-aluminium-4x4',
                    'kisa_aciklama' => 'Gazebo hexagonal en aluminium de 4×4 m pour jardins et terrasses d’hôtel : profil résistant à la corrosion, thermolaquage et panneaux préfabriqués — un lavage suffit, ni peinture ni ponçage. 5-6 personnes sur 16 m². Dimensions et plan de pose fixés après l’étude gratuite (lun.-sam. 09h00-18h00). Garantie 5 ans.',
                    'detayli_aciklama' => '**En bref**
- Sol hexagonal 4×4 m (16 m²) ; structure de terrasse aérée pour 5-6 personnes au jardin d’hôtel.
- Aluminium résistant à la corrosion, thermolaquage, panneaux préfabriqués, référence EN 13561.
- Calcul m² transparent : 16 × 2,50 EUR × 2,5 aluminium × 1,2 hexagonal × 1,6 hôtel = 192,00 EUR.
- Dimensions et plan de pose fixés pendant l’étude gratuite (lun.-sam. 09h00-18h00).

## Caractéristiques techniques

| Caractéristique | Valeur |
| --- | --- |
| Emprise au sol | 4 × 4 m — 16 m² |
| Forme | Hexagonale, six côtés |
| Matériau principal | Profil aluminium |
| Toit | Plat, drainage par panneau préfabriqué |
| Garde-corps | Aluminium, hauteur définie par projet |
| Surface | Thermolaquage électrostatique, stabile UV |
| Vent | Évaluation avec référence EN 13561 |
| Garantie | 5 ans |

## Pour qui ?

Ce modèle est conçu pour les hôtels qui portent l’expérience client à l’extérieur. Les 16 m² accueillent deux groupes de siège ou une configuration repas côte à côte, de la boutique hôtel à la terrasse de chaîne. Les établissements à fort trafic y gagnent le plus : un lavage suffit, sans peinture ni ponçage. En bord de mer, sel et humidité rencontrent la résistance naturelle de l’aluminium. Les cours de plaza et les ouvertures de véranda préfèrent la même forme. Le silhouette discrète ajoute de l’ombre sans écraser le paysage, et les joints des panneaux laissent la place à l’éclairage du soir.

## Capacité

La capacité suit le facteur hôtel approuvé en F15 : 16 m² ÷ 2,80 m² par personne ≈ 5,71, soit confortablement 5-6 personnes. C’est le standard de terrasse premium, avec de la place pour les allées de service et l’espacement des tables. L’implantation accepte deux tables de quatre ou un salon de quatre places. Pour les réceptions plus larges, voir les modèles modernes 5×5 ou rectangulaires 6×4. Le placement final se vérifie à l’étude avec le plan de mobilier.

## Pose

Le gazebo est fabriqué sur commande. Pendant l’étude gratuite, l’équipe examine le sol, l’accès, le flux de service et le besoin électrique ; dimensions et plan de pose s’y fixent. Les rendez-vous couvrent du lundi au samedi, 09h00-18h00. Le système de panneaux préfabriqués permet une pose rapide depuis le véhicule, sans perturber longtemps l’exploitation de l’hôtel. Un sol plat et porteur suffit ; les fixations se discutent à l’étude. La zone est utilisable dès la fin des travaux.

## Entretien

L’entretien est proche de zéro : un lavage suffit. Le thermolaquage ne passe pas au soleil et la résistance à la corrosion rassure en ambiance salubre salée. Vérifiez les fixations résistantes à la corrosion une fois par an et resserrez ce qui bouge. Panneaux coulissants et charnières dissimulées s’essuient au chiffon doux une fois par an. Contrairement au bois : ni traitement, ni cycle de peinture, ni ponçage. En hiver, eau savonneuse et eau simple enlèvent saleté et sel; la structure reste intacte.

## Questions fréquentes

**Combien de temps dure la pose ?** Les panneaux préfabriqués gardent le chantier court ; le jour exact se confirme à l’étude.
**Puis-je modifier les dimensions ?** Oui — toute taille est fabriquée sur commande ; 4×4 n’est qu’un point de départ par défaut.
**Que comprend le prix ?** Prix transparent au m² ; produit et pose listés séparément dans le devis.
**L’entretien est-il vraiment simple ?** Oui — un lavage suffit ; aucun cycle de peinture, traitement ou ponçage.

## Prix

Calcul m² transparent : 16 m² × 2,50 EUR (base) × 2,5 (aluminium) × 1,2 (hexagonal) × 1,6 (hôtel) = **192,00 EUR**. Les multiplicateurs sont ceux du moteur de prix du site. Le devis final se stabilise après l’étude gratuite. Étude : du lundi au samedi, 09h00-18h00.',
                    'seo_baslik' => 'Gazebo Hexagonal Aluminium 4x4 Prix et Devis 2026 | Kamelya',
                    'seo_aciklama' => 'Gazebo hexagonal aluminium 4x4 : 16 m², 5-6 places, profil thermolaqué, toit plat, EN 13561. Prix au m² transparents, étude gratuite sur rendez-vous.',
                    'seo_anahtar_kelimeler' => 'gazebo hexagonal aluminium, gazebo aluminium prix, gazebo hôtel, gazebo 4x4',
                    'cati_tipi_aciklama' => 'Le toit plat trace une ligne nette et moderne sur le corps hexagonal ; l’eau est guidée vers la façade par les joints des panneaux et ne tombe jamais sur l’assise. Le système préfabriqué fait voyager le toit du véhicule à sa place en peu de temps, joints étanchés. L’aluminium confère au toit la même résistance à la corrosion que la charpente — ni tuiles, ni bois, donc aucun cycle d’entretien. Le comportement au vent s’évalue avec référence EN 13561 et se fixe à l’étude. La forme plate s’accorde à la silhouette hôtelière et garde le plan hexagonal lisible du dessus. La charge de neige s’aborde avec l’équipe d’étude selon le site, avec discussion de la pente si besoin. Les détails de bord rassemblent l’eau en points contrôlés. Le lavage du toit suit le rythme du corps ; feuilles et poussière s’évacuent par les joints. Les fixations dissimulées disparaissent de l’extérieur, le langage du profil reste identique. Panneau clair ou opaque se choisit à l’étude selon la lumière. L’entretien se limite à un contrôle visuel annuel plus une vérification occasionnelle des joints.',
                    'korkuluk_aciklama' => 'Le garde-corps en aluminium court sans interruption sur les six arêtes du plan hexagonal, abrite les clients de la terrasse du vent et laisse un léger trait de cadre. Sa hauteur se confirme à l’étude selon les normes de sécurité et les conditions du projet hôtelier, en préférant le confort des clients à un chiffre de catalogue. Le thermolaquage ne se décolore pas au soleil et protège de la corrosion en air marin. Les balustres verticaux sont espacés pour que les doigts ne se coincent pas ; la ligne de saisie supérieure reste droite et continue et sert aussi au personnel de service. Là où le passage en fauteuil est requis, un bord s’ouvre et libère le passage. L’ajustement des panneaux coulissants ne laisse aucun jeu entre garde-corps et façade ; poussière et feuilles ne s’accumulent pas. Eau savonneuse et chiffon doux suffisent ; brosses métalliques interdites. Les assemblages sont cachés dans des boîtes de raccord, aucune tête de vis à l’extérieur. Face au bois : ni peinture, ni traitement, ni ponçage. La couleur se choisie dans la gamme de thermolaquage au devis. Un canal câble entre les profils ménage la place pour l’éclairage du soir.',
                ],
                'it' => [
                    'baslik' => 'Gazebo Esagonale Alluminio 4x4 per Hotel',
                    'slug' => 'gazebo-esagonale-alluminio-4x4',
                    'kisa_aciklama' => 'Gazebo esagonale in alluminio da 4×4 m per giardini e terrazze d’hotel: profilato resistente alla corrosione, verniciatura a polvere e pannelli prefabbricati — basta lavare, niente pittura né cartaggressione. 5-6 persone su 16 m². Misure e piano di posa fissati dopo il sopralluogo gratuito (lun.-sab. 09:00-18:00). Garanzia 5 anni.',
                    'detayli_aciklama' => '**In breve**
- Pavimento esagonale 4×4 m (16 m²); struttura terrazza ariosa per 5-6 persone in giardino d’hotel.
- Alluminio resistente alla corrosione, verniciatura a polvere, pannelli prefabbricati, riferimento EN 13561.
- Calcolo m² trasparente: 16 × 2,50 EUR × 2,5 alluminio × 1,2 esagonale × 1,6 hotel = 192,00 EUR.
- Misure e piano di posa fissati durante il sopralluogo gratuito (lun.-sab. 09:00-18:00).

## Specifiche tecniche

| Voce | Valore |
| --- | --- |
| Impronta a terra | 4 × 4 m — 16 m² |
| Forma | Esagonale, sei lati |
| Materiale principale | Profilo in alluminio |
| Tetto | Piatto, scarico con pannello prefabbricato |
| Parapetto | Alluminio, altezza definita dal progetto |
| Superficie | Verniciatura a polvere elettrostatica, stabile UV |
| Vento | Valutazione con riferimento EN 13561 |
| Garanzia | 5 anni |

## Per chi è?

Questo modello è pensato per gli hotel che portano l’esperienza ospite all’aperto. I 16 m² reggono due gruppi di seduta o un’allestimento a pranzo affiancato, dalla boutique alla terrazza della catena. Gli esercizi ad alto traffico guadagnano di più: basta lavare, senza pittura né carteggiatura. In riva al mare, sale e umidità incontrano la resistenza naturale dell’alluminio. Corte plaza e aperture di veranda preferiscono la stessa forma. La silhouette discreta aggiunge ombra senza schiacciare il paesaggio, e i giunti dei pannelli lasciano spazio all’illuminazione serale.

## Capienza

La capienza segue il fattore hotel approvato in F15: 16 m² ÷ 2,80 m² a persona ≈ 5,71, quindi 5-6 persone comodamente. È lo standard terrazza premium, con spazio per corsie di servizio e distanza tra tavoli. L’allestimento regge due tavoli da quattro o un lounge da quattro. Per ricevimenti più ampi, guardare i modelli moderni 5×5 o rettangolari 6×4. La posa finale si verifica al sopralluogo con il piano d’arredo.

## Posa

Il gazebo è prodotto su ordinazione. Durante il sopralluogo gratuito l’equipaggio valuta terreno, accesso, flusso di servizio ed esigenze elettriche; misure e piano di posa si fissano lì. Gli appuntamenti vanno da lunedì a sabato, 09:00-18:00. Il sistema a pannelli prefabbricati consente una posa rapida dal mezzo, senza bloccare a lungo l’operatività dell’hotel. Basta una base piatta e portante; le fissazioni si discutono al sopralluogo. L’area è subito utilizzabile a fine lavori.

## Manutenzione

La manutenzione è prossima allo zero: basta lavare. La verniciatura a polvere non sbiadisce ai raggi UV e la resistenza alla corrosione rassicura in ambiente salino. Controllare una volta l’anno le ferramenta resistenti alla corrosione e stringere ciò che si muove. Pannelli scorrevoli e cerniere nascoste si puliscono una volta l’anno con panno morbido. Rispetto al legno: nessun trattamento, nessun ciclo di pittura, nessuna carteggiatura. D’inverno, acqua saponata e acqua puliscono sporco e sale; la struttura resta integra.

## Domande frequenti

**Quanto dura la posa?** I pannelli prefabbricati tengono il cantiere corto; il giorno esatto si conferma al sopralluogo.
**Posso cambiare le misure?** Sì — ogni misura è su ordinazione; 4×4 è solo il punto di partenza predefinito.
**Cosa comprende il prezzo?** Prezzo trasparente al m²; prodotto e posa elencati separatamente nel preventivo.
**La manutenzione è davvero semplice?** Sì — basta lavare; nessun ciclo di pittura, trattamento o carteggiatura.

## Prezzo

Calcolo m² trasparente: 16 m² × 2,50 EUR (base) × 2,5 (alluminio) × 1,2 (esagonale) × 1,6 (hotel) = **192,00 EUR**. I moltiplicatori sono quelli del motore di prezzo del sito. Il preventivo definitivo si stabilisce dopo il sopralluogo gratuito. Sopralluogo: da lunedì a sabato, 09:00-18:00.',
                    'seo_baslik' => 'Gazebo Esagonale Alluminio 4x4 Prezzi nel 2026 | Kamelya',
                    'seo_aciklama' => 'Gazebo esagonale alluminio 4x4: 16 m², 5-6 posti, profilato verniciato, tetto piatto, EN 13561. Prezzi al m² trasparenti, sopralluogo gratuito per hotel.',
                    'seo_anahtar_kelimeler' => 'gazebo esagonale alluminio, gazebo alluminio prezzi, gazebo hotel, gazebo 4x4',
                    'cati_tipi_aciklama' => 'Il tetto piatto traccia una linea pulita e moderna sul corpo esagonale; l’acqua viene guidata verso la facciata attraverso i giunti dei pannelli e non gocciola mai sulla seduta. Il sistema prefabbricato fa arrivare il tetto dal mezzo al suo posto in breve, con giunti sigillati. L’alluminio dà al tetto la stessa resistenza alla corrosione della struttura — nessuna tegola, nessun legno, quindi nessun ciclo di manutenzione. Il comportamento al vento si valuta con riferimento EN 13561 e si definisce al sopralluogo. La forma piatta si adatta alla silhouette alberghiera e tiene il piano esagonale leggibile dall’alto. Il carico di neve si affronta con l’equipaggio in base alla sede, discutendo la pendenza se serve. I dettagli di bordo raccolgono l’acqua in punti controllati. Il lavaggio del tetto segue il ritmo del corpo; foglie e polvere escono dai giunti. Le fissazioni nascoste spariscono dall’esterno e il linguaggio del profilo resta identico. Pannello chiaro o opaco si sceglie al sopralluogo secondo la luce. La manutenzione è un controllo visivo annuale più un’occasionale verifica dei giunti.',
                    'korkuluk_aciklama' => 'Il parapetto in alluminio corre ininterrotto sui sei spigoli del piano esagonale, ripara gli ospiti della terrazza dal vento e lascia una sottile linea di cornice. L’altezza si conferma al sopralluogo secondo norme di sicurezza e condizioni del progetto alberghiero, preferendo il comfort degli ospiti a un numero fisso di catalogo. La verniciatura a polvere non sbiadisce al sole e protegge dalla corrosione in aria salmastra. I montanti verticali sono distanziati perché le dita non si incastrino; la presa superiore resta dritta e continua e serve anche al personale di servizio. Dove serve il passaggio in sedia a rotelle, un bordo si apre e lascia il transito libero. L’adattamento dei pannelli scorrevoli non lascia giochi tra parapetto e facciata; polvere e foglie non si raccolgono. Acqua saponata e panno morbido bastano; spazzole metalliche mai. Le giunzioni stanno in scatole di raccordo, nessuna testa di viva all’esterno. Rispetto al legno: nessuna pittura, nessun trattamento, nessuna carteggiatura. Il colore si sceglie dalla gamma di verniciatura a polvere in fase di preventivo. Un canale cavi tra i profili lascia spazio all’illuminazione serale.',
                ],
                'ar' => [
                    'baslik' => 'كوش سداسي ألمنيوم 4x4 لحدائق الفنادق',
                    'slug' => 'hexagonal-aluminium-kush-4x4',
                    'kisa_aciklama' => 'كوش سداسي من الألمنيوم بمقاس 4×4 متر لحدائق وشرفات الفنادق: بروفايل مقاوم للصدأ، طلاء بودرة و ألواح جاهزة — يكفي الغسل، بلا دهان ولا صنفرة. يتسع لـ 5-6 أشخاص على 16 م². تُثبَّت المقاسات وخطة التركيب بعد الاستشارة المجانية (الاثنين–السبت 09:00–18:00). ضمان 5 سنوات.',
                    'detayli_aciklama' => '**باختصار**
- أرضية سداسية 4×4 م (16 م²)؛ هيكل شرفة منتعش لـ 5-6 أشخاص في حديقة الفندق.
- ألمنيوم مقاوم للصدأ، طلاء بودرة، ألواح جاهزة، بمرجع EN 13561.
- حساب متر شفاف: 16 × 2.50 EUR × 2.5 ألمنيوم × 1.2 سداسي × 1.6 فندق = 192.00 EUR.
- تُثبَّت المقاسات وخطة التركيب في الاستشارة المجانية (الاثنين–السبت 09:00–18:00).

## المواصفات التقنية

| البند | القيمة |
| --- | --- |
| مقاس الأرضية | 4 × 4 م — 16 م² |
| الشكل | سداسي، ستة أضلاع |
| المادة الأساسية | بروفايل ألمنيوم |
| السقف | مستوٍ، تصريف بلوح جاهز |
| السور | ألمنيوم، الارتفاع حسب المشروع |
| السطح | طلاء بودرة إلكتروستاتيكي ثابت الأشعة |
| الرياح | تقييم بمرجع EN 13561 |
| الضمان | 5 سنوات |

## لمن صُمم؟

هذا الموديل للفنادق التي تنقل تجربة الضيوف إلى الخارج. أرضية 16 م² تحمل مجموعتي جلوس أو ترتيب طعام جنبًا إلى جنب، من البيوت الصغيرة إلى شرفات السلاسل. الأماكن ذات الحركة الكثيفة تستفيد أكثر: يكفي غسل السطح دون دهان أو صنفرة. على الساحل يلتقي الملح والرطوبة بمقاومة الألمنيوم الطبيعية. أفنية المدن وفتحات البيوت الزجاجية تفضّل الشكل نفسه. الصورة الهادئة تضيف ظلًا دون أن تطغى على الحديقة، وفواصل الألواح تترك مساحة لإضاءة المساء.

## السعة

تُحسب السعة ومعامل الفندق المعتمد من F15: 16 م² ÷ 2.80 م² للشخص ≈ 5.71 — أي 5-6 أشخاص براحة. هذا هو معيار الشرفة الفاخر مع فضل لمسارات الخدمة وتباعد الطاولات. يكفي الترتيب طاولتين لأربعة أو صالة لأربعة. للمآدب الأوسع تُطَّلع على موديلات 5×5 العصرية أو 6×4 المستطيلة. يُتحقق من التوضع النهائي في الاستشارة مع خطة الأثاث.

## التركيب

يُصنع الموديل عند الطلب. في الاستشارة المجانية يفحص الفريق الأرضية ومسار الخدمة وحاجة الكهرباء؛ وتُثبَّت المقاسات وخطة التركيب هناك. المواعيد من الاثنين إلى السبت 09:00–18:00. نظام الألواح الجاهزة يسمح بالتركيب السريع من السيارة دون إرباك طويل لتشغيل الفندق. كفاية أرضية مستوية وحاملة؛ التثبيتات تُناقش في الاستشارة. تصبح المساحة جاهزة فور الانتهاء. ويبدأ العمل فور انتهاء الترتيبات.

## الصيانة

الصيانة شبه معدومة: يكفي الغسل. الطلاء لا يبهت تحت الأشعة، والمقاومة للصدأ تطمئن في الهواء الملحي المسطح. تُفحص التثبيتات المقادة للصدأ مرة في السنة ويُشدّ ما يتمايل فورًا. تُمسح الألواح المنزلقة والمفصلات المخفية مرة في السنة بقطعة قماش ناعمة. بخلاف الخشب لا معالجة ولا دورة دهان ولا صنفرة. في الشتاء يزيل الماء والصابون الأتربة والملح، ويظل الهيكل محفوظًا.

## أسئلة شائعة

**كم يستغرق التركيب؟** الألواح الجاهزة تختصر العمل؛ اليوم الدقيق يُحدَّد في الاستشارة.
**هل يمكن تغيير المقاسات؟** نعم — كل المقاسات تُصنع عند الطلب؛ 4×4 نقطة انطلاق افتراضية فقط.
**ما الذي يشمله السعر؟** السعر شفاف للمتر؛ المنتج والتركيب يُذكران منفصلين في العرض.
**هل الصيانة بهذه البساطة فعلًا؟** نعم — يكفي الغسل؛ لا دهان ولا معالجة ولا صنفرة.

## السعر

حساب متر شفاف: 16 م² × 2.50 EUR (أساسي) × 2.5 (ألمنيوم) × 1.2 (سداسي) × 1.6 (فندق) = **192.00 EUR**. المضاعفات مطابقة لمحرك سعر الموقع؛ الآلة تعطي النفس الرقم. يستقر العرض النهائي بعد الاستشارة المجانية. احجز الاستشارة: الاثنين–السبت 09:00–18:00.',
                    'seo_baslik' => 'كوش سداسي ألمنيوم أسعار 2026 | Kamelya',
                    'seo_aciklama' => 'كوش سداسي ألمنيوم 4x4: 16 م²، 5-6 أشخاص، سقف مستوٍ، EN 13561. أسعار متر شفافة، استشارة مجانية.',
                    'seo_anahtar_kelimeler' => 'كوش ألمنيوم سداسي, أسعار الكوش الألمنيوم, كوش الفنادق, كوش 4x4',
                    'cati_tipi_aciklama' => 'يرسم السقف المستوي خطًا نظيفًا وعصريًا فوق الجسم السداسي؛ تُوجَّه المياه عبر فواصل الألواح إلى الواجهة ولا تتقاطر أبدًا على منطقة الجلوس. ينقل نظام اللوح الجاهز السقف من السيارة إلى مكانه بسرعة مع إحكام الفواصل. يمنح الألمنيوم السقف نفس مقاومة الصدأ للهيكل — بلا قرميد ولا خشب، وبالتالي بلا دورة صيانة. يُقيَّم سلوك الرياح بمرجع EN 13561 ويُحسم في الاستشارة. الشكل المستوي ينسجم مع صورة الفندق ويبقي خطة الشقراء مقروءة من الأعلى. يُعالج حمل الثلج مع فريق الاستشارة حسب موقع المشروع، ويُناقش زاوية الميل عند الحاجة. تجمع حواف التفاصيل المائية في نقاط مضبوطة وتصرفها. يسير غسل السقف بإيقاع الغسل نفسه؛ تخرج الأوراق والغبار من الفواصل. التثبيتات المخفية تختفي من الخارج ويبقى لغة البروفايل موحّدة. يُختار لوح شفاف أو معتم في الاستشارة حسب تفضيل الضوء. الصيانة فحص بصري سنوي وفحص فواصل من حين لآخر. يُفحص السقف مرة في السنة؛ تُنظَّف الفواصل من الأتربة وتُعاد معايرة نقاط التصريف عند الحاجة. ويظل الخط المستوي متاحًا لإضافة ألواح شفافة لاحقًا إذا أراد المالك زيادة الإضاءة الطبيعية.',
                    'korkuluk_aciklama' => 'يستمر سور الألمنيوم بلا انقطاع على الحواف الست للخطة السداسية، يحمي ضيوف الشرفة من الرياح ويترك خطًا خفيفًا كإطار. يُثبَّت الارتفاع في الاستشارة وفق معايير السلامة وظروف مشروع الفندق، مع تفضيل راحة الضيوف على رقم كتالوج ثابت. لا يبهت الطلاء تحت الشمس ويحمي من الصدأ في الهواء الملحي. تُضبط المسافات الرأسية كي لا تعلق الأصابع، ويبقى خط القبضة العلوي مستقيمًا ومتصلًا ويعمل لفريق الخدمة أيضًا. حيث يلزم مرور الكرسي المتحرك يُفتح حافة ويبقى الممر حرًا. يترك ملاءمة الألواح المنزلقة فجوة بين السور والواجهة فلا تتراكم الغبار والأوراق. يكفي الماء والصابون وقطعة قماش ناعمة؛ لا فرش معدنية. الجلسات داخل علب وصل مخفية ولا رؤوس مسامير من الخارج. بخلاف الخشب: لا دهان ولا معالجة ولا صنفرة. يُختار اللون من نطاق الطلاء عند العرض. يُترك مسافة آمنة بين البروفايلات تمنع انبعاث الأصابع دون تعطيل الرؤية إلى الحديقة، وتبقى زوايا الوصل مغلقة فلا تتراكم فيها الرمل في الأيام الرياحية. ويمكن طلاء السور بلون الهوية الفندقية عند الطلب. يُترك ممر كابلات بين البروفايلات لإضاءة المساء.',
                ],
            ],
            'KML-AHS-KAR-003' => [
                'tr' => [
                    'baslik' => 'Kare Ahşap Kamelya 4x4 Site Bahçesi Modelleri',
                    'slug' => 'kare-ahsap-kamelya-4x4',
                    'kisa_aciklama' => 'Kare ahşap kamelya, 4×4 metre tabanıyla site bahçelerine ferah ve simetrik bir oturma alanı getirir. Kaliteli çam keresteden emprenye edilerek üretilir; eğimli çatısı ve ahşap oluğu yağmuru uzağa taşır. Kapasitesi 4-5 kişidir. Net ölçü ve montaj planı ücretsiz keşifte belirlenir; 5 yıl garantilidir.',
                    'detayli_aciklama' => '**TL;DR**
- 4×4 m (16 m²) kare taban; site bahçeleri için 4-5 kişilik ferah ve simetrik oturma alanı.
- Kaliteli çam, emprenye, eğimli çatı ve ahşap oluk: dört mevsim kullanım, 5 yıl garanti.
- Şeffaf m² hesabı: 16 × 12.000 × 1,0 (ahşap) × 1,0 (kare) × 1,0 (site) = 192.000 TL.
- Net ölçü ve montaj planı ücretsiz keşifte sabitlenir (Pzt–Cmt 09:00–18:00).

## Teknik Özellikleri Nelerdir?

| Alan | Değer |
| --- | --- |
| Taban ölçüsü | 4 × 4 m — 16 m² |
| Form | Kare, dört kenar |
| Ana malzeme | Kaliteli çam kereste |
| Çatı | Eğimli, ahşap oluklu drenaj |
| Korkuluk | Ahşap, proje bazlı yükseklik |
| Yüzey işlemi | Emprenye — çürüme ve böceğe karşı |
| Bağlantı | Korozyona dayanıklı metal elemanlar |
| Garanti | 5 yıl |

## Kimler İçin?

Bu model, ortak bahçe alanı yöneten site yönetimleri için tasarlandı. 16 m² kare taban, mobilyayı serbestçe yerleştirmeye izin verir: iki koltuklu köşe düzeni, dört kişilik yemek masası veya sehpa grubu.

## Kaç Kişiliktir?

Kapasite, F15 onaylı site bahçesi katsayısıyla hesaplanır: 16 m² ÷ 3,50 m²/kişi ≈ 4,57 — yani 4-5 kişi rahat oturur. Bu katsayı bilinçli olarak yüksektir; mahremiyet ve kol hareketi serbestliği,.

## Montaj Nasıl Yapılır?

Ürün sipariş üzerine üretilir. Ücretsiz keşifte zemin, giriş yönü ve varsa elektrik ihtiyacı değerlendirilir; ölçü ve montaj planı o gün sabitlenir. Keşif randevuları Pazartesi–Cumartesi 09:00–18:00 arasındadır. Kare taban, dik açılı.

## Bakımı Nasıl Yapılır?

Bakım yılda bir kez yüzey kontrolü ve gerekirse koruyucu kaplama tazelemesinden ibarettir; iki yılda bir vernik yeterlidir. Ahşap yüzey, yumuşak fırça ve ılık suyla temizlenir; basınçlı su kullanılmaz.

## Sıkça Sorulan Sorular

**Montaj ne kadar sürer?** Kare şablon sayesinde kurulum hızlıdır; kesin gün keşifte netleşir. **Ölçüler değiştirilebilir mi?** Evet; her ürün sipariş üzerine üretilir — 4×4 standart başlangıç noktasıdır.

## Fiyat

Şeffaf m² hesabı: 16 m² × 12.000 TL (temel) × 1,0 (ahşap) × 1,0 (kare) × 1,0 (site) = **192.000 TL**. Çarpanlar, fiyat_carpanlari tablosundaki aktif satırlarla birebir aynıdır; web sitesindeki.

## Çatı Özellikleri Nelerdir?

Kare kamelyanın eğimli çatısı, dört kenar boyunca merkeze doğru hafif eğimlenir; böylece yağmur ve kar suyu dört cepheden eşit şekilde tahliye olur, tabanda su birikmez. Eğim, karın tek bir yüke toplanmasını da engeller. Oluk detayı, ahşabın doğal diline uygun bir çözümdür: su, oluğa yönlendirilip yapıdan uzaklaştırılır; damlayan su ahşap korkuluğa ve oturma alanına isabet etmez. Çatı kaplaması, çam kerestesiyle uyumlu ahşap yüzey olarak üretilir; metal ile ahşap çarpışmaz. Rüzgâr yükü davranışı, proje ölçeğinde değerlendirilir ve alan, EN 13561 referansıyla tartışılan dış cephe elemanları mantığına göre keşifte netleşir. Kare formda çatı dıştan bakıldığında da bütünlüklüdür: her cephe aynı eğimi taşır, asimetri girmez. Drenaj yönü, girişin tersine kurulur; oturma alanı her zaman kuru tarafta kalır. Çatı bakımına yılda bir yüzey kontrolü yeterlidir; oluk içindeki yaprak temizlenir, ahşap yüzey kuru tutulur. Yağmur direnci, eğim ve yüzey işçiliği birlikte çalışır; su yüzeyde durmak yerine akar. Oluk çıkışı zemin kotunun üstünde tutulur, su kontrollü iner. Gerektiğinde eğim açısı araziye göre keşif ekibiyle konuşulur.

## Korkuluk ve Güvenlik Özellikleri Nelerdir?

Ahşap korkuluk, kare planın dört kenarında eşit yükseklikte devam eder ve oturma alanını dışarıdan gelen rüzgâra karşı korurken içeride mahremiyet hissi bırakır. Yükseklik, güvenlik standartlarına ve proje koşullarına göre keşif sırasında netleşir; sabit bir katalog rakamı yerine, kullanıcının oturduğu anda bileğini destekleyen ölçü tercih edilir. Köşe birleşim noktaları çapraz bağlantılarla kilitlenir, sallanma önlenir. Yüzey, çamın doğal dokusunu koruyan emprenye ve koruyucu kaplamayla hazırlanır; elle temas pürüzsüzdür, yonga bırakmaz. Korkuluk dip kısımları, zemine geçen suyu gövdeye taşımaması için ahşap takozlarla yükseltilir ve bağlantı elemanları korozyona dayanıklı malzemeden seçilir. Çocuklu alanlarda dikey aralıklar, topun veya kolun sıkışmasını önleyecek şekilde düzenlenir; yaşlı sakinler için üst tutamak hattı düz ve kesintisizdir. Tekerlekli sandalye erişimi gereken giriş kenarında korkuluk açılıp geçiş bırakacak şekilde planlanır. Temizlikte yumuşak fırça ve ılık su yeterlidir; dip noktalar yılda bir kontrol edilir, gevşenen sıkma hemen yapılır. Ahşap korkuluk, metal alternatife göre yazın ısınmaz, kışın soğuk tutmaz; dokusu bahçenin malzeme diliyle uyumludur. İstenirse boyanabilir; renk değişikliği teklif aşamasında belirlenir.
',
                    'seo_baslik' => 'Kare Ahşap Kamelya 4x4 Fiyat Listesi 2026 | Kamelya',
                    'seo_aciklama' => 'Kare ahşap kamelya 4x4: 16 m² alan, 4-5 kişilik site bahçesi, emprenye çam ve eğimli çatı, 5 yıl garantili. Şeffaf m² fiyat, ücretsiz keşif.',
                    'seo_anahtar_kelimeler' => 'kare ahşap kamelya, ahşap kamelya fiyatları, 4x4 kamelya, site bahçesi kamelyası',
                    'cati_tipi_aciklama' => 'Kare kamelyanın eğimli çatısı, dört kenar boyunca merkeze doğru hafif eğimlenir; böylece yağmur ve kar suyu dört cepheden eşit şekilde tahliye olur, tabanda su birikmez. Eğim, karın tek bir yüke toplanmasını da engeller. Oluk detayı, ahşabın doğal diline uygun bir çözümdür: su, oluğa yönlendirilip yapıdan uzaklaştırılır; damlayan su ahşap korkuluğa ve oturma alanına isabet etmez. Çatı kaplaması, çam kerestesiyle uyumlu ahşap yüzey olarak üretilir; metal ile ahşap çarpışmaz. Rüzgâr yükü davranışı, proje ölçeğinde değerlendirilir ve alan, EN 13561 referansıyla tartışılan dış cephe elemanları mantığına göre keşifte netleşir. Kare formda çatı dıştan bakıldığında da bütünlüklüdür: her cephe aynı eğimi taşır, asimetri girmez. Drenaj yönü, girişin tersine kurulur; oturma alanı her zaman kuru tarafta kalır. Çatı bakımına yılda bir yüzey kontrolü yeterlidir; oluk içindeki yaprak temizlenir, ahşap yüzey kuru tutulur. Yağmur direnci, eğim ve yüzey işçiliği birlikte çalışır; su yüzeyde durmak yerine akar. Oluk çıkışı zemin kotunun üstünde tutulur, su kontrollü iner. Gerektiğinde eğim açısı araziye göre keşif ekibiyle konuşulur.',
                    'korkuluk_aciklama' => 'Ahşap korkuluk, kare planın dört kenarında eşit yükseklikte devam eder ve oturma alanını dışarıdan gelen rüzgâra karşı korurken içeride mahremiyet hissi bırakır. Yükseklik, güvenlik standartlarına ve proje koşullarına göre keşif sırasında netleşir; sabit bir katalog rakamı yerine, kullanıcının oturduğu anda bileğini destekleyen ölçü tercih edilir. Köşe birleşim noktaları çapraz bağlantılarla kilitlenir, sallanma önlenir. Yüzey, çamın doğal dokusunu koruyan emprenye ve koruyucu kaplamayla hazırlanır; elle temas pürüzsüzdür, yonga bırakmaz. Korkuluk dip kısımları, zemine geçen suyu gövdeye taşımaması için ahşap takozlarla yükseltilir ve bağlantı elemanları korozyona dayanıklı malzemeden seçilir. Çocuklu alanlarda dikey aralıklar, topun veya kolun sıkışmasını önleyecek şekilde düzenlenir; yaşlı sakinler için üst tutamak hattı düz ve kesintisizdir. Tekerlekli sandalye erişimi gereken giriş kenarında korkuluk açılıp geçiş bırakacak şekilde planlanır. Temizlikte yumuşak fırça ve ılık su yeterlidir; dip noktalar yılda bir kontrol edilir, gevşenen sıkma hemen yapılır. Ahşap korkuluk, metal alternatife göre yazın ısınmaz, kışın soğuk tutmaz; dokusu bahçenin malzeme diliyle uyumludur. İstenirse boyanabilir; renk değişikliği teklif aşamasında belirlenir.',
                ],
                'en' => [
                    'baslik' => 'Wooden Square Gazebo 4x4 Residential Complex',
                    'slug' => 'square-wood-gazebo-4x4',
                    'kisa_aciklama' => 'Square timber gazebo, 4×4 footprint for residential complex gardens. Impregnated pine against rot and insects and sloped roof keep four-season use simple. Seats 4-5 across 16 m². Measurements and install plan lock after the free survey (Mon–Sat 09:00–18:00). Five-year warranty.',
                    'detayli_aciklama' => '**TL;DR**
- 4×4 m (16 m²) Square, four sides floor; an 4-5-person seating area for residential complex gardens.
- Impregnated pine against rot and insects, Sloped, timber-guttered drainage: four-season use, five-year warranty.
- Transparent m² maths: 16 × 3 USD × 1.0 pine × 1.0 square × 1.0 residential = 48.00 USD.
- Final measurements and the installation plan are fixed at the free survey (Mon-Sat 09:00-18:00).

## What Are the Technical Specifications?

| Item | Value |
| --- | --- |
| Floor size | 4×4 m — 16 m² |
| Shape | Square, four sides |
| Main material | timber |
| Roof | Sloped, timber-guttered drainage |
| Railing | timber railing, height set per project |
| Surface | Impregnated pine against rot and insects |
| Warranty | 5 years |

## Who Is It For?

This model is built for residential operators and site teams that plan at garden scale.

## How Many People Does It Seat?

Seating follows the approved residential complex gardens ratio from F15: 16 m² ÷ 3.50 m² per person ≈ 4.57, so 4-5 people sit comfortably.

## How Is It Installed?

The product is made to order.

## How Is It Maintained?

Care is one surface check a year plus a protective refresh when needed.

## Why This Model?

This model balances scale and upkeep for residential complex gardens.

## What About the Roof?

The sloped roof eases toward its centre on the load-bearing edges of the 4×4 floor, so rain and snow drain evenly and nothing ponds below. The pitch also stops snow from becoming a single load. Gutters suit the timber language of the structure: water is routed into the channel and carried away, so drips never land on the railing or seating zone. Cladding matches the main material and avoids a clash in the silhouette. Wind behaviour is assessed at project scale and settled at the survey against external envelope elements referenced by EN 13561. From outside the roof reads as one piece — every elevation carries the same pitch, with no asymmetry. Drainage faces away from the entrance so the seating side stays dry. Maintenance needs one surface check a year; clear leaves and keep the surface dry. Rain resistance works through pitch plus joinery: water runs off instead of standing. The outlet stays above ground level. Where terrain differs, the slope angle is discussed with the survey crew on site.

## What About the Railing and Safety?

The timber railing runs at an even height along every edge of the plan, shielding the seating area from cross-wind while keeping privacy inside. Height is confirmed at the survey against safety standards, preferring the dimension that supports the wrist when seated over a fixed catalogue number. Joints lock with cross-braces so the frame does not sway. The surface keeps the material\'s natural grain — smooth to the touch, with no splinters. Railing feet are lifted so water never wicks into the posts; all fixings are chosen for corrosion resistance. Vertical gaps are spaced to prevent hands or balls from jamming, while the top grip line stays flat for elderly residents. Where wheelchair access is needed, one entrance edge opens and leaves a clear passage. Cleaning takes a soft brush and lukewarm water; check the feet yearly. The material doesn\'t overheat in summer or feel cold in winter, and its texture matches the garden\'s palette. The railing can be painted on request; colour is decided at quote stage. Along the 4×4 plan it draws an unbroken safety line. A hidden channel under the top rail accepts a light strip or planter rail without visible screws.
',
                    'seo_baslik' => 'Square timber Gazebo 4×4 Prices and Sizes 2026 | Kamelya',
                    'seo_aciklama' => '16 m² Square gazebo seats 4-5 with sloped roof, five-year warranty. Transparent m² pricing and free on-site survey. Book a visit Monday–Saturday — free.',
                    'seo_anahtar_kelimeler' => 'square wood gazebo, wooden gazebo prices, 4x4 gazebo, residential complex gazebo',
                    'cati_tipi_aciklama' => 'The sloped roof eases toward its centre on the load-bearing edges of the 4×4 floor, so rain and snow drain evenly and nothing ponds below. The pitch also stops snow from becoming a single load. Gutters suit the timber language of the structure: water is routed into the channel and carried away, so drips never land on the railing or seating zone. Cladding matches the main material and avoids a clash in the silhouette. Wind behaviour is assessed at project scale and settled at the survey against external envelope elements referenced by EN 13561. From outside the roof reads as one piece — every elevation carries the same pitch, with no asymmetry. Drainage faces away from the entrance so the seating side stays dry. Maintenance needs one surface check a year; clear leaves and keep the surface dry. Rain resistance works through pitch plus joinery: water runs off instead of standing. The outlet stays above ground level. Where terrain differs, the slope angle is discussed with the survey crew on site.',
                    'korkuluk_aciklama' => 'The timber railing runs at an even height along every edge of the plan, shielding the seating area from cross-wind while keeping privacy inside. Height is confirmed at the survey against safety standards, preferring the dimension that supports the wrist when seated over a fixed catalogue number. Joints lock with cross-braces so the frame does not sway. The surface keeps the material\'s natural grain — smooth to the touch, with no splinters. Railing feet are lifted so water never wicks into the posts; all fixings are chosen for corrosion resistance. Vertical gaps are spaced to prevent hands or balls from jamming, while the top grip line stays flat for elderly residents. Where wheelchair access is needed, one entrance edge opens and leaves a clear passage. Cleaning takes a soft brush and lukewarm water; check the feet yearly. The material doesn\'t overheat in summer or feel cold in winter, and its texture matches the garden\'s palette. The railing can be painted on request; colour is decided at quote stage. Along the 4×4 plan it draws an unbroken safety line. A hidden channel under the top rail accepts a light strip or planter rail without visible screws.',
                ],
                'de' => [
                    'baslik' => 'Quadratischer Holz-Pavillon 4x4 Wohnanlage',
                    'slug' => 'quadratischer-holz-pavillon-4x4',
                    'kisa_aciklama' => 'Quadratischer Holz-Pavillon 4×4 für Wohnanlagen: luftige, symmetrische Sitzfläche aus Qualitäts-Kiefer, impregniert gegen Fäulnis und Insekten; geneigtes Dach mit Holzrinne leitet Regen fern. Platz für 4-5 Personen auf 16 m². Maße und Montageplan werden nach dem kostenlosen Aufmaß fixiert (Mo-Sa 09:00-18:00). 5 Jahre Garantie.',
                    'detayli_aciklama' => '**Kurz gefasst**
- 4×4 m (16 m²) quadratische Fläche; luftiger, symmetrischer Sitzplatz für 4-5 Personen in Wohnanlagen.
- Qualitäts-Kiefer mit Impregnierung, geneigtes Dach mit Holzrinne: ganzjährige Nutzung, 5 Jahre Garantie.
- Transparente m²-Rechnung: 16 × 2,50 EUR × 1,0 Kiefer × 1,0 quadratisch × 1,0 Wohnanlage = 40,00 EUR.
- Maße und Montageplan werden beim kostenlosen Aufmaß fixiert (Mo-Sa 09:00-18:00).

## Technische Daten

| Merkmal | Wert |
| --- | --- |
| Bodenmaß | 4 × 4 m — 16 m² |
| Form | Quadratisch, vier Seiten |
| Hauptmaterial | Qualitäts-Kiefernholz |
| Dach | Geneigt, Entwässerung mit Holzrinne |
| Geländer | Holz, Höhe projektbezogen |
| Oberfläche | Impregniert gegen Fäulnis und Insekten |
| Befestigung | Korrosionsfeste Metallteile |
| Garantie | 5 Jahre |

## Für wen ist er geeignet?

Dieses Modell ist für Hausverwaltungen gebaut, die einen gemeinsamen Garten pflegen. Der 16 m²-Boden erlaubt freie Möblierung: Ecksitzgruppe, Vierer-Esstisch oder Couch-Set passen gleichermaßen. Für Familien mit Kindern ist er ein übersichtlicher Treffpunkt; für ältere Bewohner ein windgeschützter, leichter Sitzplatz. Der symmetrische Grundriss fügt sich in Gartenwege ein und hält die Baulinie sauber. Projekte mit Quartiersbudget vermeiden Kostenüberraschungen durch den Standardgrundriss; drei offene Seiten rahmen den Blick.

## Kapazität

Die Kapazität folgt dem freigegebenen Faktor der Wohnanlage aus F15: 16 m² ÷ 3,50 m² pro Person ≈ 4,57 — bequem sitzen also 4-5 Personen. Der Faktor ist bewusst großzügig; Privatsphäre und Bewegungsfreiheit stehen vor Dichte. Zwei Sessel und ein Beistelltisch — oder ein Vierer-Tisch — passen ideal. Größere Zusammenkünfte führen zum 5×5-Modern- oder 6×4-Rechteckmodell. Die endgültige Aufstellung wird beim Aufmaß zusammen mit dem Möbelplan geprüft.

## Montage

Das Modell wird auf Bestellung gefertigt. Beim kostenlosen Aufmaß prüft das Team Boden, Zugang und Strombedarf; Maße und Montageplan werden dort fixiert. Termine laufen Montag bis Samstag, 09:00-18:00. Das quadratische Maß baut sich dank rechtwinkligem Schablonenmaß schnell auf; Material geht direkt vom Fahrzeug an den Montagepunkt. Betonplatte, verdichteter Boden oder Holzterrasse genügen; die Befestigung wird beim Aufmaß besprochen. Nach Abschluss ist die Fläche sofort nutzbar.

## Pflege

Die Pflege besteht aus einer Oberflächenkontrolle pro Jahr und bei Bedarf einer frischen Schutzschicht; alle zwei Jahre ein Lackierzyklus genügt. Holz wird mit weicher Bürste und lauwarmem Wasser gereinigt — kein Hochdruckreiniger. Die Imprägnierung bleibt der erste Schutz gegen Fäulnis und Insekten und hält bei regelmäßiger Kontrolle. Laub zu Saisonbeginn aus der Rinne entfernen, Auslauf über Bodenniveau halten. Metallteile sind korrosionsfest; lose Stellen bei der ersten Kontrolle nachziehen.

## Häufige Fragen

**Wie lange dauert die Montage?** Das rechtwinklige Schablonenmaß hält den Bau kurz; der genaue Tag wird beim Aufmaß bestätigt.
**Kann ich die Maße ändern?** Ja — jedes Maß wird auf Bestellung gefertigt; 4×4 ist nur der Standard-Ausgangspunkt.
**Was ist im Preis enthalten?** Transparente m²-Rechnung; Produkt- und Montagepositionen stehen getrennt im Angebot.
**Ist die Pflege wirklich so einfach?** Ja — eine Kontrolle pro Jahr, ein Lackierzyklus alle zwei Jahre.

## Preis

Transparente m²-Rechnung: 16 m² × 2,50 EUR (Basis) × 1,0 (Kiefer) × 1,0 (quadratisch) × 1,0 (Wohnanlage) = **40,00 EUR**. Die Faktoren entsprechen den aktiven Zeilen der Preis-Faktoren; der Rechner der Website liefert dasselbe Ergebnis. Das finale Angebot steht nach dem kostenlosen Aufmaß. Aufmaß-Termin: Montag bis Samstag, 09:00-18:00.',
                    'seo_baslik' => 'Quadratischer Holz-Pavillon 4x4 Preise 2026 | Kamelya',
                    'seo_aciklama' => 'Quadratischer Holz-Pavillon 4x4: 16 m², für 4-5, imprägniertes Kiefernholz, geneigtes Dach, 5 Jahre Garantie. Transparente m²-Preise, kostenloser Aufmaß.',
                    'seo_anahtar_kelimeler' => 'quadratischer Holz-Pavillon, Holzpavillon Preise, 4x4 Pavillon, Pavillon Wohnanlage',
                    'cati_tipi_aciklama' => 'Das geneigte Dach des quadratischen Pavillons neigt sich über vier tragenden Kanten sanft zur Mitte; Regen- und Schneewasser läuft dadurch auf allen Seiten gleichmäßig ab und bleibt nicht auf dem Boden stehen. Die Neigung verhindert, dass Schnee zu einer Einzellast wird. Die Rinne passt zur Holzsprache des Pavillons: Wasser wird in den Kanal geführt und vom Rahmen weggeleitet, Tropfen treffen weder Geländer noch Sitzfläche. Die Dachdeckung entsteht als Holzoberfläche, die zur Kiefer passt — kein Metall gegen Holz in der Silhouette. Das Windverhalten wird projektmäßig bewertet und beim Aufmaß an der Logik externer Fassadenelemente mit EN-13561-Bezug abgeglichen. Von außen wirkt das Dach als ein Stück: jede Seite trägt dieselbe Neigung ohne Asymmetrie. Die Entwässerung zeigt weg vom Eingang, damit die Sitzseite trocken bleibt. Zur Pflege genügt eine Oberflächenkontrolle pro Jahr; Laub aus der Rinne entfernen, Holz trocken halten. Der Regenwiderstand entsteht aus Neigung und Verarbeitung zusammen: Wasser fließt statt zu stehen. Bei abweichendem Gelände wird der Neigungswinkel mit dem Aufmaß-Team vor Ort besprochen.',
                    'korkuluk_aciklama' => 'Das Holzgeländer läuft an allen vier Kanten des quadratischen Grundrisses in gleicher Höhe weiter und schützt die Sitzfläche vor Durchzug, während innen ein Gefühl von Privatsphäre bleibt. Die Höhe wird beim Aufmaß anhand von Sicherheitsnormen und Projektbedingungen bestätigt; statt einer festen Katalogzahl wird das Maß gewählt, das das Handgelenk im Sitzen stützt. Eckverbindungen werden mit Kreuzverband verriegelt, damit nichts wackelt. Die Oberfläche erhält eine Imprägnierung und Schutzbeschichtung, die die natürliche Kiefernstruktur bewahrt — glatt bei der Berührung, ohne Splitter. Die Geländerfüße werden mit Holzkeilen angehoben, damit bodennahes Wasser nicht in die Pfosten steigt, und alle Beschläge sind gegen Korrosion ausgelegt. In Familienbereichen sind die Vertikalabstände so gesetzt, dass Hände oder Bälle nicht klemmen; die obere Griffleitung bleibt für ältere Bewohner durchgehend gerade. Wo ein Rollstuhlvorgang nötig ist, öffnet sich die Geländerstrecke am Eingang und lässt freie Passage. Zur Reinigung genügen weiche Bürste und lauwarmes Wasser; die Füße einmal jährlich prüfen, lose Stellen sofort nachziehen. Gegenüber Metall wird das Holz im Sommer nicht heiß und im Winter nicht eiskalt, und die Textur passt zur Materialsprache des Gartens. Auf Wunsch ist es anmalbar; die Farbe wird beim Angebot festgelegt.',
                ],
                'fr' => [
                    'baslik' => 'Gazebo Carré Bois 4x4 pour Copropriété',
                    'slug' => 'gazebo-carre-bois-4x4',
                    'kisa_aciklama' => 'Gazebo carré en bois de 4×4 m pour copropriétés : espace de repos symétrique et aéré, pin de qualité traité contre pourriture et insectes, toit incliné avec gouttière bois qui éloigne la pluie. 4-5 personnes sur 16 m². Dimensions et plan de pose fixés après l’étude gratuite (lun.-sam. 09h00-18h00). Garantie 5 ans.',
                    'detayli_aciklama' => '**En bref**
- Sol carré 4×4 m (16 m²) ; espace de repos symétrique et aéré pour 4-5 personnes en copropriété.
- Pin de qualité traité, toit incliné avec gouttière bois : usage toute saison, garantie 5 ans.
- Calcul m² transparent : 16 × 2,50 EUR × 1,0 pin × 1,0 carré × 1,0 copropriété = 40,00 EUR.
- Dimensions et plan de pose fixés pendant l’étude gratuite (lun.-sam. 09h00-18h00).

## Caractéristiques techniques

| Caractéristique | Valeur |
| --- | --- |
| Emprise au sol | 4 × 4 m — 16 m² |
| Forme | Carrée, quatre côtés |
| Matériau principal | Pin de qualité |
| Toit | Incliné, drainage avec gouttière bois |
| Garde-corps | Bois, hauteur définie par projet |
| Surface | Traité contre pourriture et insectes |
| Fixations | Pièces métalliques résistantes à la corrosion |
| Garantie | 5 ans |

## Pour qui ?

Ce modèle est conçu pour les syndics qui gèrent un jardin partagé. Les 16 m² carrés laissent le mobilier libre : coin salon deux places, table à quatre ou groupe café s’installent avec la même aisance. Pour les familles c’est un point de rendez-vous ombragé et lisible ; pour les résidents âgés, une assise abritée du vent et facile d’accès. Le plan carré symétrique s’aligne sur les allées du jardin et garde la ligne paysagère nette. Les projets au budget de quartier évitent les surprises de coût grâce à l’empreinte standard, et trois côtés ouverts cadrent la vue.

## Capacité

La capacité suit le facteur copropriété approuvé en F15 : 16 m² ÷ 3,50 m² par personne ≈ 4,57 — soit confortablement 4-5 personnes. Le facteur est volontairement généreux ; intimité et aisance passent avant la densité. Deux fauteuils et une table d’appoint — ou une table à quatre — conviennent idéalement. Les réceptions plus larges passent aux modèles modernes 5×5 ou rectangulaires 6×4. Le placement final est vérifié pendant l’étude avec le plan de mobilier.

## Pose

Le gazebo est fabriqué sur commande. L’étude gratuite examine le sol, l’approche et le besoin électrique ; dimensions et plan de pose s’y fixent. Les rendez-vous couvrent du lundi au samedi, 09h00-18h00. Le plan carré se monte vite grâce au gabarit à angle droit ; le matériel va du véhicule au point d’assemblage. dalle béton, sol compacté ou terrasse bois conviennent ; le mode de fixation se précise à l’étude. La zone est utilisable dès la fin des travaux.

## Entretien

L’entretien tient en un contrôle de surface par an et une couche protectrice renouvelée si besoin ; une peinture tous les deux ans suffit. Le bois se nettoie à la brosse douce et à l’eau tiède — sans nettoyeur haute pression. Le traitement reste le premier rempart contre la pourriture et les insectes et dure avec les contrôles réguliers. Retirez les feuilles des gouttières en début de saison et gardez la sortie au-dessus du sol. Les pièces métalliques résistent à la corrosion ; resserrez ce qui bouge dès le premier contrôle.

## Questions fréquentes

**Combien de temps dure la pose ?** Le gabarit carré garde le chantier court ; le jour exact se fixe à l’étude.
**Puis-je modifier les dimensions ?** Oui — tout est fabriqué sur commande ; 4×4 n’est qu’un point de départ par défaut.
**Que comprend le prix ?** Calcul m² transparent ; produit et pose listés séparément dans le devis.
**L’entretien est-il vraiment simple ?** Oui — un contrôle par an et une peinture tous les deux ans.

## Prix

Calcul m² transparent : 16 m² × 2,50 EUR (base) × 1,0 (pin) × 1,0 (carré) × 1,0 (copropriété) = **40,00 EUR**. Les multiplicateurs sont ceux des lignes actives des facteurs de prix ; le calculateur du site donne le même résultat. Le devis final se stabilise après l’étude gratuite. Étude : du lundi au samedi, 09h00-18h00.',
                    'seo_baslik' => 'Gazebo Carré Bois 4x4 Prix et Devis 2026 | Kamelya',
                    'seo_aciklama' => 'Gazebo carré bois 4x4 : 16 m², 4-5 places assises, pin traité, toit incliné avec gouttière, garantie 5 ans. Prix au m² transparents, étude gratuite.',
                    'seo_anahtar_kelimeler' => 'gazebo carré bois, gazebo bois prix, gazebo 4x4, gazebo copropriété',
                    'cati_tipi_aciklama' => 'Le toit incliné du gazebo carré s’incline doucement vers son centre sur quatre porteurs, de sorte que pluie et neige évacuent également des quatre côtés sans stagnation au sol. La pente empêche aussi la neige de se concentrer en une charge unique. La gouttière suit le langage du bois : l’eau est dirigée dans le canal et éloignée du bâti, les gouttes n’atteignent ni le garde-corps ni l’assise. La couverture est produite en surface bois assortie au pin, évitant tout choc métal-bois dans la silhouette. Le comportement au vent s’évalue à l’échelle du projet et se fixe à l’étude selon la logique des éléments d’enveloppe extérieure référencés par l’EN 13561. De l’extérieur, le toit carré se lit comme une pièce continue : chaque façade porte la même pente, sans asymétrie. L’évacuation tourne le dos à l’entrée pour que le côté assis reste sec. L’entretien se limite à un contrôle de surface par an ; retirez les feuilles des gouttières et gardez le bois sec. L’étanchéité à la pluie naît de la pente et de la pose ensemble : l’eau s’écoule au lieu de rester. Si le terrain diffère, l’angle de pente se discute avec l’équipe d’étude sur place.',
                    'korkuluk_aciklama' => 'Le garde-corps en bois court à hauteur égale sur les quatre arêtes du plan carré, protège l’assise des traversants tout en gardant une intimité à l’intérieur. Sa hauteur se confirme à l’étude selon les normes de sécurité et les conditions du projet, en préférant la mesure qui soutient le poignet assis plutôt qu’un chiffre de catalogue fixe. Les assemblages d’angle se verrouillent par croisements pour éviter tout balancement. La surface reçoit traitement et finition protectrice qui conservent le grain naturel du pin — douce au toucher, sans échardes. Les pieds sont surélevés sur cales bois pour que l’eau du sol ne remonte pas dans les poteaux, et toutes les pièces d’attache résistent à la corrosion. Dans les espaces familiaux, les entraxes verticaux empêchent les mains ou les balles de se coincer ; la ligne de saisie supérieure reste droite et continue pour les résidents âgés. Là où le passage en fauteuil est requis, un côté d’entrée s’ouvre et libère une trajectoire libre. Le nettoyage demande une brosse douce et une eau tiède ; contrôlez les pieds une fois par an et resserrez aussitôt ce qui bouge. Contrairement au métal, le bois ne chauffe pas en été ni ne givre en hiver, et sa texture s’accorde au langage matériel du jardin. Peignable sur demande ; la couleur se décide à l’étape du devis.',
                ],
                'it' => [
                    'baslik' => 'Gazebo Quadrato in Legno 4x4 per Condominio',
                    'slug' => 'gazebo-quadrato-legno-4x4',
                    'kisa_aciklama' => 'Gazebo quadrato in legno da 4×4 m per condomini: zona seduta simmetrica e ariosa, pino di qualità trattato contro putridume e insetti, tetto inclinato con gronda in legno che allontana la pioggia. 4-5 persone su 16 m². Misure e piano di posa fissati dopo il sopralluogo gratuito (lun.-sab. 09:00-18:00). Garanzia 5 anni.',
                    'detayli_aciklama' => '**In breve**
- Pavimento quadrato 4×4 m (16 m²) ; zona seduta simmetrica e ariosa per 4-5 persone nei condomini.
- Pino di qualità trattato, tetto inclinato con gronda in legno: uso tutto l’anno, garanzia 5 anni.
- Calcolo m² trasparente: 16 × 2,50 EUR × 1,0 pino × 1,0 quadrato × 1,0 condominio = 40,00 EUR.
- Misure e piano di posa fissati durante il sopralluogo gratuito (lun.-sab. 09:00-18:00).

## Specifiche tecniche

| Voce | Valore |
| --- | --- |
| Impronta a terra | 4 × 4 m — 16 m² |
| Forma | Quadrata, quattro lati |
| Materiale principale | Pino di qualità |
| Tetto | Inclinato, scarico con gronda in legno |
| Parapetto | Legno, altezza definita dal progetto |
| Superficie | Trattata contro putridume e insetti |
| Fissaggi | Parti metalliche resistenti alla corrosione |
| Garanzia | 5 anni |

## Per chi è?

Questo modello è pensato per gli amministratori che gestiscono un giardino condiviso. I 16 m² quadrati lasciano libero l’arredamento: angolo seduta da due, tavolo da quattro o gruppo caffè convivono con la stessa facilità. Per le famiglie con bambini è un punto d’incontro ombreggiato e leggibile; per gli anziani una seduta riparata dal vento e facile da raggiungere. Il piano quadrato simmetrico si allinea ai sentieri e tiene pulita la linea del paesaggio. I progetti con budget di quartiere evitano sorprese di costo grazie all’impronta standard, e tre lati aperti inquadrano la vista.

## Capienza

La capienza segue il fattore condominio approvato in F15: 16 m² ÷ 3,50 m² a persona ≈ 4,57 — quindi 4-5 persone comodamente. Il fattore è volutamente generoso; privacy e libertà di movimento vengono prima della densità. Due poltrone e un tavolino — o un tavolo da quattro — sono ideali. Per raduni più ampi si passa ai modelli moderni 5×5 o rettangolari 6×4. La posa finale si verifica al sopralluogo con il piano d’arredo.

## Posa

Il gazebo è prodotto su ordinazione. Il sopralluogo gratuito valuta terreno, accesso ed esigenze elettriche; misure e piano di posa si fissano lì. Gli appuntamenti vanno da lunedì a sabato, 09:00-18:00. Il pavimento quadrato si monta in fretta grazie al modello ad angolo retto; i materiali vanno dal mezzo al punto di assemblaggio. Solaio in cemento, terreno compatto o terrazza in legno vanno bene; il tipo di fissaggio si definisce al sopralluogo. L’area è subito utilizzabile a fine lavori.

## Manutenzione

La manutenzione è un controllo di superficie all’anno e una mano di finitura protettiva se serve; una verniciatura ogni due anni basta. Il legno si pulisce con spazzola morbida e acqua tiepida — senza idro-lavaggio. Il trattamento resta la prima barriera contro putridume e insetti e dura con i controlli regolari. Togliere foglie dalle gronde a inizio stagione e tenere lo scarico sopra il suolo. Le ferramenta sono a prova di corrosione; stringere subito ciò che è mosso al primo controllo.

## Domande frequenti

**Quanto dura la posa?** Il modello quadrato tiene il cantiere corto; il giorno esatto si fissa al sopralluogo.
**Posso cambiare le misure?** Sì — ogni pezzo è su ordinazione; 4×4 è solo il punto di partenza predefinito.
**Cosa comprende il prezzo?** Calcolo m² trasparente; prodotto e posa elencati separatamente nel preventivo.
**La manutenzione è davvero semplice?** Sì — un controllo all’anno e una verniciatura ogni due anni.

## Prezzo

Calcolo m² trasparente: 16 m² × 2,50 EUR (base) × 1,0 (pino) × 1,0 (quadrato) × 1,0 (condominio) = **40,00 EUR**. I moltiplicatori corrispondono alle righe attive dei fattori di prezzo; il calcolatore del sito produce lo stesso risultato. Il preventivo definitivo si stabilisce dopo il sopralluogo gratuito. Sopralluogo: da lunedì a sabato, 09:00-18:00.',
                    'seo_baslik' => 'Gazebo Quadrato Legno 4x4 Prezzi nel 2026 | Kamelya',
                    'seo_aciklama' => 'Gazebo quadrato legno 4x4 per condomini: 16 m², 4-5 posti, pino trattato, tetto inclinato, garanzia 5 anni. Prezzi al m² trasparenti, sopralluogo gratuito.',
                    'seo_anahtar_kelimeler' => 'gazebo quadrato legno, gazebo legno prezzi, gazebo 4x4, gazebo condominio',
                    'cati_tipi_aciklama' => 'Il tetto inclinato del gazebo quadrato si inclina dolcemente verso il centro su quattro portanti, così pioggia e neve scaricano in modo uniforme su tutti i lati senza ristagni a terra. La pendenza impedisce anche alla neve di concentrarsi in un carico unico. La gronda segue il linguaggio del legno: l’acqua viene convogliata nel canale e allontanata dalla struttura, le gocce non colpiscono né parapetto né zona seduta. La copertura è prodotta come superficie in legno abbinata al pino, evitando uno scontro metallo-legno nella silhouette. Il comportamento al vento si valuta a scala di progetto e si definisce al sopralluogo secondo la logica degli elementi di involucro esterno richiamati dalla EN 13561. Dall’esterno il tetto quadrato si legge come un pezzo continuo: ogni prospetto porta la stessa pendenza, senza asimmetrie. Lo scarico guarda lontano dall’ingresso perché il lato seduto resti asciutto. La manutenzione richiede un controllo di superficie all’anno; togliere foglie dalle gronde e tenere il legno asciutto. La resistenza alla pioggia nasce da pendenza e posa insieme: l’acqua scorre invece di restare. Se il terreno differisce, l’angolo di pendenza si discute con l’equipaggio del sopralluogo in loco.',
                    'korkuluk_aciklama' => 'Il parapetto in legno prosegue ad altezza uguale su tutti i quattro spigoli del piano quadrato, ripara la seduta dai venti traversi e mantiene una sensazione di riservatezza dentro. L’altezza si conferma al sopralluogo secondo norme di sicurezza e condizioni di progetto, preferendo la misura che sorregge il polso da seduti a un numero fisso di catalogo. Le giunzioni d’angolo si bloccano con incroci che eliminano l’oscillazione. La superficie riceve trattamento e finitura protettiva che conservano la venatura naturale del pino — liscia al tatto, senza scaglie. I piedi sono sollevati su cunei di legno perché l’acqua del terreno non risalga nei pali, e tutte le ferramenta sono a prova di corrosione. Negli spazi familiari gli interassi verticali impediscono che mani o palline restino incastrate; la linea di presa superiore resta dritta e continua per gli anziani. Dove serve il passaggio in sedia a rotelle, un lato d’ingresso si apre e lascia il transito libero. La pulizia richiede spazzola morbida e acqua tiepida; controllare i piedi una volta l’anno e stringere subito ciò che è mosso. Rispetto al metallo, il legno non scalda d’estate né gela d’inverno, e la texture si accorda al linguaggio materiale del giardino. Colorabile a richiesta; la tinta si sceglie in fase di preventivo.',
                ],
                'ar' => [
                    'baslik' => 'كوش خشبي مربع 4x4 للمجمعات السكنية',
                    'slug' => 'square-wood-kush-4x4',
                    'kisa_aciklama' => 'كوش خشبي مربع بمقاس 4×4 متر للمجمعات السكنية: مساحة جلوس متناظرة ومنتعبة، خشب صنوبر معالج ضد التحلل والحشرات، سقف مائل بمزارب خشبية يبعد المطر. يتسع لـ 4-5 أشخاص على 16 م². تُثبَّت المقاسات وخطة التركيب بعد الاستشارة المجانية (الاثنين–السبت 09:00–18:00). ضمان 5 سنوات.',
                    'detayli_aciklama' => '**باختصار**
- أرضية مربعة 4×4 م (16 م²)؛ مساحة جلوس متناظرة ومنتعبة لـ 4-5 أشخاص في المجمعات السكنية.
- صنوبر معالج، سقف مائل بمزارب خشبية: استخدام على مدار السنة، ضمان 5 سنوات.
- حساب شفاف للمتر: 16 × 3 USD × 1.0 صنوبر × 1.0 مربع × 1.0 سكني = 48.00 USD.
- تُثبَّت المقاسات وخطة التركيب في الاستشارة المجانية (الاثنين–السبت 09:00–18:00).

## المواصفات التقنية

| البند | القيمة |
| --- | --- |
| مقاس الأرضية | 4 × 4 م — 16 م² |
| الشكل | مربع، أربعة أضلاع |
| المادة الأساسية | خشب صنوبر عالي الجودة |
| السقف | مائل، تصريف بمرزة خشبية |
| السور | خشبي، الارتفاع حسب المشروع |
| السطح | معالج ضد التحلل والحشرات |
| التثبيت | قطع معدنية مقاومة للصدأ |
| الضمان | 5 سنوات |

## لمن صُمم؟

هذا الموديل لإدارات المجمعات التي تدير حديقة مشتركة. أرضية 16 م² المربعة تترك حرية ترتيب الأثاث: ركن جلوس لشخصين أو طاولة لأربعة أو مجموعة قهوة تتوائم بيسر. للأسر ذات الأطفال نقطة لقاء مظلولة وواضحة الرؤية؛ لكبار السن جلسة محمية من الرياح يسهل الجلوس فيها. تتواءم الخطة المربعة مع ممرات الحديقة وتُبقي خط المشهد نظيفًا. المشاريع ذات ميزانية الحي تتجنّب مفاجآت التكلفة بفضل المقاس القياسي، وثلاثة وجوه مفتوحة تؤطر المشهد ولا تحجبه.

## السعة

تُحسب السعة ومعامل المجمع السكني المعتمد من F15: 16 م² ÷ 3.50 م² للشخص ≈ 4.57 — أي 4-5 أشخاص براحة. المعامل مقصود أن يكون سخيًا؛ الخصوصية وحرية الحركة تأتي قبل الازدحام. كرسيان وطاولة جانبية — أو طاولة لأربعة — مناسبان تمامًا. للتجمعات الأكبر توجد موديلات 5×5 العصرية أو 6×4 المستطيلة. يُتحقق من التوضع النهائي أثناء الاستشارة مع خطة الأثاث.

## التركيب

يُصنع الموديل عند الطلب. في الاستشارة المجانية يفحص الفريق الأرضية واتجاه الدخول وحاجة الكهرباء؛ وتُثبَّت المقاسات وخطة التركيب في ذلك الموعد. المواعيد من الاثنين إلى السبت 09:00–18:00. الأرضية المربعة تُبنى سريعًا بفضل القالب الزاوي؛ تُنقل المواد مباشرة من السيارة إلى نقطة التركيب. بلاطة خرسانية أو تربة مضغوطة أو سطح خشبي كفاية؛ تُحدَّد طريقة التثبيت في الاستشارة. تصبح المساحة جاهزة فور انتهاء العمل.

## الصيانة

الصيانة فحص سطحي سنوي ويد واقية عند الحاجة؛ تجديد دهان كل عامين يكفي. يُنظَّف الخشب بفرشاة ناعمة ومياه فاترة دون ضغط عالٍ. المعالجة تبقى الحاجز الأول ضد التحلل والحشرات وتدوم مع الفحوص المنتظمة. تُنظَّف أوراق المرزبات في بداية الموسم وتبقى فتحات التصريف فوق سطح الأرض. القطع المعدنية مقاومة للصدأ؛ يُشدّ المحكم فورًا.

## أسئلة شائعة

**كم يستغرق التركيب؟** القالب المربع يختصر العمل؛ اليوم الدقيق يُحدَّد في الاستشارة.
**هل يمكن تغيير المقاسات؟** نعم — كل القطع تُصنع عند الطلب؛ 4×4 نقطة انطلاق افتراضية فقط.
**ما الذي يشمله السعر؟** حساب متر شفاف؛ المنتج والتركيب يُذكران منفصلين في العرض.
**هل الصيانة بهذه البساطة فعلًا؟** نعم — فحص سنوي ودهان كل عامين.

## السعر

حساب متر شفاف: 16 م² × 3 USD (أساسي) × 1.0 (صنوبر) × 1.0 (مربع) × 1.0 (سكني) = **48.00 USD**. المضاعفات مطابقة للصفوف النشطة في جدول معاملات السعر؛ آلة الموقع تعطي النتيجة نفسها. يستقر العرض النهائي بعد الاستشارة المجانية. موعد الاستشارة: الاثنين–السبت 09:00–18:00.',
                    'seo_baslik' => 'كوش خشبي مربع 4x4 أسعار 2026 | Kamelya',
                    'seo_aciklama' => 'كوش خشبي مربع 4x4: 16 م²، 4-5 أشخاص، سقف مائل، ضمان 5 سنوات. أسعار متر شفافة، استشارة مجانية.',
                    'seo_anahtar_kelimeler' => 'كوش خشبي مربع, أسعار الكوش الخشبي, كوش 4x4, كوش المجمعات السكنية',
                    'cati_tipi_aciklama' => 'يتجه السقف المائل للكوش المربع نحو مركزه على أربع حاملات بانحدار خفيف، فيتصريف مياه المطر والثلج بالتساوي على جميع الوجوه دون تجمّع على الأرضية. الانحدار يمنع أيضًا تركّز الثلج في حمل واحد. المرزة تناسب لغة الخشب في الهيكل: توجَّه المياه إلى القناة وتُبعد عن الإطار، فلا تصل القطرة إلى السور ولا إلى منطقة الجلوس. تُنتج تغطية السقف كسطح خشبي متناغم مع الصنوبر، فيتجنَّب التعارض بين المعدن والخشب في السيلويت. يُقيَّم سلوك الرياح على مستوى المشروع ويُحسم في الاستشارة وفق منطق عناصر الغلاف الخارجي المرجعية (EN 13561). من الخارج يُقرأ السقف المربع قطعة واحدة: كل وجه يحمل الميل نفسه بلا عدم تماثل. يتجه التصريف بعيدًا عن المدخل فيبقى جانب الجلوس جافًا. الصيانة سطر فحص سطحي سنوي؛ تنظيف أوراق المرزبات وإبقاء الخشب جافًا. مقاومة المطر تولَّد من الانحدار والتركيب معًا: تنزلق المياه بدل أن تثبت. ومع تباين الأرضية يُناقش زاوية الانحدار مع فريق الاستشارة في الموقع. تُنظَّف المرزة من الأوراق في بداية الموسم، وتبقى فتحات التصريف فوق سطح الأرض دائمًا حتى لا تتراكم الأتربة في الزوايا.',
                    'korkuluk_aciklama' => 'يستمر السور الخشبي بارتفاع متساوٍ على الحواف الأربعة للخطة المربعة، يحمي منطقة الجلوس من الرياح المارّة مع إبقاء إحساس بالخصوصية في الداخل. يُثبَّت الارتفاع في الاستشارة وفق معايير السلامة وظروف المشروع، مع تفضيل المقاس الذي يسند المعصم أثناء الجلوس على رقم كتالوج ثابت. تُقفل زوايا الالتقاء بتثبيتات متقاطعة تمنع الاهتزاز. يُجهَّز السطح بمعالجة واقية تحافظ على نسيج الصنوبر الطبيعي — ناعم عند اللمس دون شظايا. تُرفع قواعد السور على أخماس خشبية كي لا يصعد ماء الأرض إلى الجذوع، وتُختار كل القطع المعدنية مقاومة للصدأ. في الأماكن العائلية تُضبط المسافات الرأسية بحيث لا تعلق اليد أو الكرة، وتبقى خط القبضة العلوي مستقيمًا ومتصلًا لكبار السن. حيث يلزم مرور الكرسي المتحرك، يُخطَّط أحد حواف المدخل ليُفتح السور ويترك ممرًا حرًا. التنظيف بفرشاة ناعمة ومياه فاترة يكفي؛ فحص القواعد سنويًا وشدّ المحكم فورًا. مقارنةً بالمعدن لا يسخن الصنوبر صيفًا ولا يُثلج شتاءً، ونسجه ينسجم مع لغة مواد الحديقة. يمكن طلبه باللون المطلوب عند الطلب، ويُحدَّد اللون في مرحلة العرض.',
                ],
            ],
            'KML-KOM-KAR-004' => [
                'tr' => [
                    'baslik' => 'Kare Kompozit Kamelya 5x4 Restoran Bahçesi',
                    'slug' => 'kare-kompozit-kamelya-5x4',
                    'kisa_aciklama' => 'Kare kompozit kamelya, 5×4 metre tabanıyla restoran bahçelerine dayanıklı ve bakımı kolay bir servis alanı kazandırır. Kompozit profil, neme ve böceğe karşı dirençlidir; düz çatısı yağmur suyunu kontrollü akıtır. Kapasitesi 11 kişidir. Net ölçü ve montaj planı ücretsiz keşifte belirlenir; 5 yıl garantilidir.',
                    'detayli_aciklama' => '**TL;DR**
- 5×4 m (20 m²) kare taban; restoran bahçeleri için 11 kişilik servis ve oturma alanı.
- Kompozit profil, düz çatı, böcek ve neme dirençli yüzey: dört mevsim kullanım, 5 yıl garanti.
- Şeffaf m² hesabı: 20 × 12.000 × 1,8 (kompozit) × 1,0 (kare) × 1,3 (restoran) = 561.600 TL.
- Net ölçü ve montaj planı ücretsiz keşifte sabitlenir (Pzt–Cmt 09:00–18:00).

## Teknik Özellikleri Nelerdir?

| Alan | Değer |
| --- | --- |
| Taban ölçüsü | 5 × 4 m — 20 m² |
| Form | Kare, dört kenar |
| Ana malzeme | Kompozit profil |
| Çatı | Düz, kontrollü tahliye |
| Korkuluk | Kompozit, proje bazlı yükseklik |
| Yüzey işlemi | Nem ve böceğe dirençli |
| Bağlantı | Korozyona dayanıklı metal elemanlar |
| Garanti | 5 yıl |

## Kimler İçin?

Bu model, yoğun kullanımlı restoran ve kafe bahçeleri için tasarlandı. 20 m² kare taban, iki ayrı servis grubunu yan yana yerleştirir: bir grup dört kişilik masalar, diğer grup rahat bir.

## Kaç Kişiliktir?

Kapasite, F15 onaylı restoran katsayısıyla hesaplanır: 20 m² ÷ 1,80 m²/kişi ≈ 11,11 — yani 11 kişi rahat oturur. Bu katsayı, servis koridoru ve masa aralıkları için ayrılmış yoğun bir.

## Montaj Nasıl Yapılır?

Ürün sipariş üzerine üretilir. Ücretsiz keşifte zemin, servis güzergâhı, elektrik ve aydınlatma ihtiyacı değerlendirilir; ölçü ve montaj planı o gün sabitlenir. Keşif randevuları Pazartesi–Cumartesi 09:00–18:00 arasındadır. Kompozit panel sistemi, taşıma.

## Bakımı Nasıl Yapılır?

Bakım, yılda bir yüzey yıkaması ve bağlantı kontrolünden ibarettir; kompozit yüzey vernik veya emprenye gerektirmez. Yumuşak fırça ve nötr deterjan yeterlidir; aşındırıcı temizleyiciler kullanılmaz. Düz çatıda biriken yaprak ve kir,.

## Sıkça Sorulan Sorular

**Montaj ne kadar sürer?** Kompozit panel sistemi sayesinde kurulum hızlıdır; kesin gün keşifte netleşir. **Ölçüler değiştirilebilir mi?** Evet; her ürün sipariş üzerine üretilir — 5×4 standart başlangıç noktasıdır.

## Fiyat

Şeffaf m² hesabı: 20 m² × 12.000 TL (temel) × 1,8 (kompozit) × 1,0 (kare) × 1,3 (restoran) = **561.600 TL**. Çarpanlar, fiyat_carpanlari tablosundaki aktif satırlarla birebir aynıdır; web sitesindeki.

## Çatı Özellikleri Nelerdir?

Kare kompozit kamelyanın düz çatısı, modern teras ve cephe hattıyla aynı düzlemde buluşur; yağmur suyu, panel aralarındaki kontrollü kanallardan tahliye edilir ve oturma alanına damlamaz. Düz forma, ısıtıcı, menü panosu veya pergola eki kolayca sabitlenir; eğim aramak gerekmez. Kompozit kaplama, metal ve ahşabın çarpışmadığı nötr bir yüzey sunar; UV dayanımı rengi uzun süre korur. Rüzgâr yükü davranışı, proje ölçeğinde değerlendirilir ve alan, EN 13561 referansıyla tartışılan dış cephe elemanları mantığına göre keşifte netleşir. Düz çatıda su birikintisi oluşmaması için minik bir eğim, kalıp içinde planlanır; bu eğim gözle görünmez ama tahliyeyi garanti eder. Tahliye delikleri, yaprak ve kir tutmayacak şekilde köşelere yerleştirilir. Çatı bakımına yılda bir göz kontrolü yeterlidir; delikler açık tutulur, yüzey hortumla yıkanır. Kompozit, çürüme ve böcek yapmaz; ahşap çatının tersine emprenye gerekmez. Kış aylarında biriken kar, düz formda eşit dağılır ve tek yüke dönüşmez. Kenar profilleri, suyun cepheye kontrollü inmesini sağlar; leke izi bırakmaz. Gerektiğinde tahliye planı, keşif ekibiyle araziye göre konuşulur.

## Korkuluk ve Güvenlik Özellikleri Nelerdir?

Kompozit korkuluk, kare planın dört kenarında eşit yükseklikte devam eder ve yoğun restoran trafiğinde masaları dış etkilerden ayırır. Yükseklik, güvenlik standartlarına ve proje koşullarına göre keşif sırasında netleşir; sabit bir katalog rakamı yerine, kullanıcının oturduğu anda bileğini destekleyen ölçü tercih edilir. Köşe birleşim noktaları gizli bağlantılarla kilitlenir, sallanma önlenir. Yüzey, kompozitin doğal dokusunu koruyan UV dayanıklı kaplamayla hazırlanır; elle temas pürüzsüzdür, yonga bırakmaz, çürümez. Korkuluk dip kısımları, zemine geçen suyu gövdeye taşımaması için yükseltilir ve bağlantı elemanları korozyona dayanıklı malzemeden seçilir. Yoğun kullanımlı alanlarda dikey aralıklar, sandalye ve tabak sıkışmasını önleyecek şekilde düzenlenir; üst tutamak hattı düz ve kesintisizdir. Servis geçişinde korkuluk, masaları koruyacak ama geçiş bırakacak şekilde planlanır. Temizlikte yumuşak fırça ve nötr deterjan yeterlidir; dip noktalar yılda bir kontrol edilir, gevşenen sıkma hemen yapılır. Kompozit korkuluk, metal alternatife göre yazın Isınmaz, kışın soğuk tutmaz; dokusu restoran bahçesinin malzeme diliyle uyumludur. İstenirse renk, teklif aşamasında seçilir; kompozit, renk değişikliğine de uyumludur.
',
                    'seo_baslik' => 'Kare Kompozit Kamelya 5x4 Fiyat Listesi 2026 | Kamelya',
                    'seo_aciklama' => 'Kare kompozit kamelya 5x4: 20 m² alan, 11 kişilik restoran bahçesi, nem ve böceğe dirençli yüzey, düz çatı. Şeffaf m² fiyat, ücretsiz keşif.',
                    'seo_anahtar_kelimeler' => 'kare kompozit kamelya, kompozit kamelya fiyatları, 5x4 kamelya, restoran kamelyası',
                    'cati_tipi_aciklama' => 'Kare kompozit kamelyanın düz çatısı, modern teras ve cephe hattıyla aynı düzlemde buluşur; yağmur suyu, panel aralarındaki kontrollü kanallardan tahliye edilir ve oturma alanına damlamaz. Düz forma, ısıtıcı, menü panosu veya pergola eki kolayca sabitlenir; eğim aramak gerekmez. Kompozit kaplama, metal ve ahşabın çarpışmadığı nötr bir yüzey sunar; UV dayanımı rengi uzun süre korur. Rüzgâr yükü davranışı, proje ölçeğinde değerlendirilir ve alan, EN 13561 referansıyla tartışılan dış cephe elemanları mantığına göre keşifte netleşir. Düz çatıda su birikintisi oluşmaması için minik bir eğim, kalıp içinde planlanır; bu eğim gözle görünmez ama tahliyeyi garanti eder. Tahliye delikleri, yaprak ve kir tutmayacak şekilde köşelere yerleştirilir. Çatı bakımına yılda bir göz kontrolü yeterlidir; delikler açık tutulur, yüzey hortumla yıkanır. Kompozit, çürüme ve böcek yapmaz; ahşap çatının tersine emprenye gerekmez. Kış aylarında biriken kar, düz formda eşit dağılır ve tek yüke dönüşmez. Kenar profilleri, suyun cepheye kontrollü inmesini sağlar; leke izi bırakmaz. Gerektiğinde tahliye planı, keşif ekibiyle araziye göre konuşulur.',
                    'korkuluk_aciklama' => 'Kompozit korkuluk, kare planın dört kenarında eşit yükseklikte devam eder ve yoğun restoran trafiğinde masaları dış etkilerden ayırır. Yükseklik, güvenlik standartlarına ve proje koşullarına göre keşif sırasında netleşir; sabit bir katalog rakamı yerine, kullanıcının oturduğu anda bileğini destekleyen ölçü tercih edilir. Köşe birleşim noktaları gizli bağlantılarla kilitlenir, sallanma önlenir. Yüzey, kompozitin doğal dokusunu koruyan UV dayanıklı kaplamayla hazırlanır; elle temas pürüzsüzdür, yonga bırakmaz, çürümez. Korkuluk dip kısımları, zemine geçen suyu gövdeye taşımaması için yükseltilir ve bağlantı elemanları korozyona dayanıklı malzemeden seçilir. Yoğun kullanımlı alanlarda dikey aralıklar, sandalye ve tabak sıkışmasını önleyecek şekilde düzenlenir; üst tutamak hattı düz ve kesintisizdir. Servis geçişinde korkuluk, masaları koruyacak ama geçiş bırakacak şekilde planlanır. Temizlikte yumuşak fırça ve nötr deterjan yeterlidir; dip noktalar yılda bir kontrol edilir, gevşenen sıkma hemen yapılır. Kompozit korkuluk, metal alternatife göre yazın Isınmaz, kışın soğuk tutmaz; dokusu restoran bahçesinin malzeme diliyle uyumludur. İstenirse renk, teklif aşamasında seçilir; kompozit, renk değişikliğine de uyumludur.',
                ],
                'en' => [
                    'baslik' => 'Square Composite Gazebo 5x4 Restaurant Garden',
                    'slug' => 'square-composite-gazebo-5x4',
                    'kisa_aciklama' => 'Square composite gazebo, 5×4 footprint for restaurant gardens. Moisture- and insect-resistant composite and flat roof keep four-season use simple. Seats 11 across 20 m². Measurements and install plan lock after the free survey (Mon–Sat 09:00–18:00). Five-year warranty. Built to order for gardens and terraces that need reliable shade.',
                    'detayli_aciklama' => '**TL;DR**
- 5×4 m (20 m²) Square, four sides floor; an 11-person seating area for restaurant gardens.
- Moisture- and insect-resistant composite, Flat, controlled drainage: four-season use, five-year warranty.
- Transparent m² maths: 20 × 3 USD × 1.8 composite × 1.0 square × 1.3 restaurant = 140.40 USD.
- Final measurements and the installation plan are fixed at the free survey (Mon-Sat 09:00-18:00).

## What Are the Technical Specifications?

| Item | Value |
| --- | --- |
| Floor size | 5×4 m — 20 m² |
| Shape | Square, four sides |
| Main material | composite |
| Roof | Flat, controlled drainage |
| Railing | composite railing, height set per project |
| Surface | Moisture- and insect-resistant composite |
| Warranty | 5 years |

## Who Is It For?

This model is built for restaurant operators and site teams that plan at garden scale.

## How Many People Does It Seat?

Seating follows the approved restaurant gardens ratio from F15: 20 m² ÷ 1.80 m² per person ≈ 11.11, so 11 people sit comfortably.

## How Is It Installed?

The product is made to order.

## How Is It Maintained?

Care is one surface check a year plus a protective refresh when needed.

## Why This Model?

This model balances scale and upkeep for restaurant gardens.

## What About the Roof?

The flat roof meets the modern silhouette and garden line on the same plane; rainwater drains through controlled channels between panels and never drips onto the seating zone. A tiny slope, invisible to the eye, is planned into the mould so drainage is guaranteed and no ponding forms. Wind behaviour is assessed at project scale and settled at the survey against external envelope logic referenced by EN 13561. The plane leaves room for lighting or menu-board mounts. Roof care needs one visual check a year — keep the holes open and rinse with a hose. Edge profiles guide water down the facade and leave no stain marks. Where needed, the drainage plan is discussed with the survey crew on site. The outlet stays above ground level so the surface remains dry. In winter the snow load spreads evenly across the plane instead of concentrating on one point. Cable runs for evening lighting hide under the ceiling; the drip line stays outside the seating circle. Dust and limescale rinse off with a hose; no chemicals are required.

## What About the Railing and Safety?

The composite railing runs at an even height along every edge of the plan, shielding the seating area from cross-wind while keeping privacy inside. Height is confirmed at the survey against safety standards, preferring the dimension that supports the wrist when seated over a fixed catalogue number. Joints lock with cross-braces so the frame does not sway. The surface keeps the material\'s natural grain — smooth to the touch, with no splinters. Railing feet are lifted so water never wicks into the posts; all fixings are chosen for corrosion resistance. Vertical gaps are spaced to prevent hands or balls from jamming, while the top grip line stays flat for elderly residents. Where wheelchair access is needed, one entrance edge opens and leaves a clear passage. Cleaning takes a soft brush and lukewarm water; check the feet yearly. The material doesn\'t overheat in summer or feel cold in winter, and its texture matches the garden\'s palette. The railing can be painted on request; colour is decided at quote stage. Along the 5×4 plan it draws an unbroken safety line. A hidden channel under the top rail accepts a light strip or planter rail without visible screws.
',
                    'seo_baslik' => 'Square Composite Gazebo 5×4 Price List 2026 | Kamelya',
                    'seo_aciklama' => '20 m² Square gazebo seats 11 with flat roof, five-year warranty. Transparent m² pricing and free on-site survey. Book a visit Monday–Saturday 09:00–18:00.',
                    'seo_anahtar_kelimeler' => 'square composite gazebo, composite gazebo prices, 5x4 gazebo, restaurant garden gazebo',
                    'cati_tipi_aciklama' => 'The flat roof meets the modern silhouette and garden line on the same plane; rainwater drains through controlled channels between panels and never drips onto the seating zone. A tiny slope, invisible to the eye, is planned into the mould so drainage is guaranteed and no ponding forms. Wind behaviour is assessed at project scale and settled at the survey against external envelope logic referenced by EN 13561. The plane leaves room for lighting or menu-board mounts. Roof care needs one visual check a year — keep the holes open and rinse with a hose. Edge profiles guide water down the facade and leave no stain marks. Where needed, the drainage plan is discussed with the survey crew on site. The outlet stays above ground level so the surface remains dry. In winter the snow load spreads evenly across the plane instead of concentrating on one point. Cable runs for evening lighting hide under the ceiling; the drip line stays outside the seating circle. Dust and limescale rinse off with a hose; no chemicals are required.',
                    'korkuluk_aciklama' => 'The composite railing runs at an even height along every edge of the plan, shielding the seating area from cross-wind while keeping privacy inside. Height is confirmed at the survey against safety standards, preferring the dimension that supports the wrist when seated over a fixed catalogue number. Joints lock with cross-braces so the frame does not sway. The surface keeps the material\'s natural grain — smooth to the touch, with no splinters. Railing feet are lifted so water never wicks into the posts; all fixings are chosen for corrosion resistance. Vertical gaps are spaced to prevent hands or balls from jamming, while the top grip line stays flat for elderly residents. Where wheelchair access is needed, one entrance edge opens and leaves a clear passage. Cleaning takes a soft brush and lukewarm water; check the feet yearly. The material doesn\'t overheat in summer or feel cold in winter, and its texture matches the garden\'s palette. The railing can be painted on request; colour is decided at quote stage. Along the 5×4 plan it draws an unbroken safety line. A hidden channel under the top rail accepts a light strip or planter rail without visible screws.',
                ],
                'de' => [
                    'baslik' => 'Quadratischer Komposit-Pavillon 5x4 Restaurantgarten',
                    'slug' => 'quadratischer-komposit-pavillon-5x4',
                    'kisa_aciklama' => 'Quadratischer Komposit-Pavillon 5×4 für Restaurantgärten: widerstandsfähiger, pflegeleichter Servicbereich. Kompositprofil widersteht Feuchtigkeit und Insekten; das flache Dach leitet Regenwasser kontrolliert ab. Platz für 11 Personen auf 20 m². Maße und Montageplan werden nach dem kostenlosen Aufmaß fixiert (Mo-Sa 09:00-18:00). 5 Jahre Garantie.',
                    'detayli_aciklama' => '**Kurz gefasst**
- 5×4 m (20 m²) quadratische Fläche; 11-Personen-Servicebereich für Restaurantgärten.
- Kompositprofil, flaches Dach, feuchtigkeits- und insektenfeste Oberfläche: ganzjährige Nutzung, 5 Jahre Garantie.
- Transparente m²-Rechnung: 20 × 2,50 EUR × 1,8 Komposit × 1,0 quadratisch × 1,3 Restaurant = 117,00 EUR.
- Maße und Montageplan werden beim kostenlosen Aufmaß fixiert (Mo-Sa 09:00-18:00).

## Technische Daten

| Merkmal | Wert |
| --- | --- |
| Bodenmaß | 5 × 4 m — 20 m² |
| Form | Quadratisch, vier Seiten |
| Hauptmaterial | Kompositprofil |
| Dach | Flach, kontrollierte Entwässerung |
| Geländer | Komposit, Höhe projektbezogen |
| Oberfläche | Feuchtigkeits- und insektenfest |
| Befestigung | Korrosionsfeste Metallteile |
| Garantie | 5 Jahre |

## Für wen ist er geeignet?

Dieses Modell ist für stark frequentierte Restaurant- und Cafégärten gebaut. Der 20 m²-Boden stellt zwei Servicegruppen nebeneinander: eine für Vierer-Tische, die andere für eine Lounge. Die Kompositoberfläche trocknet nach Regen schnell, macht keine Flecken und schont das Reinigungsteam in der Hochsaison. Das flache Dach fügt sich in Terrassen- und Fassadenlinie ein und lässt Platz für Menütafeln oder Heizstrahler. Für Inhaber vermeidet der Standardgrundriss Kostenüberraschungen beim zweiten Standort. Drei offene Seiten rahmen den Blick; die vierte bleibt dem Servicefluss vorbehalten.

## Kapazität

Die Kapazität folgt dem freigegebenen Restaurant-Faktor aus F15: 20 m² ÷ 1,80 m² pro Person ≈ 11,11 — bequem sitzen also 11 Personen. Der Faktor ist ein dichter Standard, der Raum für Servicekorridore und Tischabstände bereits enthält. Drei Vierer-Tische — oder zwei Tische plus Lounge — passen ideal. Größere Events können das 6×4-Rechteck- oder 5×5-Modell prüfen. Die endgültige Aufstellung wird beim Aufmaß mit dem Serviceflussplan geprüft.

## Montage

Das Modell wird auf Bestellung gefertigt. Beim kostenlosen Aufmaß prüft das Team Boden, Serviceweg, Strom- und Lichtbedarf; Maße und Montageplan werden dort fixiert. Termine laufen Montag bis Samstag, 09:00-18:00. Das Komposit-Panel-System beschleunigt Transport und Aufbau; Material geht direkt vom Fahrzeug an den Montagepunkt. Betonplatte oder fertige Terrasse genügen; die Befestigung wird beim Aufmaß besprochen. Nach Abschluss kann die Fläche sofort bewirtet werden.

## Pflege

Die Pflege besteht aus einer Waschung und einer Beschlagskontrolle pro Jahr; die Kompositoberfläche braucht keinen Lack und keine Imprägnierung. Weiche Bürste und neutrales Reinigungsmittel genügen — keine abrasiven Mittel. Laub und Schmutz vom flachen Dach zu Saisonbeginn entfernen, Drainageöffnungen offen halten. Metallteile sind korrosionsfest; lose Stellen bei der ersten Kontrolle nachziehen. Die Struktur behält ihre Originalfarbe dank UV-Beständigkeit viele Jahre.

## Häufige Fragen

**Wie lange dauert die Montage?** Das Komposit-Panel-System hält den Bau kurz; der genaue Tag wird beim Aufmaß bestätigt.
**Kann ich die Maße ändern?** Ja — jedes Maß wird auf Bestellung gefertigt; 5×4 ist nur der Standard-Ausgangspunkt.
**Was ist im Preis enthalten?** Transparente m²-Rechnung; Produkt- und Montagepositionen stehen getrennt im Angebot.
**Ist die Pflege wirklich so einfach?** Ja — die Kompositoberfläche braucht keinen Lack und keine Imprägnierung.

## Preis

Transparente m²-Rechnung: 20 m² × 2,50 EUR (Basis) × 1,8 (Komposit) × 1,0 (quadratisch) × 1,3 (Restaurant) = **117,00 EUR**. Die Faktoren entsprechen den aktiven Zeilen der Preis-Faktoren; der Rechner der Website liefert dasselbe Ergebnis. Das finale Angebot steht nach dem kostenlosen Aufmaß. Aufmaß-Termin: Montag bis Samstag, 09:00-18:00.',
                    'seo_baslik' => 'Quadratischer Komposit-Pavillon 5x4 Preise 2026 | Kamelya',
                    'seo_aciklama' => 'Quadratischer Komposit-Pavillon 5x4: 20 m², für 11, feuchtigkeitsfest, flaches Dach, Restaurantgarten. Transparente m²-Preise, kostenloser Aufmaß.',
                    'seo_anahtar_kelimeler' => 'quadratischer Komposit-Pavillon, Komposit-Pavillon Preise, 5x4 Pavillon, Pavillon Restaurantgarten',
                    'cati_tipi_aciklama' => 'Das flache Dach des quadratischen Komposit-Pavillons trifft Terrassen- und Fassadenlinie auf derselben Ebene; Regenwasser läuft durch kontrollierte Kanäle zwischen den Paneln ab und tropft nie auf die Sitzfläche. Heizstrahler, Menütafeln oder Pergola-Add-ons lassen sich am Flachdach einfach fixieren — kein Neigungswinkel nötig. Die Kompositverkleidung bietet eine neutrale Fläche, bei der Metall und Holz nicht kollidieren, und UV-Beständigkeit hält die Farbe viele Jahre. Das Windverhalten wird projektmäßig bewertet und beim Aufmaß an der Logik externer Fassadenelemente mit EN-13561-Bezug abgeglichen. Eine winzige, mit bloßem Auge unsichtbare Neigung ist in die Form geplant, damit auf dem Flachdach kein Wasser stehen bleibt und die Entwässerung garantiert ist. Drainageöffnungen sitzen in den Ecken, wo Laub und Schmutz nicht liegen bleiben. Zur Dachpflege genügt eine Sichtkontrolle pro Jahr — Öffnungen offen halten, Oberfläche mit dem Schlauch spülen. Komposit fault nicht und kennt keine Insekten; anders als Holzdächer braucht es keine Imprägnierung. Im Winter verteilt sich Schnee gleichmäßig auf der Flachform statt zur Einzellast zu werden. Kantenprofile führen das Wasser kontrolliert an der Fassade hinab und hinterlassen keine Flecken. Bei Bedarf wird der Entwässerungsplan mit dem Aufmaß-Team vor Ort besprochen.',
                    'korkuluk_aciklama' => 'Das Kompositgeländer läuft an allen vier Kanten des quadratischen Grundrisses in gleicher Höhe weiter und trennt die Tische von äußeren Einflüssen im dichten Restaurantbetrieb. Die Höhe wird beim Aufmaß anhand von Sicherheitsnormen und Projektbedingungen bestätigt; statt einer festen Katalogzahl wird das Maß gewählt, das das Handgelenk im Sitzen stützt. Eckverbindungen werden mit verdeckten Beschlägen verriegelt, damit nichts wackelt. Die Oberfläche erhält eine UV-beständige Beschichtung, die die natürliche Kompositstruktur bewahrt — glatt bei der Berührung, ohne Splitter und ohne Fäulnis. Die Geländerfüße werden angehoben, damit bodennahes Wasser nicht in die Pfosten steigt, und alle Beschläge sind gegen Korrosion ausgelegt. In stark frequentierten Bereichen sind die Vertikalabstände so gesetzt, dass Stühle und Tabletts nicht klemmen; die obere Griffleitung bleibt durchgehend gerade. Am Serviceweg schützt das Geländer die Tische und lässt trotzdem freie Passage. Zur Reinigung genügen weiche Bürste und neutrales Mittel; die Füße einmal jährlich prüfen, lose Stellen sofort nachziehen. Gegenüber Metall wird Komposit im Sommer nicht heiß und im Winter nicht eiskalt, und die Textur passt zur Materialsprache des Restaurantgartens. Die Farbe kann beim Angebot gewählt werden; Komposit nimmt Farbwechsel problemlos an.',
                ],
                'fr' => [
                    'baslik' => 'Gazebo Carré Composite 5x4 Jardin de Restaurant',
                    'slug' => 'gazebo-carre-composite-5x4',
                    'kisa_aciklama' => 'Gazebo carré en composite de 5×4 m pour jardins de restaurant : espace de service durable et facile d’entretien. Le profil composite résiste à l’humidité et aux insectes ; le toit plat évacue l’eau de pluie de manière contrôlée. 11 personnes sur 20 m². Dimensions et plan de pose fixés après l’étude gratuite (lun.-sam. 09h00-18h00). Garantie 5 ans.',
                    'detayli_aciklama' => '**En bref**
- Sol carré 5×4 m (20 m²) ; espace de service pour 11 personnes dans les jardins de restaurant.
- Profil composite, toit plat, surface résistante à l’humidité et aux insectes : usage toute saison, garantie 5 ans.
- Calcul m² transparent : 20 × 2,50 EUR × 1,8 composite × 1,0 carré × 1,3 restaurant = 117,00 EUR.
- Dimensions et plan de pose fixés pendant l’étude gratuite (lun.-sam. 09h00-18h00).

## Caractéristiques techniques

| Caractéristique | Valeur |
| --- | --- |
| Emprise au sol | 5 × 4 m — 20 m² |
| Forme | Carrée, quatre côtés |
| Matériau principal | Profil composite |
| Toit | Plat, drainage contrôlé |
| Garde-corps | Composite, hauteur définie par projet |
| Surface | Résistante à l’humidité et aux insectes |
| Fixations | Pièces métalliques résistantes à la corrosion |
| Garantie | 5 ans |

## Pour qui ?

Ce modèle est conçu pour les jardins de restaurants et cafés à fort trafic. Les 20 m² carrés placent deux groupes de service côte à côte : l’un pour des tables de quatre, l’autre pour un salon détendu. La surface composite sèche vite après la pluie, ne tache pas et ménage l’équipe de nettoyage en haute saison. Le toit plat s’aligne sur la terrasse et la façade et laisse la place aux enseignes de menu ou aux chauffeurs. Pour les exploitants, l’empreinte standard évite les surprises de coût à l’ouverture d’une seconde adresse. Trois côtés ouverts cadrent la vue ; le quatrième reste réservé au flux de service.

## Capacité

La capacité suit le facteur restaurant approuvé en F15 : 20 m² ÷ 1,80 m² par personne ≈ 11,11 — soit confortablement 11 personnes. Le facteur est un standard dense qui réserve déjà les couloirs de service et les entraxes. Trois tables de quatre — ou deux tables plus un salon — conviennent idéalement. Les événements plus larges peuvent envisager les modèles rectangulaires 6×4 ou modernes 5×5. Le placement final est vérifié pendant l’étude avec le plan de flux de service.

## Pose

Le gazebo est fabriqué sur commande. L’étude gratuite examine le sol, le parcours de service, l’électricité et l’éclairage ; dimensions et plan de pose s’y fixent. Les rendez-vous couvrent du lundi au samedi, 09h00-18h00. Le système de panneaux composite accélère transport et montage ; le matériel va du véhicule au point d’assemblage. Une dalle béton ou une terrasse finie conviennent ; le mode de fixation se précise à l’étude. La zone peut ouvrir au service dès la fin des travaux.

## Entretien

L’entretien tient en un lavage et un contrôle des fixations par an ; la surface composite n’exige ni peinture ni traitement. Une brosse douce et un détergent neutre suffisent — pas d’abrasifs. Retirez feuilles et saleté du toit plat en début de saison et gardez les éviers ouverts. Les pièces métalliques résistent à la corrosion ; resserrez ce qui bouge dès le premier contrôle. La texture conserve sa couleur d’origine des années grâce à la résistance UV.

## Questions fréquentes

**Combien de temps dure la pose ?** Le système de panneaux composite garde le chantier court ; le jour exact se fixe à l’étude.
**Puis-je modifier les dimensions ?** Oui — tout est fabriqué sur commande ; 5×4 n’est qu’un point de départ par défaut.
**Que comprend le prix ?** Calcul m² transparent ; produit et pose listés séparément dans le devis.
**L’entretien est-il vraiment simple ?** Oui — la surface composite n’exige ni peinture ni traitement.

## Prix

Calcul m² transparent : 20 m² × 2,50 EUR (base) × 1,8 (composite) × 1,0 (carré) × 1,3 (restaurant) = **117,00 EUR**. Les multiplicateurs sont ceux des lignes actives des facteurs de prix ; le calculateur du site donne le même résultat. Le devis final se stabilise après l’étude gratuite. Étude : du lundi au samedi, 09h00-18h00.',
                    'seo_baslik' => 'Gazebo Carré Composite 5x4 Prix et Devis 2026 | Kamelya',
                    'seo_aciklama' => 'Gazebo carré composite 5x4 : 20 m², 11 places, résistant à l’humidité, toit plat, jardin de restaurant. Prix au m² transparents, étude gratuite.',
                    'seo_anahtar_kelimeler' => 'gazebo carré composite, gazebo composite prix, gazebo 5x4, gazebo jardin restaurant',
                    'cati_tipi_aciklama' => 'Le toit plat du gazebo carré composite rejoint la terrasse moderne et la ligne de façade sur le même plan ; l’eau de pluie s’évacue par des canaux contrôlés entre panneaux et ne tombe jamais sur l’assise. Chauffeurs, enseignes de menu ou ajouts de pergola se fixent facilement sur la forme plane — aucune pente à chercher. Le bardage composite offre une surface neutre où métal et bois ne se heurtent pas, et la résistance UV conserve la couleur des années. Le comportement au vent s’évalue à l’échelle du projet et se fixe à l’étude selon la logique des éléments d’enveloppe extérieure référencés par l’EN 13561. Une pente minuscule, invisible à l’œil, est prévue dans le moule pour qu’aucune flaque ne subsiste sur le toit plat et que l’évacuation soit garantie. Les éviers se placent aux angles où feuilles et saleté ne s’accumulent pas. L’entretien du toit demande un contrôle visuel par an — gardez les ouvertures ouvertes et rincez la surface au tuyau. Le composite ne pourrit pas et n’accueille pas d’insectes ; contrairement au bois, aucun traitement n’est nécessaire. En hiver, la neige se répartit également sur la forme plane au lieu de former une charge unique. Les profils de bord guident l’eau vers la façade de façon contrôlée sans laisser de tache. Si besoin, le plan d’évacuation se discute avec l’équipe d’étude sur place.',
                    'korkuluk_aciklama' => 'Le garde-corps composite court à hauteur égale sur les quatre arêtes du plan carré et sépare les tables des effets extérieurs dans la fréquentation dense d’un restaurant. Sa hauteur se confirme à l’étude selon les normes de sécurité et les conditions du projet, en préférant la mesure qui soutient le poignet assis plutôt qu’un chiffre de catalogue fixe. Les assemblages d’angle se verrouillent par fixations cachées pour éviter tout balancement. La surface reçoit un revêtement UV résistant qui conserve le grain naturel du composite — douce au toucher, sans échardes et sans pourriture. Les pieds sont surélevés pour que l’eau du sol ne remonte pas dans les poteaux, et toutes les pièces d’attache résistent à la corrosion. Dans les zones à fort trafic, les entraxes verticaux empêchent chaises et plateaux de se coincer ; la ligne de saisie supérieure reste droite et continue. Le long du parcours de service, le garde-corps protège les tables tout en laissant une trajectoire libre. Le nettoyage demande une brosse douce et un détergent neutre ; contrôlez les pieds une fois par an et resserrez aussitôt ce qui bouge. Contrairement au métal, le composite ne chauffe pas en été ni ne givre en hiver, et sa texture s’accorde au langage matériel du jardin de restaurant. La couleur se choisit au devis ; le composite accepte les changements de teinte sans difficulté.',
                ],
                'it' => [
                    'baslik' => 'Gazebo Quadrato Composite 5x4 Giardino Ristorante',
                    'slug' => 'gazebo-quadrato-composite-5x4',
                    'kisa_aciklama' => 'Gazebo quadrato in composito da 5×4 m per giardini di ristorante: area di servizio durevole e a bassa manutenzione. Il profilo composito resiste all’umidità e agli insetti; il tetto piatto drena l’acqua piovana in modo controllato. 11 persone su 20 m². Misure e piano di posa fissati dopo il sopralluogo gratuito (lun.-sab. 09:00-18:00). Garanzia 5 anni.',
                    'detayli_aciklama' => '**In breve**
- Pavimento quadrato 5×4 m (20 m²) ; area di servizio per 11 persone nei giardini di ristorante.
- Profilo composito, tetto piatto, superficie resistente a umidità e insetti: uso tutto l’anno, garanzia 5 anni.
- Calcolo m² trasparente: 20 × 2,50 EUR × 1,0 composito × 1,0 quadrato × 1,3 ristorante = 117,00 EUR.
- Misure e piano di posa fissati durante il sopralluogo gratuito (lun.-sab. 09:00-18:00).

## Specifiche tecniche

| Voce | Valore |
| --- | --- |
| Impronta a terra | 5 × 4 m — 20 m² |
| Forma | Quadrata, quattro lati |
| Materiale principale | Profilo composito |
| Tetto | Piatto, scarico controllato |
| Parapetto | Composito, altezza definita dal progetto |
| Superficie | Resistente a umidità e insetti |
| Fissaggi | Parti metalliche resistenti alla corrosione |
| Garanzia | 5 anni |

## Per chi è?

Questo modello è pensato per giardini di ristoranti e caffè ad alto traffico. Il pavimento quadrato da 20 m² dispone due gruppi di servizio affiancati: uno per tavoli da quattro, l’altro per un lounge rilassato. La superficie composita si asciuga subito dopo la pioggia, non macchia e non stanca il personale di pulizia in alta stagione. Il tetto piatto si allinea a terrazza e facciata e lascia spazio a insegne menu o radiatori. Per i gestori, l’impronta standard evita sorprese di costo alla seconda apertura. Tre lati aperti inquadrano la vista; il quarto resta riservato al flusso di servizio.

## Capienza

La capienza segue il fattore ristorante approvato in F15: 20 m² ÷ 1,80 m² a persona ≈ 11,11 — quindi 11 persone comodamente. Il fattore è uno standard denso che riserva già corridoi di servizio e distanze tra tavoli. Tre tavoli da quattro — o due tavoli più un lounge — convivono idealmente. Per eventi più ampi valutare i modelli rettangolari 6×4 o moderni 5×5. La posa finale si verifica al sopralluogo con il piano del flusso di servizio.

## Posa

Il gazebo è prodotto su ordinazione. Il sopralluogo gratuito valuta terreno, percorso di servizio, elettricità e illuminazione; misure e piano di posa si fissano lì. Gli appuntamenti vanno da lunedì a sabato, 09:00-18:00. Il sistema a pannelli compositi accelera trasporto e montaggio; i materiali vanno dal mezzo al punto di assemblaggio. Solaio in cemento o terrazza finita vanno bene; il tipo di fissaggio si definisce al sopralluogo. L’area può aprire al servizio appena finiti i lavori.

## Manutenzione

La manutenzione è un lavaggio e un controllo dei fissaggi all’anno; la superficie composita non richiede vernice né trattamento. Spazzola morbida e detergente neutro bastano — niente abrasivi. Togliere foglie e sporco dal tetto piatto a inizio stagione e tenere aperti gli scarichi. Le ferramenta sono a prova di corrosione; stringere subito ciò che è mosso al primo controllo. La texture mantiene il colore originale per anni grazie alla resistenza UV.

## Domande frequenti

**Quanto dura la posa?** Il sistema a pannelli compositi tiene il cantiere corto; il giorno esatto si fissa al sopralluogo.
**Posso cambiare le misure?** Sì — ogni pezzo è su ordinazione; 5×4 è solo il punto di partenza predefinito.
**Cosa comprende il prezzo?** Calcolo m² trasparente; prodotto e posa elencati separatamente nel preventivo.
**La manutenzione è davvero semplice?** Sì — la superficie composita non richiede vernice né trattamento.

## Prezzo

Calcolo m² trasparente: 20 m² × 2,50 EUR (base) × 1,8 (composito) × 1,0 (quadrato) × 1,3 (ristorante) = **117,00 EUR**. I moltiplicatori corrispondono alle righe attive dei fattori di prezzo; il calcolatore del sito produce lo stesso risultato. Il preventivo definitivo si stabilisce dopo il sopralluogo gratuito. Sopralluogo: da lunedì a sabato, 09:00-18:00.',
                    'seo_baslik' => 'Gazebo Quadrato Composite 5x4 Prezzi nel 2026 | Kamelya',
                    'seo_aciklama' => 'Gazebo quadrato composito 5x4 per ristoranti: 20 m², 11 posti, superficie resistente all’umidità, tetto piatto. Prezzi al m², sopralluogo gratuito.',
                    'seo_anahtar_kelimeler' => 'gazebo quadrato composito, gazebo composito prezzi, gazebo 5x4, gazebo giardino ristorante',
                    'cati_tipi_aciklama' => 'Il tetto piatto del gazebo quadrato composito incontra terrazza moderna e linea di facciata sullo stesso piano; l’acqua piovana si drena attraverso canali controllati tra i pannelli e non gocciola mai sulla seduta. Radiatori, insegne menu o aggiunte di pergola si fissano facilmente sulla forma piana — nessuna pendenza da cercare. Il rivestimento composito offre una superficie neutra dove metallo e legno non si scontrano, e la resistenza UV mantiene il colore per anni. Il comportamento al vento si valuta a scala di progetto e si definisce al sopralluogo secondo la logica degli elementi di involucro esterno richiamati dalla EN 13561. Una pendenza minuscola, invisibile a occhio nudo, è prevista nello stampo perché sul tetto piatto non resti pozzanghera e lo scarico sia garantito. Le bocchette stanno agli angoli dove foglie e sporco non si raccolgono. La manutenzione del tetto richiede un controllo visivo all’anno — tenere aperte le bocchette e sciacquare la superficie con il tubo. Il composito non marcisce e non ospita insetti; a differenza del legno non serve trattamento. D’inverno la neve si distribuisce in modo uniforme sulla forma piana invece di diventare un carico unico. I profili di bordo guidano l’acqua verso la facciata in modo controllato senza lasciare macchie. Se serve, il piano di scarico si discute con l’equipaggio del sopralluogo in loco.',
                    'korkuluk_aciklama' => 'Il parapetto composito prosegue ad altezza uguale su tutti i quattro spigoli del piano quadrato e separa i tavoli dagli effetti esterni nel traffico intenso del ristorante. L’altezza si conferma al sopralluogo secondo norme di sicurezza e condizioni di progetto, preferendo la misura che sorregge il polso da seduti a un numero fisso di catalogo. Le giunzioni d’angolo si bloccano con fissaggi nascosti che eliminano l’oscillazione. La superficie riceve una finitura UV resistente che conserva la grana naturale del composito — liscia al tatto, senza scaglie e senza marcescenza. I piedi sono sollevati perché l’acqua del terreno non risalga nei pali, e tutte le ferramenta sono a prova di corrosione. Nelle aree ad alto traffico gli interassi verticali impediscono che sedie e vassoi restino incastrati; la linea di presa superiore resta dritta e continua. Lungo il percorso di servizio il parapetto protegge i tavoli e lascia comunque transito libero. La pulizia richiede spazzola morbida e detergente neutro; controllare i piedi una volta l’anno e stringere subito ciò che è mosso. Rispetto al metallo, il composito non scalda d’estate né gela d’inverno, e la texture si accorda al linguaggio materiale del giardino del ristorante. Il colore si sceglie in fase di preventivo; il composito accetta cambi di tinta senza difficoltà.',
                ],
                'ar' => [
                    'baslik' => 'كوش مربع كومبوزيت 5x4 لحدائق المطاعم',
                    'slug' => 'square-composite-kush-5x4',
                    'kisa_aciklama' => 'كوش مربع من الكومبوزيت بمقاس 5×4 متر لحدائق المطاعم: منطقة خدمة متينة. بروفايل كومبوزيت يقاوم الرطوبة والحشرات؛ السقف المستوي يصرف مياه الأمطر. يتسع لـ 11 شخصًا على 20 م². تُثبَّت المقاسات وخطة التركيب بعد الاستشارة المجانية (الاثنين–السبت 09:00–18:00). ضمان 5 سنوات.',
                    'detayli_aciklama' => '**باختصار**
- أرضية مربعة 5×4 م (20 م²)؛ منطقة خدمة وجلوس تتسع لـ 11 شخصًا في حدائق المطاعم.
- بروفايل كومبوزيت، سقف مستوي، سطح يقاوم الرطوبة والحشرات: استخدام على مدار السنة، ضمان 5 سنوات.
- حساب شفاف للمتر: 20 × 2.50 EUR × 1.8 كومبوزيت × 1.0 مربع × 1.3 مطعم = 117.00 EUR.
- تُثبَّت المقاسات وخطة التركيب في الاستشارة المجانية (الاثنين–السبت 09:00–18:00).

## المواصفات التقنية

| البند | القيمة |
| --- | --- |
| مقاس الأرضية | 5 × 4 م — 20 م² |
| الشكل | مربع، أربعة أضلاع |
| المادة الأساسية | بروفايل كومبوزيت |
| السقف | مستوٍ، تصريف مضبوط |
| السور | كومبوزيت، الارتفاع حسب المشروع |
| السطح | يقاوم الرطوبة والحشرات |
| التثبيت | قطع معدنية مقاومة للصدأ |
| الضمان | 5 سنوات |

## لمن صُمم؟

هذا الموديل لحدائق المطاعم والمقاهي ذات الحركة الكثيفة. أرضية 20 م² المربعة تضع مجموعتي خدمة جنبًا إلى جنب: واحدة لطاولات الأربعة وأخرى لصالة مريحة. يجف السطح الكومبوزيت سريعًا بعد المطر ولا يمسك البقع، فيريح فريق التنظيف في الذروة. يتواءم السقف المستوي مع خط التراس والواجهة ويترك مكانًا للوحات القوائم أو السخانات. بالنسبة لل팸ّالات، يمنع المقاس القياسي مفاجآت التكلفة عند فرع ثالث. ثلاثة وجوه مفتوحة تؤطر المشهد؛ أما الرابع فمخصّص لمسار الخدمة.

## السعة

تُحسب السعة ومعامل المطعم المعتمد من F15: 20 م² ÷ 1.80 م² للشخص ≈ 11.11 — أي 11 شخصًا براحة. المعامل معيار كثيف يخصّص مسارات الخدمة وتباعد الطاولات منذ البداية. ثلاث طاولات لأربعة — أو طاولتان وصالة — مناسبة تمامًا. للاحتفالات الأكبر توجد موديلات 6×4 المستطيلة أو 5×5 العصرية. يُتحقق من التوضع النهائي أثناء الاستشارة مع خطة مسار الخدمة.

## التركيب

يُصنع الموديل عند الطلب. في الاستشارة المجانية يفحص الفريق الأرضية ومسار الخدمة وحاجة الكهرباء والإضاءة؛ وتُثبَّت المقاسات وخطة التركيب في ذلك الموعد. المواعيد من الاثنين إلى السبت 09:00–18:00. نظام ألواح الكومبوزيت يسرّع النقل والتركيب؛ تُنقل المواد مباشرة من السيارة إلى نقطة التركيب. بلاطة خرسانية أو تراس مهيأ كفاية؛ تُحدَّد طريقة التثبيت في الاستشارة. يمكن فتح المساحة للخدمة فور انتهاء العمل.

## الصيانة

الصيانة غسلة وفحص تثبيتات سنويًا؛ السطح الكومبوزيت لا يحتاج دهانًا ولا معالجة. تكفي فرشاة ناعمة ومحلول محايد — بلا منظفات كاشطة. تُنظَّف أوراق الأرضية المستوية واتربتها في بداية الموسم وتبقى فتحات التصريف مفتوحة. القطع المعدنية مقاومة للصدأ؛ يُشدّ المحكم فورًا. يحافظ النسيج على لونه الأصلي لسنوات بفضل مقاومة الأشعة.

## أسئلة شائعة

**كم يستغرق التركيب؟** نظام ألواح الكومبوزيت يختصر العمل؛ اليوم الدقيق يُحدَّد في الاستشارة.
**هل يمكن تغيير المقاسات؟** نعم — كل القطع تُصنع عند الطلب؛ 5×4 نقطة انطلاق افتراضية فقط.
**ما الذي يشمله السعر؟** حساب متر شفاف؛ المنتج والتركيب يُذكران منفصلين في العرض.
**هل الصيانة بهذه البساطة فعلًا؟** نعم — السطح الكومبوزيت لا يحتاج دهانًا ولا معالجة.

## السعر

حساب متر شفاف: 20 م² × 2.50 EUR (أساسي) × 1.8 (كومبوزيت) × 1.0 (مربع) × 1.3 (مطعم) = **117.00 EUR**. المضاعفات مطابقة للصفوف النشطة في جدول معاملات السعر؛ آلة الموقع تعطي النتيجة نفسها. يستقر العرض النهائي بعد الاستشارة المجانية. موعد الاستشارة: الاثنين–السبت 09:00–18:00.',
                    'seo_baslik' => 'كوش مربع كومبوزيت أسعار 2026 | Kamelya',
                    'seo_aciklama' => 'كوش مربع كومبوزيت: 20 م²، 11 شخصًا، سقف مستوي، حديقة مطعم. أسعار متر شفافة، استشارة مجانية.',
                    'seo_anahtar_kelimeler' => 'كوش كومبوزيت مربع, أسعار الكوش الكومبوزيت, كوش 5x4, كوش حدائق المطاعم',
                    'cati_tipi_aciklama' => 'يلتقي السقف المستوي للكوش المربع الكومبوزيت مع خط التراس العصري والواجهة على المستوى نفسه؛ تُصرف مياه الأمطر عبر قنوات مضبوطة بين الألواح ولا تتقاطر أبدًا على منطقة الجلوس. تُثبَّت السخانات ولوحات القوائم أو إضافات المظلة بسهولة على الشكل المستوي — بلا حاجة لبحث عن ميل. يمنح التكسية الكومبوزيت سطحًا محايدًا لا يتعارض فيه المعدن مع الخشب، ومقاومة الأشعة تحفظ اللون لسنوات. يُقيَّم سلوك الرياح على مستوى المشروع ويُحسم في الاستشارة وفق منطق عناصر الغلاف الخارجي المرجعية (EN 13561). يُخطط ميل صغير غير مرئي داخل القالب كي لا تتكوّن بركة على السقف المستوي ويكون التصريف مضمونًا. توضع فتحات التصريف في الزوايا حيث لا تتراكم الأوراق والأتربة. صيانة السقف فحص بصري سنوي — إبقاء الفتحات مفتوحة وشطف السطح بالخرطوم. الكومبوزيت لا يتعفن ولا يستقبل الحشرات؛ بخلاف الخشب لا يحتاج معالجة. في الشتاء يتوزع الثلج بالتساوي على الشكل المستوي بدل أن يصبح حملًا واحدًا. تقود الحواف المائية إلى الواجهة بشكل مضبوط دون أن تترك بقعًا. وعند الحاجة يُناقش خطة التصريف مع فريق الاستشارة في الموقع.',
                    'korkuluk_aciklama' => 'يستمر سور الكومبوزيت بارتفاع متساوٍ على الحواف الأربعة للخطة المربعة ويفصل الطاولات عن المؤثرات الخارجية في حركة المطعم الكثيفة. يُثبَّت الارتفاع في الاستشارة وفق معايير السلامة وظروف المشروع، مع تفضيل المقاس الذي يسند المعصم أثناء الجلوس على رقم كتالوج ثابت. تُقفل زوايا الالتقاء بتثبيتات مخفية تمنع الاهتزاز. يُجهَّز السطح بطلاء مقاوم للأشعة يحافظ على نسيج الكومبوزيت الطبيعي — ناعم عند اللمس دون شظايا ودون تعفن. تُرفع القواعد كي لا يصعد ماء الأرض إلى الجذوع، وتُختار كل القطع المعدنية مقاومة للصدأ. في الأماكن الكثيفة تُضبط المسافات الرأسية كي لا تعلق الكراسي والأطباق، ويبقى خط القبضة العلوي مستقيمًا ومتصلًا. على مسار الخدمة يحمي السور الطاولات ويترك ممرًا حرًا في الوقت نفسه. التنظيف بفرشاة ناعمة ومحلول محايد يكفي؛ فحص القواعد سنويًا وشدّ المحكم فورًا. مقارنةً بالمعدن لا يسخن الكومبوزيت صيفًا ولا يُثلج شتاءً، ونسجه ينسجم مع لغة مواد حديقة المطعم. تبقى زوايا الوصل مغلقة فلا تتراكم فيها الأتربة. يُختار اللون في مرحلة العرض؛ يستوعب الكومبوزيت تغيير الألوان بسهولة.',
                ],
            ],
            'KML-AHS-DIK-005' => [
                'tr' => [
                    'baslik' => 'Dikdörtgen Ahşap Kamelya 6x4 Site Bahçesi',
                    'slug' => 'dikdortgen-ahsap-kamelya-6x4',
                    'kisa_aciklama' => 'Dikdörtgen ahşap kamelya, 6×4 metre tabanıyla site bahçelerine geniş ve esnek bir oturma alanı sunar. Kaliteli çam keresteden emprenye edilerek üretilir; eğimli çatısı ve ahşap oluğu yağmuru uzağa taşır. Kapasitesi 6-7 kişidir. Net ölçü ve montaj planı ücretsiz keşifte belirlenir; 5 yıl garantilidir.',
                    'detayli_aciklama' => '**TL;DR**
- 6×4 m (24 m²) dikdörtgen taban; site bahçeleri için 6-7 kişilik geniş ve esnek oturma alanı.
- Kaliteli çam, emprenye, eğimli çatı ve ahşap oluk: dört mevsim kullanım, 5 yıl garanti.
- Şeffaf m² hesabı: 24 × 12.000 × 1,0 (ahşap) × 1,0 (dikdörtgen) × 1,0 (site) = 288.000 TL.
- Net ölçü ve montaj planı ücretsiz keşifte sabitlenir (Pzt–Cmt 09:00–18:00).

## Teknik Özellikleri Nelerdir?

| Alan | Değer |
| --- | --- |
| Taban ölçüsü | 6 × 4 m — 24 m² |
| Form | Dikdörtgen, dört kenar |
| Ana malzeme | Kaliteli çam kereste |
| Çatı | Eğimli, ahşap oluklu drenaj |
| Korkuluk | Ahşap, proje bazlı yükseklik |
| Yüzey işlemi | Emprenye — çürüme ve böceğe karşı |
| Bağlantı | Korozyona dayanıklı metal elemanlar |
| Garanti | 5 yıl |

## Kimler İçin?

Bu model, geniş bahçe alanları yöneten site yönetimleri için tasarlandı. 24 m² dikdörtgen taban, iki farklı kullanım bölgesini yan yana kurmaya izin verir: bir ucu yemek masası grubuna, diğer ucu.

## Kaç Kişiliktir?

Kapasite, F15 onaylı site bahçesi katsayısıyla hesaplanır: 24 m² ÷ 3,50 m²/kişi ≈ 6,86 — yani 6-7 kişi rahat oturur. Bu katsayı bilinçli olarak yüksektir; mahremiyet ve kol hareketi serbestliği,.

## Montaj Nasıl Yapılır?

Ürün sipariş üzerine üretilir. Ücretsiz keşifte zemin, giriş yönü ve varsa elektrik ihtiyacı değerlendirilir; ölçü ve montaj planı o gün sabitlenir. Keşif randevuları Pazartesi–Cumartesi 09:00–18:00 arasındadır. Dikdörtgen taban, uzun kenar.

## Bakımı Nasıl Yapılır?

Bakım yılda bir kez yüzey kontrolü ve gerekirse koruyucu kaplama tazelemesinden ibarettir; iki yılda bir vernik yeterlidir. Ahşap yüzey, yumuşak fırça ve ılık suyla temizlenir; basınçlı su kullanılmaz.

## Sıkça Sorulan Sorular

**Montaj ne kadar sürer?** Dikdörtgen şablon sayesinde kurulum hızlıdır; kesin gün keşifte netleşir. **Ölçüler değiştirilebilir mi?** Evet; her ürün sipariş üzerine üretilir — 6×4 standart başlangıç noktasıdır.

## Fiyat

Şeffaf m² hesabı: 24 m² × 12.000 TL (temel) × 1,0 (ahşap) × 1,0 (dikdörtgen) × 1,0 (site) = **288.000 TL**. Çarpanlar, fiyat_carpanlari tablosundaki aktif satırlarla birebir aynıdır; web sitesindeki.

## Çatı Özellikleri Nelerdir?

Dikdörtgen kamelyanın eğimli çatısı, uzun kenar boyunca merkeze doğru hafif eğimlenir; böylece yağmur ve kar suyu dört cepheden dengeli şekilde tahliye olur, tabanda su birikmez. Eğim, karın tek bir yüke toplanmasını da engeller. Oluk detayı, ahşabın doğal diline uygun bir çözümdür: su, oluğa yönlendirilip yapıdan uzaklaştırılır; damlayan su ahşap korkuluğa ve oturma alanına isabet etmez. Çatı kaplaması, çam kerestesiyle uyumlu ahşap yüzey olarak üretilir; metal ile ahşap çarpışmaz. Rüzgâr yükü davranışı, proje ölçeğinde değerlendirilir ve alan, EN 13561 referansıyla tartışılan dış cephe elemanları mantığına göre keşifte netleşir. Dikdörtgen formda çatı dıştan bakıldığında da bütünlüklüdür: her cephe aynı eğimi taşır, asimetri girmez. Drenaj yönü, girişin tersine kurulur; oturma alanı her zaman kuru tarafta kalır. Çatı bakımına yılda bir yüzey kontrolü yeterlidir; oluk içindeki yaprak temizlenir, ahşap yüzey kuru tutulur. Yağmur direnci, eğim ve yüzey işçiliği birlikte çalışır; su yüzeyde durmak yerine akar. Oluk çıkışı zemin kotunun üstünde tutulur, su kontrollü iner. Gerektiğinde eğim açısı araziye göre keşif ekibiyle konuşulur.

## Korkuluk ve Güvenlik Özellikleri Nelerdir?

Ahşap korkuluk, dikdörtgen planın dört kenarında eşit yükseklikte devam eder ve oturma alanını dışarıdan gelen rüzgâra karşı korurken içeride mahremiyet hissi bırakır. Yükseklik, güvenlik standartlarına ve proje koşullarına göre keşif sırasında netleşir; sabit bir katalog rakamı yerine, kullanıcının oturduğu anda bileğini destekleyen ölçü tercih edilir. Köşe birleşim noktaları çapraz bağlantılarla kilitlenir, sallanma önlenir. Yüzey, çamın doğal dokusunu koruyan emprenye ve koruyucu kaplamayla hazırlanır; elle temas pürüzsüzdür, yonga bırakmaz. Korkuluk dip kısımları, zemine geçen suyu gövdeye taşımaması için ahşap takozlarla yükseltilir ve bağlantı elemanları korozyona dayanıklı malzemeden seçilir. Çocuklu alanlarda dikey aralıklar, topun veya kolun sıkışmasını önleyecek şekilde düzenlenir; yaşlı sakinler için üst tutamak hattı düz ve kesintisizdir. Tekerlekli sandalye erişimi gereken giriş kenarında korkuluk açılıp geçiş bırakacak şekilde planlanır. Temizlikte yumuşak fırça ve ılık su yeterlidir; dip noktalar yılda bir kontrol edilir, gevşenen sıkma hemen yapılır. Ahşap korkuluk, metal alternatife göre yazın ısınmaz, kışın soğuk tutmaz; dokusu bahçenin malzeme diliyle uyumludur. İstenirse boyanabilir; renk değişikliği teklif aşamasında belirlenir.
',
                    'seo_baslik' => 'Dikdörtgen Ahşap Kamelya 6x4 Fiyat Listesi 2026 | Kamelya',
                    'seo_aciklama' => 'Dikdörtgen ahşap kamelya 6x4: 24 m² alan, 6-7 kişilik site bahçesi, emprenye çam ve eğimli çatı, 5 yıl garantili. Şeffaf m² fiyat, ücretsiz keşif.',
                    'seo_anahtar_kelimeler' => 'dikdörtgen ahşap kamelya, ahşap kamelya fiyatları, 6x4 kamelya, site bahçesi kamelyası',
                    'cati_tipi_aciklama' => 'Dikdörtgen kamelyanın eğimli çatısı, uzun kenar boyunca merkeze doğru hafif eğimlenir; böylece yağmur ve kar suyu dört cepheden dengeli şekilde tahliye olur, tabanda su birikmez. Eğim, karın tek bir yüke toplanmasını da engeller. Oluk detayı, ahşabın doğal diline uygun bir çözümdür: su, oluğa yönlendirilip yapıdan uzaklaştırılır; damlayan su ahşap korkuluğa ve oturma alanına isabet etmez. Çatı kaplaması, çam kerestesiyle uyumlu ahşap yüzey olarak üretilir; metal ile ahşap çarpışmaz. Rüzgâr yükü davranışı, proje ölçeğinde değerlendirilir ve alan, EN 13561 referansıyla tartışılan dış cephe elemanları mantığına göre keşifte netleşir. Dikdörtgen formda çatı dıştan bakıldığında da bütünlüklüdür: her cephe aynı eğimi taşır, asimetri girmez. Drenaj yönü, girişin tersine kurulur; oturma alanı her zaman kuru tarafta kalır. Çatı bakımına yılda bir yüzey kontrolü yeterlidir; oluk içindeki yaprak temizlenir, ahşap yüzey kuru tutulur. Yağmur direnci, eğim ve yüzey işçiliği birlikte çalışır; su yüzeyde durmak yerine akar. Oluk çıkışı zemin kotunun üstünde tutulur, su kontrollü iner. Gerektiğinde eğim açısı araziye göre keşif ekibiyle konuşulur.',
                    'korkuluk_aciklama' => 'Ahşap korkuluk, dikdörtgen planın dört kenarında eşit yükseklikte devam eder ve oturma alanını dışarıdan gelen rüzgâra karşı korurken içeride mahremiyet hissi bırakır. Yükseklik, güvenlik standartlarına ve proje koşullarına göre keşif sırasında netleşir; sabit bir katalog rakamı yerine, kullanıcının oturduğu anda bileğini destekleyen ölçü tercih edilir. Köşe birleşim noktaları çapraz bağlantılarla kilitlenir, sallanma önlenir. Yüzey, çamın doğal dokusunu koruyan emprenye ve koruyucu kaplamayla hazırlanır; elle temas pürüzsüzdür, yonga bırakmaz. Korkuluk dip kısımları, zemine geçen suyu gövdeye taşımaması için ahşap takozlarla yükseltilir ve bağlantı elemanları korozyona dayanıklı malzemeden seçilir. Çocuklu alanlarda dikey aralıklar, topun veya kolun sıkışmasını önleyecek şekilde düzenlenir; yaşlı sakinler için üst tutamak hattı düz ve kesintisizdir. Tekerlekli sandalye erişimi gereken giriş kenarında korkuluk açılıp geçiş bırakacak şekilde planlanır. Temizlikte yumuşak fırça ve ılık su yeterlidir; dip noktalar yılda bir kontrol edilir, gevşenen sıkma hemen yapılır. Ahşap korkuluk, metal alternatife göre yazın ısınmaz, kışın soğuk tutmaz; dokusu bahçenin malzeme diliyle uyumludur. İstenirse boyanabilir; renk değişikliği teklif aşamasında belirlenir.',
                ],
                'en' => [
                    'baslik' => 'Rectangular Wood Gazebo 6x4 Residential Complex',
                    'slug' => 'rectangular-wood-gazebo-6x4',
                    'kisa_aciklama' => 'Rectangular timber gazebo, 6×4 footprint for residential complex gardens. Impregnated pine against rot and insects and sloped roof keep four-season use simple. Seats 6-7 across 24 m². Measurements and install plan lock after the free survey (Mon–Sat 09:00–18:00). Five-year warranty.',
                    'detayli_aciklama' => '**TL;DR**
- 6×4 m (24 m²) Rectangular, four sides floor; an 6-7-person seating area for residential complex gardens.
- Impregnated pine against rot and insects, Sloped, timber-guttered drainage: four-season use, five-year warranty.
- Transparent m² maths: 24 × 3 USD × 1.0 pine × 1.0 rectangular × 1.0 residential = 72.00 USD.
- Final measurements and the installation plan are fixed at the free survey (Mon-Sat 09:00-18:00).

## What Are the Technical Specifications?

| Item | Value |
| --- | --- |
| Floor size | 6×4 m — 24 m² |
| Shape | Rectangular, four sides |
| Main material | timber |
| Roof | Sloped, timber-guttered drainage |
| Railing | timber railing, height set per project |
| Surface | Impregnated pine against rot and insects |
| Warranty | 5 years |

## Who Is It For?

This model is built for residential operators and site teams that plan at garden scale.

## How Many People Does It Seat?

Seating follows the approved residential complex gardens ratio from F15: 24 m² ÷ 3.50 m² per person ≈ 6.86, so 6-7 people sit comfortably.

## How Is It Installed?

The product is made to order.

## How Is It Maintained?

Care is one surface check a year plus a protective refresh when needed.

## Why This Model?

This model balances scale and upkeep for residential complex gardens.

## What About the Roof?

The sloped roof eases toward its centre on the load-bearing edges of the 6×4 floor, so rain and snow drain evenly and nothing ponds below. The pitch also stops snow from becoming a single load. Gutters suit the timber language of the structure: water is routed into the channel and carried away, so drips never land on the railing or seating zone. Cladding matches the main material and avoids a clash in the silhouette. Wind behaviour is assessed at project scale and settled at the survey against external envelope elements referenced by EN 13561. From outside the roof reads as one piece — every elevation carries the same pitch, with no asymmetry. Drainage faces away from the entrance so the seating side stays dry. Maintenance needs one surface check a year; clear leaves and keep the surface dry. Rain resistance works through pitch plus joinery: water runs off instead of standing. The outlet stays above ground level. Where terrain differs, the slope angle is discussed with the survey crew on site.

## What About the Railing and Safety?

The timber railing runs at an even height along every edge of the plan, shielding the seating area from cross-wind while keeping privacy inside. Height is confirmed at the survey against safety standards, preferring the dimension that supports the wrist when seated over a fixed catalogue number. Joints lock with cross-braces so the frame does not sway. The surface keeps the material\'s natural grain — smooth to the touch, with no splinters. Railing feet are lifted so water never wicks into the posts; all fixings are chosen for corrosion resistance. Vertical gaps are spaced to prevent hands or balls from jamming, while the top grip line stays flat for elderly residents. Where wheelchair access is needed, one entrance edge opens and leaves a clear passage. Cleaning takes a soft brush and lukewarm water; check the feet yearly. The material doesn\'t overheat in summer or feel cold in winter, and its texture matches the garden\'s palette. The railing can be painted on request; colour is decided at quote stage. Along the 6×4 plan it draws an unbroken safety line. A hidden channel under the top rail accepts a light strip or planter rail without visible screws.
',
                    'seo_baslik' => 'Rectangular Timber Gazebo 6×4 Price List 2026 | Kamelya',
                    'seo_aciklama' => '24 m² Rectangular gazebo seats 6-7 with sloped roof, five-year warranty. Transparent m² pricing and free on-site survey. Book a visit Monday–Saturday.',
                    'seo_anahtar_kelimeler' => 'rectangular wood gazebo, wooden gazebo prices, 6x4 gazebo, residential complex gazebo',
                    'cati_tipi_aciklama' => 'The sloped roof eases toward its centre on the load-bearing edges of the 6×4 floor, so rain and snow drain evenly and nothing ponds below. The pitch also stops snow from becoming a single load. Gutters suit the timber language of the structure: water is routed into the channel and carried away, so drips never land on the railing or seating zone. Cladding matches the main material and avoids a clash in the silhouette. Wind behaviour is assessed at project scale and settled at the survey against external envelope elements referenced by EN 13561. From outside the roof reads as one piece — every elevation carries the same pitch, with no asymmetry. Drainage faces away from the entrance so the seating side stays dry. Maintenance needs one surface check a year; clear leaves and keep the surface dry. Rain resistance works through pitch plus joinery: water runs off instead of standing. The outlet stays above ground level. Where terrain differs, the slope angle is discussed with the survey crew on site.',
                    'korkuluk_aciklama' => 'The timber railing runs at an even height along every edge of the plan, shielding the seating area from cross-wind while keeping privacy inside. Height is confirmed at the survey against safety standards, preferring the dimension that supports the wrist when seated over a fixed catalogue number. Joints lock with cross-braces so the frame does not sway. The surface keeps the material\'s natural grain — smooth to the touch, with no splinters. Railing feet are lifted so water never wicks into the posts; all fixings are chosen for corrosion resistance. Vertical gaps are spaced to prevent hands or balls from jamming, while the top grip line stays flat for elderly residents. Where wheelchair access is needed, one entrance edge opens and leaves a clear passage. Cleaning takes a soft brush and lukewarm water; check the feet yearly. The material doesn\'t overheat in summer or feel cold in winter, and its texture matches the garden\'s palette. The railing can be painted on request; colour is decided at quote stage. Along the 6×4 plan it draws an unbroken safety line. A hidden channel under the top rail accepts a light strip or planter rail without visible screws.',
                ],
                'de' => [
                    'baslik' => 'Rechteckiger Holz-Pavillon 6x4 Wohnanlage',
                    'slug' => 'rechteckiger-holz-pavillon-6x4',
                    'kisa_aciklama' => 'Rechteckiger Holz-Pavillon 6×4 für Wohnanlagen: großer, flexibler Sitzplatz aus Qualitäts-Kiefer, impregniert gegen Fäulnis und Insekten; geneigtes Dach mit Holzrinne leitet Regen fern. Platz für 6-7 Personen auf 24 m². Maße und Montageplan werden nach dem kostenlosen Aufmaß fixiert (Mo-Sa 09:00-18:00). 5 Jahre Garantie.',
                    'detayli_aciklama' => '**Kurz gefasst**
- 6×4 m (24 m²) rechteckige Fläche; großer, flexibler Sitzplatz für 6-7 Personen in Wohnanlagen.
- Qualitäts-Kiefer mit Impregnierung, geneigtes Dach mit Holzrinne: ganzjährige Nutzung, 5 Jahre Garantie.
- Transparente m²-Rechnung: 24 × 2,50 EUR × 1,0 Kiefer × 1,0 rechteckig × 1,0 Wohnanlage = 60,00 EUR.
- Maße und Montageplan werden beim kostenlosen Aufmaß fixiert (Mo-Sa 09:00-18:00).

## Technische Daten

| Merkmal | Wert |
| --- | --- |
| Bodenmaß | 6 × 4 m — 24 m² |
| Form | Rechteckig, vier Seiten |
| Hauptmaterial | Qualitäts-Kiefernholz |
| Dach | Geneigt, Entwässerung mit Holzrinne |
| Geländer | Holz, Höhe projektbezogen |
| Oberfläche | Impregniert gegen Fäulnis und Insekten |
| Befestigung | Korrosionsfeste Metallteile |
| Garantie | 5 Jahre |

## Für wen ist er geeignet?

Dieses Modell ist für Hausverwaltungen gebaut, die größere Gärten pflegen. Der 24 m²-Boden stellt zwei Zonen nebeneinander: das eine Ende für den Esstisch, das andere für eine ruhige Ecke. Für Familien mit Kindern ist er ein übersichtlicher Treffpunkt; für ältere Bewohner ein windgeschützter, leichter Sitzplatz. Die lange Kante fügt sich in Gartenweg oder Beckenlinie ein und hält die Baulinie sauber. Projekte mit Quartiersbudget vermeiden Kostenüberraschungen durch den Standardgrundriss; drei offene Seiten rahmen den Blick.

## Kapazität

Die Kapazität folgt dem freigegebenen Faktor der Wohnanlage aus F15: 24 m² ÷ 3,50 m² pro Person ≈ 6,86 — bequem sitzen also 6-7 Personen. Der Faktor ist bewusst großzügig; Privatsphäre und Bewegungsfreiheit stehen vor Dichte. Eine Ecksitzgruppe und ein Vierer-Tisch passen ideal. Größere Zusammenkünfte können das 5×5-Modell prüfen. Die endgültige Aufstellung wird beim Aufmaß mit dem Möbelplan geprüft.

## Montage

Das Modell wird auf Bestellung gefertigt. Beim kostenlosen Aufmaß prüft das Team Boden, Zugang und Strombedarf; Maße und Montageplan werden dort fixiert. Termine laufen Montag bis Samstag, 09:00-18:00. Der rechteckige Boden baut sich entlang der langen Träger schnell auf; Material geht direkt vom Fahrzeug an den Montagepunkt. Betonplatte, verdichteter Boden oder Holzterrasse genügen; die Befestigung wird beim Aufmaß besprochen. Nach Abschluss ist die Fläche sofort nutzbar.

## Pflege

Die Pflege besteht aus einer Oberflächenkontrolle pro Jahr und bei Bedarf einer frischen Schutzschicht; alle zwei Jahre ein Lackierzyklus genügt. Holz wird mit weicher Bürste und lauwarmem Wasser gereinigt — kein Hochdruckreiniger. Die Imprägnierung bleibt der erste Schutz gegen Fäulnis und Insekten und hält bei regelmäßiger Kontrolle. Laub zu Saisonbeginn aus der Rinne entfernen, Auslauf über Bodenniveau halten. Metallteile sind korrosionsfest; lose Stellen bei der ersten Kontrolle nachziehen.

## Häufige Fragen

**Wie lange dauert die Montage?** Das rechteckige Schablonenmaß hält den Bau kurz; der genaue Tag wird beim Aufmaß bestätigt.
**Kann ich die Maße ändern?** Ja — jedes Maß wird auf Bestellung gefertigt; 6×4 ist nur der Standard-Ausgangspunkt.
**Was ist im Preis enthalten?** Transparente m²-Rechnung; Produkt- und Montagepositionen stehen getrennt im Angebot.
**Ist die Pflege wirklich so einfach?** Ja — eine Kontrolle pro Jahr, ein Lackierzyklus alle zwei Jahre.

## Preis

Transparente m²-Rechnung: 24 m² × 2,50 EUR (Basis) × 1,0 (Kiefer) × 1,0 (rechteckig) × 1,0 (Wohnanlage) = **60,00 EUR**. Die Faktoren entsprechen den aktiven Zeilen der Preis-Faktoren; der Rechner der Website liefert dasselbe Ergebnis. Das finale Angebot steht nach dem kostenlosen Aufmaß. Aufmaß-Termin: Montag bis Samstag, 09:00-18:00.',
                    'seo_baslik' => 'Rechteckiger Holz-Pavillon 6x4 Preise 2026 | Kamelya',
                    'seo_aciklama' => 'Rechteckiger Holz-Pavillon 6x4: 24 m², für 6-7, imprägniertes Kiefernholz, geneigtes Dach, 5 Jahre Garantie. Transparente m²-Preise, kostenloser Aufmaß.',
                    'seo_anahtar_kelimeler' => 'rechteckiger Holz-Pavillon, Holzpavillon Preise, 6x4 Pavillon, Pavillon Wohnanlage',
                    'cati_tipi_aciklama' => 'Das geneigte Dach des rechteckigen Pavillons neigt sich entlang der langen Kante sanft zur Mitte; Regen- und Schneewasser läuft auf allen Seiten gleichmäßig ab und bleibt nicht auf dem Boden stehen. Die Neigung verhindert, dass Schnee zu einer Einzellast wird. Die Rinne passt zur Holzsprache des Pavillons: Wasser wird in den Kanal geführt und vom Rahmen weggeleitet, Tropfen treffen weder Geländer noch Sitzfläche. Die Dachdeckung entsteht als Holzoberfläche, die zur Kiefer passt — kein Metall gegen Holz in der Silhouette. Das Windverhalten wird projektmäßig bewertet und beim Aufmaß an der Logik externer Fassadenelemente mit EN-13561-Bezug abgeglichen. Von außen wirkt das Dach als ein Stück: jede Seite trägt dieselbe Neigung ohne Asymmetrie. Die Entwässerung zeigt weg vom Eingang, damit die Sitzseite trocken bleibt. Zur Pflege genügt eine Oberflächenkontrolle pro Jahr; Laub aus der Rinne entfernen, Holz trocken halten. Der Regenwiderstand entsteht aus Neigung und Verarbeitung zusammen: Wasser fließt statt zu stehen. Bei abweichendem Gelände wird der Neigungswinkel mit dem Aufmaß-Team vor Ort besprochen.',
                    'korkuluk_aciklama' => 'Das Holzgeländer läuft an allen vier Kanten des rechteckigen Grundrisses in gleicher Höhe weiter und schützt die Sitzfläche vor Durchzug, während innen ein Gefühl von Privatsphäre bleibt. Die Höhe wird beim Aufmaß anhand von Sicherheitsnormen und Projektbedingungen bestätigt; statt einer festen Katalogzahl wird das Maß gewählt, das das Handgelenk im Sitzen stützt. Eckverbindungen werden mit Kreuzverband verriegelt, damit nichts wackelt. Die Oberfläche erhält eine Imprägnierung und Schutzbeschichtung, die die natürliche Kiefernstruktur bewahrt — glatt bei der Berührung, ohne Splitter. Die Geländerfüße werden mit Holzkeilen angehoben, damit bodennahes Wasser nicht in die Pfosten steigt, und alle Beschläge sind gegen Korrosion ausgelegt. In Familienbereichen sind die Vertikalabstände so gesetzt, dass Hände oder Bälle nicht klemmen; die obere Griffleitung bleibt für ältere Bewohner durchgehend gerade. Wo ein Rollstuhlvorgang nötig ist, öffnet sich die Geländerstrecke am Eingang und lässt freie Passage. Zur Reinigung genügen weiche Bürste und lauwarmes Wasser; die Füße einmal jährlich prüfen, lose Stellen sofort nachziehen. Gegenüber Metall wird das Holz im Sommer nicht heiß und im Winter nicht eiskalt, und die Textur passt zur Materialsprache des Gartens. Auf Wunsch ist es anmalbar; die Farbe wird beim Angebot festgelegt.',
                ],
                'fr' => [
                    'baslik' => 'Gazebo Rectangulaire Bois 6x4 pour Copropriété',
                    'slug' => 'gazebo-rectangulaire-bois-6x4',
                    'kisa_aciklama' => 'Gazebo rectangulaire en bois de 6×4 m pour copropriétés : espace de repos large et modulable, pin de qualité traité contre pourriture et insectes, toit incliné avec gouttière bois qui éloigne la pluie. 6-7 personnes sur 24 m². Dimensions et plan de pose fixés après l’étude gratuite (lun.-sam. 09h00-18h00). Garantie 5 ans.',
                    'detayli_aciklama' => '**En bref**
- Sol rectangulaire 6×4 m (24 m²) ; espace de repos large et modulable pour 6-7 personnes en copropriété.
- Pin de qualité traité, toit incliné avec gouttière bois : usage toute saison, garantie 5 ans.
- Calcul m² transparent : 24 × 2,50 EUR × 1,0 pin × 1,0 rectangulaire × 1,0 copropriété = 60,00 EUR.
- Dimensions et plan de pose fixés pendant l’étude gratuite (lun.-sam. 09h00-18h00).

## Caractéristiques techniques

| Caractéristique | Valeur |
| --- | --- |
| Emprise au sol | 6 × 4 m — 24 m² |
| Forme | Rectangulaire, quatre côtés |
| Matériau principal | Pin de qualité |
| Toit | Incliné, drainage avec gouttière bois |
| Garde-corps | Bois, hauteur définie par projet |
| Surface | Traité contre pourriture et insectes |
| Fixations | Pièces métalliques résistantes à la corrosion |
| Garantie | 5 ans |

## Pour qui ?

Ce modèle est conçu pour les syndics qui gèrent de plus grands jardins. Les 24 m² rectangulaires installent deux zones côte à côte : une extrémité pour la table de l’autre pour un coin calme. Pour les familles c’est un point de rendez-vous ombragé et lisible ; pour les résidents âgés, une assise abritée du vent et facile d’accès. Le long bord s’aligne sur l’allée du jardin ou la ligne de bassin et garde le paysage net. Les projets au budget de quartier évitent les surprises de coût grâce à l’empreinte standard, et trois côtés ouverts cadrent la vue.

## Capacité

La capacité suit le facteur copropriété approuvé en F15 : 24 m² ÷ 3,50 m² par personne ≈ 6,86 — soit confortablement 6-7 personnes. Le facteur est volontairement généreux ; intimité et aisance passent avant la densité. Un coin salon deux places et une table à quatre conviennent idéalement. Les réceptions plus larges peuvent envisager le modèle moderne 5×5. Le placement final est vérifié pendant l’étude avec le plan de mobilier.

## Pose

Le gazebo est fabriqué sur commande. L’étude gratuite examine le sol, l’approche et le besoin électrique ; dimensions et plan de pose s’y fixent. Les rendez-vous couvrent du lundi au samedi, 09h00-18h00. Le plan rectangulaire se monte vite le long de ses longerons ; le matériel va du véhicule au point d’assemblage. dalle béton, sol compacté ou terrasse bois conviennent ; le mode de fixation se précise à l’étude. La zone est utilisable dès la fin des travaux.

## Entretien

L’entretien tient en un contrôle de surface par an et une couche protectrice renouvelée si besoin ; une peinture tous les deux ans suffit. Le bois se nettoie à la brosse douce et à l’eau tiède — sans nettoyeur haute pression. Le traitement reste le premier rempart contre la pourriture et les insectes et dure avec les contrôles réguliers. Retirez les feuilles des gouttières en début de saison et gardez la sortie au-dessus du sol. Les pièces métalliques résistent à la corrosion ; resserrez ce qui bouge dès le premier contrôle.

## Questions fréquentes

**Combien de temps dure la pose ?** Le gabarit rectangulaire garde le chantier court ; le jour exact se fixe à l’étude.
**Puis-je modifier les dimensions ?** Oui — tout est fabriqué sur commande ; 6×4 n’est qu’un point de départ par défaut.
**Que comprend le prix ?** Calcul m² transparent ; produit et pose listés séparément dans le devis.
**L’entretien est-il vraiment simple ?** Oui — un contrôle par an et une peinture tous les deux ans.

## Prix

Calcul m² transparent : 24 m² × 2,50 EUR (base) × 1,0 (pin) × 1,0 (rectangulaire) × 1,0 (copropriété) = **60,00 EUR**. Les multiplicateurs sont ceux des lignes actives des facteurs de prix ; le calculateur du site donne le même résultat. Le devis final se stabilise après l’étude gratuite. Étude : du lundi au samedi, 09h00-18h00.',
                    'seo_baslik' => 'Gazebo Rectangulaire Bois 6x4 Prix et Devis 2026 | Kamelya',
                    'seo_aciklama' => 'Gazebo rectangulaire bois 6x4 : 24 m², 6-7 places, pin traité, toit incliné avec gouttière, garantie 5 ans. Prix au m² transparents, étude gratuite.',
                    'seo_anahtar_kelimeler' => 'gazebo rectangulaire bois, gazebo bois prix, gazebo 6x4, gazebo copropriété',
                    'cati_tipi_aciklama' => 'Le toit incliné du gazebo rectangulaire s’incline doucement vers son centre le long du long bord, de sorte que pluie et neige évacuent également des quatre côtés sans stagnation au sol. La pente empêche aussi la neige de se concentrer en une charge unique. La gouttière suit le langage du bois : l’eau est dirigée dans le canal et éloignée du bâti, les gouttes n’atteignent ni le garde-corps ni l’assise. La couverture est produite en surface bois assortie au pin, évitant tout choc métal-bois dans la silhouette. Le comportement au vent s’évalue à l’échelle du projet et se fixe à l’étude selon la logique des éléments d’enveloppe extérieure référencés par l’EN 13561. De l’extérieur, le toit rectangulaire se lit comme une pièce continue : chaque façade porte la même pente, sans asymétrie. L’évacuation tourne le dos à l’entrée pour que le côté assis reste sec. L’entretien se limite à un contrôle de surface par an ; retirez les feuilles des gouttières et gardez le bois sec. L’étanchéité à la pluie naît de la pente et de la pose ensemble : l’eau s’écoule au lieu de rester. Si le terrain diffère, l’angle de pente se discute avec l’équipe d’étude sur place.',
                    'korkuluk_aciklama' => 'Le garde-corps en bois court à hauteur égale sur les quatre arêtes du plan rectangulaire, protège l’assise des traversants tout en gardant une intimité à l’intérieur. Sa hauteur se confirme à l’étude selon les normes de sécurité et les conditions du projet, en préférant la mesure qui soutient le poignet assis plutôt qu’un chiffre de catalogue fixe. Les assemblages d’angle se verrouillent par croisements pour éviter tout balancement. La surface reçoit traitement et finition protectrice qui conservent le grain naturel du pin — douce au toucher, sans échardes. Les pieds sont surélevés sur cales bois pour que l’eau du sol ne remonte pas dans les poteaux, et toutes les pièces d’attache résistent à la corrosion. Dans les espaces familiaux, les entraxes verticaux empêchent les mains ou les balles de se coincer ; la ligne de saisie supérieure reste droite et continue pour les résidents âgés. Là où le passage en fauteuil est requis, un côté d’entrée s’ouvre et libère une trajectoire libre. Le nettoyage demande une brosse douce et une eau tiède ; contrôlez les pieds une fois par an et resserrez aussitôt ce qui bouge. Contrairement au métal, le bois ne chauffe pas en été ni ne givre en hiver, et sa texture s’accorde au langage matériel du jardin. Peignable sur demande ; la couleur se décide à l’étape du devis.',
                ],
                'it' => [
                    'baslik' => 'Gazebo Rettangolare in Legno 6x4 Condominio',
                    'slug' => 'gazebo-rettangolare-legno-6x4',
                    'kisa_aciklama' => 'Gazebo rettangolare in legno da 6×4 m per condomini: zona seduta ampia e modulabile, pino di qualità trattato contro putridume e insetti, tetto inclinato con gronda in legno che allontana la pioggia. 6-7 persone su 24 m². Misure e piano di posa fissati dopo il sopralluogo gratuito (lun.-sab. 09:00-18:00). Garanzia 5 anni.',
                    'detayli_aciklama' => '**In breve**
- Pavimento rettangolare 6×4 m (24 m²) ; zona seduta ampia e modulabile per 6-7 persone nei condomini.
- Pino di qualità trattato, tetto inclinato con gronda in legno: uso tutto l’anno, garanzia 5 anni.
- Calcolo m² trasparente: 24 × 2,50 EUR × 1,0 pino × 1,0 rettangolare × 1,0 condominio = 60,00 EUR.
- Misure e piano di posa fissati durante il sopralluogo gratuito (lun.-sab. 09:00-18:00).

## Specifiche tecniche

| Voce | Valore |
| --- | --- |
| Impronta a terra | 6 × 4 m — 24 m² |
| Forma | Rettangolare, quattro lati |
| Materiale principale | Pino di qualità |
| Tetto | Inclinato, scarico con gronda in legno |
| Parapetto | Legno, altezza definita dal progetto |
| Superficie | Trattata contro putridume e insetti |
| Fissaggi | Parti metalliche resistenti alla corrosione |
| Garanzia | 5 anni |

## Per chi è?

Questo modello è pensato per gli amministratori che gestiscono giardini più grandi. I 24 m² rettangolari mettono due zone affiancate: un estremo per il tavolo da pranzo, l’altro per un angolo tranquillo. Per le famiglie con bambini è un punto d’incontro ombreggiato e leggibile; per gli anziani una seduta riparata dal vento e facile da raggiungere. Il lato lungo si allinea al sentiero o alla linea piscina e tiene pulita la linea del paesaggio. I progetti con budget di quartiere evitano sorprese di costo grazie all’impronta standard, e tre lati aperti inquadrano la vista.

## Capienza

La capienza segue il fattore condominio approvato in F15: 24 m² ÷ 3,50 m² a persona ≈ 6,86 — quindi 6-7 persone comodamente. Il fattore è volutamente generoso; privacy e libertà di movimento vengono prima della densità. Un angolo seduta da due e un tavolo da quattro sono ideali. Per raduni più ampi valutare il modello moderno 5×5. La posa finale si verifica al sopralluogo con il piano d’arredo.

## Posa

Il gazebo è prodotto su ordinazione. Il sopralluogo gratuito valuta terreno, accesso ed esigenze elettriche; misure e piano di posa si fissano lì. Gli appuntamenti vanno da lunedì a sabato, 09:00-18:00. Il pavimento rettangolare si monta in fretta lungo i longheroni; i materiali vanno dal mezzo al punto di assemblaggio. Solaio in cemento, terreno compatto o terrazza in legno vanno bene; il tipo di fissaggio si definisce al sopralluogo. L’area è subito utilizzabile a fine lavori.

## Manutenzione

La manutenzione è un controllo di superficie all’anno e una mano di finitura protettiva se serve; una verniciatura ogni due anni basta. Il legno si pulisce con spazzola morbida e acqua tiepida — senza idro-lavaggio. Il trattamento resta la prima barriera contro putridume e insetti e dura con i controlli regolari. Togliere foglie dalle gronde a inizio stagione e tenere lo scarico sopra il suolo. Le ferramenta sono a prova di corrosione; stringere subito ciò che è mosso al primo controllo.

## Domande frequenti

**Quanto dura la posa?** Il modello rettangolare tiene il cantiere corto; il giorno esatto si fissa al sopralluogo.
**Posso cambiare le misure?** Sì — ogni pezzo è su ordinazione; 6×4 è solo il punto di partenza predefinito.
**Cosa comprende il prezzo?** Calcolo m² trasparente; prodotto e posa elencati separatamente nel preventivo.
**La manutenzione è davvero semplice?** Sì — un controllo all’anno e una verniciatura ogni due anni.

## Prezzo

Calcolo m² trasparente: 24 m² × 2,50 EUR (base) × 1,0 (pino) × 1,0 (rettangolare) × 1,0 (condominio) = **60,00 EUR**. I moltiplicatori corrispondono alle righe attive dei fattori di prezzo; il calcolatore del sito produce lo stesso risultato. Il preventivo definitivo si stabilisce dopo il sopralluogo gratuito. Sopralluogo: da lunedì a sabato, 09:00-18:00.',
                    'seo_baslik' => 'Gazebo Rettangolare Legno 6x4 Prezzi nel 2026 | Kamelya',
                    'seo_aciklama' => 'Gazebo rettangolare legno 6x4 condominio: 24 m², 6-7 posti, pino trattato, tetto inclinato, garanzia 5 anni. Prezzi al m² trasparenti, sopralluogo gratuito.',
                    'seo_anahtar_kelimeler' => 'gazebo rettangolare legno, gazebo legno prezzi, gazebo 6x4, gazebo condominio',
                    'cati_tipi_aciklama' => 'Il tetto inclinato del gazebo rettangolare si inclina dolcemente verso il centro lungo il lato lungo, così pioggia e neve scaricano in modo uniforme su tutti i lati senza ristagni a terra. La pendenza impedisce anche alla neve di concentrarsi in un carico unico. La gronda segue il linguaggio del legno: l’acqua viene convogliata nel canale e allontanata dalla struttura, le gocce non colpiscono né parapetto né zona seduta. La copertura è prodotta come superficie in legno abbinata al pino, evitando uno scontro metallo-legno nella silhouette. Il comportamento al vento si valuta a scala di progetto e si definisce al sopralluogo secondo la logica degli elementi di involucro esterno richiamati dalla EN 13561. Dall’esterno il tetto rettangolare si legge come un pezzo continuo: ogni prospetto porta la stessa pendenza, senza asimmetrie. Lo scarico guarda lontano dall’ingresso perché il lato seduto resti asciutto. La manutenzione richiede un controllo di superficie all’anno; togliere foglie dalle gronde e tenere il legno asciutto. La resistenza alla pioggia nasce da pendenza e posa insieme: l’acqua scorre invece di restare. Se il terreno differisce, l’angolo di pendenza si discute con l’equipaggio del sopralluogo in loco.',
                    'korkuluk_aciklama' => 'Il parapetto in legno prosegue ad altezza uguale su tutti i quattro spigoli del piano rettangolare, ripara la seduta dai venti traversi e mantiene una sensazione di riservatezza dentro. L’altezza si conferma al sopralluogo secondo norme di sicurezza e condizioni di progetto, preferendo la misura che sorregge il polso da seduti a un numero fisso di catalogo. Le giunzioni d’angolo si bloccano con incroci che eliminano l’oscillazione. La superficie riceve trattamento e finitura protettiva che conservano la venatura naturale del pino — liscia al tatto, senza scaglie. I piedi sono sollevati su cunei di legno perché l’acqua del terreno non risalga nei pali, e tutte le ferramenta sono a prova di corrosione. Negli spazi familiari gli interassi verticali impediscono che mani o palline restino incastrate; la linea di presa superiore resta dritta e continua per gli anziani. Dove serve il passaggio in sedia a rotelle, un lato d’ingresso si apre e lascia il transito libero. La pulizia richiede spazzola morbida e acqua tiepida; controllare i piedi una volta l’anno e stringere subito ciò che è mosso. Rispetto al metallo, il legno non scalda d’estate né gela d’inverno, e la texture si accorda al linguaggio materiale del giardino. Colorabile a richiesta; la tinta si sceglie in fase di preventivo.',
                ],
                'ar' => [
                    'baslik' => 'كوش خشبي مستطيل 6x4 للمجمعات السكنية',
                    'slug' => 'rectangular-wood-kush-6x4',
                    'kisa_aciklama' => 'كوش خشبي مستطيل بمقاس 6×4 متر للمجمعات السكنية: مساحة جلوس واسعة ومرنة، خشب صنوبر معالج ضد التحلل والحشرات، سقف مائل بمزارب خشبية يبعد المطر. يتسع لـ 6-7 أشخاص على 24 م². تُثبَّت المقاسات وخطة التركيب بعد الاستشارة المجانية (الاثنين–السبت 09:00–18:00). ضمان 5 سنوات.',
                    'detayli_aciklama' => '**باختصار**
- أرضية مستطيلة 6×4 م (24 م²)؛ مساحة جلوس واسعة ومرنة لـ 6-7 أشخاص في المجمعات السكنية.
- صنوبر معالج، سقف مائل بمزارب خشبية: استخدام على مدار السنة، ضمان 5 سنوات.
- حساب شفاف للمتر: 24 × 3 USD × 1.0 صنوبر × 1.0 مستطيل × 1.0 سكني = 72.00 USD.
- تُثبَّت المقاسات وخطة التركيب في الاستشارة المجانية (الاثنين–السبت 09:00–18:00).

## المواصفات التقنية

| البند | القيمة |
| --- | --- |
| مقاس الأرضية | 6 × 4 م — 24 م² |
| الشكل | مستطيل، أربعة أضلاع |
| المادة الأساسية | خشب صنوبر عالي الجودة |
| السقف | مائل، تصريف بمرزة خشبية |
| السور | خشبي، الارتفاع حسب المشروع |
| السطح | معالج ضد التحلل والحشرات |
| التثبيت | قطع معدنية مقاومة للصدأ |
| الضمان | 5 سنوات |

## لمن صُمم؟

هذا الموديل لإدارات المجمعات التي تدير حدائق أكبر. أرضية 24 م² المستطيلة تضع منطقتين جنبًا إلى جنب: طرف لطاولة الطعام وطرف لركن هادئ. للأسر ذات الأطفال نقطة لقاء مظلولة وواضحة الرؤية؛ لكبار السن جلسة محمية من الرياح يسهل الجلوس فيها. يتواءم الطول الأطول مع ممر الحديقة أو خط المسبح ويبقي خط المشهد نظيفًا. المشاريع ذات ميزانية الحي تتجنّب مفاجآت التكلفة بفضل المقاس القياسي، وثلاثة وجوه مفتوحة تؤطر المشهد ولا تحجبه.

## السعة

تُحسب السعة ومعامل المجمع السكني المعتمد من F15: 24 م² ÷ 3.50 م² للشخص ≈ 6.86 — أي 6-7 أشخاص براحة. المعامل مقصود أن يكون سخيًا؛ الخصوصية وحرية الحركة تأتي قبل الازدحام. ركن جلوس لشخصين وطاولة لأربعة مناسبان تمامًا. للتجمعات الأكبر يُطَّلع على موديل 5×5 العصري. يُتحقق من التوضع النهائي أثناء الاستشارة مع خطة الأثاث.

## التركيب

يُصنع الموديل عند الطلب. في الاستشارة المجانية يفحص الفريق الأرضية واتجاه الدخول وحاجة الكهرباء؛ وتُثبَّت المقاسات وخطة التركيب في ذلك الموعد. المواعيد من الاثنين إلى السبت 09:00–18:00. تُبنى الأرضية المستطيلة بسرعة على طول حاملاتها الطويلة؛ تُنقل المواد مباشرة من السيارة إلى نقطة التركيب. بلاطة خرسانية أو تربة مضغوطة أو سطح خشبي كفاية؛ تُحدَّد طريقة التثبيت في الاستشارة. تصبح المساحة جاهزة فور انتهاء العمل.

## الصيانة

الصيانة فحص سطحي سنوي ويد واقية عند الحاجة؛ تجديد دهان كل عامين يكفي. يُنظَّف الخشب بفرشاة ناعمة ومياه فاترة دون ضغط عالٍ. المعالجة تبقى الحاجز الأول ضد التحلل والحشرات وتدوم مع الفحوص المنتظمة. تُنظَّف أوراق المرزبات في بداية الموسم وتبقى فتحات التصريف فوق سطح الأرض. القطع المعدنية مقاومة للصدأ؛ يُشدّ المحكم فورًا.

## أسئلة شائعة

**كم يستغرق التركيب؟** القالب المستطيل يختصر العمل؛ اليوم الدقيق يُحدَّد في الاستشارة.
**هل يمكن تغيير المقاسات؟** نعم — كل القطع تُصنع عند الطلب؛ 6×4 نقطة انطلاق افتراضية فقط.
**ما الذي يشمله السعر؟** حساب متر شفاف؛ المنتج والتركيب يُذكران منفصلين في العرض.
**هل الصيانة بهذه البساطة فعلًا؟** نعم — فحص سنوي ودهان كل عامين.

## السعر

حساب متر شفاف: 24 م² × 3 USD (أساسي) × 1.0 (صنوبر) × 1.0 (مستطيل) × 1.0 (سكني) = **72.00 USD**. المضاعفات مطابقة للصفوف النشطة في جدول معاملات السعر؛ آلة الموقع تعطي النتيجة نفسها. يستقر العرض النهائي بعد الاستشارة المجانية. موعد الاستشارة: الاثنين–السبت 09:00–18:00.',
                    'seo_baslik' => 'كوش خشبي مستطيل 6x4 أسعار 2026 | Kamelya',
                    'seo_aciklama' => 'كوش خشبي مستطيل 6x4: 24 م²، 6-7 أشخاص، سقف مائل، ضمان 5 سنوات. أسعار متر شفافة، استشارة مجانية',
                    'seo_anahtar_kelimeler' => 'كوش خشبي مستطيل, أسعار الكوش الخشبي, كوش 6x4, كوش المجمعات السكنية',
                    'cati_tipi_aciklama' => 'يتجه السقف المائل للكوش المستطيل بانحدار خفيف نحو مركزه على طول الحافة الطويلة، فيتصريف مياه المطر والثلج بالتساوي على جميع الوجوه دون تجمّع على الأرضية. الانحدار يمنع أيضًا تركّز الثلج في حمل واحد. المرزة تناسب لغة الخشب في الهيكل: توجَّه المياه إلى القناة وتُبعد عن الإطار، فلا تصل القطرة إلى السور ولا إلى منطقة الجلوس. تُنتج تغطية السقف كسطح خشبي متناغم مع الصنوبر، فيتجنَّب التعارض بين المعدن والخشب في السيلويت. يُقيَّم سلوك الرياح على مستوى المشروع ويُحسم في الاستشارة وفق منطق عناصر الغلاف الخارجي المرجعية (EN 13561). من الخارج يُقرأ السقف المستطيل قطعة واحدة: كل وجه يحمل الميل نفسه بلا عدم تماثل. يتجه التصريف بعيدًا عن المدخل فيبقى جانب الجلوس جافًا. الصيانة سطر فحص سطحي سنوي؛ تنظيف أوراق المرزبات وإبقاء الخشب جافًا. مقاومة المطر تولَّد من الانحدار والتركيب معًا: تنزلق المياه بدل أن تثبت. ومع تباين الأرضية يُناقش زاوية الانحدار مع فريق الاستشارة في الموقع. تُنظَّف المرزة من الأوراق في بداية الموسم وتبقى فتحات التصريف فوق سطح الأرض دائمًا.',
                    'korkuluk_aciklama' => 'يستمر السور الخشبي بارتفاع متساوٍ على الحواف الأربعة للخطة المستطيلة، يحمي منطقة الجلوس من الرياح المارّة مع إبقاء إحساس بالخصوصية في الداخل. يُثبَّت الارتفاع في الاستشارة وفق معايير السلامة وظروف المشروع، مع تفضيل المقاس الذي يسند المعصم أثناء الجلوس على رقم كتالوج ثابت. تُقفل زوايا الالتقاء بتثبيتات متقاطعة تمنع الاهتزاز. يُجهَّز السطح بمعالجة واقية تحافظ على نسيج الصنوبر الطبيعي — ناعم عند اللمس دون شظايا. تُرفع قواعد السور على أخماس خشبية كي لا يصعد ماء الأرض إلى الجذوع، وتُختار كل القطع المعدنية مقاومة للصدأ. في الأماكن العائلية تُضبط المسافات الرأسية بحيث لا تعلق اليد أو الكرة، وتبقى خط القبضة العلوي مستقيمًا ومتصلًا لكبار السن. حيث يلزم مرور الكرسي المتحرك، يُخطَّط أحد حواف المدخل ليُفتح السور ويترك ممرًا حرًا. التنظيف بفرشاة ناعمة ومياه فاترة يكفي؛ فحص القواعد سنويًا وشدّ المحكم فورًا. مقارنةً بالمعدن لا يسخن الصنوبر صيفًا ولا يُثلج شتاءً، ونسجه ينسجم مع لغة مواد الحديقة. يمكن طلبه باللون المطلوب عند الطلب، ويُحدَّد اللون في مرحلة العرض.',
                ],
            ],
            'KML-ALU-DIK-006' => [
                'tr' => [
                    'baslik' => 'Dikdörtgen Alüminyum Kamelya 6x3 Belediye Meydanı',
                    'slug' => 'dikdortgen-aluminyum-kamelya-6x3',
                    'kisa_aciklama' => 'Dikdörtgen alüminyum kamelya, 6×3 metre tabanıyla belediye meydanları ve halka açık alanlar için dayanıklı bir dinlenme noktası sunar. Toz boyalı alüminyum profil, tuzlu su ve UV dayanımıyla öne çıkar; düz çatısı yağmur suyunu kontrollü akıtır. Kapasitesi 15 kişidir. Net ölçü ve montaj planı ücretsiz keşifte belirlenir; 5 yıl garantilidir.',
                    'detayli_aciklama' => '**TL;DR**
- 6×3 m (18 m²) dikdörtgen taban; belediye meydanları ve halka açık alanlar için 15 kişilik dinlenme noktası.
- Toz boyalı alüminyum, düz çatı, tuzlu su ve UV dayanımı: dört mevsim kullanım, 5 yıl garanti.
- Şeffaf m² hesabı: 18 × 12.000 × 2,5 (alüminyum) × 1,0 (dikdörtgen) × 1,8 (belediye) = 972.000 TL.
- Net ölçü ve montaj planı ücretsiz keşifte sabitlenir (Pzt–Cmt 09:00–18:00).

## Teknik Özellikleri Nelerdir?

| Alan | Değer |
| --- | --- |
| Taban ölçüsü | 6 × 3 m — 18 m² |
| Form | Dikdörtgen, dört kenar |
| Ana malzeme | Toz boyalı alüminyum profil |
| Çatı | Düz, kontrollü tahliye |
| Korkuluk | Alüminyum, proje bazlı yükseklik |
| Yüzey işlemi | Toz boya — UV ve tuzlu su dayanımı |
| Bağlantı | Korozyona dayanıklı metal elemanlar |
| Garanti | 5 yıl |

## Kimler İçin?

Bu model, halka açık alanlar düzenleyen belediyeler için tasarlandı. 18 m² dikdörtgen taban, meydan kenarında uzun bir dinlenme şeridi oluşturur: bank sıraları, engelli erişimine açık geçiş ve bilgilendirme panosu yan.

## Kaç Kişiliktir?

Kapasite, F15 onaylı belediye katsayısıyla hesaplanır: 18 m² ÷ 1,20 m²/kişi = 15 — yani 15 kişi rahat oturur. Bu katsayı, kamusal alanların yoğun kullanımını ve tekerlekli sandalye manevra payını.

## Montaj Nasıl Yapılır?

Ürün sipariş üzerine üretilir. Ücretsiz keşifte zemin, yaya akışı, engelli erişimi ve aydınlatma ihtiyacı değerlendirilir; ölçü ve montaj planı o gün sabitlenir. Keşif randevuları Pazartesi–Cumartesi 09:00–18:00 arasındadır.

## Bakımı Nasıl Yapılır?

Bakım, yılda bir yüzey yıkaması ve bağlantı kontrolünden ibarettir; alüminyum yüzeyde vernik veya emprenye gerekmez. Yumuşak fırça ve nötr deterjan yeterlidir; aşındırıcı temizleyiciler kullanılmaz. Düz çatıda biriken yaprak ve kir,.

## Sıkça Sorulan Sorular

**Montaj ne kadar sürer?** Alüminyum panel sistemi sayesinde kurulum hızlıdır; kesin gün keşifte netleşir. **Ölçüler değiştirilebilir mi?** Evet; her ürün sipariş üzerine üretilir — 6×3 standart başlangıç noktasıdır.

## Fiyat

Şeffaf m² hesabı: 18 m² × 12.000 TL (temel) × 2,5 (alüminyum) × 1,0 (dikdörtgen) × 1,8 (belediye) = **972.000 TL**. Çarpanlar, fiyat_carpanlari tablosundaki aktif satırlarla birebir aynıdır; web sitesindeki.

## Çatı Özellikleri Nelerdir?

Dikdörtgen alüminyum kamelyanın düz çatısı, şehir silueti ve meydan çizgisiyle aynı düzlemde buluşur; yağmur suyu, panel aralarındaki kontrollü kanallardan tahliye edilir ve oturma alanına damlamaz. Düz forma, bilgilendirme panosu, ışıklandırma veya kameralı aydınlatma kolayca sabitlenir; eğim aramak gerekmez. Alüminyum kaplama, tuzlu ve klorlu ortamlarda paslanmaz; toz boya UV dayanımıyla rengini korur. Rüzgâr yükü davranışı, proje ölçeğinde değerlendirilir ve alan, EN 13561 referansıyla tartışılan dış cephe elemanları mantığına göre keşifte netleşir. Düz çatıda su birikintisi oluşmaması için minik bir eğim, kalıp içinde planlanır; bu eğim gözle görünmez ama tahliyeyi garanti eder. Tahliye delikleri, yaprak ve kir tutmayacak şekilde köşelere yerleştirilir. Çatı bakımına yılda bir göz kontrolü yeterlidir; delikler açık tutulur, yüzey hortumla yıkanır. Alüminyum, çürüme ve böcek yapmaz; ahşap çatının tersine emprenye gerekmez. Kenar profilleri, suyun cepheye kontrollü inmesini sağlar; leke izi bırakmaz. Gerektiğinde tahliye planı, keşif ekibiyle araziye göre konuşulur. Oluk çıkışı zemin kotunun üstünde tutulur; yüzey kuru kalır, su kontrollü iner.

## Korkuluk ve Güvenlik Özellikleri Nelerdir?

Alüminyum korkuluk, dikdörtgen planın dört kenarında eşit yükseklikte devam eder ve meydan trafiğinde dinlenenleri araç ve yaya akışından ayırır. Yükseklik, güvenlik standartlarına ve proje koşullarına göre keşif sırasında netleşir; sabit bir katalog rakamı yerine, kullanıcının oturduğu anda bileğini destekleyen ölçü tercih edilir. Köşe birleşim noktaları gizli bağlantılarla kilitlenir, sallanma önlenir. Yüzey, toz boyanın UV dayanımıyla korunur; elle temas pürüzsüzdür, yonga bırakmaz, paslanmaz. Korkuluk dip kısımları, zemine geçen suyu gövdeye taşımaması için yükseltilir ve bağlantı elemanları korozyona dayanıklı malzemeden seçilir. Kamusal alanlarda dikey aralıklar, çocuk ve tekerlekli sandalye güvenliği için dengelenir; üst tutamak hattı düz ve kesintisizdir. Engelli erişimi gereken kenarda korkuluk açılıp geçiş bırakacak şekilde planlanır. Temizlikte yumuşak fırça ve nötr deterjan yeterlidir; dip noktalar yılda bir kontrol edilir, gevşenen sıkma hemen yapılır. Alüminyum korkuluk, yazın ısınmaz, kışın soğuk tutmaz; dokusu kent mobilyası diliyle uyumludur. İstenirse renk, belediye renk paletinden teklif aşamasında seçilir; toz boya renk değişikliğine de uygundur. Kablo kanalı profil arasında bırakılır.
',
                    'seo_baslik' => 'Dikdörtgen Alüminyum Kamelya 6x3 Fiyatları 2026 | Kamelya',
                    'seo_aciklama' => 'Dikdörtgen alüminyum kamelya 6x3: 18 m² alan, 15 kişilik belediye meydanı, toz boyalı profil, düz çatı. Şeffaf m² fiyat, ücretsiz keşif randevusu.',
                    'seo_anahtar_kelimeler' => 'dikdörtgen alüminyum kamelya, alüminyum kamelya fiyatları, belediye kamelyası, 6x3 kamelya',
                    'cati_tipi_aciklama' => 'Dikdörtgen alüminyum kamelyanın düz çatısı, şehir silueti ve meydan çizgisiyle aynı düzlemde buluşur; yağmur suyu, panel aralarındaki kontrollü kanallardan tahliye edilir ve oturma alanına damlamaz. Düz forma, bilgilendirme panosu, ışıklandırma veya kameralı aydınlatma kolayca sabitlenir; eğim aramak gerekmez. Alüminyum kaplama, tuzlu ve klorlu ortamlarda paslanmaz; toz boya UV dayanımıyla rengini korur. Rüzgâr yükü davranışı, proje ölçeğinde değerlendirilir ve alan, EN 13561 referansıyla tartışılan dış cephe elemanları mantığına göre keşifte netleşir. Düz çatıda su birikintisi oluşmaması için minik bir eğim, kalıp içinde planlanır; bu eğim gözle görünmez ama tahliyeyi garanti eder. Tahliye delikleri, yaprak ve kir tutmayacak şekilde köşelere yerleştirilir. Çatı bakımına yılda bir göz kontrolü yeterlidir; delikler açık tutulur, yüzey hortumla yıkanır. Alüminyum, çürüme ve böcek yapmaz; ahşap çatının tersine emprenye gerekmez. Kenar profilleri, suyun cepheye kontrollü inmesini sağlar; leke izi bırakmaz. Gerektiğinde tahliye planı, keşif ekibiyle araziye göre konuşulur. Oluk çıkışı zemin kotunun üstünde tutulur; yüzey kuru kalır, su kontrollü iner.',
                    'korkuluk_aciklama' => 'Alüminyum korkuluk, dikdörtgen planın dört kenarında eşit yükseklikte devam eder ve meydan trafiğinde dinlenenleri araç ve yaya akışından ayırır. Yükseklik, güvenlik standartlarına ve proje koşullarına göre keşif sırasında netleşir; sabit bir katalog rakamı yerine, kullanıcının oturduğu anda bileğini destekleyen ölçü tercih edilir. Köşe birleşim noktaları gizli bağlantılarla kilitlenir, sallanma önlenir. Yüzey, toz boyanın UV dayanımıyla korunur; elle temas pürüzsüzdür, yonga bırakmaz, paslanmaz. Korkuluk dip kısımları, zemine geçen suyu gövdeye taşımaması için yükseltilir ve bağlantı elemanları korozyona dayanıklı malzemeden seçilir. Kamusal alanlarda dikey aralıklar, çocuk ve tekerlekli sandalye güvenliği için dengelenir; üst tutamak hattı düz ve kesintisizdir. Engelli erişimi gereken kenarda korkuluk açılıp geçiş bırakacak şekilde planlanır. Temizlikte yumuşak fırça ve nötr deterjan yeterlidir; dip noktalar yılda bir kontrol edilir, gevşenen sıkma hemen yapılır. Alüminyum korkuluk, yazın ısınmaz, kışın soğuk tutmaz; dokusu kent mobilyası diliyle uyumludur. İstenirse renk, belediye renk paletinden teklif aşamasında seçilir; toz boya renk değişikliğine de uygundur. Kablo kanalı profil arasında bırakılır.',
                ],
                'en' => [
                    'baslik' => 'Rectangular Aluminium Gazebo 6x3 Municipality Square',
                    'slug' => 'rectangular-aluminium-gazebo-6x3',
                    'kisa_aciklama' => 'Rectangular aluminium gazebo, 6×3 footprint for municipality squares. Powder coat with saltwater and UV resistance and flat roof keep four-season use simple. Seats 15 across 18 m². Measurements and install plan lock after the free survey (Mon–Sat 09:00–18:00). Five-year warranty.',
                    'detayli_aciklama' => '**TL;DR**
- 6×3 m (18 m²) Rectangular, four sides floor; an 15-person seating area for municipality squares.
- Powder coat with saltwater and UV resistance, Flat, controlled drainage: four-season use, five-year warranty.
- Transparent m² maths: 18 × 3 USD × 2.5 aluminium × 1.0 rectangular × 1.8 municipality = 243.00 USD.
- Final measurements and the installation plan are fixed at the free survey (Mon-Sat 09:00-18:00).

## What Are the Technical Specifications?

| Item | Value |
| --- | --- |
| Floor size | 6×3 m — 18 m² |
| Shape | Rectangular, four sides |
| Main material | aluminium |
| Roof | Flat, controlled drainage |
| Railing | aluminium railing, height set per project |
| Surface | Powder coat with saltwater and UV resistance |
| Warranty | 5 years |

## Who Is It For?

This model is built for municipality operators and site teams that plan at garden scale.

## How Many People Does It Seat?

Seating follows the approved municipality squares ratio from F15: 18 m² ÷ 1.20 m² per person ≈ 15, so 15 people sit comfortably.

## How Is It Installed?

The product is made to order.

## How Is It Maintained?

Care is one surface check a year plus a protective refresh when needed.

## Why This Model?

This model balances scale and upkeep for municipality squares.

## What About the Roof?

The flat roof meets the modern silhouette and garden line on the same plane; rainwater drains through controlled channels between panels and never drips onto the seating zone. A tiny slope, invisible to the eye, is planned into the mould so drainage is guaranteed and no ponding forms. Wind behaviour is assessed at project scale and settled at the survey against external envelope logic referenced by EN 13561. The plane leaves room for lighting or menu-board mounts. Roof care needs one visual check a year — keep the holes open and rinse with a hose. Edge profiles guide water down the facade and leave no stain marks. Where needed, the drainage plan is discussed with the survey crew on site. The outlet stays above ground level so the surface remains dry. In winter the snow load spreads evenly across the plane instead of concentrating on one point. Cable runs for evening lighting hide under the ceiling; the drip line stays outside the seating circle. Dust and limescale rinse off with a hose; no chemicals are required.

## What About the Railing and Safety?

The aluminium railing runs at an even height along every edge of the plan, shielding the seating area from cross-wind while keeping privacy inside. Height is confirmed at the survey against safety standards, preferring the dimension that supports the wrist when seated over a fixed catalogue number. Joints lock with cross-braces so the frame does not sway. The surface keeps the material\'s natural grain — smooth to the touch, with no splinters. Railing feet are lifted so water never wicks into the posts; all fixings are chosen for corrosion resistance. Vertical gaps are spaced to prevent hands or balls from jamming, while the top grip line stays flat for elderly residents. Where wheelchair access is needed, one entrance edge opens and leaves a clear passage. Cleaning takes a soft brush and lukewarm water; check the feet yearly. The material doesn\'t overheat in summer or feel cold in winter, and its texture matches the garden\'s palette. The railing can be painted on request; colour is decided at quote stage. Along the 6×3 plan it draws an unbroken safety line. A hidden channel under the top rail accepts a light strip or planter rail without visible screws.
',
                    'seo_baslik' => 'Rectangular aluminium Gazebo 6×3 Prices 2026 | Kamelya',
                    'seo_aciklama' => '18 m² Rectangular gazebo seats 15 with flat roof, five-year warranty. Transparent m² pricing and free on-site survey. Book a visit Monday–Saturday.',
                    'seo_anahtar_kelimeler' => 'rectangular aluminium gazebo, aluminium gazebo prices, municipality gazebo, 6x3 gazebo',
                    'cati_tipi_aciklama' => 'The flat roof meets the modern silhouette and garden line on the same plane; rainwater drains through controlled channels between panels and never drips onto the seating zone. A tiny slope, invisible to the eye, is planned into the mould so drainage is guaranteed and no ponding forms. Wind behaviour is assessed at project scale and settled at the survey against external envelope logic referenced by EN 13561. The plane leaves room for lighting or menu-board mounts. Roof care needs one visual check a year — keep the holes open and rinse with a hose. Edge profiles guide water down the facade and leave no stain marks. Where needed, the drainage plan is discussed with the survey crew on site. The outlet stays above ground level so the surface remains dry. In winter the snow load spreads evenly across the plane instead of concentrating on one point. Cable runs for evening lighting hide under the ceiling; the drip line stays outside the seating circle. Dust and limescale rinse off with a hose; no chemicals are required.',
                    'korkuluk_aciklama' => 'The aluminium railing runs at an even height along every edge of the plan, shielding the seating area from cross-wind while keeping privacy inside. Height is confirmed at the survey against safety standards, preferring the dimension that supports the wrist when seated over a fixed catalogue number. Joints lock with cross-braces so the frame does not sway. The surface keeps the material\'s natural grain — smooth to the touch, with no splinters. Railing feet are lifted so water never wicks into the posts; all fixings are chosen for corrosion resistance. Vertical gaps are spaced to prevent hands or balls from jamming, while the top grip line stays flat for elderly residents. Where wheelchair access is needed, one entrance edge opens and leaves a clear passage. Cleaning takes a soft brush and lukewarm water; check the feet yearly. The material doesn\'t overheat in summer or feel cold in winter, and its texture matches the garden\'s palette. The railing can be painted on request; colour is decided at quote stage. Along the 6×3 plan it draws an unbroken safety line. A hidden channel under the top rail accepts a light strip or planter rail without visible screws.',
                ],
                'de' => [
                    'baslik' => 'Rechteckiger Aluminium-Pavillon 6x3 Stadtplatz',
                    'slug' => 'rechteckiger-aluminium-pavillon-6x3',
                    'kisa_aciklama' => 'Rechteckiger Aluminium-Pavillon 6×3 für Stadtplätze und öffentliche Flächen: widerstandsfähiger Ruhepunkt. Pulverbeschichtetes Aluminiumprofil beständig gegen Salzwasser und UV; das flache Dach leitet Regenwasser kontrolliert ab. Platz für 15 Personen auf 18 m². Maße und Montageplan werden nach dem kostenlosen Aufmaß fixiert (Mo-Sa 09:00-18:00). 5 Jahre Garantie.',
                    'detayli_aciklama' => '**Kurz gefasst**
- 6×3 m (18 m²) rechteckige Fläche; 15-Personen-Ruhepunkt für Stadtplätze und öffentliche Flächen.
- Pulverbeschichtetes Aluminium, flaches Dach, Salzwasser- und UV-Beständigkeit: ganzjährige Nutzung, 5 Jahre Garantie.
- Transparente m²-Rechnung: 18 × 2,50 EUR × 2,5 Aluminium × 1,0 rechteckig × 1,8 Gemeinde = 202,50 EUR.
- Maße und Montageplan werden beim kostenlosen Aufmaß fixiert (Mo-Sa 09:00-18:00).

## Technische Daten

| Merkmal | Wert |
| --- | --- |
| Bodenmaß | 6 × 3 m — 18 m² |
| Form | Rechteckig, vier Seiten |
| Hauptmaterial | Pulverbeschichtetes Aluminiumprofil |
| Dach | Flach, kontrollierte Entwässerung |
| Geländer | Aluminium, Höhe projektbezogen |
| Oberfläche | Pulverbeschichtung — Salzwasser- und UV-Beständigkeit |
| Befestigung | Korrosionsfeste Metallteile |
| Garantie | 5 Jahre |

## Für wen ist er geeignet?

Dieses Modell ist für Gemeinden gebaut, die öffentliche Flächen anlegen. Der 18 m²-Boden bildet am Platzrand einen langen Ruhestreifen: Bankreihen, rollstuhlgängiger Durchgang und Informationstafel stehen nebeneinander. Das Aluminiumprofil rostet nicht in salzigen oder chlorierten Bereichen, und die Pulverfarbe bleibt viele Jahre frisch. Schuleingänge, Parkeingänge, Haltestellen und Fußgängerzonen passen zu diesem Maß. Das flache Dach fügt sich in die Stadtsilhouette ein und lässt Platz für Leuchten. Drei offene Seiten rahmen den Blick; die vierte bleibt dem Fußgverkehr vorbehalten.

## Kapazität

Die Kapazität folgt dem freigegebenen Gemeinde-Faktor aus F15: 18 m² ÷ 1,20 m² pro Person = 15 — bequem sitzen also 15 Personen. Der Faktor enthält dichten öffentlichen Nutzungsdruck und Rollstuhl-Manövrierraum. Drei Bankreihen — oder zwei Bänke plus Info-Insel — passen ideal. Breitere Überdachungen können das 6×4- oder 5×5-Modell prüfen. Die endgültige Aufstellung wird beim Aufmaß mit dem Fußgängerflussplan geprüft.

## Montage

Das Modell wird auf Bestellung gefertigt. Beim kostenlosen Aufmaß prüft das Team Boden, Fußgängerfluss, Rollstuhlzugang und Lichtbedarf; Maße und Montageplan werden dort fixiert. Termine laufen Montag bis Samstag, 09:00-18:00. Das Aluminium-Panel-System beschleunigt Transport und Aufbau; Material geht direkt vom Fahrzeug an den Montagepunkt. Betonplatte oder Pflaster genügen; die Befestigung wird beim Aufmaß besprochen. Nach Abschluss kann die Fläche sofort für die Öffentlichkeit geöffnet werden.

## Pflege

Die Pflege besteht aus einer Waschung und einer Beschlagskontrolle pro Jahr; die Aluminiumoberfläche braucht keinen Lack und keine Imprägnierung. Weiche Bürste und neutrales Reinigungsmittel genügen — keine abrasiven Mittel. Laub und Schmutz vom flachen Dach zu Saisonbeginn entfernen, Drainageöffnungen offen halten. Metallteile sind korrosionsfest; lose Stellen bei der ersten Kontrolle nachziehen. Die Pulverfarbe bleibt dank UV-Beständigkeit viele Jahre frisch.

## Häufige Fragen

**Wie lange dauert die Montage?** Das Aluminium-Panel-System hält den Bau kurz; der genaue Tag wird beim Aufmaß bestätigt.
**Kann ich die Maße ändern?** Ja — jedes Maß wird auf Bestellung gefertigt; 6×3 ist nur der Standard-Ausgangspunkt.
**Was ist im Preis enthalten?** Transparente m²-Rechnung; Produkt- und Montagepositionen stehen getrennt im Angebot.
**Ist die Pflege wirklich so einfach?** Ja — die Aluminiumoberfläche braucht keinen Lack und keine Imprägnierung.

## Preis

Transparente m²-Rechnung: 18 m² × 2,50 EUR (Basis) × 2,5 (Aluminium) × 1,0 (rechteckig) × 1,8 (Gemeinde) = **202,50 EUR**. Die Faktoren entsprechen den aktiven Zeilen der Preis-Faktoren; der Rechner der Website liefert dasselbe Ergebnis. Das finale Angebot steht nach dem kostenlosen Aufmaß. Aufmaß-Termin: Montag bis Samstag, 09:00-18:00.',
                    'seo_baslik' => 'Rechteckiger Aluminium-Pavillon 6x3 Preise 2026 | Kamelya',
                    'seo_aciklama' => 'Rechteckiger Aluminium-Pavillon 6x3: 18 m², für 15, pulverbeschichtet, flaches Dach, Stadtplatz. Transparente m²-Preise, kostenloser Aufmaß vor Ort.',
                    'seo_anahtar_kelimeler' => 'rechteckiger Aluminium-Pavillon, Aluminium Pavillon Preise, Pavillon Stadtplatz, 6x3 Pavillon',
                    'cati_tipi_aciklama' => 'Das flache Dach des rechteckigen Aluminium-Pavillons trifft Stadtsilhouette und Platzlinie auf derselben Ebene; Regenwasser läuft durch kontrollierte Kanäle zwischen den Paneln ab und tropft nie auf die Sitzfläche. Informationstafeln, Leuchten oder Kamerahalterungen lassen sich am Flachdach einfach fixieren — kein Neigungswinkel nötig. Die Aluminiumverkleidung rostet nicht in salzigen oder chlorierten Umgebungen, und die Pulverbeschichtung hält die Farbe dank UV-Beständigkeit. Das Windverhalten wird projektmäßig bewertet und beim Aufmaß an der Logik externer Fassadenelemente mit EN-13561-Bezug abgeglichen. Eine winzige, mit bloßem Auge unsichtbare Neigung ist in die Form geplant, damit auf dem Flachdach kein Wasser stehen bleibt und die Entwässerung garantiert ist. Drainageöffnungen sitzen in den Ecken, wo Laub und Schmutz nicht liegen bleiben. Zur Dachpflege genügt eine Sichtkontrolle pro Jahr — Öffnungen offen halten, Oberfläche mit dem Schlauch spülen. Aluminium fault nicht und kennt keine Insekten; anders als Holzdächer braucht es keine Imprägnierung. Kantenprofile führen das Wasser kontrolliert an der Fassade hinab und hinterlassen keine Flecken. Bei Bedarf wird der Entwässerungsplan mit dem Aufmaß-Team vor Ort besprochen.',
                    'korkuluk_aciklama' => 'Das Aluminiumgeländer läuft an allen vier Kanten des rechteckigen Grundrisses in gleicher Höhe weiter und trennt Ruhegäste von Fahr- und Fußgängerströmen am Platz. Die Höhe wird beim Aufmaß anhand von Sicherheitsnormen und Projektbedingungen bestätigt; statt einer festen Katalogzahl wird das Maß gewählt, das das Handgelenk im Sitzen stützt. Eckverbindungen werden mit verdeckten Beschlägen verriegelt, damit nichts wackelt. Die Oberfläche wird durch UV-beständige Pulverbeschichtung geschützt — glatt bei der Berührung, ohne Splitter und ohne Rost. Die Geländerfüße werden angehoben, damit bodennahes Wasser nicht in die Pfosten steigt, und alle Beschläge sind gegen Korrosion ausgelegt. In öffentlichen Bereichen balancieren die Vertikalabstände Kindersicherheit und Rollstuhlzugang; die obere Griffleitung bleibt durchgehend gerade. Wo ein Rollstuhlvorgang nötig ist, öffnet sich eine Kante und lässt freie Passage. Zur Reinigung genügen weiche Bürste und neutrales Mittel; die Füße einmal jährlich prüfen, lose Stellen sofort nachziehen. Das Aluminiumgeländer wird im Sommer nicht heiß und im Winter nicht eiskalt, und die Textur passt zur Sprache der Stadtmöbel. Die Farbe kann beim Angebot aus der Gemeindepalette gewählt werden; die Pulverbeschichtung nimmt Farbwechsel problemlos an.',
                ],
                'fr' => [
                    'baslik' => 'Gazebo Rectangulaire Aluminium 6x3 Place de Ville',
                    'slug' => 'gazebo-rectangulaire-aluminium-6x3',
                    'kisa_aciklama' => 'Gazebo rectangulaire en aluminium de 6×3 m pour places de ville et espaces publics : point de repos. Profil aluminium thermolaqué résistant à l’eau salée et aux UV ; le toit plat évacue l’eau de pluie de manière contrôlée. 15 personnes sur 18 m². Dimensions et plan de pose fixés après l’étude gratuite (lun.-sam. 09h00-18h00). Garantie 5 ans.',
                    'detayli_aciklama' => '**En bref**
- Sol rectangulaire 6×3 m (18 m²) ; point de repos pour 15 personnes sur les places de ville et espaces publics.
- Aluminium thermolaqué, toit plat, résistance à l’eau salée et aux UV : usage toute saison, garantie 5 ans.
- Calcul m² transparent : 18 × 2,50 EUR × 2,5 aluminium × 1,0 rectangulaire × 1,8 municipalité = 202,50 EUR.
- Dimensions et plan de pose fixés pendant l’étude gratuite (lun.-sam. 09h00-18h00).

## Caractéristiques techniques

| Caractéristique | Valeur |
| --- | --- |
| Emprise au sol | 6 × 3 m — 18 m² |
| Forme | Rectangulaire, quatre côtés |
| Matériau principal | Profil aluminium thermolaqué |
| Toit | Plat, drainage contrôlé |
| Garde-corps | Aluminium, hauteur définie par projet |
| Surface | Thermolaquage — résistance eau salée et UV |
| Fixations | Pièces métalliques résistantes à la corrosion |
| Garantie | 5 ans |

## Pour qui ?

Ce modèle est conçu pour les municipalités qui aménagent des espaces publics. Les 18 m² rectangulaires forment une bande de repos le long de la place : bancs, passage accessible en fauteuil et panneau d’information se placent côte à côte. L’aluminium ne rouille pas en ambiance salée ou chlorée, et la couleur du thermolaquage tient des années. Entrées d’école, parcs, arrêts de bus et zones piétonnes s’accordent à cette taille. Le toit plat s’aligne sur la silhouette urbaine et laisse la place aux luminaires. Trois côtés ouverts cadrent la vue ; le quatrième reste au flux piéton.

## Capacité

La capacité suit le facteur municipalité approuvé en F15 : 18 m² ÷ 1,20 m² par personne = 15, soit confortablement 15 personnes. Le facteur inclut déjà la densité d’usage public et la manœuvre en fauteuil. Trois rangées de bancs — ou deux bancs plus une îlot d’information — conviennent idéalement. Les surfaces couvertes plus larges peuvent envisager les modèles 6×4 ou 5×5. Le placement final est vérifié pendant l’étude avec le plan de flux piéton.

## Pose

Le gazebo est fabriqué sur commande. L’étude gratuite examine le sol, le flux piéton, l’accessibilité fauteuil et l’éclairage ; dimensions et plan de pose s’y fixent. Les rendez-vous couvrent du lundi au samedi, 09h00-18h00. Le système de panneaux aluminium accélère transport et montage ; le matériel va du véhicule au point d’assemblage. Une dalle béton ou un pavage conviennent ; le mode de fixation se précise à l’étude. La zone peut ouvrir au public dès la fin des travaux.

## Entretien

L’entretien tient en un lavage et un contrôle des fixations par an ; la surface aluminium n’exige ni peinture ni traitement. Une brosse douce et un détergent neutre suffisent — pas d’abrasifs. Retirez feuilles et saleté du toit plat en début de saison et gardez les éviers ouverts. Les pièces métalliques résistent à la corrosion ; resserrez ce qui bouge dès le premier contrôle. Le thermolaquage conserve sa couleur des années grâce à la résistance UV.

## Questions fréquentes

**Combien de temps dure la pose ?** Le système de panneaux aluminium garde le chantier court ; le jour exact se fixe à l’étude.
**Puis-je modifier les dimensions ?** Oui — tout est fabriqué sur commande ; 6×3 n’est qu’un point de départ par défaut.
**Que comprend le prix ?** Calcul m² transparent ; produit et pose listés séparément dans le devis.
**L’entretien est-il vraiment simple ?** Oui — la surface aluminium n’exige ni peinture ni traitement.

## Prix

Calcul m² transparent : 18 m² × 2,50 EUR (base) × 2,5 (aluminium) × 1,0 (rectangulaire) × 1,8 (municipalité) = **202,50 EUR**. Les multiplicateurs sont ceux des lignes actives des facteurs de prix ; le calculateur du site donne le même résultat. Le devis final se stabilise après l’étude gratuite. Étude : du lundi au samedi, 09h00-18h00.',
                    'seo_baslik' => 'Gazebo Rectangulaire Aluminium 6x3 Prix 2026 | Kamelya',
                    'seo_aciklama' => 'Gazebo rectangulaire aluminium 6x3 : 18 m², 15 places, profil thermolaqué, toit plat, place de ville. Prix au m² transparents, étude gratuite sur place.',
                    'seo_anahtar_kelimeler' => 'gazebo rectangulaire aluminium, gazebo aluminium prix, gazebo municipalité, gazebo 6x3',
                    'cati_tipi_aciklama' => 'Le toit plat du gazebo rectangulaire aluminium rejoint la silhouette urbaine et la ligne de place sur le même plan ; l’eau de pluie s’évacue par des canaux contrôlés entre panneaux et ne tombe jamais sur l’assise. Panneaux d’information, luminaires ou support caméra se fixent facilement sur la forme plane — aucune pente à chercher. Le bardage aluminium ne rouille pas en ambiance salée ou chlorée, et le thermolaquage conserve sa couleur grâce à la résistance UV. Le comportement au vent s’évalue à l’échelle du projet et se fixe à l’étude selon la logique des éléments d’enveloppe extérieure référencés par l’EN 13561. Une pente minuscule, invisible à l’œil, est prévue dans le moule pour qu’aucune flaque ne subsiste sur le toit plat et que l’évacuation soit garantie. Les éviers se placent aux angles où feuilles et saleté ne s’accumulent pas. L’entretien du toit demande un contrôle visuel par an — gardez les ouvertures ouvertes et rincez la surface au tuyau. L’aluminium ne pourrit pas et n’accueille pas d’insectes ; contrairement au bois, aucun traitement n’est nécessaire. Les profils de bord guident l’eau vers la façade de façon contrôlée sans laisser de tache. Si besoin, le plan d’évacuation se discute avec l’équipe d’étude sur place.',
                    'korkuluk_aciklama' => 'Le garde-corps en aluminium court à hauteur égale sur les quatre arêtes du plan rectangulaire et sépare les personnes au repos des flux véhicules et piétons sur la place. Sa hauteur se confirme à l’étude selon les normes de sécurité et les conditions du projet, en préférant la mesure qui soutient le poignet assis plutôt qu’un chiffre de catalogue fixe. Les assemblages d’angle se verrouillent par fixations cachées pour éviter tout balancement. La surface est protégée par un thermolaquage résistant aux UV — douce au toucher, sans échardes et sans rouille. Les pieds sont surélevés pour que l’eau du sol ne remonte pas dans les poteaux, et toutes les pièces d’attache résistent à la corrosion. Dans les espaces publics, les entraxes verticaux équilibrent sécurité des enfants et accès en fauteuil ; la ligne de saisie supérieure reste droite et continue. Là où le passage en fauteuil est requis, un côté s’ouvre et libère une trajectoire libre. Le nettoyage demande une brosse douce et un détergent neutre ; contrôlez les pieds une fois par an et resserrez aussitôt ce qui bouge. Le garde-corps aluminium ne chauffe pas en été ni ne givre en hiver, et sa texture s’accorde au langage du mobilier urbain. La couleur se choisit dans la palette municipale au devis ; le thermolaquage accepte les changements de teinte sans difficulté.',
                ],
                'it' => [
                    'baslik' => 'Gazebo Rettangolare Alluminio 6x3 Piazza Comunale',
                    'slug' => 'gazebo-rettangolare-alluminio-6x3',
                    'kisa_aciklama' => 'Gazebo rettangolare in alluminio da 6×3 m per piazze comunali e spazi pubblici: punto di riposo. Profilo in alluminio verniciato a polvere resistente a sale e raggi UV ; il tetto piatto drena l’acqua piovana in modo controllato. 15 persone su 18 m². Misure e piano di posa fissati dopo il sopralluogo gratuito (lun.-sab. 09:00-18:00). Garanzia 5 anni.',
                    'detayli_aciklama' => '**In breve**
- Pavimento rettangolare 6×3 m (18 m²) ; punto di riposo per 15 persone nelle piazze comunali e spazi pubblici.
- Alluminio verniciato a polvere, tetto piatto, resistenza a sale e raggi UV: uso tutto l’anno, garanzia 5 anni.
- Calcolo m² trasparente: 18 × 2,50 EUR × 2,5 alluminio × 1,0 rettangolare × 1,8 comune = 202,50 EUR.
- Misure e piano di posa fissati durante il sopralluogo gratuito (lun.-sab. 09:00-18:00).

## Specifiche tecniche

| Voce | Valore |
| --- | --- |
| Impronta a terra | 6 × 3 m — 18 m² |
| Forma | Rettangolare, quattro lati |
| Materiale principale | Profilo in alluminio verniciato a polvere |
| Tetto | Piatto, scarico controllato |
| Parapetto | Alluminio, altezza definita dal progetto |
| Superficie | Verniciatura a polvere — resistenza a sale e UV |
| Fissaggi | Parti metalliche resistenti alla corrosione |
| Garanzia | 5 anni |

## Per chi è?

Questo modello è pensato per i comuni che allestiscono spazi pubblici. Il pavimento rettangolare da 18 m² forma una striscia di riposo lungo la piazza: panchine, passaggio accessibile in sedia a rotelle e tabellone informativo stanno affiancati. Il profilo in alluminio non arrugginisce in ambienti salini o clorati, e il colore della verniciatura dura anni. Ingressi di scuola, entrate di parchi, fermate e zone pedonali si adattano bene a questa misura. Il tetto piatto si allinea allo skyline urbano e lascia spazio ai supporti luce. Tre lati aperti inquadrano la vista; il quarto resta al flusso pedonale.

## Capienza

La capienza segue il fattore comune approvato in F15: 18 m² ÷ 1,20 m² a persona = 15, quindi 15 persone comodamente. Il fattore include già la densità d’uso pubblico e lo spazio di manovra per sedia a rotelle. Tre file di panchine — o due panchine più un’isola informativa — sono ideali. Superfici coperte più ampie possono valutare i modelli 6×4 o 5×5. La posa finale si verifica al sopralluogo con il piano del flusso pedonale.

## Posa

Il gazebo è prodotto su ordinazione. Il sopralluogo gratuito valuta terreno, flusso pedonale, accessibilità e illuminazione; misure e piano di posa si fissano lì. Gli appuntamenti vanno da lunedì a sabato, 09:00-18:00. Il sistema a pannelli in alluminio accelera trasporto e montaggio; i materiali vanno dal mezzo al punto di assemblaggio. Solaio in cemento o pavimentazione vanno bene; il tipo di fissaggio si definisce al sopralluogo. L’area può aprire al pubblico appena finiti i lavori.

## Manutenzione

La manutenzione è un lavaggio e un controllo dei fissaggi all’anno; la superficie in alluminio non richiede vernice né trattamento. Spazzola morbida e detergente neutro bastano — niente abrasivi. Togliere foglie e sporco dal tetto piatto a inizio stagione e tenere aperti gli scarichi. Le ferramenta sono a prova di corrosione; stringere subito ciò che è mosso al primo controllo. La verniciatura a polvere mantiene il colore per anni grazie alla resistenza UV.

## Domande frequenti

**Quanto dura la posa?** Il sistema a pannelli in alluminio tiene il cantiere corto; il giorno esatto si fissa al sopralluogo.
**Posso cambiare le misure?** Sì — ogni pezzo è su ordinazione; 6×3 è solo il punto di partenza predefinito.
**Cosa comprende il prezzo?** Calcolo m² trasparente; prodotto e posa elencati separatamente nel preventivo.
**La manutenzione è davvero semplice?** Sì — la superficie in alluminio non richiede vernice né trattamento.

## Prezzo

Calcolo m² trasparente: 18 m² × 2,50 EUR (base) × 2,5 (alluminio) × 1,0 (rettangolare) × 1,8 (comune) = **202,50 EUR**. I moltiplicatori corrispondono alle righe attive dei fattori di prezzo; il calcolatore del sito produce lo stesso risultato. Il preventivo definitivo si stabilisce dopo il sopralluogo gratuito. Sopralluogo: da lunedì a sabato, 09:00-18:00.',
                    'seo_baslik' => 'Gazebo Rettangolare Alluminio 6x3 Prezzi nel 2026 | Kamelya',
                    'seo_aciklama' => 'Gazebo rettangolare alluminio 6x3: 18 m², 15 posti, profilato verniciato, tetto piatto, piazza comunale. Prezzi al m² trasparenti, sopralluogo gratuito.',
                    'seo_anahtar_kelimeler' => 'gazebo rettangolare alluminio, gazebo alluminio prezzi, gazebo comune, gazebo 6x3',
                    'cati_tipi_aciklama' => 'Il tetto piatto del gazebo rettangolare in alluminio incontra skyline urbano e linea di piazza sullo stesso piano; l’acqua piovana si drena attraverso canali controllati tra i pannelli e non gocciola mai sulla seduta. Tabelloni informativi, luci o supporti telecamera si fissano facilmente sulla forma piana — nessuna pendenza da cercare. Il rivestimento in alluminio non arrugginisce in ambienti salini o clorati, e la verniciatura a polvere mantiene il colore grazie alla resistenza UV. Il comportamento al vento si valuta a scala di progetto e si definisce al sopralluogo secondo la logica degli elementi di involucro esterno richiamati dalla EN 13561. Una pendenza minuscola, invisibile a occhio nudo, è prevista nello stampo perché sul tetto piatto non resti pozzanghera e lo scarico sia garantito. Le bocchette stanno agli angoli dove foglie e sporco non si raccolgono. La manutenzione del tetto richiede un controllo visivo all’anno — tenere aperte le bocchette e sciacquare la superficie con il tubo. L’alluminio non marcisce e non ospita insetti; a differenza del legno non serve trattamento. I profili di bordo guidano l’acqua verso la facciata in modo controllato senza lasciare macchie. Se serve, il piano di scarico si discute con l’equipaggio del sopralluogo in loco.',
                    'korkuluk_aciklama' => 'Il parapetto in alluminio prosegue ad altezza uguale su tutti i quattro spigoli del piano rettangolare e separa chi riposa dai flussi veicolari e pedonali in piazza. L’altezza si conferma al sopralluogo secondo norme di sicurezza e condizioni di progetto, preferendo la misura che sorregge il polso da seduti a un numero fisso di catalogo. Le giunzioni d’angolo si bloccano con fissaggi nascosti che eliminano l’oscillazione. La superficie è protetta da verniciatura a polvere resistente ai raggi UV — liscia al tatto, senza scaglie e senza ruggine. I piedi sono sollevati perché l’acqua del terreno non risalga nei pali, e tutte le ferramenta sono a prova di corrosione. Negli spazi pubblici gli interassi verticali bilanciano sicurezza dei bambini e accesso in sedia a rotelle; la linea di presa superiore resta dritta e continua. Dove serve il passaggio in sedia a rotelles, un lato si apre e lascia il transito libero. La pulizia richiede spazzola morbida e detergente neutro; controllare i piedi una volta l’anno e stringere subito ciò che è mosso. Il parapetto in alluminio non scalda d’estate né gela d’inverno, e la texture si accorda al linguaggio dei mobili urbani. Il colore si sceglie dalla palette comunale in fase di preventivo; la verniciatura accetta cambi di tinta senza difficoltà.',
                ],
                'ar' => [
                    'baslik' => 'كوش مستطيل ألمنيوم 6x3 لساحات البلديات',
                    'slug' => 'rectangular-aluminium-kush-6x3',
                    'kisa_aciklama' => 'كوش مستطيل ألمنيوم بمقاس 6×3 متر لساحات البلديات: نقطة استراحة متينة. بروفايل ألمنيوم يقاوم الماء المالح والأشعة؛ السقف المستوي يصرف الأمطار. يتسع لـ 15 شخصًا على 18 م². تُثبَّت المقاسات وخطة التركيب بعد الاستشارة المجانية (الاثنين–السبت 09:00–18:00). ضمان 5 سنوات.',
                    'detayli_aciklama' => '**باختصار**
- أرضية مستطيلة 6×3 م (18 م²)؛ نقطة استراحة تتسع لـ 15 شخصًا في ساحات البلديات والفضاءات العامة.
- ألمنيوم مطلي بالبودرة، سقف مستوي، مقاومة الماء المالح والأشعة: استخدام على مدار السنة، ضمان 5 سنوات.
- حساب شفاف للمتر: 18 × 3 USD × 2.5 ألمنيوم × 1.0 مستطيل × 1.8 بلدية = 243.00 USD.
- تُثبَّت المقاسات وخطة التركيب في الاستشارة المجانية (الاثنين–السبت 09:00–18:00).

## المواصفات التقنية

| البند | القيمة |
| --- | --- |
| مقاس الأرضية | 6 × 3 م — 18 م² |
| الشكل | مستطيل، أربعة أضلاع |
| المادة الأساسية | بروفايل ألمنيوم مطلي بالبودرة |
| السقف | مستوٍ، تصريف مضبوط |
| السور | ألمنيوم، الارتفاع حسب المشروع |
| السطح | طلاء بودرة — مقاومة الماء المالح والأشعة |
| التثبيت | قطع معدنية مقاومة للصدأ |
| الضمان | 5 سنوات |

## لمن صُمم؟

هذا الموديل للبلديات التي تنظم الفضاءات العامة. أرضية 18 م² المستطيلة تشكّل شريط استراحة طويلًا على حافة الساحة: صفوف مقاعد وممر مهيأ للكراسي المتحركة ولوحة معلومات تتوائم. لا يصدأ بروفايل الألمنيوم في البيئات المالحة أو الكلورية، ويبقى لون الطلاء لسنوات. مدارس ومتنزهات ومحطات ومناطق مشاة تتسع لهذا المقاس بسهولة. يتواءم السقف المستوي مع خط المدينة ويكفي للتثبيتات الإضاءة. ثلاثة وجوه مفتوحة تؤطر المشهد؛ أما الرابع فمخصّص لحركة المشاة.

## السعة

تُحسب السعة ومعامل البلدية المعتمد من F15: 18 م² ÷ 1.20 م² للشخص = 15 — أي 15 شخصًا براحة. يضم المعامل كثافة الاستخدام العام ومساحة المناورة لكراسي المتحركة. ثلاثة صفوف مقاعد — أو مقعدان وجزيرة معلومات — مناسبة تمامًا. للمساحات المغطاة الأوسع توجد موديلات 6×4 أو 5×5. يُتحقق من التوضع النهائي أثناء الاستشارة مع خطة حركة المشاة.

## التركيب

يُصنع الموديل عند الطلب. في الاستشارة المجانية يفحص الفريق الأرضية وحركة المشاة والوصول للكراسي وحاجة الإضاءة؛ وتُثبَّت المقاسات وخطة التركيب في ذلك الموعد. المواعيد من الاثنين إلى السبت 09:00–18:00. نظام ألواح الألمنيوم يسرّع النقل والتركيب؛ تُنقل المواد مباشرة من السيارة إلى نقطة التركيب. بلاطة خرسانية أو رصف كفاية؛ تُحدَّد طريقة التثبيت في الاستشارة. يمكن فتح المساحة للجمهور فور انتهاء العمل.

## الصيانة

الصيانة غسلة وفحص تثبيتات سنويًا؛ سطح الألمنيوم لا يحتاج دهانًا ولا معالجة. تكفي فرشاة ناعمة ومحلول محايد — بلا منظفات كاشطة. تُنظَّف أوراق الأرضية المستوية واتربتها في بداية الموسم وتبقى فتحات التصريف مفتوحة. القطع المعدنية مقاومة للصدأ؛ يُشدّ المحكم فورًا. يحافظ الطلاء على لونه لسنوات بفضل مقاومة الأشعة.

## أسئلة شائعة

**كم يستغرق التركيب؟** نظام ألواح الألمنيوم يختصر العمل؛ اليوم الدقيق يُحدَّد في الاستشارة.
**هل يمكن تغيير المقاسات؟** نعم — كل القطع تُصنع عند الطلب؛ 6×3 نقطة انطلاق افتراضية فقط.
**ما الذي يشمله السعر؟** حساب متر شفاف؛ المنتج والتركيب يُذكران منفصلين في العرض.
**هل الصيانة بهذه البساطة فعلًا؟** نعم — سطح الألمنيوم لا يحتاج دهانًا ولا معالجة.

## السعر

حساب متر شفاف: 18 م² × 3 USD (أساسي) × 2.5 (ألمنيوم) × 1.0 (مستطيل) × 1.8 (بلدية) = **243.00 USD**. المضاعفات مطابقة للصفوف النشطة في جدول معاملات السعر؛ آلة الموقع تعطي النتيجة نفسها. يستقر العرض النهائي بعد الاستشارة المجانية. موعد الاستشارة: الاثنين–السبت 09:00–18:00.',
                    'seo_baslik' => 'كوش مستطيل ألمنيوم أسعار 2026 | Kamelya',
                    'seo_aciklama' => 'كوش مستطيل ألمنيوم: 18 م²، 15 شخصًا، سقف مستوي، ساحة بلدية. أسعار متر شفاف، استشارة مجانية',
                    'seo_anahtar_kelimeler' => 'كوش ألمنيوم مستطيل, أسعار الكوش الألمنيوم, كوش البلديات, كوش 6x3',
                    'cati_tipi_aciklama' => 'يلتقي السقف المستوي للكوش المستطيل الألمنيوم مع خط المدينة والساحة على المستوى نفسه؛ تُصرف مياه الأمطر عبر قنوات مضبوطة بين الألواح ولا تتقاطر أبدًا على منطقة الجلوس. تُثبَّت لوحات المعلومات والإضاءة أو حاملات الكاميرات بسهولة على الشكل المستوي — بلا حاجة لبحث عن ميل. لا يصدأ تكسية الألمنيوم في البيئات المالحة أو الكلورية، ويحافظ الطلاء على لونه بفضل مقاومة الأشعة. يُقيَّم سلوك الرياح على مستوى المشروع ويُحسم في الاستشارة وفق منطق عناصر الغلاف الخارجي المرجعية (EN 13561). يُخطط ميل صغير غير مرئي داخل القالب كي لا تتكوّن بركة على السقف المستوي ويكون التصريف مضمونًا. توضع فتحات التصريف في الزوايا حيث لا تتراكم الأوراق والأتربة. صيانة السقف فحص بصري سنوي — إبقاء الفتحات مفتوحة وشطف السطح بالخرطوم. الألمنيوم لا يتعفن ولا يستقبل الحشرات؛ بخلاف الخشب لا يحتاج معالجة. تقود الحواف المائية إلى الواجهة بشكل مضبوط دون أن تترك بقعًا. وعند الحاجة يُناقش خطة التصريف مع فريق الاستشارة في الموقع. تُنظَّف المرزة من الأوراق في بداية الموسم وتبقى فتحات التصريف فوق سطح الأرض دائمًا.',
                    'korkuluk_aciklama' => 'يستمر سور الألمنيوم بارتفاع متساوٍ على الحواف الأربعة للخطة المستطيلة ويفصل المتنزهين عن حركة المركبات والمشاة في الساحة. يُثبَّت الارتفاع في الاستشارة وفق معايير السلامة وظروف المشروع، مع تفضيل المقاس الذي يسند المعصم أثناء الجلوس على رقم كتالوج ثابت. تُقفل زوايا الالتقاء بتثبيتات مخفية تمنع الاهتزاز. يُحمى السطح بطلاء بودرة مقاوم للأشعة — ناعم عند اللمس دون شظايا ودون صدأ. تُرفع القواعد كي لا يصعد ماء الأرض إلى الجذوع، وتُختار كل القطع المعدنية مقاومة للصدأ. في الفضاءات العامة توازن المسافات الرأسية بين أمان الأطفال ووصول الكراسي، ويبقى خط القبضة العلوي مستقيمًا ومتصلًا. حيث يلزم مرور الكرسي المتحرك تُفتح حافة ويبقى الممر حرًا. التنظيف بفرشاة ناعمة ومحلول محايد يكفي؛ فحص القواعد سنويًا وشدّ المحكم فورًا. لا يسخن سور الألمنيوم صيفًا ولا يُثلج شتاءً، ونسجه ينسجم مع لغة أثاث المدينة. يُختار اللون من لوحة البلدية عند العرض؛ يستوعب الطلاء تغيير الألوان بسهولة. وتبقى زوايا الالتقاء مغلقة فلا تتراكم فيها الأتربة، ويترك ممر كابلات بين البروفايلات لإضاءة المساء.',
                ],
            ],
            'KML-ALU-MOD-007' => [
                'tr' => [
                    'baslik' => 'Modern Alüminyum Kamelya 5x5 Otel Bahçesi',
                    'slug' => 'modern-aluminyum-kamelya-5x5-otel-007',
                    'kisa_aciklama' => 'Modern alüminyum kamelya, 5×5 metre tabanıyla Otel Bahçesi için 8-9 kişilik dengeli bir oturma alanı sunar. Toz boyalı alüminyum profil öne çıkar; düz çatı yağmuru kontrollü tahliye eder. Elektrostatik toz boya, UV stabil ile dört mevsim kullanıma uygundur. Net ölçü ve montaj planı ücretsiz keşifte belirlenir; 5 yıl garantilidir.',
                    'detayli_aciklama' => '**TL;DR**
- 5×5 m (25 m²) modern taban; Otel Bahçesi için 8-9 kişilik ferah oturma alanı.
- toz boyalı alüminyum profil, Düz, kontrollü tahliye: dört mevsim kullanım, 5 yıl garanti.
- Şeffaf m² hesabı: 25 × 12.000 × 2.5 (Alüminyum) × 1.5 (modern) × 1.6 (otel) = 1.800.000 TL.
- Net ölçü ve montaj planı ücretsiz keşifte sabitlenir (Pzt–Cmt 09:00–18:00).

## Teknik Özellikleri Nelerdir?

| Alan | Değer |
| --- | --- |
| Taban ölçüsü | 5×5 m — 25 m² |
| Form | Modern, geniş açıklık |
| Ana malzeme | toz boyalı alüminyum profil |
| Çatı | Düz, kontrollü tahliye |
| Korkuluk | alüminyum korkuluk, proje bazlı yükseklik |
| Yüzey işlemi | Elektrostatik toz boya, UV stabil |
| Bağlantı | Korozyona dayanıklı metal elemanlar |
| Garanti | 5 yıl |

## Kimler İçin?

Bu model, Otel Bahçesi ölçeğinde çalışan işletmeler ve site yönetimleri için tasarlandı. 25 m² modern taban, mobilyayı serbestçe yerleştirir: 8-9 kişilik bir düzen rahatça kurulur; yemek masası ile dinlenme köşesi yan yana yerleşir.

## Kaç Kişiliktir?

Kapasite, F15 onaylı otel katsayısıyla hesaplanır: 25 m² ÷ 2,80 m²/kişi ≈ 8,93 — yani 8-9 kişi rahat oturur. Bu katsayı, kullanım yoğunluğuna göre seçilir; mahremiyet ve kol hareketi serbestliği, sıkışıklıktan önce gelir.

## Montaj Nasıl Yapılır?

Ürün sipariş üzerine üretilir. Ücretsiz keşifte zemin, giriş yönü ve varsa elektrik ihtiyacı değerlendirilir; net ölçü ile montaj planı bu görüşmede sabitlenir. Keşif randevuları Pzt–Cmt 09:00–18:00 arasındadır ve ücretsizdir.

## Bakımı Nasıl Yapılır?

Bakım, yılda bir yüzey kontrolü ve gerekirse koruyucu yenilemeden ibarettir. Elektrostatik toz boya, UV stabil. Çatı olukları ve tahliye açıklıkları yapraktan temizlenir; düz çatı üzerinde su birikintisi bırakılmaz. Korkuluk bağlantıları yılda bir sıkıştırılır.

## Neden Bu Model?

Bu model, Otel Bahçesi için doğru dengeyi kurar: 25 m² taban ne dar kalır ne de bakımı şişirir. Modern, geniş açıklık form, peyzaj çizgisine uyar; Düz, kontrollü tahliye yağmuru oturma alanından uzak tutar.

## Çatı Özellikleri Nelerdir?

Düz çatı, modern silueti ve bahçe çizgisiyle aynı düzlemde buluşur; yağmur suyu panel aralarındaki kontrollü kanallardan tahliye edilir ve oturma alanına damlamaz. toz boyalı alüminyum profil üzerinde su birikintisi oluşmaması için minik bir eğim kalıp içinde planlanır; bu eğim gözle görünmez ama tahliyeyi garanti eder. Rüzgâr yükü davranışı, proje ölçeğinde değerlendirilir ve alan, EN 13561 referansıyla tartışılan dış cephe elemanları mantığına göre keşifte netleşir. Düz yüzey, aydınlatma veya menü panosu montajına alan bırakır. Çatı bakımına yılda bir göz kontrolü yeterlidir; tahliye delikleri açık tutulur, yüzey hortumla yıkanır. Elektrostatik toz boya, UV stabil; ahşap çatının tersine emprenye döngüsü gerekmez. Kenar profilleri, suyun cepheye kontrollü inmesini sağlar ve leke izi bırakmaz. Gerektiğinde tahliye planı, keşif ekibiyle araziye göre konuşulur. Oluk çıkışı zemin kotunun üstünde tutulur; yüzey kuru kalır, su kontrollü iner. Kış aylarında kar yükü, düz planda eşit dağılır; tek noktaya toplanmaz. Gece aydınlatması eklenirse kablo tavan altından gizlenir, damla noktası dışarıda kalır. Kireç ve toz birikintisi hortum suyuyla birkaç dakikada akar; kimyasal gerekmez.

## Korkuluk ve Güvenlik Özellikleri Nelerdir?

alüminyum korkuluk, planın her kenarında eşit yükseklikte devam eder ve oturma alanını dışarıdan gelen rüzgâra karşı korurken içeride mahremiyet hissi bırakır. Yükseklik, güvenlik standartlarına ve proje koşullarına göre keşif sırasında netleşir; sabit bir katalog rakamı yerine, kullanıcının oturduğu anda bileğini destekleyen ölçü tercih edilir. Birleşim noktaları çapraz bağlantılarla kilitlenir, sallanma önlenir. Yüzey, malzemenin doğal dokusunu koruyan koruyucu kaplamayla hazırlanır; elle temas pürüzsüzdür, yonga bırakmaz. Korkuluk dip kısımları, zemine geçen suyu gövdeye taşımaması için yükseltilir ve bağlantı elemanları korozyona dayanıklı malzemeden seçilir. Çocuklu alanlarda dikey aralıklar, topun veya kolun sıkışmasını önleyecek şekilde düzenlenir; yaşlı sakinler için üst tutamak hattı düz ve kesintisizdir. Tekerlekli sandalye erişimi gereken giriş kenarında korkuluk açılıp geçiş bırakacak şekilde planlanır. Temizlikte yumuşak fırça ve ılık su yeterlidir; dip noktalar yılda bir kontrol edilir, gevşenen sıkma hemen yapılır. Malzeme, alternatife göre yazın ısınmaz, kışın soğuk tutmaz; dokusu bahçenin malzeme diliyle uyumludur. İstenirse boyanabilir; renk değişikliği teklif aşamasında belirlenir. Korkuluk, 5x5 planında kesintisiz bir güvenlik hattı çizer ve mobilya yerleşimini içeriden çerçeveler. Işık bandı veya saksı rafı istenirse üst profilin iç yüzüne gizli kanal açılır; görünür vida bırakılmaz.
',
                    'seo_baslik' => 'Modern Alüminyum Kamelya 5x5 Fiyatları 2026 | Kamelya',
                    'seo_aciklama' => 'Modern alüminyum kamelya 5x5: 25 m² alan, 8-9 kişilik otel bahçesi, düz çatı, 5 yıl garantili. Şeffaf m² fiyat listesi ve ücretsiz keşif randevusu.',
                    'seo_anahtar_kelimeler' => 'modern alüminyum kamelya, alüminyum kamelya fiyatları, 5x5 kamelya, otel kamelyası',
                    'cati_tipi_aciklama' => 'Düz çatı, modern silueti ve bahçe çizgisiyle aynı düzlemde buluşur; yağmur suyu panel aralarındaki kontrollü kanallardan tahliye edilir ve oturma alanına damlamaz. toz boyalı alüminyum profil üzerinde su birikintisi oluşmaması için minik bir eğim kalıp içinde planlanır; bu eğim gözle görünmez ama tahliyeyi garanti eder. Rüzgâr yükü davranışı, proje ölçeğinde değerlendirilir ve alan, EN 13561 referansıyla tartışılan dış cephe elemanları mantığına göre keşifte netleşir. Düz yüzey, aydınlatma veya menü panosu montajına alan bırakır. Çatı bakımına yılda bir göz kontrolü yeterlidir; tahliye delikleri açık tutulur, yüzey hortumla yıkanır. Elektrostatik toz boya, UV stabil; ahşap çatının tersine emprenye döngüsü gerekmez. Kenar profilleri, suyun cepheye kontrollü inmesini sağlar ve leke izi bırakmaz. Gerektiğinde tahliye planı, keşif ekibiyle araziye göre konuşulur. Oluk çıkışı zemin kotunun üstünde tutulur; yüzey kuru kalır, su kontrollü iner. Kış aylarında kar yükü, düz planda eşit dağılır; tek noktaya toplanmaz. Gece aydınlatması eklenirse kablo tavan altından gizlenir, damla noktası dışarıda kalır. Kireç ve toz birikintisi hortum suyuyla birkaç dakikada akar; kimyasal gerekmez.',
                    'korkuluk_aciklama' => 'alüminyum korkuluk, planın her kenarında eşit yükseklikte devam eder ve oturma alanını dışarıdan gelen rüzgâra karşı korurken içeride mahremiyet hissi bırakır. Yükseklik, güvenlik standartlarına ve proje koşullarına göre keşif sırasında netleşir; sabit bir katalog rakamı yerine, kullanıcının oturduğu anda bileğini destekleyen ölçü tercih edilir. Birleşim noktaları çapraz bağlantılarla kilitlenir, sallanma önlenir. Yüzey, malzemenin doğal dokusunu koruyan koruyucu kaplamayla hazırlanır; elle temas pürüzsüzdür, yonga bırakmaz. Korkuluk dip kısımları, zemine geçen suyu gövdeye taşımaması için yükseltilir ve bağlantı elemanları korozyona dayanıklı malzemeden seçilir. Çocuklu alanlarda dikey aralıklar, topun veya kolun sıkışmasını önleyecek şekilde düzenlenir; yaşlı sakinler için üst tutamak hattı düz ve kesintisizdir. Tekerlekli sandalye erişimi gereken giriş kenarında korkuluk açılıp geçiş bırakacak şekilde planlanır. Temizlikte yumuşak fırça ve ılık su yeterlidir; dip noktalar yılda bir kontrol edilir, gevşenen sıkma hemen yapılır. Malzeme, alternatife göre yazın ısınmaz, kışın soğuk tutmaz; dokusu bahçenin malzeme diliyle uyumludur. İstenirse boyanabilir; renk değişikliği teklif aşamasında belirlenir. Korkuluk, 5x5 planında kesintisiz bir güvenlik hattı çizer ve mobilya yerleşimini içeriden çerçeveler. Işık bandı veya saksı rafı istenirse üst profilin iç yüzüne gizli kanal açılır; görünür vida bırakılmaz.',
                ],
                'en' => [
                    'baslik' => 'Modern Aluminium Gazebo 5x5 Hotel Garden',
                    'slug' => 'modern-aluminium-gazebo-5x5-007',
                    'kisa_aciklama' => 'Modern aluminium gazebo, 5×5 footprint for hotel gardens. Powder coat with saltwater and UV resistance and flat roof keep four-season use simple. Seats 8-9 across 25 m². Measurements and install plan lock after the free survey (Mon–Sat 09:00–18:00). Five-year warranty.',
                    'detayli_aciklama' => '**TL;DR**
- 5×5 m (25 m²) Modern, open span floor; an 8-9-person seating area for hotel gardens.
- Powder coat with saltwater and UV resistance, Flat, controlled drainage: four-season use, five-year warranty.
- Transparent m² maths: 25 × 3 USD × 2.5 × 1.5 × 1.6 = 450 USD.
- Final measurements and the installation plan are fixed at the free survey (Mon-Sat 09:00-18:00).

## What Are the Technical Specifications?

| Item | Value |
| --- | --- |
| Floor size | 5×5 m — 25 m² |
| Shape | Modern, open span |
| Main material | aluminium |
| Roof | Flat, controlled drainage |
| Railing | aluminium railing, height set per project |
| Surface | Powder coat with saltwater and UV resistance |
| Warranty | 5 years |

## Who Is It For?

This model is built for hotel operators and site teams that plan at garden scale.

## How Many People Does It Seat?

Seating follows the approved hotel gardens ratio from F15: 25 m² ÷ 2.80 m² per person ≈ 8.93, so 8-9 people sit comfortably.

## How Is It Installed?

The product is made to order.

## How Is It Maintained?

Care is one surface check a year plus a protective refresh when needed.

## Why This Model?

This model balances scale and upkeep for hotel gardens.

## What About the Roof?

The flat roof meets the modern silhouette and garden line on the same plane; rainwater drains through controlled channels between panels and never drips onto the seating zone. A tiny slope, invisible to the eye, is planned into the mould so drainage is guaranteed and no ponding forms. Wind behaviour is assessed at project scale and settled at the survey against external envelope logic referenced by EN 13561. The plane leaves room for lighting or menu-board mounts. Roof care needs one visual check a year — keep the holes open and rinse with a hose. Edge profiles guide water down the facade and leave no stain marks. Where needed, the drainage plan is discussed with the survey crew on site. The outlet stays above ground level so the surface remains dry. In winter the snow load spreads evenly across the plane instead of concentrating on one point. Cable runs for evening lighting hide under the ceiling; the drip line stays outside the seating circle. Dust and limescale rinse off with a hose; no chemicals are required.

## What About the Railing and Safety?

The aluminium railing runs at an even height along every edge of the plan, shielding the seating area from cross-wind while keeping privacy inside. Height is confirmed at the survey against safety standards, preferring the dimension that supports the wrist when seated over a fixed catalogue number. Joints lock with cross-braces so the frame does not sway. The surface keeps the material\'s natural grain — smooth to the touch, with no splinters. Railing feet are lifted so water never wicks into the posts; all fixings are chosen for corrosion resistance. Vertical gaps are spaced to prevent hands or balls from jamming, while the top grip line stays flat for elderly residents. Where wheelchair access is needed, one entrance edge opens and leaves a clear passage. Cleaning takes a soft brush and lukewarm water; check the feet yearly. The material doesn\'t overheat in summer or feel cold in winter, and its texture matches the garden\'s palette. The railing can be painted on request; colour is decided at quote stage. Along the 5×5 plan it draws an unbroken safety line. A hidden channel under the top rail accepts a light strip or planter rail without visible screws.
',
                    'seo_baslik' => 'Modern Aluminium Gazebo 5×5 Price List 2026 | Kamelya',
                    'seo_aciklama' => '25 m² Modern gazebo seats 8-9 with flat roof, five-year warranty. Transparent m² pricing and free on-site survey. Book a visit Monday–Saturday — free.',
                    'seo_anahtar_kelimeler' => 'modern aluminium gazebo, aluminium gazebo prices, 5x5 gazebo, hotel gazebo',
                    'cati_tipi_aciklama' => 'The flat roof meets the modern silhouette and garden line on the same plane; rainwater drains through controlled channels between panels and never drips onto the seating zone. A tiny slope, invisible to the eye, is planned into the mould so drainage is guaranteed and no ponding forms. Wind behaviour is assessed at project scale and settled at the survey against external envelope logic referenced by EN 13561. The plane leaves room for lighting or menu-board mounts. Roof care needs one visual check a year — keep the holes open and rinse with a hose. Edge profiles guide water down the facade and leave no stain marks. Where needed, the drainage plan is discussed with the survey crew on site. The outlet stays above ground level so the surface remains dry. In winter the snow load spreads evenly across the plane instead of concentrating on one point. Cable runs for evening lighting hide under the ceiling; the drip line stays outside the seating circle. Dust and limescale rinse off with a hose; no chemicals are required.',
                    'korkuluk_aciklama' => 'The aluminium railing runs at an even height along every edge of the plan, shielding the seating area from cross-wind while keeping privacy inside. Height is confirmed at the survey against safety standards, preferring the dimension that supports the wrist when seated over a fixed catalogue number. Joints lock with cross-braces so the frame does not sway. The surface keeps the material\'s natural grain — smooth to the touch, with no splinters. Railing feet are lifted so water never wicks into the posts; all fixings are chosen for corrosion resistance. Vertical gaps are spaced to prevent hands or balls from jamming, while the top grip line stays flat for elderly residents. Where wheelchair access is needed, one entrance edge opens and leaves a clear passage. Cleaning takes a soft brush and lukewarm water; check the feet yearly. The material doesn\'t overheat in summer or feel cold in winter, and its texture matches the garden\'s palette. The railing can be painted on request; colour is decided at quote stage. Along the 5×5 plan it draws an unbroken safety line. A hidden channel under the top rail accepts a light strip or planter rail without visible screws.',
                ],
                'de' => [
                    'baslik' => 'ModernerAluminium--Pavillon 5x5 Hotelgarten',
                    'slug' => 'moderner-aluminium-pavillon-5x5-007',
                    'kisa_aciklama' => 'Moderner Aluminium-Pavillon 5x5 für Hotelgärten: pulverbeschichtetes Aluminiumprofil, flaches Dach mit kontrollierter Entwässerung und klarer Sitzordnung. Platz für 8-9 Personen auf 25 m². Maße und Montageplan werden nach dem kostenlosen Aufmaß fixiert (Mo-Sa 09:00-18:00). 5 Jahre Garantie inklusive.',
                    'detayli_aciklama' => '**Kurz gefasst**
- 5×5 m (25 m²) modern Fläche; 8-9-Personen-Sitzplatz für Hotelgärten.
- pulverbeschichtetes Aluminiumprofil, Flach, kontrollierte Entwässerung: ganzjährige Nutzung, 5 Jahre Garantie.
- Transparente m²-Rechnung: 25 × 2,50 EUR × 2.5 × 1.5 × 1.6 = 375 EUR.
- Maße und Montageplan werden beim kostenlosen Aufmaß fixiert (Mo-Sa 09:00-18:00).

## Technische Daten

| Merkmal | Wert |
| --- | --- |
| Bodenmaß | 5×5 m — 25 m² |
| Form | Modern, weite Spanne |
| Hauptmaterial | pulverbeschichtetes Aluminiumprofil |
| Dach | Flach, kontrollierte Entwässerung |
| Geländer | Aluminiumgeländer, Höhe projektbezogen |
| Oberfläche | Pulverbeschichtung — Salzwasser- und UV-Beständigkeit |
| Befestigung | Korrosionsfeste Metallteile |
| Garantie | 5 Jahre |

## Für wen ist er geeignet?

Dieses Modell ist für Hotelgärten und Hausverwaltungen gebaut, die im Gartenmaß planen. Der 25 m²-modern-Boden lässt Möbel frei stehen: eine 8-9-Personen-Aufteilung passt mühelos; Esstisch und ruhige Ecke teilen sich den Platz. Die Oberfläche spart Reinigung in der Hochsaison. Gäste finden einen übersichtlichen Treffpunkt; das Aluminiumgeländer schützt die Sitzfläche an der Windseite. Schmale Höfe und Pläne passen zum Maß, ohne die Landschaftslinie zu brechen. Projekte mit Quartiersbudget vermeiden Kostenüberraschungen durch den Standardgrundriss. Drei offene Seiten rahmen den Blick; die vierte bleibt Service oder Fußgverkehr vorbehalten.

## Kapazität

Die Kapazität folgt dem freigegebenen Faktor aus F15: 25 m² ÷ 2,80 m² pro Person ≈ 8,93 — bequem sitzen also 8-9 Personen. Der Faktor folgt der Nutzungsdichte; Privatsphäre und Bewegungsfreiheit stehen vor Dichte. Die Aufteilung ist ideal für 8-9 Personen; größere Feste wechseln zu breiteren Modellen. Die endgültige Aufstellung wird beim Aufmaß mit dem Möbelplan geprüft.

## Montage

Das Modell wird auf Bestellung gefertigt. Das kostenlose Aufmaß prüft Untergrund, Zufahrt und Strombedarf und fixiert Maße und Montageplan in diesem Termin. Termine laufen Montag bis Samstag 09:00-18:00 ohne Kosten. Am Montagetag wird die Verankerung für Platte, verdichteten Boden oder Holzdeck gewählt. Material geht direkt vom Fahrzeug zum Aufbau; Verschnitt bleibt minimal. Nach Abschluss wird die Oberfläche gereinigt und das Datenblatt übergeben.

## Pflege

Pflege ist eine Oberflächenkontrolle pro Jahr plus Schutzschicht bei Bedarf. Pulverbeschichtung — Salzwasser- und UV-Beständigkeit. Rinnen und Drainageöffnungen werden von Laub befreit; das flaches Dach hält kein Stauwasser. Geländerbeschläge werden einmal jährlich nachgezogen. Die 5-Jahres-Garantie deckt Material- und Fertigungsfehler; Aufmaß und Montageplan bleiben auf der Garantiekarte.

## Warum dieses Modell?

Dieses Modell hält Massstab und Pflegeaufwand für Hotelgärten in Balance. Der Standardgrundriss macht das Angebot kalkulierbar; das Aufmaß fixiert die Maße vor der Fertigung, damit die Baustelle ohne Nacharbeit auskommt. Modern, weite Spanne fügt sich in die Gartenlinie ein, Flach, kontrollierte Entwässerung hält Regen fern, und die Oberfläche braucht im Jahr nur eine Sichtkontrolle. Die Preisliste folgt derselben Formel wie der Konfigurator: Fläche mal Grundpreis mal Material-, Form- und NutzungsFaktor — ohne versteckte Zuschläge. Fünf Jahre Garantie und kostenloses Aufmaß von Montag bis Samstag 09:00-18:00 senken das Projektrisiko. Wer mehr Platz braucht, greift zum größeren Modell derselben Baureihe.',
                    'seo_baslik' => 'ModernerAluminium--Pavillon 5x5 Preise 2026 | Kamelya',
                    'seo_aciklama' => 'ModernerAluminium--Pavillon 5x5: 25 m², für 8-9, flaches Dach, 5 Jahre Garantie. Transparente m²-Preise und kostenloses Aufmaß buchen. Details auf der',
                    'seo_anahtar_kelimeler' => 'modern aluminium Pavillon, aluminium Pavillon Preise, 5x5 Pavillon, Pavillon Hotel',
                    'cati_tipi_aciklama' => 'Das flache Dach trifft die modern Silhouette und die Gartenlinie auf derselben Ebene; Regenwasser läuft durch kontrollierte Kanäle zwischen den Paneln ab und tropft nie auf die Sitzfläche. Eine winzige, unsichtbare Neigung ist in die Form geplant, damit kein Wasser stehen bleibt und die Entwässerung garantiert ist. Das Windverhalten wird projektmäßig bewertet und beim Aufmaß an der Logik externer Fassadenelemente mit EN-13561-Bezug abgeglichen. Die Fläche lässt Platz für Leuchten oder Menüschilder. Zur Dachpflege genügt eine Sichtkontrolle pro Jahr. Die Oberfläche widersteht dem Klima; anders als Holzdächer braucht sie keine Imprägnierung. Kantenprofile führen das Wasser kontrolliert an der Fassade hinab und hinterlassen keine Flecken. Bei Bedarf wird der Entwässerungsplan mit dem Aufmaß-Team vor Ort besprochen. Die Ablauföffnung bleibt über dem Bodenniveau. Im Winter verteilt sich die Schneelast gleichmäßig auf der Fläche statt an einem Punkt. Kabelwege für Abendbeleuchtung laufen unter der Decke; die Tropfzone bleibt außen. Kalk und Stahlspäne spülen mit dem Schlauch ab; Chemie ist nicht nötig.',
                    'korkuluk_aciklama' => 'Das Aluminiumgeländer läuft an jeder Kante des Grundrisses in gleicher Höhe weiter und schützt die Sitzfläche vor Durchzug, während innen Privatsphäre bleibt. Die Höhe wird beim Aufmaß anhand von Sicherheitsnormen bestätigt; statt einer festen Katalogzahl wird das Maß gewählt, das das Handgelenk im Sitzen stützt. Verbindungen werden mit Kreuzverband verriegelt. Die Oberfläche erhält eine Schutzschicht, die die natürliche Struktur bewahrt. Die Füße werden angehoben, damit bodennahes Wasser nicht in die Pfosten steigt; alle Beschläge sind korrosionsfest. In Familienbereichen balancieren Vertikalabstände Kindersicherheit; die obere Griffleitung bleibt gerade. Wo ein Rollstuhlvorgang nötig ist, öffnet sich eine Ecke und lässt freie Passage. Zur Reinigung genügen weiche Bürste und lauwarmes Wasser; Füße einmal jährlich prüfen. Das Material wird im Sommer nicht heiß und im Winter nicht eiskalt. Auf Wunsch anmalbar; die Farbe wird beim Angebot festgelegt. Auf dem 5x5-Grundriss zieht es eine durchgehende Sicherheitslinie und fasst die Möbel von innen ein. Ein versteckter Kanal unter der Oberkante nimmt Lichtband oder Pflanzschiene auf, ohne sichtbare Schrauben.',
                ],
                'fr' => [
                    'baslik' => 'Gazebo Moderne Aluminium 5x5 Jardin d\'Hôtel',
                    'slug' => 'gazebo-moderne-aluminium-5x5-007',
                    'kisa_aciklama' => 'Gazebo moderne Aluminium de 5×5 m pour jardins d’hôtel : profil aluminium thermolaqué, toit plat à évacuation contrôlée et assise dégagée. 8-9 personnes sur 25 m² pour un usage toute saison. Dimensions et plan de pose fixés après l’étude gratuite (lun.-sam. 09h00-18h00). Garantie 5 ans incluse.',
                    'detayli_aciklama' => '**En bref**
- Sol moderne 5×5 m (25 m²) ; espace pour 8-9 personnes en jardins d’hôtel.
- profil aluminium thermolaqué, Plat, drainage contrôlé : usage toute saison, garantie 5 ans.
- Calcul m² transparent : 25 × 2,50 EUR × 2.5 × 1.5 × 1.6 = 375 EUR.
- Dimensions et plan de pose fixés pendant l’étude gratuite (lun.-sam. 09h00-18h00).

## Caractéristiques techniques

| Caractéristique | Valeur |
| --- | --- |
| Emprise au sol | 5×5 m — 25 m² |
| Forme | Moderne, portée ouverte |
| Matériau principal | profil aluminium thermolaqué |
| Toit | Plat, drainage contrôlé |
| Garde-corps | garde-corps en aluminium, hauteur définie par projet |
| Surface | Thermolaquage — résistance eau salée et UV |
| Fixations | Pièces métalliques résistantes à la corrosion |
| Garantie | 5 ans |

## Pour qui ?

Ce modèle est conçu pour les jardins d’hôtel et syndics qui planifient à l’échelle du jardin. Le sol moderne de 25 m² laisse le mobilier libre : une organisation 8-9 personnes tient sans effort ; table à manger et coin calme se partagent l’espace. La surface allège le nettoyage en haute saison. Les clients trouvent un point de rendez-vous ombragé ; le garde-corps en aluminium protège l’assise côté vent. Cours étroits et plans s’accordent à la mesure sans casser la ligne de paysage. Les projets de budget de quartier évitent les surprises de coût. Trois côtés ouverts cadrent la vue ; le quatrième reste au service ou au flux piéton.

## Capacité

La capacité suit le facteur approuvé en F15 : 25 m² ÷ 2,80 m² par personne ≈ 8,93 — soit confortablement 8-9 personnes. Le facteur suit l’intensité d’usage ; intimité et aisance passent avant la densité. L’organisation est idéale pour 8-9 personnes ; les réceptions plus larges passent à des modèles plus amples. Le placement final se vérifie pendant l’étude avec le plan de mobilier.

## Pose

Le gazebo est fabriqué sur commande. L’étude gratuite examine le sol, l’accès et le besoin électrique, et fixe dimensions et plan de pose. Les créneaux vont du lundi au samedi 09h00-18h00 sans frais. Le jour de pose, l’ancrage s’adapte à la dalle, au sol compacté ou au deck bois. Les matériaux passent du véhicule au chantier ; les chutes restent minimales. À la fin, la surface est nettoyée et la fiche d’usage remise.

## Entretien

L’entretien se limite à un contrôle de surface par un renouvellement protecteur si besoin. Thermolaquage — résistance eau salée et UV. Gouttières et orifices d’évacuation sont dégagés des feuilles ; le toit plat ne garde jamais d’eau stagnante. Les fixations du garde-corps se resserrent une fois par an. La garantie 5 ans couvre défauts de fabrication et de matière ; l’étude et le plan de pose restent sur la carte de garantie.

## Pourquoi ce modèle ?

Ce modèle équilibre échelle et entretien pour les jardins d’hôtel ; l’empreinte standard garde le devis prévisible et l’étude fixe les dimensions avant fabrication.',
                    'seo_baslik' => 'Gazebo Moderne Aluminium 5x5 Prix et Mesures 2026 | Kamelya',
                    'seo_aciklama' => 'Gazebo moderne aluminium 5x5 : 25 m², 8-9 places, toit plat, garantie 5 ans. Prix au m² transparents, réservation d’une étude gratuite. Détails sur la',
                    'seo_anahtar_kelimeler' => 'gazebo moderne aluminium, gazebo aluminium prix, gazebo 5x5, gazebo hôtel',
                    'cati_tipi_aciklama' => 'Le toit plat rejoint la silhouette moderne et la ligne du jardin sur le même plan ; l’eau s’évacue par des canaux contrôlés entre panneaux et ne tombe jamais sur l’assise. Une pente minuscule, invisible à l’œil, est prévue dans le moule pour qu’aucune flaque ne subsiste. Le comportement du vent s’évalue à l’échelle du projet et se fixe à l’étude selon la logique des éléments d’enveloppe référencés par l’EN 13561. La surface laisse la place aux luminaires ou enseignes. L’entretien demande un contrôle visuel par an. Le revêtement résiste au climat ; contrairement au bois, aucun traitement n’est nécessaire. Les profils de bord guident l’eau vers la façade sans tache. Si besoin, le plan d’évacuation se discute avec l’équipe d’étude. La sortie reste au-dessus du sol. En hiver la neige se répartit également sur la forme plane. Les câbles d’éclairage du soir passent sous le plafond ; la ligne de gouttes reste hors de l’assise. La poussière et le calcaire partent au tuyau en quelques minutes, sans produit chimique.',
                    'korkuluk_aciklama' => 'Le garde-corps en aluminium court à hauteur égale sur chaque arête, protège l’assise des traversants et garde l’intimité. Sa hauteur se confirme à l’étude selon les normes, préférant la mesure qui soutient le poignet au chiffre de catalogue. Les assemblages se verrouillent par croisements. La surface reçoit une finition protectrice qui conserve le grain. Les pieds sont surélevés pour que l’eau du sol ne remonte pas ; les pièces d’attache résistent à la corrosion. Dans les espaces familiaux les entraxes empêchent les mains de se coincer ; la ligne de saisie reste droite. Là où le fauteuil est requis, un côté s’ouvre. Nettoyage : brosse douce et eau tiède ; contrôle annuel des pieds. Le matériau ne chauffe ni ne givre. Peignable sur demande. Sur le plan 5x5 il trace une ligne continue et cadre le mobilier de l’intérieur. Un canal discret sous le rail supérieur accueille bandeau lumineux ou corniche à plantes, sans vis apparente.',
                ],
                'it' => [
                    'baslik' => 'Gazebo Moderno Alluminio 5x5 Giardino Hotel',
                    'slug' => 'gazebo-moderno-alluminio-5x5-007',
                    'kisa_aciklama' => 'Gazebo moderno in Alluminio da 5×5 m per giardini di hotel: profilo in alluminio verniciato a polvere, tetto piatto a scarico controllato. 8-9 persone su 25 m². Misure e piano di posa fissati dopo il sopralluogo gratuito (lun.-sab. 09:00-18:00). Garanzia 5 anni.',
                    'detayli_aciklama' => '**In breve**
- Pavimento moderno 5×5 m (25 m²) ; zona seduta per 8-9 persone in giardini di hotel.
- profilo in alluminio verniciato a polvere, Piatto, scarico controllato : uso tutto l’anno, garanzia 5 anni.
- Calcolo m² trasparente : 25 × 2,50 EUR × 2.5 × 1.5 × 1.6 = 375 EUR.
- Misure e piano di posa fissati durante il sopralluogo gratuito (lun.-sab. 09:00-18:00).

## Specifiche tecniche

| Voce | Valore |
| --- | --- |
| Impronta a terra | 5×5 m — 25 m² |
| Forma | Moderno, luce ampia |
| Materiale principale | profilo in alluminio verniciato a polvere |
| Tetto | Piatto, scarico controllato |
| Parapetto | parapetto in alluminio, altezza definita dal progetto |
| Superficie | Verniciatura a polvere — resistenza a sale e UV |
| Fissaggi | Parti metalliche resistenti alla corrosione |
| Garanzia | 5 anni |

## Per chi è?

Questo modello è pensato per giardini di hotel e amministratori che progettano alla scala del giardino. Il pavimento moderno da 25 m² lascia libero l’arredamento: una disposizione 8-9 persone entra con naturalezza; tavolo da pranzo e angolo relax condividono lo spazio. La superficie alleggerisce la pulizia in alta stagione. Gli ospiti trovano un punto d’incontro ombreggiato; il parapetto in alluminio ripara la seduta dal vento. Cortili stretti e piani si adattano alla misura senza spezzare la linea del paesaggio. I progetti con budget di quartiere evitano sorprese di costo. Tre lati aperti inquadrano la vista; il quarto resta a servizio o flusso pedonale.

## Capienza

La capienza segue il fattore approvato in F15: 25 m² ÷ 2,80 m² a persona ≈ 8,93 — quindi 8-9 persone comodamente. Il fattore segue l’intensità d’uso; privacy e libertà di movimento vengono prima della densità. La disposizione è ideale per 8-9 persone ; le feste più ampie passano a modelli più ampi. La posa finale si verifica al sopralluogo con il piano d’arredo.

## Posa

Il gazebo è prodotto su ordinazione. Il sopralluogo gratuito valuta terreno, accesso ed eventuale bisogno elettrico, e fissa misure e piano di posa. Gli appuntamenti vanno da lunedì a sabato 09:00-18:00 senza costi. Il giorno della posa si sceglie l’ancoraggio per la lastra, il terreno compatto o il deck in legno. I materiali passano dal veicolo al cantiere; gli scarti restano minimi. Al termine la superficie viene pulita e consegnata la scheda d’uso.

## Manutenzione

La manutenzione è un controllo di superficie all’anno e un rinnovo protettivo se serve. Verniciatura a polvere — resistenza a sale e UV. Gronde e bocchette si liberano dalle foglie; il tetto piatto non trattiene acqua. Le fissaggi del parapetto si stringono una volta l’anno. La garanzia 5 anni copre difetti di fabbricazione e materiale; sopralluogo e piano restano sulla garanzia.

## Perché questo modello?

Questo modello bilancia scala e manutenzione per giardini di hotel. L’impronta standard tiene il preventivo prevedibile e il sopralluogo fissa le misure prima della produzione, così il cantiere procede senza rifacimenti. Moderno, luce ampia si adatta alla linea del giardino, Piatto, scarico controllato allontana la pioggia dalla seduta, e la superficie richiede un solo controllo visivo all’anno. Il listino segue la stessa formula del configuratore: metri quadri per prezzo base per fattori di materiale, forma e uso — senza sorprese. Garanzia di 5 anni e sopralluogo gratuito da lunedì a sabato 09:00-18:00 riducono il rischio del progetto.',
                    'seo_baslik' => 'Gazebo Moderno Alluminio 5x5 Prezzi e Misure 2026 | Kamelya',
                    'seo_aciklama' => 'Gazebo moderno alluminio 5x5: 25 m², 8-9 posti, tetto piatto, garanzia 5 anni. Prezzi al m² trasparenti, sopralluogo gratuito su richiesta. Dettagli in',
                    'seo_anahtar_kelimeler' => 'gazebo moderno alluminio, gazebo alluminio prezzi, gazebo 5x5, gazebo hotel',
                    'cati_tipi_aciklama' => 'Il tetto piatto incontra la silhouette moderno e la linea del giardino sullo stesso piano; l’acqua piovana si drena attraverso canali controllati tra i pannelli e non gocciola mai sulla seduta. Una pendenza minuscola, invisibile, è prevista nello stampo perché non resti pozzanghera. Il comportamento al vento si valuta a scala di progetto e si definisce al sopralluogo con riferimento EN 13561. La superficie lascia spazio a luci o tabelloni. La manutenzione richiede un controllo visivo all’anno. Il rivestimento resiste al clima; a differenza del legno non serve trattamento. I profili di bordo guidano l’acqua verso la facciata senza macchie. Se serve, il piano di scarico si discute con l’equipaggio. La bocchetta resta sopra il suolo. D’inverno la neve si riparte sul piano. I cavi per l’illuminazione serale passano sotto il soffitto; la linea di gocce resta fuori. Polvere e calcare si sciolgono al tubo in pochi minuti, senza prodotti chimici.',
                    'korkuluk_aciklama' => 'Il parapetto in alluminio prosegue ad altezza uguale su ogni spigolo, ripara la seduta dai venti traversi e mantiene la riservatezza. L’altezza si conferma al sopralluogo secondo le norme, preferendo la misura che sorregge il polso a un numero fisso di catalogo. Le giunzioni si bloccano con incroci. La superficie riceve una finitura protettiva che conserva la venatura. I piedi sono sollevati perché l’acqua del terreno non risalga; le ferramenta sono a prova di corrosione. Negli spazi familiari gli interassi impediscono che mani restino incastrate; la linea di presa resta dritta. Dove serve la sedia a rotelle, un lato si apre. Pulizia : spazzola morbida e acqua tiepida ; controllo annuale dei piedi. Il materiale non scalda né gela. Colorabile a richiesta. Sul piano 5x5 disegna una linea continua e incornicia l’arredo dall’interno. Un canale discreto sotto il binario superiore ospita fascia luminosa o mensola per piante, senza viti a vista.',
                ],
                'ar' => [
                    'baslik' => 'كوش عصري ألمنيوم 5x5 لـحديقة فندق',
                    'slug' => 'modern-aluminium-kush-5x5-007',
                    'kisa_aciklama' => 'كوش عصري ألمنيوم بمقاس 5×5 متر لـحدائق الفنادق: بروفايل ألمنيوم مطلي بالبودرة، سقف مستوي بتصريف مضبوط ومساحة جلوس مريحة. يتسع لـ8-9 أشخاص على 25 م² لاستخدام على مدار السنة. تُثبَّت المقاسات وخطة التركيب بعد الاستشارة المجانية (الاثنين–السبت 09:00–18:00). ضمان 5 سنوات كامل.',
                    'detayli_aciklama' => '**باختصار**
- أرضية عصري 5×5 م (25 م²)؛ مساحة جلوس لـ8-9 أشخاص في حدائق الفنادق.
- بروفايل ألمنيوم مطلي بالبودرة، مستوي، تصريف مضبوط: استخدام على مدار السنة، ضمان 5 سنوات.
- حساب شفاف للمتر: 25 × 3 USD × 2.5 × 1.5 × 1.6 = 450 USD.
- تُثبَّت المقاسات وخطة التركيب في الاستشارة المجانية (الاثنين–السبت 09:00–18:00).

## المواصفات التقنية

| البند | القيمة |
| --- | --- |
| مقاس الأرضية | 5×5 م — 25 م² |
| الشكل | عصري، امتداد واسع |
| المادة الأساسية | بروفايل ألمنيوم مطلي بالبودرة |
| السقف | مستوي، تصريف مضبوط |
| السور | سور ألمنيوم، الارتفاع حسب المشروع |
| السطح | طلاء بودرة — مقاومة الماء المالح والأشعة |
| التثبيت | قطع معدنية مقاومة للصدأ |
| الضمان | 5 سنوات |

## لمن صُمم؟

هذا الموديل لـحدائق الفنادق وإدارات تخطط على مقياس الحديقة. الأرضية عصري بمساحة 25 م² تترك الأثاث حرًا: ترتيب 8-9 أشخاص يدخل بيسر؛ طاولة الطعام وركن الهدوء يتقاسمان المساحة. السطح يخفف التنظيف في الموسم الذروة. الزائدون يجدون نقطة لقاء مظلولة؛ وسور ألمنيوم يحمي منطقة الجلوس من جهة الرياح. الأفنية الضيقة والخطط تتلاءم مع المقاس دون كسر خط المشهد. المشاريع ذات ميزانية الحي تتجنّب مفاجآت التكلفة. ثلاثة وجوه مفتوحة تؤطر المشهد؛ والرابع يبقى للخدمة أو حركة المشاة.

## السعة

تُحسب السعة ومعامل المعتمد من F15: 25 م² ÷ 2.80 م² للشخص ≈ 8.93 — أي 8-9 أشخاص براحة. المعامل يتبع كثافة الاستخدام؛ الخصوصية وحرية الحركة تأتي قبل الازدحام. الترتيب مثالي لـ8-9 أشخاص؛ والتجمعات الأكبر تنتقل لمقاسات أوسع. يُتحقق من التوضع النهائي أثناء الاستشارة مع خطة الأثاث.

## التركيب

يُصنع الموديل عند الطلب. في الاستشارة المجانية يُفحص الأرضية واتجاه الدخول وحاجة الكهرباء، وتُثبَّت المقاسات وخطة التركيب في ذلك الموعد. المواعيد من الاثنين إلى السبت 09:00–18:00 بدون رسوم. يوم التركيب تُختار طريقة التثبيت حسب اللوحة أو التربة المضغوطة أو السطح الخشبي. تُنقل المواد مباشرة من السيارة؛ ويبقى الهدر ضئيلًا. بعد الانتهاء يُنظَّف السطح وتُسلَّم ورقة الاستخدام.

## الصيانة

الصيانة فحص سطحي سنوي وتجديد واقع عند الحاجة. طلاء بودرة — مقاومة الماء المالح والأشعة. تُنظَّف المرزبات وفتحات التصريف من الأوراق؛ وسقف مستوي لا يترك ماءً راكدًا. تُشدّ مثبتات السور مرة في السنة. ضمان 5 سنوات يشمل عيوب التصنيع والمواد؛ والاستشارة وخطة التركيب تبقى في بطاقة الضمان.

## لماذا هذا الموديل؟

يوازن هذا الموديل بين المقياس وجدول الصيانة لـحدائق الفنادق؛ المقاس القياسي يبقي عرض السعر قابلًا للتوقع، والاستشارة تثبت المقاسات قبل بدء التصنيع حتى لا يُعاد العمل في الموقع. عصري، امتداد واسع يلائم خط المشهد، ومستوي، تصريف مضبوط يبعد المطر عن منطقة الجلوس، والسطح لا يحتاج إلا فحصًا بصريًا واحدًا في السنة. قائمة الأسعار تتبع المعادلة نفسها في الأداة: متر مربع في سعر الأساس في عوامل المادة والشكل والاستخدام — دون رسوم خفية. خمس سنوات ضمان واستشارة مجانية من الاثنين إلى السبت 09:00–18:00 تخفض مخاطر المشروع. الفرق بين هذا الموديل والمقاس المجاور يظهر في جدول السعة لا في بنود مخفية. من يحتاج مساحة أكبر ينتقل إلى الموديل الأكبر من العائلة نفسها، ومن يملك أرضية أضيق يبدأ بالمربع الصغير ثم يرقّى لاحقًا دون تغيير لغة العرض.',
                    'seo_baslik' => 'كوش عصري ألمنيوم 5x5 أسعار 2026 | Kamelya',
                    'seo_aciklama' => 'كوش عصري ألمنيوم 5x5: 25 م²، 8-9 أشخاص، سقف مستوي، ضمان 5 سنوات. أسعار المتر وحجز استشارة.',
                    'seo_anahtar_kelimeler' => 'كوش عصري ألمنيوم, أسعار الكوش ألمنيوم, كوش 5x5, كوش الفنادق',
                    'cati_tipi_aciklama' => 'يلتقي السقف المستوي مع سيلويت عصري وخط الحديقة على المستوى نفسه؛ تُصرف مياه الأمطار عبر قنوات مضبوطة بين الألواح ولا تتقاطر أبدًا على منطقة الجلوس. يُخطط ميل صغير غير مرئي داخل القالب كي لا تتكوّن بركة ويكون التصريف مضمونًا. يُقيَّم سلوك الرياح على مستوى المشروع ويُحسم في الاستشارة وفق منطق عناصر الغلاف الخارجي المرجعية (EN 13561). تترك السطح مساحة للإضاءة أو لوحات القوائم. صيانة السقف فحص بصري سنوي. تكسية السطح تصمد أمام المناخ؛ بخلاف الخشب لا تحتاج معالجة. تقود الحواف المائية إلى الواجهة بشكل مضبوط دون أن تترك بقعًا. وعند الحاجة يُناقش خطة التصريف مع فريق الاستشارة في الموقع. تبقى فتحة التصريف فوق سطح الأرض. وفي الشتاء تتوزع حملة الثلج بالتساوي على السطح بدل أن تتركز في نقطة واحدة. مسارات كابلات الإضاءة المسائية تمر تحت السقف ويبقى خط التقطير خارج دائرة الجلوس. الغبار والترسّبات تُشطف بالخرطوم في دقائق دون مواد كيميائية. الحواف المعدنية تحمل شرائط تجميع مقاومة للصدأ ولا تترك صدأً على الأرضية.',
                    'korkuluk_aciklama' => 'يستمر سور ألمنيوم بارتفاع متساوٍ على كل حافة من حواف الخطة، يحمي منطقة الجلوس من الرياح المارّة مع إبقاء الخصوصية في الداخل. يُثبَّت الارتفاع في الاستشارة وفق معايير السلامة، مع تفضيل المقاس الذي يسند المعصم أثناء الجلوس على رقم كتالوج ثابت. تُقفل نقاط التقاء بتثبيتات متقاطعة تمنع الاهتزاز. يُجهَّز السطح بطبقة واقية تحافظ على نسيج المادة الطبيعي — ناعم عند اللمس دون شظايا. تُرفع القواعد كي لا يصعد ماء الأرض إلى الجذوع، وتُختار كل القطع المعدنية مقاومة للصدأ. في الأماكن العائلية تُضبط المسافات الرأسية بحيث لا تعلق اليد، ويبقى خط القبضة العلوي مستقيمًا. حيث يلزم مرور الكرسي المتحرك يُفتح جانب. التنظيف بفرشاة ناعمة ومياه فاترة؛ فحص القواعد سنويًا. لا يسخن الصيف ولا يُثلج الشتاء. يمكن طلبه باللون عند الطلب. وعلى خطة 5x5 يرسم خطًا أمنيًا متصلًا ويؤطر الأثاث من الداخل. قناة مخفية أسفل السطح العلوي تستقبل شريطًا ضوئيًا أو رف نباتات دون مسامير ظاهرة. تُراجع وصلات الربط مرة كل موسم مع فحص عام للهيكل كاملاً دون إهمال الزوايا.',
                ],
            ],
            'KML-KOM-MOD-008' => [
                'tr' => [
                    'baslik' => 'Modern Kompozit Kamelya 4x4 Restoran Bahçesi',
                    'slug' => 'modern-kompozit-kamelya-4x4-restoran-008',
                    'kisa_aciklama' => 'Modern kompozit kamelya, 4×4 metre tabanıyla Restoran Bahçesi için 8-9 kişilik dengeli bir oturma alanı sunar. Kompozit profil öne çıkar; düz çatı yağmuru kontrollü tahliye eder. Nem ve böceğe dirençli yüzey ile dört mevsim kullanıma uygundur. Net ölçü ve montaj planı ücretsiz keşifte belirlenir; 5 yıl garantilidir.',
                    'detayli_aciklama' => '**TL;DR**
- 4×4 m (16 m²) modern taban; Restoran Bahçesi için 8-9 kişilik ferah oturma alanı.
- kompozit profil, Düz, kontrollü tahliye: dört mevsim kullanım, 5 yıl garanti.
- Şeffaf m² hesabı: 16 × 12.000 × 1.8 (Kompozit) × 1.5 (modern) × 1.3 (restoran) = 673.920 TL.
- Net ölçü ve montaj planı ücretsiz keşifte sabitlenir (Pzt–Cmt 09:00–18:00).

## Teknik Özellikleri Nelerdir?

| Alan | Değer |
| --- | --- |
| Taban ölçüsü | 4×4 m — 16 m² |
| Form | Modern, geniş açıklık |
| Ana malzeme | kompozit profil |
| Çatı | Düz, kontrollü tahliye |
| Korkuluk | kompozit korkuluk, proje bazlı yükseklik |
| Yüzey işlemi | Nem ve böceğe dirençli yüzey |
| Bağlantı | Korozyona dayanıklı metal elemanlar |
| Garanti | 5 yıl |

## Kimler İçin?

Bu model, Restoran Bahçesi ölçeğinde çalışan işletmeler ve site yönetimleri için tasarlandı. 16 m² modern taban, mobilyayı serbestçe yerleştirir: 8-9 kişilik bir düzen rahatça kurulur; yemek masası ile dinlenme köşesi yan yana yerleşir.

## Kaç Kişiliktir?

Kapasite, F15 onaylı restoran katsayısıyla hesaplanır: 16 m² ÷ 1,80 m²/kişi ≈ 8,89 — yani 8-9 kişi rahat oturur. Bu katsayı, kullanım yoğunluğuna göre seçilir; mahremiyet ve kol hareketi serbestliği, sıkışıklıktan önce gelir.

## Montaj Nasıl Yapılır?

Ürün sipariş üzerine üretilir. Ücretsiz keşifte zemin, giriş yönü ve varsa elektrik ihtiyacı değerlendirilir; net ölçü ile montaj planı bu görüşmede sabitlenir. Keşif randevuları Pzt–Cmt 09:00–18:00 arasındadır ve ücretsizdir.

## Bakımı Nasıl Yapılır?

Bakım, yılda bir yüzey kontrolü ve gerekirse koruyucu yenilemeden ibarettir. Nem ve böceğe dirençli yüzey. Çatı olukları ve tahliye açıklıkları yapraktan temizlenir; düz çatı üzerinde su birikintisi bırakılmaz. Korkuluk bağlantıları yılda bir sıkıştırılır.

## Neden Bu Model?

Bu model, Restoran Bahçesi için doğru dengeyi kurar: 16 m² taban ne dar kalır ne de bakımı şişirir. Modern, geniş açıklık form, peyzaj çizgisine uyar; Düz, kontrollü tahliye yağmuru oturma alanından uzak tutar.

## Çatı Özellikleri Nelerdir?

Düz çatı, modern silueti ve bahçe çizgisiyle aynı düzlemde buluşur; yağmur suyu panel aralarındaki kontrollü kanallardan tahliye edilir ve oturma alanına damlamaz. kompozit profil üzerinde su birikintisi oluşmaması için minik bir eğim kalıp içinde planlanır; bu eğim gözle görünmez ama tahliyeyi garanti eder. Rüzgâr yükü davranışı, proje ölçeğinde değerlendirilir ve alan, EN 13561 referansıyla tartışılan dış cephe elemanları mantığına göre keşifte netleşir. Düz yüzey, aydınlatma veya menü panosu montajına alan bırakır. Çatı bakımına yılda bir göz kontrolü yeterlidir; tahliye delikleri açık tutulur, yüzey hortumla yıkanır. Nem ve böceğe dirençli yüzey; ahşap çatının tersine emprenye döngüsü gerekmez. Kenar profilleri, suyun cepheye kontrollü inmesini sağlar ve leke izi bırakmaz. Gerektiğinde tahliye planı, keşif ekibiyle araziye göre konuşulur. Oluk çıkışı zemin kotunun üstünde tutulur; yüzey kuru kalır, su kontrollü iner. Kış aylarında kar yükü, düz planda eşit dağılır; tek noktaya toplanmaz. Gece aydınlatması eklenirse kablo tavan altından gizlenir, damla noktası dışarıda kalır. Kireç ve toz birikintisi hortum suyuyla birkaç dakikada akar; kimyasal gerekmez.

## Korkuluk ve Güvenlik Özellikleri Nelerdir?

kompozit korkuluk, planın her kenarında eşit yükseklikte devam eder ve oturma alanını dışarıdan gelen rüzgâra karşı korurken içeride mahremiyet hissi bırakır. Yükseklik, güvenlik standartlarına ve proje koşullarına göre keşif sırasında netleşir; sabit bir katalog rakamı yerine, kullanıcının oturduğu anda bileğini destekleyen ölçü tercih edilir. Birleşim noktaları çapraz bağlantılarla kilitlenir, sallanma önlenir. Yüzey, malzemenin doğal dokusunu koruyan koruyucu kaplamayla hazırlanır; elle temas pürüzsüzdür, yonga bırakmaz. Korkuluk dip kısımları, zemine geçen suyu gövdeye taşımaması için yükseltilir ve bağlantı elemanları korozyona dayanıklı malzemeden seçilir. Çocuklu alanlarda dikey aralıklar, topun veya kolun sıkışmasını önleyecek şekilde düzenlenir; yaşlı sakinler için üst tutamak hattı düz ve kesintisizdir. Tekerlekli sandalye erişimi gereken giriş kenarında korkuluk açılıp geçiş bırakacak şekilde planlanır. Temizlikte yumuşak fırça ve ılık su yeterlidir; dip noktalar yılda bir kontrol edilir, gevşenen sıkma hemen yapılır. Malzeme, alternatife göre yazın ısınmaz, kışın soğuk tutmaz; dokusu bahçenin malzeme diliyle uyumludur. İstenirse boyanabilir; renk değişikliği teklif aşamasında belirlenir. Korkuluk, 4x4 planında kesintisiz bir güvenlik hattı çizer ve mobilya yerleşimini içeriden çerçeveler. Işık bandı veya saksı rafı istenirse üst profilin iç yüzüne gizli kanal açılır; görünür vida bırakılmaz.
',
                    'seo_baslik' => 'Modern Kompozit Kamelya 4x4 Fiyatları 2026 | Kamelya',
                    'seo_aciklama' => 'Modern kompozit kamelya 4x4: 16 m² alan, 8-9 kişilik restoran bahçesi, düz çatı, 5 yıl garantili. Şeffaf m² fiyat listesi ve ücretsiz keşif',
                    'seo_anahtar_kelimeler' => 'modern kompozit kamelya, kompozit kamelya fiyatları, 4x4 kamelya, restoran kamelyası',
                    'cati_tipi_aciklama' => 'Düz çatı, modern silueti ve bahçe çizgisiyle aynı düzlemde buluşur; yağmur suyu panel aralarındaki kontrollü kanallardan tahliye edilir ve oturma alanına damlamaz. kompozit profil üzerinde su birikintisi oluşmaması için minik bir eğim kalıp içinde planlanır; bu eğim gözle görünmez ama tahliyeyi garanti eder. Rüzgâr yükü davranışı, proje ölçeğinde değerlendirilir ve alan, EN 13561 referansıyla tartışılan dış cephe elemanları mantığına göre keşifte netleşir. Düz yüzey, aydınlatma veya menü panosu montajına alan bırakır. Çatı bakımına yılda bir göz kontrolü yeterlidir; tahliye delikleri açık tutulur, yüzey hortumla yıkanır. Nem ve böceğe dirençli yüzey; ahşap çatının tersine emprenye döngüsü gerekmez. Kenar profilleri, suyun cepheye kontrollü inmesini sağlar ve leke izi bırakmaz. Gerektiğinde tahliye planı, keşif ekibiyle araziye göre konuşulur. Oluk çıkışı zemin kotunun üstünde tutulur; yüzey kuru kalır, su kontrollü iner. Kış aylarında kar yükü, düz planda eşit dağılır; tek noktaya toplanmaz. Gece aydınlatması eklenirse kablo tavan altından gizlenir, damla noktası dışarıda kalır. Kireç ve toz birikintisi hortum suyuyla birkaç dakikada akar; kimyasal gerekmez.',
                    'korkuluk_aciklama' => 'kompozit korkuluk, planın her kenarında eşit yükseklikte devam eder ve oturma alanını dışarıdan gelen rüzgâra karşı korurken içeride mahremiyet hissi bırakır. Yükseklik, güvenlik standartlarına ve proje koşullarına göre keşif sırasında netleşir; sabit bir katalog rakamı yerine, kullanıcının oturduğu anda bileğini destekleyen ölçü tercih edilir. Birleşim noktaları çapraz bağlantılarla kilitlenir, sallanma önlenir. Yüzey, malzemenin doğal dokusunu koruyan koruyucu kaplamayla hazırlanır; elle temas pürüzsüzdür, yonga bırakmaz. Korkuluk dip kısımları, zemine geçen suyu gövdeye taşımaması için yükseltilir ve bağlantı elemanları korozyona dayanıklı malzemeden seçilir. Çocuklu alanlarda dikey aralıklar, topun veya kolun sıkışmasını önleyecek şekilde düzenlenir; yaşlı sakinler için üst tutamak hattı düz ve kesintisizdir. Tekerlekli sandalye erişimi gereken giriş kenarında korkuluk açılıp geçiş bırakacak şekilde planlanır. Temizlikte yumuşak fırça ve ılık su yeterlidir; dip noktalar yılda bir kontrol edilir, gevşenen sıkma hemen yapılır. Malzeme, alternatife göre yazın ısınmaz, kışın soğuk tutmaz; dokusu bahçenin malzeme diliyle uyumludur. İstenirse boyanabilir; renk değişikliği teklif aşamasında belirlenir. Korkuluk, 4x4 planında kesintisiz bir güvenlik hattı çizer ve mobilya yerleşimini içeriden çerçeveler. Işık bandı veya saksı rafı istenirse üst profilin iç yüzüne gizli kanal açılır; görünür vida bırakılmaz.',
                ],
                'en' => [
                    'baslik' => 'Modern Composite Gazebo 4x4 Restaurant Garden',
                    'slug' => 'modern-composite-gazebo-4x4-008',
                    'kisa_aciklama' => 'Modern composite gazebo, 4×4 footprint for restaurant gardens. Moisture- and insect-resistant composite and flat roof keep four-season use simple. Seats 8-9 across 16 m². Measurements and install plan lock after the free survey (Mon–Sat 09:00–18:00). Five-year warranty. Built to order for gardens and terraces that need reliable shade.',
                    'detayli_aciklama' => '**TL;DR**
- 4×4 m (16 m²) Modern, open span floor; an 8-9-person seating area for restaurant gardens.
- Moisture- and insect-resistant composite, Flat, controlled drainage: four-season use, five-year warranty.
- Transparent m² maths: 16 × 3 USD × 1.8 × 1.5 × 1.3 = 168.48 USD.
- Final measurements and the installation plan are fixed at the free survey (Mon-Sat 09:00-18:00).

## What Are the Technical Specifications?

| Item | Value |
| --- | --- |
| Floor size | 4×4 m — 16 m² |
| Shape | Modern, open span |
| Main material | composite |
| Roof | Flat, controlled drainage |
| Railing | composite railing, height set per project |
| Surface | Moisture- and insect-resistant composite |
| Warranty | 5 years |

## Who Is It For?

This model is built for restaurant operators and site teams that plan at garden scale.

## How Many People Does It Seat?

Seating follows the approved restaurant gardens ratio from F15: 16 m² ÷ 1.80 m² per person ≈ 8.89, so 8-9 people sit comfortably.

## How Is It Installed?

The product is made to order.

## How Is It Maintained?

Care is one surface check a year plus a protective refresh when needed.

## Why This Model?

This model balances scale and upkeep for restaurant gardens.

## What About the Roof?

The flat roof meets the modern silhouette and garden line on the same plane; rainwater drains through controlled channels between panels and never drips onto the seating zone. A tiny slope, invisible to the eye, is planned into the mould so drainage is guaranteed and no ponding forms. Wind behaviour is assessed at project scale and settled at the survey against external envelope logic referenced by EN 13561. The plane leaves room for lighting or menu-board mounts. Roof care needs one visual check a year — keep the holes open and rinse with a hose. Edge profiles guide water down the facade and leave no stain marks. Where needed, the drainage plan is discussed with the survey crew on site. The outlet stays above ground level so the surface remains dry. In winter the snow load spreads evenly across the plane instead of concentrating on one point. Cable runs for evening lighting hide under the ceiling; the drip line stays outside the seating circle. Dust and limescale rinse off with a hose; no chemicals are required.

## What About the Railing and Safety?

The composite railing runs at an even height along every edge of the plan, shielding the seating area from cross-wind while keeping privacy inside. Height is confirmed at the survey against safety standards, preferring the dimension that supports the wrist when seated over a fixed catalogue number. Joints lock with cross-braces so the frame does not sway. The surface keeps the material\'s natural grain — smooth to the touch, with no splinters. Railing feet are lifted so water never wicks into the posts; all fixings are chosen for corrosion resistance. Vertical gaps are spaced to prevent hands or balls from jamming, while the top grip line stays flat for elderly residents. Where wheelchair access is needed, one entrance edge opens and leaves a clear passage. Cleaning takes a soft brush and lukewarm water; check the feet yearly. The material doesn\'t overheat in summer or feel cold in winter, and its texture matches the garden\'s palette. The railing can be painted on request; colour is decided at quote stage. Along the 4×4 plan it draws an unbroken safety line. A hidden channel under the top rail accepts a light strip or planter rail without visible screws.
',
                    'seo_baslik' => 'Modern Composite Gazebo 4×4 Price List 2026 | Kamelya',
                    'seo_aciklama' => '16 m² Modern gazebo seats 8-9 with flat roof, five-year warranty. Transparent m² pricing and free on-site survey. Book a visit Monday–Saturday — free.',
                    'seo_anahtar_kelimeler' => 'modern composite gazebo, composite gazebo prices, 4x4 gazebo, restaurant gazebo',
                    'cati_tipi_aciklama' => 'The flat roof meets the modern silhouette and garden line on the same plane; rainwater drains through controlled channels between panels and never drips onto the seating zone. A tiny slope, invisible to the eye, is planned into the mould so drainage is guaranteed and no ponding forms. Wind behaviour is assessed at project scale and settled at the survey against external envelope logic referenced by EN 13561. The plane leaves room for lighting or menu-board mounts. Roof care needs one visual check a year — keep the holes open and rinse with a hose. Edge profiles guide water down the facade and leave no stain marks. Where needed, the drainage plan is discussed with the survey crew on site. The outlet stays above ground level so the surface remains dry. In winter the snow load spreads evenly across the plane instead of concentrating on one point. Cable runs for evening lighting hide under the ceiling; the drip line stays outside the seating circle. Dust and limescale rinse off with a hose; no chemicals are required.',
                    'korkuluk_aciklama' => 'The composite railing runs at an even height along every edge of the plan, shielding the seating area from cross-wind while keeping privacy inside. Height is confirmed at the survey against safety standards, preferring the dimension that supports the wrist when seated over a fixed catalogue number. Joints lock with cross-braces so the frame does not sway. The surface keeps the material\'s natural grain — smooth to the touch, with no splinters. Railing feet are lifted so water never wicks into the posts; all fixings are chosen for corrosion resistance. Vertical gaps are spaced to prevent hands or balls from jamming, while the top grip line stays flat for elderly residents. Where wheelchair access is needed, one entrance edge opens and leaves a clear passage. Cleaning takes a soft brush and lukewarm water; check the feet yearly. The material doesn\'t overheat in summer or feel cold in winter, and its texture matches the garden\'s palette. The railing can be painted on request; colour is decided at quote stage. Along the 4×4 plan it draws an unbroken safety line. A hidden channel under the top rail accepts a light strip or planter rail without visible screws.',
                ],
                'de' => [
                    'baslik' => 'ModernerKomposit--Pavillon 4x4 Restaurantgarten',
                    'slug' => 'moderner-komposit-pavillon-4x4-008',
                    'kisa_aciklama' => 'Moderner Komposit-Pavillon 4x4 für Restaurantgärten: Kompositprofil, flaches Dach mit kontrollierter Entwässerung und klarer Sitzordnung. Platz für 8-9 Personen auf 16 m². Maße und Montageplan werden nach dem kostenlosen Aufmaß fixiert (Mo-Sa 09:00-18:00). 5 Jahre Garantie inklusive.',
                    'detayli_aciklama' => '**Kurz gefasst**
- 4×4 m (16 m²) modern Fläche; 8-9-Personen-Sitzplatz für Restaurantgärten.
- Kompositprofil, Flach, kontrollierte Entwässerung: ganzjährige Nutzung, 5 Jahre Garantie.
- Transparente m²-Rechnung: 16 × 2,50 EUR × 1.8 × 1.5 × 1.3 = 140.4 EUR.
- Maße und Montageplan werden beim kostenlosen Aufmaß fixiert (Mo-Sa 09:00-18:00).

## Technische Daten

| Merkmal | Wert |
| --- | --- |
| Bodenmaß | 4×4 m — 16 m² |
| Form | Modern, weite Spanne |
| Hauptmaterial | Kompositprofil |
| Dach | Flach, kontrollierte Entwässerung |
| Geländer | Kompositgeländer, Höhe projektbezogen |
| Oberfläche | Feuchtigkeits- und insektenfeste Oberfläche |
| Befestigung | Korrosionsfeste Metallteile |
| Garantie | 5 Jahre |

## Für wen ist er geeignet?

Dieses Modell ist für Restaurantgärten und Hausverwaltungen gebaut, die im Gartenmaß planen. Der 16 m²-modern-Boden lässt Möbel frei stehen: eine 8-9-Personen-Aufteilung passt mühelos; Esstisch und ruhige Ecke teilen sich den Platz. Die Oberfläche spart Reinigung in der Hochsaison. Gäste finden einen übersichtlichen Treffpunkt; das Kompositgeländer schützt die Sitzfläche an der Windseite. Schmale Höfe und Pläne passen zum Maß, ohne die Landschaftslinie zu brechen. Projekte mit Quartiersbudget vermeiden Kostenüberraschungen durch den Standardgrundriss. Drei offene Seiten rahmen den Blick; die vierte bleibt Service oder Fußgverkehr vorbehalten.

## Kapazität

Die Kapazität folgt dem freigegebenen Faktor aus F15: 16 m² ÷ 1,80 m² pro Person ≈ 8,89 — bequem sitzen also 8-9 Personen. Der Faktor folgt der Nutzungsdichte; Privatsphäre und Bewegungsfreiheit stehen vor Dichte. Die Aufteilung ist ideal für 8-9 Personen; größere Feste wechseln zu breiteren Modellen. Die endgültige Aufstellung wird beim Aufmaß mit dem Möbelplan geprüft.

## Montage

Das Modell wird auf Bestellung gefertigt. Das kostenlose Aufmaß prüft Untergrund, Zufahrt und Strombedarf und fixiert Maße und Montageplan in diesem Termin. Termine laufen Montag bis Samstag 09:00-18:00 ohne Kosten. Am Montagetag wird die Verankerung für Platte, verdichteten Boden oder Holzdeck gewählt. Material geht direkt vom Fahrzeug zum Aufbau; Verschnitt bleibt minimal. Nach Abschluss wird die Oberfläche gereinigt und das Datenblatt übergeben.

## Pflege

Pflege ist eine Oberflächenkontrolle pro Jahr plus Schutzschicht bei Bedarf. Feuchtigkeits- und insektenfeste Oberfläche. Rinnen und Drainageöffnungen werden von Laub befreit; das flaches Dach hält kein Stauwasser. Geländerbeschläge werden einmal jährlich nachgezogen. Die 5-Jahres-Garantie deckt Material- und Fertigungsfehler; Aufmaß und Montageplan bleiben auf der Garantiekarte.

## Warum dieses Modell?

Dieses Modell hält Massstab und Pflegeaufwand für Restaurantgärten in Balance. Der Standardgrundriss macht das Angebot kalkulierbar; das Aufmaß fixiert die Maße vor der Fertigung, damit die Baustelle ohne Nacharbeit auskommt. Modern, weite Spanne fügt sich in die Gartenlinie ein, Flach, kontrollierte Entwässerung hält Regen fern, und die Oberfläche braucht im Jahr nur eine Sichtkontrolle. Die Preisliste folgt derselben Formel wie der Konfigurator: Fläche mal Grundpreis mal Material-, Form- und NutzungsFaktor — ohne versteckte Zuschläge. Fünf Jahre Garantie und kostenloses Aufmaß von Montag bis Samstag 09:00-18:00 senken das Projektrisiko. Wer mehr Platz braucht, greift zum größeren Modell derselben Baureihe.',
                    'seo_baslik' => 'ModernerKomposit--Pavillon 4x4 Preise 2026 | Kamelya',
                    'seo_aciklama' => 'ModernerKomposit--Pavillon 4x4: 16 m², für 8-9, flaches Dach, 5 Jahre Garantie. Transparente m²-Preise und kostenloses Aufmaß buchen. Details auf der Seite.',
                    'seo_anahtar_kelimeler' => 'modern komposit Pavillon, komposit Pavillon Preise, 4x4 Pavillon, Pavillon Restaurant',
                    'cati_tipi_aciklama' => 'Das flache Dach trifft die modern Silhouette und die Gartenlinie auf derselben Ebene; Regenwasser läuft durch kontrollierte Kanäle zwischen den Paneln ab und tropft nie auf die Sitzfläche. Eine winzige, unsichtbare Neigung ist in die Form geplant, damit kein Wasser stehen bleibt und die Entwässerung garantiert ist. Das Windverhalten wird projektmäßig bewertet und beim Aufmaß an der Logik externer Fassadenelemente mit EN-13561-Bezug abgeglichen. Die Fläche lässt Platz für Leuchten oder Menüschilder. Zur Dachpflege genügt eine Sichtkontrolle pro Jahr. Die Oberfläche widersteht dem Klima; anders als Holzdächer braucht sie keine Imprägnierung. Kantenprofile führen das Wasser kontrolliert an der Fassade hinab und hinterlassen keine Flecken. Bei Bedarf wird der Entwässerungsplan mit dem Aufmaß-Team vor Ort besprochen. Die Ablauföffnung bleibt über dem Bodenniveau. Im Winter verteilt sich die Schneelast gleichmäßig auf der Fläche statt an einem Punkt. Kabelwege für Abendbeleuchtung laufen unter der Decke; die Tropfzone bleibt außen. Kalk und Stahlspäne spülen mit dem Schlauch ab; Chemie ist nicht nötig.',
                    'korkuluk_aciklama' => 'Das Kompositgeländer läuft an jeder Kante des Grundrisses in gleicher Höhe weiter und schützt die Sitzfläche vor Durchzug, während innen Privatsphäre bleibt. Die Höhe wird beim Aufmaß anhand von Sicherheitsnormen bestätigt; statt einer festen Katalogzahl wird das Maß gewählt, das das Handgelenk im Sitzen stützt. Verbindungen werden mit Kreuzverband verriegelt. Die Oberfläche erhält eine Schutzschicht, die die natürliche Struktur bewahrt. Die Füße werden angehoben, damit bodennahes Wasser nicht in die Pfosten steigt; alle Beschläge sind korrosionsfest. In Familienbereichen balancieren Vertikalabstände Kindersicherheit; die obere Griffleitung bleibt gerade. Wo ein Rollstuhlvorgang nötig ist, öffnet sich eine Ecke und lässt freie Passage. Zur Reinigung genügen weiche Bürste und lauwarmes Wasser; Füße einmal jährlich prüfen. Das Material wird im Sommer nicht heiß und im Winter nicht eiskalt. Auf Wunsch anmalbar; die Farbe wird beim Angebot festgelegt. Auf dem 4x4-Grundriss zieht es eine durchgehende Sicherheitslinie und fasst die Möbel von innen ein. Ein versteckter Kanal unter der Oberkante nimmt Lichtband oder Pflanzschiene auf, ohne sichtbare Schrauben.',
                ],
                'fr' => [
                    'baslik' => 'Gazebo Moderne Composite 4x4 Jardin de Restaurant',
                    'slug' => 'gazebo-moderne-composite-4x4-008',
                    'kisa_aciklama' => 'Gazebo moderne Composite de 4×4 m pour jardins de restaurant : profil composite, toit plat à évacuation contrôlée et assise dégagée. 8-9 personnes sur 16 m² pour un usage toute saison. Dimensions et plan de pose fixés après l’étude gratuite (lun.-sam. 09h00-18h00). Garantie 5 ans incluse.',
                    'detayli_aciklama' => '**En bref**
- Sol moderne 4×4 m (16 m²) ; espace pour 8-9 personnes en jardins de restaurant.
- profil composite, Plat, drainage contrôlé : usage toute saison, garantie 5 ans.
- Calcul m² transparent : 16 × 2,50 EUR × 1.8 × 1.5 × 1.3 = 140.4 EUR.
- Dimensions et plan de pose fixés pendant l’étude gratuite (lun.-sam. 09h00-18h00).

## Caractéristiques techniques

| Caractéristique | Valeur |
| --- | --- |
| Emprise au sol | 4×4 m — 16 m² |
| Forme | Moderne, portée ouverte |
| Matériau principal | profil composite |
| Toit | Plat, drainage contrôlé |
| Garde-corps | garde-corps composite, hauteur définie par projet |
| Surface | Surface résistante à l’humidité et aux insectes |
| Fixations | Pièces métalliques résistantes à la corrosion |
| Garantie | 5 ans |

## Pour qui ?

Ce modèle est conçu pour les jardins de restaurant et syndics qui planifient à l’échelle du jardin. Le sol moderne de 16 m² laisse le mobilier libre : une organisation 8-9 personnes tient sans effort ; table à manger et coin calme se partagent l’espace. La surface allège le nettoyage en haute saison. Les clients trouvent un point de rendez-vous ombragé ; le garde-corps composite protège l’assise côté vent. Cours étroits et plans s’accordent à la mesure sans casser la ligne de paysage. Les projets de budget de quartier évitent les surprises de coût. Trois côtés ouverts cadrent la vue ; le quatrième reste au service ou au flux piéton.

## Capacité

La capacité suit le facteur approuvé en F15 : 16 m² ÷ 1,80 m² par personne ≈ 8,89 — soit confortablement 8-9 personnes. Le facteur suit l’intensité d’usage ; intimité et aisance passent avant la densité. L’organisation est idéale pour 8-9 personnes ; les réceptions plus larges passent à des modèles plus amples. Le placement final se vérifie pendant l’étude avec le plan de mobilier.

## Pose

Le gazebo est fabriqué sur commande. L’étude gratuite examine le sol, l’accès et le besoin électrique, et fixe dimensions et plan de pose. Les créneaux vont du lundi au samedi 09h00-18h00 sans frais. Le jour de pose, l’ancrage s’adapte à la dalle, au sol compacté ou au deck bois. Les matériaux passent du véhicule au chantier ; les chutes restent minimales. À la fin, la surface est nettoyée et la fiche d’usage remise.

## Entretien

L’entretien se limite à un contrôle de surface par un renouvellement protecteur si besoin. Surface résistante à l’humidité et aux insectes. Gouttières et orifices d’évacuation sont dégagés des feuilles ; le toit plat ne garde jamais d’eau stagnante. Les fixations du garde-corps se resserrent une fois par an. La garantie 5 ans couvre défauts de fabrication et de matière ; l’étude et le plan de pose restent sur la carte de garantie.

## Pourquoi ce modèle ?

Ce modèle équilibre échelle et entretien pour les jardins de restaurant ; l’empreinte standard garde le devis prévisible et l’étude fixe les dimensions avant fabrication.',
                    'seo_baslik' => 'Gazebo Moderne Composite 4x4 Prix et Mesures 2026 | Kamelya',
                    'seo_aciklama' => 'Gazebo moderne composite 4x4 : 16 m², 8-9 places, toit plat, garantie 5 ans. Prix au m² transparents, réservation d’une étude gratuite. Détails sur la',
                    'seo_anahtar_kelimeler' => 'gazebo moderne composite, gazebo composite prix, gazebo 4x4, gazebo restaurant',
                    'cati_tipi_aciklama' => 'Le toit plat rejoint la silhouette moderne et la ligne du jardin sur le même plan ; l’eau s’évacue par des canaux contrôlés entre panneaux et ne tombe jamais sur l’assise. Une pente minuscule, invisible à l’œil, est prévue dans le moule pour qu’aucune flaque ne subsiste. Le comportement du vent s’évalue à l’échelle du projet et se fixe à l’étude selon la logique des éléments d’enveloppe référencés par l’EN 13561. La surface laisse la place aux luminaires ou enseignes. L’entretien demande un contrôle visuel par an. Le revêtement résiste au climat ; contrairement au bois, aucun traitement n’est nécessaire. Les profils de bord guident l’eau vers la façade sans tache. Si besoin, le plan d’évacuation se discute avec l’équipe d’étude. La sortie reste au-dessus du sol. En hiver la neige se répartit également sur la forme plane. Les câbles d’éclairage du soir passent sous le plafond ; la ligne de gouttes reste hors de l’assise. La poussière et le calcaire partent au tuyau en quelques minutes, sans produit chimique.',
                    'korkuluk_aciklama' => 'Le garde-corps composite court à hauteur égale sur chaque arête, protège l’assise des traversants et garde l’intimité. Sa hauteur se confirme à l’étude selon les normes, préférant la mesure qui soutient le poignet au chiffre de catalogue. Les assemblages se verrouillent par croisements. La surface reçoit une finition protectrice qui conserve le grain. Les pieds sont surélevés pour que l’eau du sol ne remonte pas ; les pièces d’attache résistent à la corrosion. Dans les espaces familiaux les entraxes empêchent les mains de se coincer ; la ligne de saisie reste droite. Là où le fauteuil est requis, un côté s’ouvre. Nettoyage : brosse douce et eau tiède ; contrôle annuel des pieds. Le matériau ne chauffe ni ne givre. Peignable sur demande. Sur le plan 4x4 il trace une ligne continue et cadre le mobilier de l’intérieur. Un canal discret sous le rail supérieur accueille bandeau lumineux ou corniche à plantes, sans vis apparente.',
                ],
                'it' => [
                    'baslik' => 'Gazebo Moderno Composito 4x4 Giardino Ristorante',
                    'slug' => 'gazebo-moderno-composito-4x4-008',
                    'kisa_aciklama' => 'Gazebo moderno in Composito da 4×4 m per giardini di ristorante: profilo composito, tetto piatto a scarico controllato. 8-9 persone su 16 m². Misure e piano di posa fissati dopo il sopralluogo gratuito (lun.-sab. 09:00-18:00). Garanzia 5 anni.',
                    'detayli_aciklama' => '**In breve**
- Pavimento moderno 4×4 m (16 m²) ; zona seduta per 8-9 persone in giardini di ristorante.
- profilo composito, Piatto, scarico controllato : uso tutto l’anno, garanzia 5 anni.
- Calcolo m² trasparente : 16 × 2,50 EUR × 1.8 × 1.5 × 1.3 = 140.4 EUR.
- Misure e piano di posa fissati durante il sopralluogo gratuito (lun.-sab. 09:00-18:00).

## Specifiche tecniche

| Voce | Valore |
| --- | --- |
| Impronta a terra | 4×4 m — 16 m² |
| Forma | Moderno, luce ampia |
| Materiale principale | profilo composito |
| Tetto | Piatto, scarico controllato |
| Parapetto | parapetto composito, altezza definita dal progetto |
| Superficie | Superficie resistente a umidità e insetti |
| Fissaggi | Parti metalliche resistenti alla corrosione |
| Garanzia | 5 anni |

## Per chi è?

Questo modello è pensato per giardini di ristorante e amministratori che progettano alla scala del giardino. Il pavimento moderno da 16 m² lascia libero l’arredamento: una disposizione 8-9 persone entra con naturalezza; tavolo da pranzo e angolo relax condividono lo spazio. La superficie alleggerisce la pulizia in alta stagione. Gli ospiti trovano un punto d’incontro ombreggiato; il parapetto composito ripara la seduta dal vento. Cortili stretti e piani si adattano alla misura senza spezzare la linea del paesaggio. I progetti con budget di quartiere evitano sorprese di costo. Tre lati aperti inquadrano la vista; il quarto resta a servizio o flusso pedonale.

## Capienza

La capienza segue il fattore approvato in F15: 16 m² ÷ 1,80 m² a persona ≈ 8,89 — quindi 8-9 persone comodamente. Il fattore segue l’intensità d’uso; privacy e libertà di movimento vengono prima della densità. La disposizione è ideale per 8-9 persone ; le feste più ampie passano a modelli più ampi. La posa finale si verifica al sopralluogo con il piano d’arredo.

## Posa

Il gazebo è prodotto su ordinazione. Il sopralluogo gratuito valuta terreno, accesso ed eventuale bisogno elettrico, e fissa misure e piano di posa. Gli appuntamenti vanno da lunedì a sabato 09:00-18:00 senza costi. Il giorno della posa si sceglie l’ancoraggio per la lastra, il terreno compatto o il deck in legno. I materiali passano dal veicolo al cantiere; gli scarti restano minimi. Al termine la superficie viene pulita e consegnata la scheda d’uso.

## Manutenzione

La manutenzione è un controllo di superficie all’anno e un rinnovo protettivo se serve. Superficie resistente a umidità e insetti. Gronde e bocchette si liberano dalle foglie; il tetto piatto non trattiene acqua. Le fissaggi del parapetto si stringono una volta l’anno. La garanzia 5 anni copre difetti di fabbricazione e materiale; sopralluogo e piano restano sulla garanzia.

## Perché questo modello?

Questo modello bilancia scala e manutenzione per giardini di ristorante. L’impronta standard tiene il preventivo prevedibile e il sopralluogo fissa le misure prima della produzione, così il cantiere procede senza rifacimenti. Moderno, luce ampia si adatta alla linea del giardino, Piatto, scarico controllato allontana la pioggia dalla seduta, e la superficie richiede un solo controllo visivo all’anno. Il listino segue la stessa formula del configuratore: metri quadri per prezzo base per fattori di materiale, forma e uso — senza sorprese. Garanzia di 5 anni e sopralluogo gratuito da lunedì a sabato 09:00-18:00 riducono il rischio del progetto.',
                    'seo_baslik' => 'Gazebo Moderno Composito 4x4 Prezzi e Misure 2026 | Kamelya',
                    'seo_aciklama' => 'Gazebo moderno composito 4x4: 16 m², 8-9 posti, tetto piatto, garanzia 5 anni. Prezzi al m² trasparenti, sopralluogo gratuito su richiesta. Dettagli in',
                    'seo_anahtar_kelimeler' => 'gazebo moderno composito, gazebo composito prezzi, gazebo 4x4, gazebo ristorante',
                    'cati_tipi_aciklama' => 'Il tetto piatto incontra la silhouette moderno e la linea del giardino sullo stesso piano; l’acqua piovana si drena attraverso canali controllati tra i pannelli e non gocciola mai sulla seduta. Una pendenza minuscola, invisibile, è prevista nello stampo perché non resti pozzanghera. Il comportamento al vento si valuta a scala di progetto e si definisce al sopralluogo con riferimento EN 13561. La superficie lascia spazio a luci o tabelloni. La manutenzione richiede un controllo visivo all’anno. Il rivestimento resiste al clima; a differenza del legno non serve trattamento. I profili di bordo guidano l’acqua verso la facciata senza macchie. Se serve, il piano di scarico si discute con l’equipaggio. La bocchetta resta sopra il suolo. D’inverno la neve si riparte sul piano. I cavi per l’illuminazione serale passano sotto il soffitto; la linea di gocce resta fuori. Polvere e calcare si sciolgono al tubo in pochi minuti, senza prodotti chimici.',
                    'korkuluk_aciklama' => 'Il parapetto composito prosegue ad altezza uguale su ogni spigolo, ripara la seduta dai venti traversi e mantiene la riservatezza. L’altezza si conferma al sopralluogo secondo le norme, preferendo la misura che sorregge il polso a un numero fisso di catalogo. Le giunzioni si bloccano con incroci. La superficie riceve una finitura protettiva che conserva la venatura. I piedi sono sollevati perché l’acqua del terreno non risalga; le ferramenta sono a prova di corrosione. Negli spazi familiari gli interassi impediscono che mani restino incastrate; la linea di presa resta dritta. Dove serve la sedia a rotelle, un lato si apre. Pulizia : spazzola morbida e acqua tiepida ; controllo annuale dei piedi. Il materiale non scalda né gela. Colorabile a richiesta. Sul piano 4x4 disegna una linea continua e incornicia l’arredo dall’interno. Un canale discreto sotto il binario superiore ospita fascia luminosa o mensola per piante, senza viti a vista.',
                ],
                'ar' => [
                    'baslik' => 'كوش عصري كومبوزيت 4x4 لـحديقة مطعم',
                    'slug' => 'modern-composite-kush-4x4-008',
                    'kisa_aciklama' => 'كوش عصري كومبوزيت بمقاس 4×4 متر لـحدائق المطاعم: بروفايل كومبوزيت، سقف مستوي بتصريف مضبوط ومساحة جلوس مريحة. يتسع لـ8-9 أشخاص على 16 م² لاستخدام على مدار السنة. تُثبَّت المقاسات وخطة التركيب بعد الاستشارة المجانية (الاثنين–السبت 09:00–18:00). ضمان 5 سنوات كامل.',
                    'detayli_aciklama' => '**باختصار**
- أرضية عصري 4×4 م (16 م²)؛ مساحة جلوس لـ8-9 أشخاص في حدائق المطاعم.
- بروفايل كومبوزيت، مستوي، تصريف مضبوط: استخدام على مدار السنة، ضمان 5 سنوات.
- حساب شفاف للمتر: 16 × 3 USD × 1.8 × 1.5 × 1.3 = 168.48 USD.
- تُثبَّت المقاسات وخطة التركيب في الاستشارة المجانية (الاثنين–السبت 09:00–18:00).

## المواصفات التقنية

| البند | القيمة |
| --- | --- |
| مقاس الأرضية | 4×4 م — 16 م² |
| الشكل | عصري، امتداد واسع |
| المادة الأساسية | بروفايل كومبوزيت |
| السقف | مستوي، تصريف مضبوط |
| السور | سور كومبوزيت، الارتفاع حسب المشروع |
| السطح | سطح مقاوم للرطوبة والحشرات |
| التثبيت | قطع معدنية مقاومة للصدأ |
| الضمان | 5 سنوات |

## لمن صُمم؟

هذا الموديل لـحدائق المطاعم وإدارات تخطط على مقياس الحديقة. الأرضية عصري بمساحة 16 م² تترك الأثاث حرًا: ترتيب 8-9 أشخاص يدخل بيسر؛ طاولة الطعام وركن الهدوء يتقاسمان المساحة. السطح يخفف التنظيف في الموسم الذروة. الزائدون يجدون نقطة لقاء مظلولة؛ وسور كومبوزيت يحمي منطقة الجلوس من جهة الرياح. الأفنية الضيقة والخطط تتلاءم مع المقاس دون كسر خط المشهد. المشاريع ذات ميزانية الحي تتجنّب مفاجآت التكلفة. ثلاثة وجوه مفتوحة تؤطر المشهد؛ والرابع يبقى للخدمة أو حركة المشاة.

## السعة

تُحسب السعة ومعامل المعتمد من F15: 16 م² ÷ 1.80 م² للشخص ≈ 8.89 — أي 8-9 أشخاص براحة. المعامل يتبع كثافة الاستخدام؛ الخصوصية وحرية الحركة تأتي قبل الازدحام. الترتيب مثالي لـ8-9 أشخاص؛ والتجمعات الأكبر تنتقل لمقاسات أوسع. يُتحقق من التوضع النهائي أثناء الاستشارة مع خطة الأثاث.

## التركيب

يُصنع الموديل عند الطلب. في الاستشارة المجانية يُفحص الأرضية واتجاه الدخول وحاجة الكهرباء، وتُثبَّت المقاسات وخطة التركيب في ذلك الموعد. المواعيد من الاثنين إلى السبت 09:00–18:00 بدون رسوم. يوم التركيب تُختار طريقة التثبيت حسب اللوحة أو التربة المضغوطة أو السطح الخشبي. تُنقل المواد مباشرة من السيارة؛ ويبقى الهدر ضئيلًا. بعد الانتهاء يُنظَّف السطح وتُسلَّم ورقة الاستخدام.

## الصيانة

الصيانة فحص سطحي سنوي وتجديد واقع عند الحاجة. سطح مقاوم للرطوبة والحشرات. تُنظَّف المرزبات وفتحات التصريف من الأوراق؛ وسقف مستوي لا يترك ماءً راكدًا. تُشدّ مثبتات السور مرة في السنة. ضمان 5 سنوات يشمل عيوب التصنيع والمواد؛ والاستشارة وخطة التركيب تبقى في بطاقة الضمان.

## لماذا هذا الموديل؟

يوازن هذا الموديل بين المقياس وجدول الصيانة لـحدائق المطاعم؛ المقاس القياسي يبقي عرض السعر قابلًا للتوقع، والاستشارة تثبت المقاسات قبل بدء التصنيع حتى لا يُعاد العمل في الموقع. عصري، امتداد واسع يلائم خط المشهد، ومستوي، تصريف مضبوط يبعد المطر عن منطقة الجلوس، والسطح لا يحتاج إلا فحصًا بصريًا واحدًا في السنة. قائمة الأسعار تتبع المعادلة نفسها في الأداة: متر مربع في سعر الأساس في عوامل المادة والشكل والاستخدام — دون رسوم خفية. خمس سنوات ضمان واستشارة مجانية من الاثنين إلى السبت 09:00–18:00 تخفض مخاطر المشروع. الفرق بين هذا الموديل والمقاس المجاور يظهر في جدول السعة لا في بنود مخفية. من يحتاج مساحة أكبر ينتقل إلى الموديل الأكبر من العائلة نفسها، ومن يملك أرضية أضيق يبدأ بالمربع الصغير ثم يرقّى لاحقًا دون تغيير لغة العرض.',
                    'seo_baslik' => 'كوش عصري كومبوزيت 4x4 أسعار 2026',
                    'seo_aciklama' => 'كوش عصري كومبوزيت 4x4: 16 م²، 8-9 أشخاص، سقف مستوي، ضمان 5 سنوات. أسعار المتر وحجز استشارة.',
                    'seo_anahtar_kelimeler' => 'كوش عصري كومبوزيت, أسعار الكوش كومبوزيت, كوش 4x4, كوش المطاعم',
                    'cati_tipi_aciklama' => 'يلتقي السقف المستوي مع سيلويت عصري وخط الحديقة على المستوى نفسه؛ تُصرف مياه الأمطار عبر قنوات مضبوطة بين الألواح ولا تتقاطر أبدًا على منطقة الجلوس. يُخطط ميل صغير غير مرئي داخل القالب كي لا تتكوّن بركة ويكون التصريف مضمونًا. يُقيَّم سلوك الرياح على مستوى المشروع ويُحسم في الاستشارة وفق منطق عناصر الغلاف الخارجي المرجعية (EN 13561). تترك السطح مساحة للإضاءة أو لوحات القوائم. صيانة السقف فحص بصري سنوي. تكسية السطح تصمد أمام المناخ؛ بخلاف الخشب لا تحتاج معالجة. تقود الحواف المائية إلى الواجهة بشكل مضبوط دون أن تترك بقعًا. وعند الحاجة يُناقش خطة التصريف مع فريق الاستشارة في الموقع. تبقى فتحة التصريف فوق سطح الأرض. وفي الشتاء تتوزع حملة الثلج بالتساوي على السطح بدل أن تتركز في نقطة واحدة. مسارات كابلات الإضاءة المسائية تمر تحت السقف ويبقى خط التقطير خارج دائرة الجلوس. الغبار والترسّبات تُشطف بالخرطوم في دقائق دون مواد كيميائية. الحواف المعدنية تحمل شرائط تجميع مقاومة للصدأ ولا تترك صدأً على الأرضية.',
                    'korkuluk_aciklama' => 'يستمر سور كومبوزيت بارتفاع متساوٍ على كل حافة من حواف الخطة، يحمي منطقة الجلوس من الرياح المارّة مع إبقاء الخصوصية في الداخل. يُثبَّت الارتفاع في الاستشارة وفق معايير السلامة، مع تفضيل المقاس الذي يسند المعصم أثناء الجلوس على رقم كتالوج ثابت. تُقفل نقاط التقاء بتثبيتات متقاطعة تمنع الاهتزاز. يُجهَّز السطح بطبقة واقية تحافظ على نسيج المادة الطبيعي — ناعم عند اللمس دون شظايا. تُرفع القواعد كي لا يصعد ماء الأرض إلى الجذوع، وتُختار كل القطع المعدنية مقاومة للصدأ. في الأماكن العائلية تُضبط المسافات الرأسية بحيث لا تعلق اليد، ويبقى خط القبضة العلوي مستقيمًا. حيث يلزم مرور الكرسي المتحرك يُفتح جانب. التنظيف بفرشاة ناعمة ومياه فاترة؛ فحص القواعد سنويًا. لا يسخن الصيف ولا يُثلج الشتاء. يمكن طلبه باللون عند الطلب. وعلى خطة 4x4 يرسم خطًا أمنيًا متصلًا ويؤطر الأثاث من الداخل. قناة مخفية أسفل السطح العلوي تستقبل شريطًا ضوئيًا أو رف نباتات دون مسامير ظاهرة. تُراجع وصلات الربط مرة كل موسم مع فحص عام للهيكل كاملاً دون إهمال الزوايا.',
                ],
            ],
            'KML-AHS-KLA-009' => [
                'tr' => [
                    'baslik' => 'Klasik Ahşap Kamelya 5x5 Site Bahçesi Mod',
                    'slug' => 'klasik-ahsap-kamelya-5x5-site-bahcesi-009',
                    'kisa_aciklama' => 'Klasik ahşap kamelya, 5×5 metre tabanıyla Site Bahçesi için 7-8 kişilik dengeli bir oturma alanı sunar. Kaliteli çam kereste öne çıkar; eğimli çatı yağmuru kontrollü tahliye eder. Emprenye — çürüme ve böceğe karşı ile dört mevsim kullanıma uygundur. Net ölçü ve montaj planı ücretsiz keşifte belirlenir; 5 yıl garantilidir.',
                    'detayli_aciklama' => '**TL;DR**
- 5×5 m (25 m²) klasik taban; Site Bahçesi için 7-8 kişilik ferah oturma alanı.
- kaliteli çam kereste, Eğimli, oluklu drenaj: dört mevsim kullanım, 5 yıl garanti.
- Şeffaf m² hesabı: 25 × 12.000 × 1 (Ahşap) × 1 (klasik) × 1 (site) = 300.000 TL.
- Net ölçü ve montaj planı ücretsiz keşifte sabitlenir (Pzt–Cmt 09:00–18:00).

## Teknik Özellikleri Nelerdir?

| Alan | Değer |
| --- | --- |
| Taban ölçüsü | 5×5 m — 25 m² |
| Form | Klasik, dört köşe |
| Ana malzeme | kaliteli çam kereste |
| Çatı | Eğimli, oluklu drenaj |
| Korkuluk | ahşap korkuluk, proje bazlı yükseklik |
| Yüzey işlemi | Emprenye — çürüme ve böceğe karşı |
| Bağlantı | Korozyona dayanıklı metal elemanlar |
| Garanti | 5 yıl |

## Kimler İçin?

Bu model, Site Bahçesi ölçeğinde çalışan işletmeler ve site yönetimleri için tasarlandı. 25 m² klasik taban, mobilyayı serbestçe yerleştirir: 7-8 kişilik bir düzen rahatça kurulur; yemek masası ile dinlenme köşesi.

## Kaç Kişiliktir?

Kapasite, F15 onaylı site bahçesi katsayısıyla hesaplanır: 25 m² ÷ 3,50 m²/kişi ≈ 7,14 — yani 7-8 kişi rahat oturur. Bu katsayı, kullanım yoğunluğuna göre seçilir; mahremiyet ve kol hareketi.

## Montaj Nasıl Yapılır?

Ürün sipariş üzerine üretilir. Ücretsiz keşifte zemin, giriş yönü ve varsa elektrik ihtiyacı değerlendirilir; net ölçü ile montaj planı bu görüşmede sabitlenir. Keşif randevuları Pzt–Cmt 09:00–18:00 arasındadır ve ücretsizdir.

## Bakımı Nasıl Yapılır?

Bakım, yılda bir yüzey kontrolü ve gerekirse koruyucu yenilemeden ibarettir. Emprenye — çürüme ve böceğe karşı. Çatı olukları ve tahliye açıklıkları yapraktan temizlenir; eğimli çatı üzerinde su birikintisi bırakılmaz.

## Neden Bu Model?

Bu model, Site Bahçesi için doğru dengeyi kurar: 25 m² taban ne dar kalır ne de bakımı şişirir. Klasik, dört köşe form, peyzaj çizgisine uyar; Eğimli, oluklu drenaj yağmuru oturma.

## Çatı Özellikleri Nelerdir?

Eğimli çatı, 5x5 tabanın taşıyıcılarıyla merkeze doğru hafif eğimlenir; böylece yağmur ve kar suyu dört cepheden eşit şekilde tahliye olur, tabanda su birikmez. Eğim, karın tek bir yüke toplanmasını da engeller. Oluk detayı, kaliteli çam kerestenin diline uygun bir çözümdür: su, oluğa yönlendirilip yapıdan uzaklaştırılır; damlayan su ahşap korkuluke ve oturma alanına isabet etmez. Çatı kaplaması, malzemeyle uyumlu yüzey olarak üretilir; metal ile ahşap çarpışmaz. Rüzgâr yükü davranışı, proje ölçeğinde değerlendirilir ve alan, EN 13561 referansıyla tartışılan dış cephe elemanları mantığına göre keşifte netleşir. Formda çatı dıştan bakıldığında da bütünlüklüdür: her cephe aynı eğimi taşır, asimetri girmez. Drenaj yönü, girişin tersine kurulur; oturma alanı her zaman kuru tarafta kalır. Çatı bakımına yılda bir yüzey kontrolü yeterlidir; oluk içindeki yaprak temizlenir, yüzey kuru tutulur. Yağmur direnci, eğim ve yüzey işçiliği birlikte çalışır; su yüzeyde durmak yerine akar. Oluk çıkışı zemin kotunun üstünde tutulur, su kontrollü iner. Gerektiğinde eğim açısı araziye göre keşif ekibiyle konuşulur. Kışın kar, eğim sayesinde zemine yumuşak iner; buz kütlesi oluğa dolmaz. Elektrik hattı gerekiyorsa tavandan değil, taşıyıcı kolondan beslenir.

## Korkuluk ve Güvenlik Özellikleri Nelerdir?

ahşap korkuluk, planın her kenarında eşit yükseklikte devam eder ve oturma alanını dışarıdan gelen rüzgâra karşı korurken içeride mahremiyet hissi bırakır. Yükseklik, güvenlik standartlarına ve proje koşullarına göre keşif sırasında netleşir; sabit bir katalog rakamı yerine, kullanıcının oturduğu anda bileğini destekleyen ölçü tercih edilir. Birleşim noktaları çapraz bağlantılarla kilitlenir, sallanma önlenir. Yüzey, malzemenin doğal dokusunu koruyan koruyucu kaplamayla hazırlanır; elle temas pürüzsüzdür, yonga bırakmaz. Korkuluk dip kısımları, zemine geçen suyu gövdeye taşımaması için yükseltilir ve bağlantı elemanları korozyona dayanıklı malzemeden seçilir. Çocuklu alanlarda dikey aralıklar, topun veya kolun sıkışmasını önleyecek şekilde düzenlenir; yaşlı sakinler için üst tutamak hattı düz ve kesintisizdir. Tekerlekli sandalye erişimi gereken giriş kenarında korkuluk açılıp geçiş bırakacak şekilde planlanır. Temizlikte yumuşak fırça ve ılık su yeterlidir; dip noktalar yılda bir kontrol edilir, gevşenen sıkma hemen yapılır. Malzeme, alternatife göre yazın ısınmaz, kışın soğuk tutmaz; dokusu bahçenin malzeme diliyle uyumludur. İstenirse boyanabilir; renk değişikliği teklif aşamasında belirlenir. Korkuluk, 5x5 planında kesintisiz bir güvenlik hattı çizer ve mobilya yerleşimini içeriden çerçeveler. Işık bandı veya saksı rafı istenirse üst profilin iç yüzüne gizli kanal açılır; görünür vida bırakılmaz.
',
                    'seo_baslik' => 'Klasik Ahşap Kamelya 5x5 Fiyatları 2026 | Kamelya',
                    'seo_aciklama' => 'Klasik ahşap kamelya 5x5: 25 m² alan, 7-8 kişilik site bahçesi, eğimli çatı, 5 yıl garantili. Şeffaf m² fiyat listesi ve ücretsiz keşif randevusu.',
                    'seo_anahtar_kelimeler' => 'klasik ahşap kamelya, ahşap kamelya fiyatları, 5x5 kamelya, site bahçesi kamelyası',
                    'cati_tipi_aciklama' => 'Eğimli çatı, 5x5 tabanın taşıyıcılarıyla merkeze doğru hafif eğimlenir; böylece yağmur ve kar suyu dört cepheden eşit şekilde tahliye olur, tabanda su birikmez. Eğim, karın tek bir yüke toplanmasını da engeller. Oluk detayı, kaliteli çam kerestenin diline uygun bir çözümdür: su, oluğa yönlendirilip yapıdan uzaklaştırılır; damlayan su ahşap korkuluke ve oturma alanına isabet etmez. Çatı kaplaması, malzemeyle uyumlu yüzey olarak üretilir; metal ile ahşap çarpışmaz. Rüzgâr yükü davranışı, proje ölçeğinde değerlendirilir ve alan, EN 13561 referansıyla tartışılan dış cephe elemanları mantığına göre keşifte netleşir. Formda çatı dıştan bakıldığında da bütünlüklüdür: her cephe aynı eğimi taşır, asimetri girmez. Drenaj yönü, girişin tersine kurulur; oturma alanı her zaman kuru tarafta kalır. Çatı bakımına yılda bir yüzey kontrolü yeterlidir; oluk içindeki yaprak temizlenir, yüzey kuru tutulur. Yağmur direnci, eğim ve yüzey işçiliği birlikte çalışır; su yüzeyde durmak yerine akar. Oluk çıkışı zemin kotunun üstünde tutulur, su kontrollü iner. Gerektiğinde eğim açısı araziye göre keşif ekibiyle konuşulur. Kışın kar, eğim sayesinde zemine yumuşak iner; buz kütlesi oluğa dolmaz. Elektrik hattı gerekiyorsa tavandan değil, taşıyıcı kolondan beslenir.',
                    'korkuluk_aciklama' => 'ahşap korkuluk, planın her kenarında eşit yükseklikte devam eder ve oturma alanını dışarıdan gelen rüzgâra karşı korurken içeride mahremiyet hissi bırakır. Yükseklik, güvenlik standartlarına ve proje koşullarına göre keşif sırasında netleşir; sabit bir katalog rakamı yerine, kullanıcının oturduğu anda bileğini destekleyen ölçü tercih edilir. Birleşim noktaları çapraz bağlantılarla kilitlenir, sallanma önlenir. Yüzey, malzemenin doğal dokusunu koruyan koruyucu kaplamayla hazırlanır; elle temas pürüzsüzdür, yonga bırakmaz. Korkuluk dip kısımları, zemine geçen suyu gövdeye taşımaması için yükseltilir ve bağlantı elemanları korozyona dayanıklı malzemeden seçilir. Çocuklu alanlarda dikey aralıklar, topun veya kolun sıkışmasını önleyecek şekilde düzenlenir; yaşlı sakinler için üst tutamak hattı düz ve kesintisizdir. Tekerlekli sandalye erişimi gereken giriş kenarında korkuluk açılıp geçiş bırakacak şekilde planlanır. Temizlikte yumuşak fırça ve ılık su yeterlidir; dip noktalar yılda bir kontrol edilir, gevşenen sıkma hemen yapılır. Malzeme, alternatife göre yazın ısınmaz, kışın soğuk tutmaz; dokusu bahçenin malzeme diliyle uyumludur. İstenirse boyanabilir; renk değişikliği teklif aşamasında belirlenir. Korkuluk, 5x5 planında kesintisiz bir güvenlik hattı çizer ve mobilya yerleşimini içeriden çerçeveler. Işık bandı veya saksı rafı istenirse üst profilin iç yüzüne gizli kanal açılır; görünür vida bırakılmaz.',
                ],
                'en' => [
                    'baslik' => 'Classic Wood Gazebo 5x5 Residential Complex',
                    'slug' => 'classic-wood-gazebo-5x5-009',
                    'kisa_aciklama' => 'Classic timber gazebo, 5×5 footprint for residential complex gardens. Impregnated pine against rot and insects and sloped roof keep four-season use simple. Seats 7 across 25 m². Measurements and install plan lock after the free survey (Mon–Sat 09:00–18:00). Five-year warranty.',
                    'detayli_aciklama' => '**TL;DR**
- 5×5 m (25 m²) Classic, pitched silhouette floor; an 7-person seating area for residential complex gardens.
- Impregnated pine against rot and insects, Sloped, timber-guttered drainage: four-season use, five-year warranty.
- Transparent m² maths: 25 × 3 USD × 1.0 × 1.0 × 1.0 = 75.00 USD.
- Final measurements and the installation plan are fixed at the free survey (Mon-Sat 09:00-18:00).

## What Are the Technical Specifications?

| Item | Value |
| --- | --- |
| Floor size | 5×5 m — 25 m² |
| Shape | Classic, pitched silhouette |
| Main material | timber |
| Roof | Sloped, timber-guttered drainage |
| Railing | timber railing, height set per project |
| Surface | Impregnated pine against rot and insects |
| Warranty | 5 years |

## Who Is It For?

This model is built for residential operators and site teams that plan at garden scale.

## How Many People Does It Seat?

Seating follows the approved residential complex gardens ratio from F15: 25 m² ÷ 3.50 m² per person ≈ 7.14, so 7 people sit comfortably.

## How Is It Installed?

The product is made to order.

## How Is It Maintained?

Care is one surface check a year plus a protective refresh when needed.

## Why This Model?

This model balances scale and upkeep for residential complex gardens.

## What About the Roof?

The sloped roof eases toward its centre on the load-bearing edges of the 5×5 floor, so rain and snow drain evenly and nothing ponds below. The pitch also stops snow from becoming a single load. Gutters suit the timber language of the structure: water is routed into the channel and carried away, so drips never land on the railing or seating zone. Cladding matches the main material and avoids a clash in the silhouette. Wind behaviour is assessed at project scale and settled at the survey against external envelope elements referenced by EN 13561. From outside the roof reads as one piece — every elevation carries the same pitch, with no asymmetry. Drainage faces away from the entrance so the seating side stays dry. Maintenance needs one surface check a year; clear leaves and keep the surface dry. Rain resistance works through pitch plus joinery: water runs off instead of standing. The outlet stays above ground level. Where terrain differs, the slope angle is discussed with the survey crew on site.

## What About the Railing and Safety?

The timber railing runs at an even height along every edge of the plan, shielding the seating area from cross-wind while keeping privacy inside. Height is confirmed at the survey against safety standards, preferring the dimension that supports the wrist when seated over a fixed catalogue number. Joints lock with cross-braces so the frame does not sway. The surface keeps the material\'s natural grain — smooth to the touch, with no splinters. Railing feet are lifted so water never wicks into the posts; all fixings are chosen for corrosion resistance. Vertical gaps are spaced to prevent hands or balls from jamming, while the top grip line stays flat for elderly residents. Where wheelchair access is needed, one entrance edge opens and leaves a clear passage. Cleaning takes a soft brush and lukewarm water; check the feet yearly. The material doesn\'t overheat in summer or feel cold in winter, and its texture matches the garden\'s palette. The railing can be painted on request; colour is decided at quote stage. Along the 5×5 plan it draws an unbroken safety line. A hidden channel under the top rail accepts a light strip or planter rail without visible screws.
',
                    'seo_baslik' => 'Classic timber Gazebo 5×5 Prices and Sizes 2026 | Kamelya',
                    'seo_aciklama' => '25 m² Classic gazebo seats 7 with sloped roof, five-year warranty. Transparent m² pricing and free on-site survey. Book a visit Monday–Saturday — free.',
                    'seo_anahtar_kelimeler' => 'classic wood gazebo, wood gazebo prices, 5x5 gazebo, residential gazebo',
                    'cati_tipi_aciklama' => 'The sloped roof eases toward its centre on the load-bearing edges of the 5×5 floor, so rain and snow drain evenly and nothing ponds below. The pitch also stops snow from becoming a single load. Gutters suit the timber language of the structure: water is routed into the channel and carried away, so drips never land on the railing or seating zone. Cladding matches the main material and avoids a clash in the silhouette. Wind behaviour is assessed at project scale and settled at the survey against external envelope elements referenced by EN 13561. From outside the roof reads as one piece — every elevation carries the same pitch, with no asymmetry. Drainage faces away from the entrance so the seating side stays dry. Maintenance needs one surface check a year; clear leaves and keep the surface dry. Rain resistance works through pitch plus joinery: water runs off instead of standing. The outlet stays above ground level. Where terrain differs, the slope angle is discussed with the survey crew on site.',
                    'korkuluk_aciklama' => 'The timber railing runs at an even height along every edge of the plan, shielding the seating area from cross-wind while keeping privacy inside. Height is confirmed at the survey against safety standards, preferring the dimension that supports the wrist when seated over a fixed catalogue number. Joints lock with cross-braces so the frame does not sway. The surface keeps the material\'s natural grain — smooth to the touch, with no splinters. Railing feet are lifted so water never wicks into the posts; all fixings are chosen for corrosion resistance. Vertical gaps are spaced to prevent hands or balls from jamming, while the top grip line stays flat for elderly residents. Where wheelchair access is needed, one entrance edge opens and leaves a clear passage. Cleaning takes a soft brush and lukewarm water; check the feet yearly. The material doesn\'t overheat in summer or feel cold in winter, and its texture matches the garden\'s palette. The railing can be painted on request; colour is decided at quote stage. Along the 5×5 plan it draws an unbroken safety line. A hidden channel under the top rail accepts a light strip or planter rail without visible screws.',
                ],
                'de' => [
                    'baslik' => 'KlassischerHolz--Pavillon 5x5 Wohnanlage',
                    'slug' => 'klassischer-holz-pavillon-5x5-009',
                    'kisa_aciklama' => 'Klassischer Holz-Pavillon 5x5 für Wohnanlagen: Qualitäts-Kiefernholz, geneigtes Dach mit kontrollierter Entwässerung und klarer Sitzordnung. Platz für 7-8 Personen auf 25 m². Maße und Montageplan werden nach dem kostenlosen Aufmaß fixiert (Mo-Sa 09:00-18:00). 5 Jahre Garantie inklusive.',
                    'detayli_aciklama' => '**Kurz gefasst**
- 5×5 m (25 m²) klassisch Fläche; 7-8-Personen-Sitzplatz für Wohnanlagen.
- Qualitäts-Kiefernholz, Geneigt, Rinne: ganzjährige Nutzung, 5 Jahre Garantie.
- Transparente m²-Rechnung: 25 × 2,50 EUR × 1 × 1 × 1 = 62.5 EUR.
- Maße und Montageplan werden beim kostenlosen Aufmaß fixiert (Mo-Sa 09:00-18:00).

## Technische Daten

| Merkmal | Wert |
| --- | --- |
| Bodenmaß | 5×5 m — 25 m² |
| Form | Klassisch, vier Ecken |
| Hauptmaterial | Qualitäts-Kiefernholz |
| Dach | Geneigt, Rinne |
| Geländer | Holzgeländer, Höhe projektbezogen |
| Oberfläche | Imprägniert gegen Fäulnis und Insekten |
| Befestigung | Korrosionsfeste Metallteile |
| Garantie | 5 Jahre |

## Für wen ist er geeignet?

Dieses Modell ist für Wohnanlagen und Hausverwaltungen gebaut, die im Gartenmaß planen. Der 25 m²-klassisch-Boden lässt Möbel frei stehen: eine 7-8-Personen-Aufteilung passt mühelos; Esstisch und ruhige Ecke teilen sich den Platz. Die Oberfläche spart Reinigung in der Hochsaison. Gäste finden einen übersichtlichen Treffpunkt; das Holzgeländer schützt die Sitzfläche an der Windseite. Schmale Höfe und Pläne passen zum Maß, ohne die Landschaftslinie zu brechen. Projekte mit Quartiersbudget vermeiden Kostenüberraschungen durch den Standardgrundriss. Drei offene Seiten rahmen den Blick; die vierte bleibt Service oder Fußgverkehr vorbehalten.

## Kapazität

Die Kapazität folgt dem freigegebenen Faktor aus F15: 25 m² ÷ 3,50 m² pro Person ≈ 7,14 — bequem sitzen also 7-8 Personen. Der Faktor folgt der Nutzungsdichte; Privatsphäre und Bewegungsfreiheit stehen vor Dichte. Die Aufteilung ist ideal für 7-8 Personen; größere Feste wechseln zu breiteren Modellen. Die endgültige Aufstellung wird beim Aufmaß mit dem Möbelplan geprüft.

## Montage

Das Modell wird auf Bestellung gefertigt. Das kostenlose Aufmaß prüft Untergrund, Zufahrt und Strombedarf und fixiert Maße und Montageplan in diesem Termin. Termine laufen Montag bis Samstag 09:00-18:00 ohne Kosten. Am Montagetag wird die Verankerung für Platte, verdichteten Boden oder Holzdeck gewählt. Material geht direkt vom Fahrzeug zum Aufbau; Verschnitt bleibt minimal. Nach Abschluss wird die Oberfläche gereinigt und das Datenblatt übergeben.

## Pflege

Pflege ist eine Oberflächenkontrolle pro Jahr plus Schutzschicht bei Bedarf. Imprägniert gegen Fäulnis und Insekten. Rinnen und Drainageöffnungen werden von Laub befreit; das geneigtes Dach hält kein Stauwasser. Geländerbeschläge werden einmal jährlich nachgezogen. Die 5-Jahres-Garantie deckt Material- und Fertigungsfehler; Aufmaß und Montageplan bleiben auf der Garantiekarte.

## Warum dieses Modell?

Dieses Modell hält Massstab und Pflegeaufwand für Wohnanlagen in Balance. Der Standardgrundriss macht das Angebot kalkulierbar; das Aufmaß fixiert die Maße vor der Fertigung, damit die Baustelle ohne Nacharbeit auskommt. Klassisch, vier Ecken fügt sich in die Gartenlinie ein, Geneigt, Rinne hält Regen fern, und die Oberfläche braucht im Jahr nur eine Sichtkontrolle. Die Preisliste folgt derselben Formel wie der Konfigurator: Fläche mal Grundpreis mal Material-, Form- und NutzungsFaktor — ohne versteckte Zuschläge. Fünf Jahre Garantie und kostenloses Aufmaß von Montag bis Samstag 09:00-18:00 senken das Projektrisiko. Wer mehr Platz braucht, greift zum größeren Modell derselben Baureihe.',
                    'seo_baslik' => 'KlassischerHolz--Pavillon 5x5 Preise 2026 | Kamelya',
                    'seo_aciklama' => 'KlassischerHolz--Pavillon 5x5: 25 m², für 7-8, geneigtes Dach, 5 Jahre Garantie. Transparente m²-Preise und kostenloses Aufmaß buchen. Details auf der',
                    'seo_anahtar_kelimeler' => 'klassisch holz Pavillon, holz Pavillon Preise, 5x5 Pavillon, Pavillon Wohnanlage',
                    'cati_tipi_aciklama' => 'Das geneigte Dach neigt sich über die tragenden Kanten des 5x5-Bodens sanft zur Mitte; Regen- und Schneewasser läuft gleichmäßig ab und bleibt nicht auf dem Boden. Die Neigung verhindert, dass Schnee zu einer Einzellast wird. Die Rinne passt zur Materialsprache: Wasser wird in den Kanal geführt und vom Rahmen weggeleitet, Tropfen treffen weder Holzgeländer noch Sitzfläche. Die Deckung entsteht materialgleich — kein Kontrast in der Silhouette. Das Windverhalten wird projektmäßig bewertet und beim Aufmaß an der Logik externer Fassadenelemente mit EN-13561-Bezug abgeglichen. Von außen wirkt das Dach als ein Stück ohne Asymmetrie. Die Entwässerung zeigt weg vom Eingang. Zur Pflege genügt eine Oberflächenkontrolle pro Jahr. Der Regenwiderstand entsteht aus Neigung und Verarbeitung. Die Ablauföffnung bleibt über dem Boden. Bei abweichendem Gelände wird der Neigungswinkel mit dem Aufmaß-Team besprochen. Im Winter gleitet Schnee weich zum Boden und verstopft die Rinne nicht. Strom für Lampen läuft an der Strebe, nicht unter der Dachhaut.',
                    'korkuluk_aciklama' => 'Das Holzgeländer läuft an jeder Kante des Grundrisses in gleicher Höhe weiter und schützt die Sitzfläche vor Durchzug, während innen Privatsphäre bleibt. Die Höhe wird beim Aufmaß anhand von Sicherheitsnormen bestätigt; statt einer festen Katalogzahl wird das Maß gewählt, das das Handgelenk im Sitzen stützt. Verbindungen werden mit Kreuzverband verriegelt. Die Oberfläche erhält eine Schutzschicht, die die natürliche Struktur bewahrt. Die Füße werden angehoben, damit bodennahes Wasser nicht in die Pfosten steigt; alle Beschläge sind korrosionsfest. In Familienbereichen balancieren Vertikalabstände Kindersicherheit; die obere Griffleitung bleibt gerade. Wo ein Rollstuhlvorgang nötig ist, öffnet sich eine Ecke und lässt freie Passage. Zur Reinigung genügen weiche Bürste und lauwarmes Wasser; Füße einmal jährlich prüfen. Das Material wird im Sommer nicht heiß und im Winter nicht eiskalt. Auf Wunsch anmalbar; die Farbe wird beim Angebot festgelegt. Auf dem 5x5-Grundriss zieht es eine durchgehende Sicherheitslinie und fasst die Möbel von innen ein. Ein versteckter Kanal unter der Oberkante nimmt Lichtband oder Pflanzschiene auf, ohne sichtbare Schrauben.',
                ],
                'fr' => [
                    'baslik' => 'Gazebo Classique Bois 5x5 Copropriété Modèle',
                    'slug' => 'gazebo-classique-bois-5x5-009',
                    'kisa_aciklama' => 'Gazebo classique Bois de 5×5 m pour copropriétés : pin de qualité, toit incliné à évacuation contrôlée et assise dégagée. 7-8 personnes sur 25 m² pour un usage toute saison. Dimensions et plan de pose fixés après l’étude gratuite (lun.-sam. 09h00-18h00). Garantie 5 ans incluse.',
                    'detayli_aciklama' => '**En bref**
- Sol classique 5×5 m (25 m²) ; espace pour 7-8 personnes en copropriétés.
- pin de qualité, Incliné, gouttière : usage toute saison, garantie 5 ans.
- Calcul m² transparent : 25 × 2,50 EUR × 1 × 1 × 1 = 62.5 EUR.
- Dimensions et plan de pose fixés pendant l’étude gratuite (lun.-sam. 09h00-18h00).

## Caractéristiques techniques

| Caractéristique | Valeur |
| --- | --- |
| Emprise au sol | 5×5 m — 25 m² |
| Forme | Classique, quatre angles |
| Matériau principal | pin de qualité |
| Toit | Incliné, gouttière |
| Garde-corps | garde-corps en bois, hauteur définie par projet |
| Surface | Traité contre pourriture et insectes |
| Fixations | Pièces métalliques résistantes à la corrosion |
| Garantie | 5 ans |

## Pour qui ?

Ce modèle est conçu pour les copropriétés et syndics qui planifient à l’échelle du jardin. Le sol classique de 25 m² laisse le mobilier libre : une organisation 7-8 personnes tient sans effort ; table à manger et coin calme se partagent l’espace. La surface allège le nettoyage en haute saison. Les clients trouvent un point de rendez-vous ombragé ; le garde-corps en bois protège l’assise côté vent. Cours étroits et plans s’accordent à la mesure sans casser la ligne de paysage. Les projets de budget de quartier évitent les surprises de coût. Trois côtés ouverts cadrent la vue ; le quatrième reste au service ou au flux piéton.

## Capacité

La capacité suit le facteur approuvé en F15 : 25 m² ÷ 3,50 m² par personne ≈ 7,14 — soit confortablement 7-8 personnes. Le facteur suit l’intensité d’usage ; intimité et aisance passent avant la densité. L’organisation est idéale pour 7-8 personnes ; les réceptions plus larges passent à des modèles plus amples. Le placement final se vérifie pendant l’étude avec le plan de mobilier.

## Pose

Le gazebo est fabriqué sur commande. L’étude gratuite examine le sol, l’accès et le besoin électrique, et fixe dimensions et plan de pose. Les créneaux vont du lundi au samedi 09h00-18h00 sans frais. Le jour de pose, l’ancrage s’adapte à la dalle, au sol compacté ou au deck bois. Les matériaux passent du véhicule au chantier ; les chutes restent minimales. À la fin, la surface est nettoyée et la fiche d’usage remise.

## Entretien

L’entretien se limite à un contrôle de surface par un renouvellement protecteur si besoin. Traité contre pourriture et insectes. Gouttières et orifices d’évacuation sont dégagés des feuilles ; le toit incliné ne garde jamais d’eau stagnante. Les fixations du garde-corps se resserrent une fois par an. La garantie 5 ans couvre défauts de fabrication et de matière ; l’étude et le plan de pose restent sur la carte de garantie.

## Pourquoi ce modèle ?

Ce modèle équilibre échelle et entretien pour les copropriétés ; l’empreinte standard garde le devis prévisible et l’étude fixe les dimensions avant fabrication.',
                    'seo_baslik' => 'Gazebo Classique Bois 5x5 Prix et Mesures 2026 | Kamelya',
                    'seo_aciklama' => 'Gazebo classique bois 5x5 : 25 m², 7-8 places, toit incliné, garantie 5 ans. Prix au m² transparents, réservation d’une étude gratuite. Détails sur la',
                    'seo_anahtar_kelimeler' => 'gazebo classique bois, gazebo bois prix, gazebo 5x5, gazebo copropriété',
                    'cati_tipi_aciklama' => 'Le toit incliné s’incline doucement vers son centre sur les porteurs du sol 5x5 ; pluie et neige évacuent sans stagnation. La pente empêche la neige de concentrer une charge unique. La gouttière suit le langage des matériaux : l’eau est dirigée dans le canal, les gouttes n’atteignent ni garde-corps en bois ni l’assise. La couverture est assortie au matériau principal. Le vent s’évalue selon l’EN 13561 à l’étude. Dehors le toit se lit comme une pièce continue sans asymétrie. L’évacuation regarde l’entrée. Un contrôle de surface par an suffit. L’eau coule au lieu de rester. La sortie reste au-dessus du sol. Si le terrain diffère, la pente se discute sur place. En hiver la neige glisse doucement au sol et ne bouche jamais le canal. Le courant des lampes suit un montant, pas la toiture. Les fixations du bac se resserrent avec la grondaire lors du contrôle annuel de la structure entière.',
                    'korkuluk_aciklama' => 'Le garde-corps en bois court à hauteur égale sur chaque arête, protège l’assise des traversants et garde l’intimité. Sa hauteur se confirme à l’étude selon les normes, préférant la mesure qui soutient le poignet au chiffre de catalogue. Les assemblages se verrouillent par croisements. La surface reçoit une finition protectrice qui conserve le grain. Les pieds sont surélevés pour que l’eau du sol ne remonte pas ; les pièces d’attache résistent à la corrosion. Dans les espaces familiaux les entraxes empêchent les mains de se coincer ; la ligne de saisie reste droite. Là où le fauteuil est requis, un côté s’ouvre. Nettoyage : brosse douce et eau tiède ; contrôle annuel des pieds. Le matériau ne chauffe ni ne givre. Peignable sur demande. Sur le plan 5x5 il trace une ligne continue et cadre le mobilier de l’intérieur. Un canal discret sous le rail supérieur accueille bandeau lumineux ou corniche à plantes, sans vis apparente.',
                ],
                'it' => [
                    'baslik' => 'Gazebo Classico Legno 5x5 Condominio Modello',
                    'slug' => 'gazebo-classico-legno-5x5-009',
                    'kisa_aciklama' => 'Gazebo classico in Legno da 5×5 m per condomini: pino di qualità, tetto inclinato a scarico controllato. 7-8 persone su 25 m². Misure e piano di posa fissati dopo il sopralluogo gratuito (lun.-sab. 09:00-18:00). Garanzia 5 anni.',
                    'detayli_aciklama' => '**In breve**
- Pavimento classico 5×5 m (25 m²) ; zona seduta per 7-8 persone in condomini.
- pino di qualità, Inclinato, gronda : uso tutto l’anno, garanzia 5 anni.
- Calcolo m² trasparente : 25 × 2,50 EUR × 1 × 1 × 1 = 62.5 EUR.
- Misure e piano di posa fissati durante il sopralluogo gratuito (lun.-sab. 09:00-18:00).

## Specifiche tecniche

| Voce | Valore |
| --- | --- |
| Impronta a terra | 5×5 m — 25 m² |
| Forma | Classico, quattro angoli |
| Materiale principale | pino di qualità |
| Tetto | Inclinato, gronda |
| Parapetto | parapetto in legno, altezza definita dal progetto |
| Superficie | Trattato contro putridume e insetti |
| Fissaggi | Parti metalliche resistenti alla corrosione |
| Garanzia | 5 anni |

## Per chi è?

Questo modello è pensato per condomini e amministratori che progettano alla scala del giardino. Il pavimento classico da 25 m² lascia libero l’arredamento: una disposizione 7-8 persone entra con naturalezza; tavolo da pranzo e angolo relax condividono lo spazio. La superficie alleggerisce la pulizia in alta stagione. Gli ospiti trovano un punto d’incontro ombreggiato; il parapetto in legno ripara la seduta dal vento. Cortili stretti e piani si adattano alla misura senza spezzare la linea del paesaggio. I progetti con budget di quartiere evitano sorprese di costo. Tre lati aperti inquadrano la vista; il quarto resta a servizio o flusso pedonale.

## Capienza

La capienza segue il fattore approvato in F15: 25 m² ÷ 3,50 m² a persona ≈ 7,14 — quindi 7-8 persone comodamente. Il fattore segue l’intensità d’uso; privacy e libertà di movimento vengono prima della densità. La disposizione è ideale per 7-8 persone ; le feste più ampie passano a modelli più ampi. La posa finale si verifica al sopralluogo con il piano d’arredo.

## Posa

Il gazebo è prodotto su ordinazione. Il sopralluogo gratuito valuta terreno, accesso ed eventuale bisogno elettrico, e fissa misure e piano di posa. Gli appuntamenti vanno da lunedì a sabato 09:00-18:00 senza costi. Il giorno della posa si sceglie l’ancoraggio per la lastra, il terreno compatto o il deck in legno. I materiali passano dal veicolo al cantiere; gli scarti restano minimi. Al termine la superficie viene pulita e consegnata la scheda d’uso.

## Manutenzione

La manutenzione è un controllo di superficie all’anno e un rinnovo protettivo se serve. Trattato contro putridume e insetti. Gronde e bocchette si liberano dalle foglie; il tetto inclinato non trattiene acqua. Le fissaggi del parapetto si stringono una volta l’anno. La garanzia 5 anni copre difetti di fabbricazione e materiale; sopralluogo e piano restano sulla garanzia.

## Perché questo modello?

Questo modello bilancia scala e manutenzione per condomini. L’impronta standard tiene il preventivo prevedibile e il sopralluogo fissa le misure prima della produzione, così il cantiere procede senza rifacimenti. Classico, quattro angoli si adatta alla linea del giardino, Inclinato, gronda allontana la pioggia dalla seduta, e la superficie richiede un solo controllo visivo all’anno. Il listino segue la stessa formula del configuratore: metri quadri per prezzo base per fattori di materiale, forma e uso — senza sorprese. Garanzia di 5 anni e sopralluogo gratuito da lunedì a sabato 09:00-18:00 riducono il rischio del progetto.',
                    'seo_baslik' => 'Gazebo Classico Legno 5x5 Prezzi e Misure 2026 | Kamelya',
                    'seo_aciklama' => 'Gazebo classico legno 5x5: 25 m², 7-8 posti, tetto inclinato, garanzia 5 anni. Prezzi al m² trasparenti, sopralluogo gratuito su richiesta. Dettagli in',
                    'seo_anahtar_kelimeler' => 'gazebo classico legno, gazebo legno prezzi, gazebo 5x5, gazebo condominio',
                    'cati_tipi_aciklama' => 'Il tetto inclinato si inclina dolcemente verso il centro sui portanti del pavimento 5x5 ; pioggia e neve scaricano senza ristagni. La pendenza impedisce alla neve di concentrarsi in un carico unico. La gronda segue il linguaggio dei materiali : l’acqua va nel canale, le gocce non colpiscono né parapetto in legno né la seduta. La copertura è abbinata al materiale principale. Il vento si valuta con EN 13561 al sopralluogo. D’esterno il tetto si legge come pezzo continuo senza asimmetrie. Lo scarico guarda l’ingresso. Un controllo di superficie all’anno basta. L’acqua scorre invece di restare. La bocchetta resta sopra il suolo. Se il terreno differisce, la pendenza si discute in loco. D’inverno la neve scivola piano a terra e non ostruisce mai il canale. La corrente delle lampade segue un montante, non il sottotetto. Le fissaggi del bracciolo si stringono con la gronda nel controllo annuale della struttura intera.',
                    'korkuluk_aciklama' => 'Il parapetto in legno prosegue ad altezza uguale su ogni spigolo, ripara la seduta dai venti traversi e mantiene la riservatezza. L’altezza si conferma al sopralluogo secondo le norme, preferendo la misura che sorregge il polso a un numero fisso di catalogo. Le giunzioni si bloccano con incroci. La superficie riceve una finitura protettiva che conserva la venatura. I piedi sono sollevati perché l’acqua del terreno non risalga; le ferramenta sono a prova di corrosione. Negli spazi familiari gli interassi impediscono che mani restino incastrate; la linea di presa resta dritta. Dove serve la sedia a rotelle, un lato si apre. Pulizia : spazzola morbida e acqua tiepida ; controllo annuale dei piedi. Il materiale non scalda né gela. Colorabile a richiesta. Sul piano 5x5 disegna una linea continua e incornicia l’arredo dall’interno. Un canale discreto sotto il binario superiore ospita fascia luminosa o mensola per piante, senza viti a vista.',
                ],
                'ar' => [
                    'baslik' => 'كوش كلاسيكي خشبي 5x5 لـمجمع سكني',
                    'slug' => 'classic-wood-kush-5x5-009',
                    'kisa_aciklama' => 'كوش كلاسيكي خشبي بمقاس 5×5 متر لـالمجمعات السكنية: خشب صنوبر عالي الجودة، سقف مائل بتصريف مضبوط ومساحة جلوس مريحة. يتسع لـ7-8 أشخاص على 25 م² لاستخدام على مدار السنة. تُثبَّت المقاسات وخطة التركيب بعد الاستشارة المجانية (الاثنين–السبت 09:00–18:00). ضمان 5 سنوات كامل.',
                    'detayli_aciklama' => '**باختصار**
- أرضية كلاسيكي 5×5 م (25 م²)؛ مساحة جلوس لـ7-8 أشخاص في المجمعات السكنية.
- خشب صنوبر عالي الجودة، مائل، مرزة: استخدام على مدار السنة، ضمان 5 سنوات.
- حساب شفاف للمتر: 25 × 3 USD × 1 × 1 × 1 = 75 USD.
- تُثبَّت المقاسات وخطة التركيب في الاستشارة المجانية (الاثنين–السبت 09:00–18:00).

## المواصفات التقنية

| البند | القيمة |
| --- | --- |
| مقاس الأرضية | 5×5 م — 25 م² |
| الشكل | كلاسيكي، أربعة زوايا |
| المادة الأساسية | خشب صنوبر عالي الجودة |
| السقف | مائل، مرزة |
| السور | سور خشبي، الارتفاع حسب المشروع |
| السطح | معالج ضد التحلل والحشرات |
| التثبيت | قطع معدنية مقاومة للصدأ |
| الضمان | 5 سنوات |

## لمن صُمم؟

هذا الموديل لـالمجمعات السكنية وإدارات تخطط على مقياس الحديقة. الأرضية كلاسيكي بمساحة 25 م² تترك الأثاث حرًا: ترتيب 7-8 أشخاص يدخل بيسر؛ طاولة الطعام وركن الهدوء يتقاسمان المساحة. السطح يخفف التنظيف في الموسم الذروة. الزائدون يجدون نقطة لقاء مظلولة؛ وسور خشبي يحمي منطقة الجلوس من جهة الرياح. الأفنية الضيقة والخطط تتلاءم مع المقاس دون كسر خط المشهد. المشاريع ذات ميزانية الحي تتجنّب مفاجآت التكلفة. ثلاثة وجوه مفتوحة تؤطر المشهد؛ والرابع يبقى للخدمة أو حركة المشاة.

## السعة

تُحسب السعة ومعامل المعتمد من F15: 25 م² ÷ 3.50 م² للشخص ≈ 7.14 — أي 7-8 أشخاص براحة. المعامل يتبع كثافة الاستخدام؛ الخصوصية وحرية الحركة تأتي قبل الازدحام. الترتيب مثالي لـ7-8 أشخاص؛ والتجمعات الأكبر تنتقل لمقاسات أوسع. يُتحقق من التوضع النهائي أثناء الاستشارة مع خطة الأثاث.

## التركيب

يُصنع الموديل عند الطلب. في الاستشارة المجانية يُفحص الأرضية واتجاه الدخول وحاجة الكهرباء، وتُثبَّت المقاسات وخطة التركيب في ذلك الموعد. المواعيد من الاثنين إلى السبت 09:00–18:00 بدون رسوم. يوم التركيب تُختار طريقة التثبيت حسب اللوحة أو التربة المضغوطة أو السطح الخشبي. تُنقل المواد مباشرة من السيارة؛ ويبقى الهدر ضئيلًا. بعد الانتهاء يُنظَّف السطح وتُسلَّم ورقة الاستخدام.

## الصيانة

الصيانة فحص سطحي سنوي وتجديد واقع عند الحاجة. معالج ضد التحلل والحشرات. تُنظَّف المرزبات وفتحات التصريف من الأوراق؛ وسقف مائل لا يترك ماءً راكدًا. تُشدّ مثبتات السور مرة في السنة. ضمان 5 سنوات يشمل عيوب التصنيع والمواد؛ والاستشارة وخطة التركيب تبقى في بطاقة الضمان.

## لماذا هذا الموديل؟

يوازن هذا الموديل بين المقياس وجدول الصيانة لـالمجمعات السكنية؛ المقاس القياسي يبقي عرض السعر قابلًا للتوقع، والاستشارة تثبت المقاسات قبل بدء التصنيع حتى لا يُعاد العمل في الموقع. كلاسيكي، أربعة زوايا يلائم خط المشهد، ومائل، مرزة يبعد المطر عن منطقة الجلوس، والسطح لا يحتاج إلا فحصًا بصريًا واحدًا في السنة. قائمة الأسعار تتبع المعادلة نفسها في الأداة: متر مربع في سعر الأساس في عوامل المادة والشكل والاستخدام — دون رسوم خفية. خمس سنوات ضمان واستشارة مجانية من الاثنين إلى السبت 09:00–18:00 تخفض مخاطر المشروع. الفرق بين هذا الموديل والمقاس المجاور يظهر في جدول السعة لا في بنود مخفية. من يحتاج مساحة أكبر ينتقل إلى الموديل الأكبر من العائلة نفسها، ومن يملك أرضية أضيق يبدأ بالمربع الصغير ثم يرقّى لاحقًا دون تغيير لغة العرض.',
                    'seo_baslik' => 'كوش كلاسيكي خشبي 5x5 أسعار 2026 | Kamelya',
                    'seo_aciklama' => 'كوش كلاسيكي خشبي 5x5: 25 م²، 7-8 أشخاص، سقف مائل، ضمان 5 سنوات. أسعار المتر وحجز استشارة.',
                    'seo_anahtar_kelimeler' => 'كوش كلاسيكي خشبي, أسعار الكوش خشبي, كوش 5x5, كوش المجمعات السكنية',
                    'cati_tipi_aciklama' => 'يتجه السقف المائل بانحدار خفيف نحو مركزه على حاملات الأرضية 5x5؛ فيتصريف مياه المطر والثلج بالتساوي دون تجمّع. الانحدار يمنع تركّز الثلج في حمل واحد. المرزة تناسب لغة المواد: تُوجَّه المياه إلى القناة وتُبعد عن الإطار، فلا تصل القطرة إلى سور خشبي ولا إلى منطقة الجلوس. تُنتج التغطية مطابقة للمادة الأساسية. يُقيَّم سلوك الرياح وفق EN 13561 في الاستشارة. من الخارج يُقرأ السقف قطعة واحدة بلا عدم تماثل. يتجه التصريف بعيدًا عن المدخل. فحص سطحي سنوي يكفي. تنزلق المياه بدل أن تثبت. تبقى فتحة التصريف فوق الأرض. ومع تباين الأرضية تُناقش زاوية الانحدار مع فريق الاستشارة في الموقع. في الشتاء ينزل الثلج ناعمًا إلى الأرض ولا يسدّ القناة أبدًا. كهرباء المصابيح تسير مع العمود لا تحت سقف المظلة. تُفحص وصلات الرفات سنويًا مع فحص السور. والزاوية تُثبَّت على معين معدني يوزّع الحمل على العمود الرأسي دون أن تظهر فواصل على الواجهة الخارجية للطاولة أو المقاعد. يبقى الميل ظاهرًا للفاحص الفني في تقرير الاستلام النهائي.',
                    'korkuluk_aciklama' => 'يستمر سور خشبي بارتفاع متساوٍ على كل حافة من حواف الخطة، يحمي منطقة الجلوس من الرياح المارّة مع إبقاء الخصوصية في الداخل. يُثبَّت الارتفاع في الاستشارة وفق معايير السلامة، مع تفضيل المقاس الذي يسند المعصم أثناء الجلوس على رقم كتالوج ثابت. تُقفل نقاط التقاء بتثبيتات متقاطعة تمنع الاهتزاز. يُجهَّز السطح بطبقة واقية تحافظ على نسيج المادة الطبيعي — ناعم عند اللمس دون شظايا. تُرفع القواعد كي لا يصعد ماء الأرض إلى الجذوع، وتُختار كل القطع المعدنية مقاومة للصدأ. في الأماكن العائلية تُضبط المسافات الرأسية بحيث لا تعلق اليد، ويبقى خط القبضة العلوي مستقيمًا. حيث يلزم مرور الكرسي المتحرك يُفتح جانب. التنظيف بفرشاة ناعمة ومياه فاترة؛ فحص القواعد سنويًا. لا يسخن الصيف ولا يُثلج الشتاء. يمكن طلبه باللون عند الطلب. وعلى خطة 5x5 يرسم خطًا أمنيًا متصلًا ويؤطر الأثاث من الداخل. قناة مخفية أسفل السطح العلوي تستقبل شريطًا ضوئيًا أو رف نباتات دون مسامير ظاهرة. تُراجع وصلات الربط مرة كل موسم مع فحص عام للهيكل كاملاً دون إهمال الزوايا.',
                ],
            ],
            'KML-AHS-KLA-010' => [
                'tr' => [
                    'baslik' => 'Klasik Ahşap Kamelya 4x3 Restoran Bahçesi',
                    'slug' => 'klasik-ahsap-kamelya-4x3-restoran-010',
                    'kisa_aciklama' => 'Klasik ahşap kamelya, 4×3 metre tabanıyla Restoran Bahçesi için 6-7 kişilik dengeli bir oturma alanı sunar. Kaliteli çam kereste öne çıkar; eğimli çatı yağmuru kontrollü tahliye eder. Emprenye — çürüme ve böceğe karşı ile dört mevsim kullanıma uygundur. Net ölçü ve montaj planı ücretsiz keşifte belirlenir; 5 yıl garantilidir.',
                    'detayli_aciklama' => '**TL;DR**
- 4×3 m (12 m²) klasik taban; Restoran Bahçesi için 6-7 kişilik ferah oturma alanı.
- kaliteli çam kereste, Eğimli, oluklu drenaj: dört mevsim kullanım, 5 yıl garanti.
- Şeffaf m² hesabı: 12 × 12.000 × 1 (Ahşap) × 1 (klasik) × 1.3 (restoran) = 187.200 TL.
- Net ölçü ve montaj planı ücretsiz keşifte sabitlenir (Pzt–Cmt 09:00–18:00).

## Teknik Özellikleri Nelerdir?

| Alan | Değer |
| --- | --- |
| Taban ölçüsü | 4×3 m — 12 m² |
| Form | Klasik, dört köşe |
| Ana malzeme | kaliteli çam kereste |
| Çatı | Eğimli, oluklu drenaj |
| Korkuluk | ahşap korkuluk, proje bazlı yükseklik |
| Yüzey işlemi | Emprenye — çürüme ve böceğe karşı |
| Bağlantı | Korozyona dayanıklı metal elemanlar |
| Garanti | 5 yıl |

## Kimler İçin?

Bu model, Restoran Bahçesi ölçeğinde çalışan işletmeler ve site yönetimleri için tasarlandı. 12 m² klasik taban, mobilyayı serbestçe yerleştirir: 6-7 kişilik bir düzen rahatça kurulur; yemek masası ile dinlenme köşesi.

## Kaç Kişiliktir?

Kapasite, F15 onaylı restoran katsayısıyla hesaplanır: 12 m² ÷ 1,80 m²/kişi ≈ 6,67 — yani 6-7 kişi rahat oturur. Bu katsayı, kullanım yoğunluğuna göre seçilir; mahremiyet ve kol hareketi serbestliği,.

## Montaj Nasıl Yapılır?

Ürün sipariş üzerine üretilir. Ücretsiz keşifte zemin, giriş yönü ve varsa elektrik ihtiyacı değerlendirilir; net ölçü ile montaj planı bu görüşmede sabitlenir. Keşif randevuları Pzt–Cmt 09:00–18:00 arasındadır ve ücretsizdir.

## Bakımı Nasıl Yapılır?

Bakım, yılda bir yüzey kontrolü ve gerekirse koruyucu yenilemeden ibarettir. Emprenye — çürüme ve böceğe karşı. Çatı olukları ve tahliye açıklıkları yapraktan temizlenir; eğimli çatı üzerinde su birikintisi bırakılmaz.

## Neden Bu Model?

Bu model, Restoran Bahçesi için doğru dengeyi kurar: 12 m² taban ne dar kalır ne de bakımı şişirir. Klasik, dört köşe form, peyzaj çizgisine uyar; Eğimli, oluklu drenaj yağmuru oturma.

## Çatı Özellikleri Nelerdir?

Eğimli çatı, 4x3 tabanın taşıyıcılarıyla merkeze doğru hafif eğimlenir; böylece yağmur ve kar suyu dört cepheden eşit şekilde tahliye olur, tabanda su birikmez. Eğim, karın tek bir yüke toplanmasını da engeller. Oluk detayı, kaliteli çam kerestenin diline uygun bir çözümdür: su, oluğa yönlendirilip yapıdan uzaklaştırılır; damlayan su ahşap korkuluke ve oturma alanına isabet etmez. Çatı kaplaması, malzemeyle uyumlu yüzey olarak üretilir; metal ile ahşap çarpışmaz. Rüzgâr yükü davranışı, proje ölçeğinde değerlendirilir ve alan, EN 13561 referansıyla tartışılan dış cephe elemanları mantığına göre keşifte netleşir. Formda çatı dıştan bakıldığında da bütünlüklüdür: her cephe aynı eğimi taşır, asimetri girmez. Drenaj yönü, girişin tersine kurulur; oturma alanı her zaman kuru tarafta kalır. Çatı bakımına yılda bir yüzey kontrolü yeterlidir; oluk içindeki yaprak temizlenir, yüzey kuru tutulur. Yağmur direnci, eğim ve yüzey işçiliği birlikte çalışır; su yüzeyde durmak yerine akar. Oluk çıkışı zemin kotunun üstünde tutulur, su kontrollü iner. Gerektiğinde eğim açısı araziye göre keşif ekibiyle konuşulur. Kışın kar, eğim sayesinde zemine yumuşak iner; buz kütlesi oluğa dolmaz. Elektrik hattı gerekiyorsa tavandan değil, taşıyıcı kolondan beslenir.

## Korkuluk ve Güvenlik Özellikleri Nelerdir?

ahşap korkuluk, planın her kenarında eşit yükseklikte devam eder ve oturma alanını dışarıdan gelen rüzgâra karşı korurken içeride mahremiyet hissi bırakır. Yükseklik, güvenlik standartlarına ve proje koşullarına göre keşif sırasında netleşir; sabit bir katalog rakamı yerine, kullanıcının oturduğu anda bileğini destekleyen ölçü tercih edilir. Birleşim noktaları çapraz bağlantılarla kilitlenir, sallanma önlenir. Yüzey, malzemenin doğal dokusunu koruyan koruyucu kaplamayla hazırlanır; elle temas pürüzsüzdür, yonga bırakmaz. Korkuluk dip kısımları, zemine geçen suyu gövdeye taşımaması için yükseltilir ve bağlantı elemanları korozyona dayanıklı malzemeden seçilir. Çocuklu alanlarda dikey aralıklar, topun veya kolun sıkışmasını önleyecek şekilde düzenlenir; yaşlı sakinler için üst tutamak hattı düz ve kesintisizdir. Tekerlekli sandalye erişimi gereken giriş kenarında korkuluk açılıp geçiş bırakacak şekilde planlanır. Temizlikte yumuşak fırça ve ılık su yeterlidir; dip noktalar yılda bir kontrol edilir, gevşenen sıkma hemen yapılır. Malzeme, alternatife göre yazın ısınmaz, kışın soğuk tutmaz; dokusu bahçenin malzeme diliyle uyumludur. İstenirse boyanabilir; renk değişikliği teklif aşamasında belirlenir. Korkuluk, 4x3 planında kesintisiz bir güvenlik hattı çizer ve mobilya yerleşimini içeriden çerçeveler. Işık bandı veya saksı rafı istenirse üst profilin iç yüzüne gizli kanal açılır; görünür vida bırakılmaz.
',
                    'seo_baslik' => 'Klasik Ahşap Kamelya 4x3 Fiyatları 2026 | Kamelya',
                    'seo_aciklama' => 'Klasik ahşap kamelya 4x3: 12 m² alan, 6-7 kişilik restoran bahçesi, eğimli çatı, 5 yıl garantili. Şeffaf m² fiyat listesi ve ücretsiz keşif',
                    'seo_anahtar_kelimeler' => 'klasik ahşap kamelya, ahşap kamelya fiyatları, 4x3 kamelya, restoran kamelyası',
                    'cati_tipi_aciklama' => 'Eğimli çatı, 4x3 tabanın taşıyıcılarıyla merkeze doğru hafif eğimlenir; böylece yağmur ve kar suyu dört cepheden eşit şekilde tahliye olur, tabanda su birikmez. Eğim, karın tek bir yüke toplanmasını da engeller. Oluk detayı, kaliteli çam kerestenin diline uygun bir çözümdür: su, oluğa yönlendirilip yapıdan uzaklaştırılır; damlayan su ahşap korkuluke ve oturma alanına isabet etmez. Çatı kaplaması, malzemeyle uyumlu yüzey olarak üretilir; metal ile ahşap çarpışmaz. Rüzgâr yükü davranışı, proje ölçeğinde değerlendirilir ve alan, EN 13561 referansıyla tartışılan dış cephe elemanları mantığına göre keşifte netleşir. Formda çatı dıştan bakıldığında da bütünlüklüdür: her cephe aynı eğimi taşır, asimetri girmez. Drenaj yönü, girişin tersine kurulur; oturma alanı her zaman kuru tarafta kalır. Çatı bakımına yılda bir yüzey kontrolü yeterlidir; oluk içindeki yaprak temizlenir, yüzey kuru tutulur. Yağmur direnci, eğim ve yüzey işçiliği birlikte çalışır; su yüzeyde durmak yerine akar. Oluk çıkışı zemin kotunun üstünde tutulur, su kontrollü iner. Gerektiğinde eğim açısı araziye göre keşif ekibiyle konuşulur. Kışın kar, eğim sayesinde zemine yumuşak iner; buz kütlesi oluğa dolmaz. Elektrik hattı gerekiyorsa tavandan değil, taşıyıcı kolondan beslenir.',
                    'korkuluk_aciklama' => 'ahşap korkuluk, planın her kenarında eşit yükseklikte devam eder ve oturma alanını dışarıdan gelen rüzgâra karşı korurken içeride mahremiyet hissi bırakır. Yükseklik, güvenlik standartlarına ve proje koşullarına göre keşif sırasında netleşir; sabit bir katalog rakamı yerine, kullanıcının oturduğu anda bileğini destekleyen ölçü tercih edilir. Birleşim noktaları çapraz bağlantılarla kilitlenir, sallanma önlenir. Yüzey, malzemenin doğal dokusunu koruyan koruyucu kaplamayla hazırlanır; elle temas pürüzsüzdür, yonga bırakmaz. Korkuluk dip kısımları, zemine geçen suyu gövdeye taşımaması için yükseltilir ve bağlantı elemanları korozyona dayanıklı malzemeden seçilir. Çocuklu alanlarda dikey aralıklar, topun veya kolun sıkışmasını önleyecek şekilde düzenlenir; yaşlı sakinler için üst tutamak hattı düz ve kesintisizdir. Tekerlekli sandalye erişimi gereken giriş kenarında korkuluk açılıp geçiş bırakacak şekilde planlanır. Temizlikte yumuşak fırça ve ılık su yeterlidir; dip noktalar yılda bir kontrol edilir, gevşenen sıkma hemen yapılır. Malzeme, alternatife göre yazın ısınmaz, kışın soğuk tutmaz; dokusu bahçenin malzeme diliyle uyumludur. İstenirse boyanabilir; renk değişikliği teklif aşamasında belirlenir. Korkuluk, 4x3 planında kesintisiz bir güvenlik hattı çizer ve mobilya yerleşimini içeriden çerçeveler. Işık bandı veya saksı rafı istenirse üst profilin iç yüzüne gizli kanal açılır; görünür vida bırakılmaz.',
                ],
                'en' => [
                    'baslik' => 'Classic Wood Gazebo 4x3 Restaurant Garden',
                    'slug' => 'classic-wood-gazebo-4x3-010',
                    'kisa_aciklama' => 'Classic timber gazebo, 4×3 footprint for restaurant gardens. Impregnated pine against rot and insects and sloped roof keep four-season use simple. Seats 6-7 across 12 m². Measurements and install plan lock after the free survey (Mon–Sat 09:00–18:00). Five-year warranty. Built to order for gardens and terraces that need reliable shade.',
                    'detayli_aciklama' => '**TL;DR**
- 4×3 m (12 m²) Classic, pitched silhouette floor; an 6-7-person seating area for restaurant gardens.
- Impregnated pine against rot and insects, Sloped, timber-guttered drainage: four-season use, five-year warranty.
- Transparent m² maths: 12 × 3 USD × 1.0 × 1.0 × 1.3 = 46.80 USD.
- Final measurements and the installation plan are fixed at the free survey (Mon-Sat 09:00-18:00).

## What Are the Technical Specifications?

| Item | Value |
| --- | --- |
| Floor size | 4×3 m — 12 m² |
| Shape | Classic, pitched silhouette |
| Main material | timber |
| Roof | Sloped, timber-guttered drainage |
| Railing | timber railing, height set per project |
| Surface | Impregnated pine against rot and insects |
| Warranty | 5 years |

## Who Is It For?

This model is built for restaurant operators and site teams that plan at garden scale.

## How Many People Does It Seat?

Seating follows the approved restaurant gardens ratio from F15: 12 m² ÷ 1.80 m² per person ≈ 6.67, so 6-7 people sit comfortably.

## How Is It Installed?

The product is made to order.

## How Is It Maintained?

Care is one surface check a year plus a protective refresh when needed.

## Why This Model?

This model balances scale and upkeep for restaurant gardens.

## What About the Roof?

The sloped roof eases toward its centre on the load-bearing edges of the 4×3 floor, so rain and snow drain evenly and nothing ponds below. The pitch also stops snow from becoming a single load. Gutters suit the timber language of the structure: water is routed into the channel and carried away, so drips never land on the railing or seating zone. Cladding matches the main material and avoids a clash in the silhouette. Wind behaviour is assessed at project scale and settled at the survey against external envelope elements referenced by EN 13561. From outside the roof reads as one piece — every elevation carries the same pitch, with no asymmetry. Drainage faces away from the entrance so the seating side stays dry. Maintenance needs one surface check a year; clear leaves and keep the surface dry. Rain resistance works through pitch plus joinery: water runs off instead of standing. The outlet stays above ground level. Where terrain differs, the slope angle is discussed with the survey crew on site.

## What About the Railing and Safety?

The timber railing runs at an even height along every edge of the plan, shielding the seating area from cross-wind while keeping privacy inside. Height is confirmed at the survey against safety standards, preferring the dimension that supports the wrist when seated over a fixed catalogue number. Joints lock with cross-braces so the frame does not sway. The surface keeps the material\'s natural grain — smooth to the touch, with no splinters. Railing feet are lifted so water never wicks into the posts; all fixings are chosen for corrosion resistance. Vertical gaps are spaced to prevent hands or balls from jamming, while the top grip line stays flat for elderly residents. Where wheelchair access is needed, one entrance edge opens and leaves a clear passage. Cleaning takes a soft brush and lukewarm water; check the feet yearly. The material doesn\'t overheat in summer or feel cold in winter, and its texture matches the garden\'s palette. The railing can be painted on request; colour is decided at quote stage. Along the 4×3 plan it draws an unbroken safety line. A hidden channel under the top rail accepts a light strip or planter rail without visible screws.
',
                    'seo_baslik' => 'Classic timber Gazebo 4×3 Prices and Sizes 2026 | Kamelya',
                    'seo_aciklama' => '12 m² Classic gazebo seats 6-7 with sloped roof, five-year warranty. Transparent m² pricing and free on-site survey. Book a visit Monday–Saturday.',
                    'seo_anahtar_kelimeler' => 'classic wood gazebo, wood gazebo prices, 4x3 gazebo, restaurant gazebo',
                    'cati_tipi_aciklama' => 'The sloped roof eases toward its centre on the load-bearing edges of the 4×3 floor, so rain and snow drain evenly and nothing ponds below. The pitch also stops snow from becoming a single load. Gutters suit the timber language of the structure: water is routed into the channel and carried away, so drips never land on the railing or seating zone. Cladding matches the main material and avoids a clash in the silhouette. Wind behaviour is assessed at project scale and settled at the survey against external envelope elements referenced by EN 13561. From outside the roof reads as one piece — every elevation carries the same pitch, with no asymmetry. Drainage faces away from the entrance so the seating side stays dry. Maintenance needs one surface check a year; clear leaves and keep the surface dry. Rain resistance works through pitch plus joinery: water runs off instead of standing. The outlet stays above ground level. Where terrain differs, the slope angle is discussed with the survey crew on site.',
                    'korkuluk_aciklama' => 'The timber railing runs at an even height along every edge of the plan, shielding the seating area from cross-wind while keeping privacy inside. Height is confirmed at the survey against safety standards, preferring the dimension that supports the wrist when seated over a fixed catalogue number. Joints lock with cross-braces so the frame does not sway. The surface keeps the material\'s natural grain — smooth to the touch, with no splinters. Railing feet are lifted so water never wicks into the posts; all fixings are chosen for corrosion resistance. Vertical gaps are spaced to prevent hands or balls from jamming, while the top grip line stays flat for elderly residents. Where wheelchair access is needed, one entrance edge opens and leaves a clear passage. Cleaning takes a soft brush and lukewarm water; check the feet yearly. The material doesn\'t overheat in summer or feel cold in winter, and its texture matches the garden\'s palette. The railing can be painted on request; colour is decided at quote stage. Along the 4×3 plan it draws an unbroken safety line. A hidden channel under the top rail accepts a light strip or planter rail without visible screws.',
                ],
                'de' => [
                    'baslik' => 'KlassischerHolz--Pavillon 4x3 Restaurantgarten',
                    'slug' => 'klassischer-holz-pavillon-4x3-010',
                    'kisa_aciklama' => 'Klassischer Holz-Pavillon 4x3 für Restaurantgärten: Qualitäts-Kiefernholz, geneigtes Dach mit kontrollierter Entwässerung und klarer Sitzordnung. Platz für 6-7 Personen auf 12 m². Maße und Montageplan werden nach dem kostenlosen Aufmaß fixiert (Mo-Sa 09:00-18:00). 5 Jahre Garantie inklusive.',
                    'detayli_aciklama' => '**Kurz gefasst**
- 4×3 m (12 m²) klassisch Fläche; 6-7-Personen-Sitzplatz für Restaurantgärten.
- Qualitäts-Kiefernholz, Geneigt, Rinne: ganzjährige Nutzung, 5 Jahre Garantie.
- Transparente m²-Rechnung: 12 × 2,50 EUR × 1 × 1 × 1.3 = 39 EUR.
- Maße und Montageplan werden beim kostenlosen Aufmaß fixiert (Mo-Sa 09:00-18:00).

## Technische Daten

| Merkmal | Wert |
| --- | --- |
| Bodenmaß | 4×3 m — 12 m² |
| Form | Klassisch, vier Ecken |
| Hauptmaterial | Qualitäts-Kiefernholz |
| Dach | Geneigt, Rinne |
| Geländer | Holzgeländer, Höhe projektbezogen |
| Oberfläche | Imprägniert gegen Fäulnis und Insekten |
| Befestigung | Korrosionsfeste Metallteile |
| Garantie | 5 Jahre |

## Für wen ist er geeignet?

Dieses Modell ist für Restaurantgärten und Hausverwaltungen gebaut, die im Gartenmaß planen. Der 12 m²-klassisch-Boden lässt Möbel frei stehen: eine 6-7-Personen-Aufteilung passt mühelos; Esstisch und ruhige Ecke teilen sich den Platz. Die Oberfläche spart Reinigung in der Hochsaison. Gäste finden einen übersichtlichen Treffpunkt; das Holzgeländer schützt die Sitzfläche an der Windseite. Schmale Höfe und Pläne passen zum Maß, ohne die Landschaftslinie zu brechen. Projekte mit Quartiersbudget vermeiden Kostenüberraschungen durch den Standardgrundriss. Drei offene Seiten rahmen den Blick; die vierte bleibt Service oder Fußgverkehr vorbehalten.

## Kapazität

Die Kapazität folgt dem freigegebenen Faktor aus F15: 12 m² ÷ 1,80 m² pro Person ≈ 6,67 — bequem sitzen also 6-7 Personen. Der Faktor folgt der Nutzungsdichte; Privatsphäre und Bewegungsfreiheit stehen vor Dichte. Die Aufteilung ist ideal für 6-7 Personen; größere Feste wechseln zu breiteren Modellen. Die endgültige Aufstellung wird beim Aufmaß mit dem Möbelplan geprüft.

## Montage

Das Modell wird auf Bestellung gefertigt. Das kostenlose Aufmaß prüft Untergrund, Zufahrt und Strombedarf und fixiert Maße und Montageplan in diesem Termin. Termine laufen Montag bis Samstag 09:00-18:00 ohne Kosten. Am Montagetag wird die Verankerung für Platte, verdichteten Boden oder Holzdeck gewählt. Material geht direkt vom Fahrzeug zum Aufbau; Verschnitt bleibt minimal. Nach Abschluss wird die Oberfläche gereinigt und das Datenblatt übergeben.

## Pflege

Pflege ist eine Oberflächenkontrolle pro Jahr plus Schutzschicht bei Bedarf. Imprägniert gegen Fäulnis und Insekten. Rinnen und Drainageöffnungen werden von Laub befreit; das geneigtes Dach hält kein Stauwasser. Geländerbeschläge werden einmal jährlich nachgezogen. Die 5-Jahres-Garantie deckt Material- und Fertigungsfehler; Aufmaß und Montageplan bleiben auf der Garantiekarte.

## Warum dieses Modell?

Dieses Modell hält Massstab und Pflegeaufwand für Restaurantgärten in Balance. Der Standardgrundriss macht das Angebot kalkulierbar; das Aufmaß fixiert die Maße vor der Fertigung, damit die Baustelle ohne Nacharbeit auskommt. Klassisch, vier Ecken fügt sich in die Gartenlinie ein, Geneigt, Rinne hält Regen fern, und die Oberfläche braucht im Jahr nur eine Sichtkontrolle. Die Preisliste folgt derselben Formel wie der Konfigurator: Fläche mal Grundpreis mal Material-, Form- und NutzungsFaktor — ohne versteckte Zuschläge. Fünf Jahre Garantie und kostenloses Aufmaß von Montag bis Samstag 09:00-18:00 senken das Projektrisiko. Wer mehr Platz braucht, greift zum größeren Modell derselben Baureihe.',
                    'seo_baslik' => 'KlassischerHolz--Pavillon 4x3 Preise 2026 | Kamelya',
                    'seo_aciklama' => 'KlassischerHolz--Pavillon 4x3: 12 m², für 6-7, geneigtes Dach, 5 Jahre Garantie. Transparente m²-Preise und kostenloses Aufmaß buchen. Details auf der',
                    'seo_anahtar_kelimeler' => 'klassisch holz Pavillon, holz Pavillon Preise, 4x3 Pavillon, Pavillon Restaurant',
                    'cati_tipi_aciklama' => 'Das geneigte Dach neigt sich über die tragenden Kanten des 4x3-Bodens sanft zur Mitte; Regen- und Schneewasser läuft gleichmäßig ab und bleibt nicht auf dem Boden. Die Neigung verhindert, dass Schnee zu einer Einzellast wird. Die Rinne passt zur Materialsprache: Wasser wird in den Kanal geführt und vom Rahmen weggeleitet, Tropfen treffen weder Holzgeländer noch Sitzfläche. Die Deckung entsteht materialgleich — kein Kontrast in der Silhouette. Das Windverhalten wird projektmäßig bewertet und beim Aufmaß an der Logik externer Fassadenelemente mit EN-13561-Bezug abgeglichen. Von außen wirkt das Dach als ein Stück ohne Asymmetrie. Die Entwässerung zeigt weg vom Eingang. Zur Pflege genügt eine Oberflächenkontrolle pro Jahr. Der Regenwiderstand entsteht aus Neigung und Verarbeitung. Die Ablauföffnung bleibt über dem Boden. Bei abweichendem Gelände wird der Neigungswinkel mit dem Aufmaß-Team besprochen. Im Winter gleitet Schnee weich zum Boden und verstopft die Rinne nicht. Strom für Lampen läuft an der Strebe, nicht unter der Dachhaut.',
                    'korkuluk_aciklama' => 'Das Holzgeländer läuft an jeder Kante des Grundrisses in gleicher Höhe weiter und schützt die Sitzfläche vor Durchzug, während innen Privatsphäre bleibt. Die Höhe wird beim Aufmaß anhand von Sicherheitsnormen bestätigt; statt einer festen Katalogzahl wird das Maß gewählt, das das Handgelenk im Sitzen stützt. Verbindungen werden mit Kreuzverband verriegelt. Die Oberfläche erhält eine Schutzschicht, die die natürliche Struktur bewahrt. Die Füße werden angehoben, damit bodennahes Wasser nicht in die Pfosten steigt; alle Beschläge sind korrosionsfest. In Familienbereichen balancieren Vertikalabstände Kindersicherheit; die obere Griffleitung bleibt gerade. Wo ein Rollstuhlvorgang nötig ist, öffnet sich eine Ecke und lässt freie Passage. Zur Reinigung genügen weiche Bürste und lauwarmes Wasser; Füße einmal jährlich prüfen. Das Material wird im Sommer nicht heiß und im Winter nicht eiskalt. Auf Wunsch anmalbar; die Farbe wird beim Angebot festgelegt. Auf dem 4x3-Grundriss zieht es eine durchgehende Sicherheitslinie und fasst die Möbel von innen ein. Ein versteckter Kanal unter der Oberkante nimmt Lichtband oder Pflanzschiene auf, ohne sichtbare Schrauben.',
                ],
                'fr' => [
                    'baslik' => 'Gazebo Classique Bois 4x3 Jardin de Restaurant',
                    'slug' => 'gazebo-classique-bois-4x3-010',
                    'kisa_aciklama' => 'Gazebo classique Bois de 4×3 m pour jardins de restaurant : pin de qualité, toit incliné à évacuation contrôlée et assise dégagée. 6-7 personnes sur 12 m² pour un usage toute saison. Dimensions et plan de pose fixés après l’étude gratuite (lun.-sam. 09h00-18h00). Garantie 5 ans incluse.',
                    'detayli_aciklama' => '**En bref**
- Sol classique 4×3 m (12 m²) ; espace pour 6-7 personnes en jardins de restaurant.
- pin de qualité, Incliné, gouttière : usage toute saison, garantie 5 ans.
- Calcul m² transparent : 12 × 2,50 EUR × 1 × 1 × 1.3 = 39 EUR.
- Dimensions et plan de pose fixés pendant l’étude gratuite (lun.-sam. 09h00-18h00).

## Caractéristiques techniques

| Caractéristique | Valeur |
| --- | --- |
| Emprise au sol | 4×3 m — 12 m² |
| Forme | Classique, quatre angles |
| Matériau principal | pin de qualité |
| Toit | Incliné, gouttière |
| Garde-corps | garde-corps en bois, hauteur définie par projet |
| Surface | Traité contre pourriture et insectes |
| Fixations | Pièces métalliques résistantes à la corrosion |
| Garantie | 5 ans |

## Pour qui ?

Ce modèle est conçu pour les jardins de restaurant et syndics qui planifient à l’échelle du jardin. Le sol classique de 12 m² laisse le mobilier libre : une organisation 6-7 personnes tient sans effort ; table à manger et coin calme se partagent l’espace. La surface allège le nettoyage en haute saison. Les clients trouvent un point de rendez-vous ombragé ; le garde-corps en bois protège l’assise côté vent. Cours étroits et plans s’accordent à la mesure sans casser la ligne de paysage. Les projets de budget de quartier évitent les surprises de coût. Trois côtés ouverts cadrent la vue ; le quatrième reste au service ou au flux piéton.

## Capacité

La capacité suit le facteur approuvé en F15 : 12 m² ÷ 1,80 m² par personne ≈ 6,67 — soit confortablement 6-7 personnes. Le facteur suit l’intensité d’usage ; intimité et aisance passent avant la densité. L’organisation est idéale pour 6-7 personnes ; les réceptions plus larges passent à des modèles plus amples. Le placement final se vérifie pendant l’étude avec le plan de mobilier.

## Pose

Le gazebo est fabriqué sur commande. L’étude gratuite examine le sol, l’accès et le besoin électrique, et fixe dimensions et plan de pose. Les créneaux vont du lundi au samedi 09h00-18h00 sans frais. Le jour de pose, l’ancrage s’adapte à la dalle, au sol compacté ou au deck bois. Les matériaux passent du véhicule au chantier ; les chutes restent minimales. À la fin, la surface est nettoyée et la fiche d’usage remise.

## Entretien

L’entretien se limite à un contrôle de surface par un renouvellement protecteur si besoin. Traité contre pourriture et insectes. Gouttières et orifices d’évacuation sont dégagés des feuilles ; le toit incliné ne garde jamais d’eau stagnante. Les fixations du garde-corps se resserrent une fois par an. La garantie 5 ans couvre défauts de fabrication et de matière ; l’étude et le plan de pose restent sur la carte de garantie.

## Pourquoi ce modèle ?

Ce modèle équilibre échelle et entretien pour les jardins de restaurant ; l’empreinte standard garde le devis prévisible et l’étude fixe les dimensions avant fabrication.',
                    'seo_baslik' => 'Gazebo Classique Bois 4x3 Prix et Mesures 2026 | Kamelya',
                    'seo_aciklama' => 'Gazebo classique bois 4x3 : 12 m², 6-7 places, toit incliné, garantie 5 ans. Prix au m² transparents, réservation d’une étude gratuite. Détails sur la',
                    'seo_anahtar_kelimeler' => 'gazebo classique bois, gazebo bois prix, gazebo 4x3, gazebo restaurant',
                    'cati_tipi_aciklama' => 'Le toit incliné s’incline doucement vers son centre sur les porteurs du sol 4x3 ; pluie et neige évacuent sans stagnation. La pente empêche la neige de concentrer une charge unique. La gouttière suit le langage des matériaux : l’eau est dirigée dans le canal, les gouttes n’atteignent ni garde-corps en bois ni l’assise. La couverture est assortie au matériau principal. Le vent s’évalue selon l’EN 13561 à l’étude. Dehors le toit se lit comme une pièce continue sans asymétrie. L’évacuation regarde l’entrée. Un contrôle de surface par an suffit. L’eau coule au lieu de rester. La sortie reste au-dessus du sol. Si le terrain diffère, la pente se discute sur place. En hiver la neige glisse doucement au sol et ne bouche jamais le canal. Le courant des lampes suit un montant, pas la toiture. Les fixations du bac se resserrent avec la grondaire lors du contrôle annuel de la structure entière.',
                    'korkuluk_aciklama' => 'Le garde-corps en bois court à hauteur égale sur chaque arête, protège l’assise des traversants et garde l’intimité. Sa hauteur se confirme à l’étude selon les normes, préférant la mesure qui soutient le poignet au chiffre de catalogue. Les assemblages se verrouillent par croisements. La surface reçoit une finition protectrice qui conserve le grain. Les pieds sont surélevés pour que l’eau du sol ne remonte pas ; les pièces d’attache résistent à la corrosion. Dans les espaces familiaux les entraxes empêchent les mains de se coincer ; la ligne de saisie reste droite. Là où le fauteuil est requis, un côté s’ouvre. Nettoyage : brosse douce et eau tiède ; contrôle annuel des pieds. Le matériau ne chauffe ni ne givre. Peignable sur demande. Sur le plan 4x3 il trace une ligne continue et cadre le mobilier de l’intérieur. Un canal discret sous le rail supérieur accueille bandeau lumineux ou corniche à plantes, sans vis apparente.',
                ],
                'it' => [
                    'baslik' => 'Gazebo Classico Legno 4x3 Giardino Ristorante',
                    'slug' => 'gazebo-classico-legno-4x3-010',
                    'kisa_aciklama' => 'Gazebo classico in Legno da 4×3 m per giardini di ristorante: pino di qualità, tetto inclinato a scarico controllato. 6-7 persone su 12 m². Misure e piano di posa fissati dopo il sopralluogo gratuito (lun.-sab. 09:00-18:00). Garanzia 5 anni.',
                    'detayli_aciklama' => '**In breve**
- Pavimento classico 4×3 m (12 m²) ; zona seduta per 6-7 persone in giardini di ristorante.
- pino di qualità, Inclinato, gronda : uso tutto l’anno, garanzia 5 anni.
- Calcolo m² trasparente : 12 × 2,50 EUR × 1 × 1 × 1.3 = 39 EUR.
- Misure e piano di posa fissati durante il sopralluogo gratuito (lun.-sab. 09:00-18:00).

## Specifiche tecniche

| Voce | Valore |
| --- | --- |
| Impronta a terra | 4×3 m — 12 m² |
| Forma | Classico, quattro angoli |
| Materiale principale | pino di qualità |
| Tetto | Inclinato, gronda |
| Parapetto | parapetto in legno, altezza definita dal progetto |
| Superficie | Trattato contro putridume e insetti |
| Fissaggi | Parti metalliche resistenti alla corrosione |
| Garanzia | 5 anni |

## Per chi è?

Questo modello è pensato per giardini di ristorante e amministratori che progettano alla scala del giardino. Il pavimento classico da 12 m² lascia libero l’arredamento: una disposizione 6-7 persone entra con naturalezza; tavolo da pranzo e angolo relax condividono lo spazio. La superficie alleggerisce la pulizia in alta stagione. Gli ospiti trovano un punto d’incontro ombreggiato; il parapetto in legno ripara la seduta dal vento. Cortili stretti e piani si adattano alla misura senza spezzare la linea del paesaggio. I progetti con budget di quartiere evitano sorprese di costo. Tre lati aperti inquadrano la vista; il quarto resta a servizio o flusso pedonale.

## Capienza

La capienza segue il fattore approvato in F15: 12 m² ÷ 1,80 m² a persona ≈ 6,67 — quindi 6-7 persone comodamente. Il fattore segue l’intensità d’uso; privacy e libertà di movimento vengono prima della densità. La disposizione è ideale per 6-7 persone ; le feste più ampie passano a modelli più ampi. La posa finale si verifica al sopralluogo con il piano d’arredo.

## Posa

Il gazebo è prodotto su ordinazione. Il sopralluogo gratuito valuta terreno, accesso ed eventuale bisogno elettrico, e fissa misure e piano di posa. Gli appuntamenti vanno da lunedì a sabato 09:00-18:00 senza costi. Il giorno della posa si sceglie l’ancoraggio per la lastra, il terreno compatto o il deck in legno. I materiali passano dal veicolo al cantiere; gli scarti restano minimi. Al termine la superficie viene pulita e consegnata la scheda d’uso.

## Manutenzione

La manutenzione è un controllo di superficie all’anno e un rinnovo protettivo se serve. Trattato contro putridume e insetti. Gronde e bocchette si liberano dalle foglie; il tetto inclinato non trattiene acqua. Le fissaggi del parapetto si stringono una volta l’anno. La garanzia 5 anni copre difetti di fabbricazione e materiale; sopralluogo e piano restano sulla garanzia.

## Perché questo modello?

Questo modello bilancia scala e manutenzione per giardini di ristorante. L’impronta standard tiene il preventivo prevedibile e il sopralluogo fissa le misure prima della produzione, così il cantiere procede senza rifacimenti. Classico, quattro angoli si adatta alla linea del giardino, Inclinato, gronda allontana la pioggia dalla seduta, e la superficie richiede un solo controllo visivo all’anno. Il listino segue la stessa formula del configuratore: metri quadri per prezzo base per fattori di materiale, forma e uso — senza sorprese. Garanzia di 5 anni e sopralluogo gratuito da lunedì a sabato 09:00-18:00 riducono il rischio del progetto.',
                    'seo_baslik' => 'Gazebo Classico Legno 4x3 Prezzi e Misure 2026 | Kamelya',
                    'seo_aciklama' => 'Gazebo classico legno 4x3: 12 m², 6-7 posti, tetto inclinato, garanzia 5 anni. Prezzi al m² trasparenti, sopralluogo gratuito su richiesta. Dettagli in',
                    'seo_anahtar_kelimeler' => 'gazebo classico legno, gazebo legno prezzi, gazebo 4x3, gazebo ristorante',
                    'cati_tipi_aciklama' => 'Il tetto inclinato si inclina dolcemente verso il centro sui portanti del pavimento 4x3 ; pioggia e neve scaricano senza ristagni. La pendenza impedisce alla neve di concentrarsi in un carico unico. La gronda segue il linguaggio dei materiali : l’acqua va nel canale, le gocce non colpiscono né parapetto in legno né la seduta. La copertura è abbinata al materiale principale. Il vento si valuta con EN 13561 al sopralluogo. D’esterno il tetto si legge come pezzo continuo senza asimmetrie. Lo scarico guarda l’ingresso. Un controllo di superficie all’anno basta. L’acqua scorre invece di restare. La bocchetta resta sopra il suolo. Se il terreno differisce, la pendenza si discute in loco. D’inverno la neve scivola piano a terra e non ostruisce mai il canale. La corrente delle lampade segue un montante, non il sottotetto. Le fissaggi del bracciolo si stringono con la gronda nel controllo annuale della struttura intera.',
                    'korkuluk_aciklama' => 'Il parapetto in legno prosegue ad altezza uguale su ogni spigolo, ripara la seduta dai venti traversi e mantiene la riservatezza. L’altezza si conferma al sopralluogo secondo le norme, preferendo la misura che sorregge il polso a un numero fisso di catalogo. Le giunzioni si bloccano con incroci. La superficie riceve una finitura protettiva che conserva la venatura. I piedi sono sollevati perché l’acqua del terreno non risalga; le ferramenta sono a prova di corrosione. Negli spazi familiari gli interassi impediscono che mani restino incastrate; la linea di presa resta dritta. Dove serve la sedia a rotelle, un lato si apre. Pulizia : spazzola morbida e acqua tiepida ; controllo annuale dei piedi. Il materiale non scalda né gela. Colorabile a richiesta. Sul piano 4x3 disegna una linea continua e incornicia l’arredo dall’interno. Un canale discreto sotto il binario superiore ospita fascia luminosa o mensola per piante, senza viti a vista.',
                ],
                'ar' => [
                    'baslik' => 'كوش كلاسيكي خشبي 4x3 لـحديقة مطعم',
                    'slug' => 'classic-wood-kush-4x3-010',
                    'kisa_aciklama' => 'كوش كلاسيكي خشبي بمقاس 4×3 متر لـحدائق المطاعم: خشب صنوبر عالي الجودة، سقف مائل بتصريف مضبوط ومساحة جلوس مريحة. يتسع لـ6-7 أشخاص على 12 م² لاستخدام على مدار السنة. تُثبَّت المقاسات وخطة التركيب بعد الاستشارة المجانية (الاثنين–السبت 09:00–18:00). ضمان 5 سنوات كامل.',
                    'detayli_aciklama' => '**باختصار**
- أرضية كلاسيكي 4×3 م (12 م²)؛ مساحة جلوس لـ6-7 أشخاص في حدائق المطاعم.
- خشب صنوبر عالي الجودة، مائل، مرزة: استخدام على مدار السنة، ضمان 5 سنوات.
- حساب شفاف للمتر: 12 × 3 USD × 1 × 1 × 1.3 = 46.8 USD.
- تُثبَّت المقاسات وخطة التركيب في الاستشارة المجانية (الاثنين–السبت 09:00–18:00).

## المواصفات التقنية

| البند | القيمة |
| --- | --- |
| مقاس الأرضية | 4×3 م — 12 م² |
| الشكل | كلاسيكي، أربعة زوايا |
| المادة الأساسية | خشب صنوبر عالي الجودة |
| السقف | مائل، مرزة |
| السور | سور خشبي، الارتفاع حسب المشروع |
| السطح | معالج ضد التحلل والحشرات |
| التثبيت | قطع معدنية مقاومة للصدأ |
| الضمان | 5 سنوات |

## لمن صُمم؟

هذا الموديل لـحدائق المطاعم وإدارات تخطط على مقياس الحديقة. الأرضية كلاسيكي بمساحة 12 م² تترك الأثاث حرًا: ترتيب 6-7 أشخاص يدخل بيسر؛ طاولة الطعام وركن الهدوء يتقاسمان المساحة. السطح يخفف التنظيف في الموسم الذروة. الزائدون يجدون نقطة لقاء مظلولة؛ وسور خشبي يحمي منطقة الجلوس من جهة الرياح. الأفنية الضيقة والخطط تتلاءم مع المقاس دون كسر خط المشهد. المشاريع ذات ميزانية الحي تتجنّب مفاجآت التكلفة. ثلاثة وجوه مفتوحة تؤطر المشهد؛ والرابع يبقى للخدمة أو حركة المشاة.

## السعة

تُحسب السعة ومعامل المعتمد من F15: 12 م² ÷ 1.80 م² للشخص ≈ 6.67 — أي 6-7 أشخاص براحة. المعامل يتبع كثافة الاستخدام؛ الخصوصية وحرية الحركة تأتي قبل الازدحام. الترتيب مثالي لـ6-7 أشخاص؛ والتجمعات الأكبر تنتقل لمقاسات أوسع. يُتحقق من التوضع النهائي أثناء الاستشارة مع خطة الأثاث.

## التركيب

يُصنع الموديل عند الطلب. في الاستشارة المجانية يُفحص الأرضية واتجاه الدخول وحاجة الكهرباء، وتُثبَّت المقاسات وخطة التركيب في ذلك الموعد. المواعيد من الاثنين إلى السبت 09:00–18:00 بدون رسوم. يوم التركيب تُختار طريقة التثبيت حسب اللوحة أو التربة المضغوطة أو السطح الخشبي. تُنقل المواد مباشرة من السيارة؛ ويبقى الهدر ضئيلًا. بعد الانتهاء يُنظَّف السطح وتُسلَّم ورقة الاستخدام.

## الصيانة

الصيانة فحص سطحي سنوي وتجديد واقع عند الحاجة. معالج ضد التحلل والحشرات. تُنظَّف المرزبات وفتحات التصريف من الأوراق؛ وسقف مائل لا يترك ماءً راكدًا. تُشدّ مثبتات السور مرة في السنة. ضمان 5 سنوات يشمل عيوب التصنيع والمواد؛ والاستشارة وخطة التركيب تبقى في بطاقة الضمان.

## لماذا هذا الموديل؟

يوازن هذا الموديل بين المقياس وجدول الصيانة لـحدائق المطاعم؛ المقاس القياسي يبقي عرض السعر قابلًا للتوقع، والاستشارة تثبت المقاسات قبل بدء التصنيع حتى لا يُعاد العمل في الموقع. كلاسيكي، أربعة زوايا يلائم خط المشهد، ومائل، مرزة يبعد المطر عن منطقة الجلوس، والسطح لا يحتاج إلا فحصًا بصريًا واحدًا في السنة. قائمة الأسعار تتبع المعادلة نفسها في الأداة: متر مربع في سعر الأساس في عوامل المادة والشكل والاستخدام — دون رسوم خفية. خمس سنوات ضمان واستشارة مجانية من الاثنين إلى السبت 09:00–18:00 تخفض مخاطر المشروع. الفرق بين هذا الموديل والمقاس المجاور يظهر في جدول السعة لا في بنود مخفية. من يحتاج مساحة أكبر ينتقل إلى الموديل الأكبر من العائلة نفسها، ومن يملك أرضية أضيق يبدأ بالمربع الصغير ثم يرقّى لاحقًا دون تغيير لغة العرض.',
                    'seo_baslik' => 'كوش كلاسيكي خشبي 4x3 أسعار 2026 | Kamelya',
                    'seo_aciklama' => 'كوش كلاسيكي خشبي 4x3: 12 م²، 6-7 أشخاص، سقف مائل، ضمان 5 سنوات. أسعار المتر وحجز استشارة.',
                    'seo_anahtar_kelimeler' => 'كوش كلاسيكي خشبي, أسعار الكوش خشبي, كوش 4x3, كوش المطاعم',
                    'cati_tipi_aciklama' => 'يتجه السقف المائل بانحدار خفيف نحو مركزه على حاملات الأرضية 4x3؛ فيتصريف مياه المطر والثلج بالتساوي دون تجمّع. الانحدار يمنع تركّز الثلج في حمل واحد. المرزة تناسب لغة المواد: تُوجَّه المياه إلى القناة وتُبعد عن الإطار، فلا تصل القطرة إلى سور خشبي ولا إلى منطقة الجلوس. تُنتج التغطية مطابقة للمادة الأساسية. يُقيَّم سلوك الرياح وفق EN 13561 في الاستشارة. من الخارج يُقرأ السقف قطعة واحدة بلا عدم تماثل. يتجه التصريف بعيدًا عن المدخل. فحص سطحي سنوي يكفي. تنزلق المياه بدل أن تثبت. تبقى فتحة التصريف فوق الأرض. ومع تباين الأرضية تُناقش زاوية الانحدار مع فريق الاستشارة في الموقع. في الشتاء ينزل الثلج ناعمًا إلى الأرض ولا يسدّ القناة أبدًا. كهرباء المصابيح تسير مع العمود لا تحت سقف المظلة. تُفحص وصلات الرفات سنويًا مع فحص السور. والزاوية تُثبَّت على معين معدني يوزّع الحمل على العمود الرأسي دون أن تظهر فواصل على الواجهة الخارجية للطاولة أو المقاعد. يبقى الميل ظاهرًا للفاحص الفني في تقرير الاستلام النهائي.',
                    'korkuluk_aciklama' => 'يستمر سور خشبي بارتفاع متساوٍ على كل حافة من حواف الخطة، يحمي منطقة الجلوس من الرياح المارّة مع إبقاء الخصوصية في الداخل. يُثبَّت الارتفاع في الاستشارة وفق معايير السلامة، مع تفضيل المقاس الذي يسند المعصم أثناء الجلوس على رقم كتالوج ثابت. تُقفل نقاط التقاء بتثبيتات متقاطعة تمنع الاهتزاز. يُجهَّز السطح بطبقة واقية تحافظ على نسيج المادة الطبيعي — ناعم عند اللمس دون شظايا. تُرفع القواعد كي لا يصعد ماء الأرض إلى الجذوع، وتُختار كل القطع المعدنية مقاومة للصدأ. في الأماكن العائلية تُضبط المسافات الرأسية بحيث لا تعلق اليد، ويبقى خط القبضة العلوي مستقيمًا. حيث يلزم مرور الكرسي المتحرك يُفتح جانب. التنظيف بفرشاة ناعمة ومياه فاترة؛ فحص القواعد سنويًا. لا يسخن الصيف ولا يُثلج الشتاء. يمكن طلبه باللون عند الطلب. وعلى خطة 4x3 يرسم خطًا أمنيًا متصلًا ويؤطر الأثاث من الداخل. قناة مخفية أسفل السطح العلوي تستقبل شريطًا ضوئيًا أو رف نباتات دون مسامير ظاهرة. تُراجع وصلات الربط مرة كل موسم مع فحص عام للهيكل كاملاً دون إهمال الزوايا.',
                ],
            ],
            'KML-ALU-KAR-011' => [
                'tr' => [
                    'baslik' => 'Kare Alüminyum Kamelya 3x3 Belediye Meydanı',
                    'slug' => 'kare-aluminyum-kamelya-3x3-belediye-011',
                    'kisa_aciklama' => 'Kare alüminyum kamelya, 3×3 metre tabanıyla Belediye Meydanı için 7-8 kişilik dengeli bir oturma alanı sunar. Toz boyalı alüminyum profil öne çıkar; düz çatı yağmuru kontrollü tahliye eder. Elektrostatik toz boya, UV stabil ile dört mevsim kullanıma uygundur. Net ölçü ve montaj planı ücretsiz keşifte belirlenir; 5 yıl garantilidir.',
                    'detayli_aciklama' => '**TL;DR**
- 3×3 m (9 m²) kare taban; Belediye Meydanı için 7-8 kişilik ferah oturma alanı.
- toz boyalı alüminyum profil, Düz, kontrollü tahliye: dört mevsim kullanım, 5 yıl garanti.
- Şeffaf m² hesabı: 9 × 12.000 × 2.5 (Alüminyum) × 1 (kare) × 1.8 (belediye) = 486.000 TL.
- Net ölçü ve montaj planı ücretsiz keşifte sabitlenir (Pzt–Cmt 09:00–18:00).

## Teknik Özellikleri Nelerdir?

| Alan | Değer |
| --- | --- |
| Taban ölçüsü | 3×3 m — 9 m² |
| Form | Kare, dört kenar |
| Ana malzeme | toz boyalı alüminyum profil |
| Çatı | Düz, kontrollü tahliye |
| Korkuluk | alüminyum korkuluk, proje bazlı yükseklik |
| Yüzey işlemi | Elektrostatik toz boya, UV stabil |
| Bağlantı | Korozyona dayanıklı metal elemanlar |
| Garanti | 5 yıl |

## Kimler İçin?

Bu model, Belediye Meydanı ölçeğinde çalışan işletmeler ve site yönetimleri için tasarlandı. 9 m² kare taban, mobilyayı serbestçe yerleştirir: 7-8 kişilik bir düzen rahatça kurulur; yemek masası ile dinlenme köşesi yan yana yerleşir.

## Kaç Kişiliktir?

Kapasite, F15 onaylı belediye katsayısıyla hesaplanır: 9 m² ÷ 1,20 m²/kişi ≈ 7,50 — yani 7-8 kişi rahat oturur. Bu katsayı, kullanım yoğunluğuna göre seçilir; mahremiyet ve kol hareketi serbestliği, sıkışıklıktan önce gelir.

## Montaj Nasıl Yapılır?

Ürün sipariş üzerine üretilir. Ücretsiz keşifte zemin, giriş yönü ve varsa elektrik ihtiyacı değerlendirilir; net ölçü ile montaj planı bu görüşmede sabitlenir. Keşif randevuları Pzt–Cmt 09:00–18:00 arasındadır ve ücretsizdir.

## Bakımı Nasıl Yapılır?

Bakım, yılda bir yüzey kontrolü ve gerekirse koruyucu yenilemeden ibarettir. Elektrostatik toz boya, UV stabil. Çatı olukları ve tahliye açıklıkları yapraktan temizlenir; düz çatı üzerinde su birikintisi bırakılmaz. Korkuluk bağlantıları yılda bir sıkıştırılır.

## Neden Bu Model?

Bu model, Belediye Meydanı için doğru dengeyi kurar: 9 m² taban ne dar kalır ne de bakımı şişirir. Kare, dört kenar form, peyzaj çizgisine uyar; Düz, kontrollü tahliye yağmuru oturma alanından uzak tutar.

## Çatı Özellikleri Nelerdir?

Düz çatı, kare silueti ve bahçe çizgisiyle aynı düzlemde buluşur; yağmur suyu panel aralarındaki kontrollü kanallardan tahliye edilir ve oturma alanına damlamaz. toz boyalı alüminyum profil üzerinde su birikintisi oluşmaması için minik bir eğim kalıp içinde planlanır; bu eğim gözle görünmez ama tahliyeyi garanti eder. Rüzgâr yükü davranışı, proje ölçeğinde değerlendirilir ve alan, EN 13561 referansıyla tartışılan dış cephe elemanları mantığına göre keşifte netleşir. Düz yüzey, aydınlatma veya menü panosu montajına alan bırakır. Çatı bakımına yılda bir göz kontrolü yeterlidir; tahliye delikleri açık tutulur, yüzey hortumla yıkanır. Elektrostatik toz boya, UV stabil; ahşap çatının tersine emprenye döngüsü gerekmez. Kenar profilleri, suyun cepheye kontrollü inmesini sağlar ve leke izi bırakmaz. Gerektiğinde tahliye planı, keşif ekibiyle araziye göre konuşulur. Oluk çıkışı zemin kotunun üstünde tutulur; yüzey kuru kalır, su kontrollü iner. Kış aylarında kar yükü, düz planda eşit dağılır; tek noktaya toplanmaz. Gece aydınlatması eklenirse kablo tavan altından gizlenir, damla noktası dışarıda kalır. Kireç ve toz birikintisi hortum suyuyla birkaç dakikada akar; kimyasal gerekmez.

## Korkuluk ve Güvenlik Özellikleri Nelerdir?

alüminyum korkuluk, planın her kenarında eşit yükseklikte devam eder ve oturma alanını dışarıdan gelen rüzgâra karşı korurken içeride mahremiyet hissi bırakır. Yükseklik, güvenlik standartlarına ve proje koşullarına göre keşif sırasında netleşir; sabit bir katalog rakamı yerine, kullanıcının oturduğu anda bileğini destekleyen ölçü tercih edilir. Birleşim noktaları çapraz bağlantılarla kilitlenir, sallanma önlenir. Yüzey, malzemenin doğal dokusunu koruyan koruyucu kaplamayla hazırlanır; elle temas pürüzsüzdür, yonga bırakmaz. Korkuluk dip kısımları, zemine geçen suyu gövdeye taşımaması için yükseltilir ve bağlantı elemanları korozyona dayanıklı malzemeden seçilir. Çocuklu alanlarda dikey aralıklar, topun veya kolun sıkışmasını önleyecek şekilde düzenlenir; yaşlı sakinler için üst tutamak hattı düz ve kesintisizdir. Tekerlekli sandalye erişimi gereken giriş kenarında korkuluk açılıp geçiş bırakacak şekilde planlanır. Temizlikte yumuşak fırça ve ılık su yeterlidir; dip noktalar yılda bir kontrol edilir, gevşenen sıkma hemen yapılır. Malzeme, alternatife göre yazın ısınmaz, kışın soğuk tutmaz; dokusu bahçenin malzeme diliyle uyumludur. İstenirse boyanabilir; renk değişikliği teklif aşamasında belirlenir. Korkuluk, 3x3 planında kesintisiz bir güvenlik hattı çizer ve mobilya yerleşimini içeriden çerçeveler. Işık bandı veya saksı rafı istenirse üst profilin iç yüzüne gizli kanal açılır; görünür vida bırakılmaz.
',
                    'seo_baslik' => 'Kare Alüminyum Kamelya 3x3 Fiyatları 2026 | Kamelya',
                    'seo_aciklama' => 'Kare alüminyum kamelya 3x3: 9 m² alan, 7-8 kişilik belediye meydanı, düz çatı, 5 yıl garantili. Şeffaf m² fiyat listesi ve ücretsiz keşif randevusu.',
                    'seo_anahtar_kelimeler' => 'kare alüminyum kamelya, alüminyum kamelya fiyatları, 3x3 kamelya, belediye kamelyası',
                    'cati_tipi_aciklama' => 'Düz çatı, kare silueti ve bahçe çizgisiyle aynı düzlemde buluşur; yağmur suyu panel aralarındaki kontrollü kanallardan tahliye edilir ve oturma alanına damlamaz. toz boyalı alüminyum profil üzerinde su birikintisi oluşmaması için minik bir eğim kalıp içinde planlanır; bu eğim gözle görünmez ama tahliyeyi garanti eder. Rüzgâr yükü davranışı, proje ölçeğinde değerlendirilir ve alan, EN 13561 referansıyla tartışılan dış cephe elemanları mantığına göre keşifte netleşir. Düz yüzey, aydınlatma veya menü panosu montajına alan bırakır. Çatı bakımına yılda bir göz kontrolü yeterlidir; tahliye delikleri açık tutulur, yüzey hortumla yıkanır. Elektrostatik toz boya, UV stabil; ahşap çatının tersine emprenye döngüsü gerekmez. Kenar profilleri, suyun cepheye kontrollü inmesini sağlar ve leke izi bırakmaz. Gerektiğinde tahliye planı, keşif ekibiyle araziye göre konuşulur. Oluk çıkışı zemin kotunun üstünde tutulur; yüzey kuru kalır, su kontrollü iner. Kış aylarında kar yükü, düz planda eşit dağılır; tek noktaya toplanmaz. Gece aydınlatması eklenirse kablo tavan altından gizlenir, damla noktası dışarıda kalır. Kireç ve toz birikintisi hortum suyuyla birkaç dakikada akar; kimyasal gerekmez.',
                    'korkuluk_aciklama' => 'alüminyum korkuluk, planın her kenarında eşit yükseklikte devam eder ve oturma alanını dışarıdan gelen rüzgâra karşı korurken içeride mahremiyet hissi bırakır. Yükseklik, güvenlik standartlarına ve proje koşullarına göre keşif sırasında netleşir; sabit bir katalog rakamı yerine, kullanıcının oturduğu anda bileğini destekleyen ölçü tercih edilir. Birleşim noktaları çapraz bağlantılarla kilitlenir, sallanma önlenir. Yüzey, malzemenin doğal dokusunu koruyan koruyucu kaplamayla hazırlanır; elle temas pürüzsüzdür, yonga bırakmaz. Korkuluk dip kısımları, zemine geçen suyu gövdeye taşımaması için yükseltilir ve bağlantı elemanları korozyona dayanıklı malzemeden seçilir. Çocuklu alanlarda dikey aralıklar, topun veya kolun sıkışmasını önleyecek şekilde düzenlenir; yaşlı sakinler için üst tutamak hattı düz ve kesintisizdir. Tekerlekli sandalye erişimi gereken giriş kenarında korkuluk açılıp geçiş bırakacak şekilde planlanır. Temizlikte yumuşak fırça ve ılık su yeterlidir; dip noktalar yılda bir kontrol edilir, gevşenen sıkma hemen yapılır. Malzeme, alternatife göre yazın ısınmaz, kışın soğuk tutmaz; dokusu bahçenin malzeme diliyle uyumludur. İstenirse boyanabilir; renk değişikliği teklif aşamasında belirlenir. Korkuluk, 3x3 planında kesintisiz bir güvenlik hattı çizer ve mobilya yerleşimini içeriden çerçeveler. Işık bandı veya saksı rafı istenirse üst profilin iç yüzüne gizli kanal açılır; görünür vida bırakılmaz.',
                ],
                'en' => [
                    'baslik' => 'Square Aluminium Gazebo 3x3 Municipality Square',
                    'slug' => 'square-aluminium-gazebo-3x3-011',
                    'kisa_aciklama' => 'Square aluminium gazebo, 3×3 footprint for municipality squares. Powder coat with saltwater and UV resistance and flat roof keep four-season use simple. Seats 7 across 9 m². Measurements and install plan lock after the free survey (Mon–Sat 09:00–18:00). Five-year warranty.',
                    'detayli_aciklama' => '**TL;DR**
- 3×3 m (9 m²) Square, four sides floor; an 7-person seating area for municipality squares.
- Powder coat with saltwater and UV resistance, Flat, controlled drainage: four-season use, five-year warranty.
- Transparent m² maths: 9 × 3 USD × 2.5 × 1.0 × 1.8 = 121.50 USD.
- Final measurements and the installation plan are fixed at the free survey (Mon-Sat 09:00-18:00).

## What Are the Technical Specifications?

| Item | Value |
| --- | --- |
| Floor size | 3×3 m — 9 m² |
| Shape | Square, four sides |
| Main material | aluminium |
| Roof | Flat, controlled drainage |
| Railing | aluminium railing, height set per project |
| Surface | Powder coat with saltwater and UV resistance |
| Warranty | 5 years |

## Who Is It For?

This model is built for municipality operators and site teams that plan at garden scale.

## How Many People Does It Seat?

Seating follows the approved municipality squares ratio from F15: 9 m² ÷ 1.20 m² per person ≈ 7.50, so 7 people sit comfortably.

## How Is It Installed?

The product is made to order.

## How Is It Maintained?

Care is one surface check a year plus a protective refresh when needed.

## Why This Model?

This model balances scale and upkeep for municipality squares.

## What About the Roof?

The flat roof meets the modern silhouette and garden line on the same plane; rainwater drains through controlled channels between panels and never drips onto the seating zone. A tiny slope, invisible to the eye, is planned into the mould so drainage is guaranteed and no ponding forms. Wind behaviour is assessed at project scale and settled at the survey against external envelope logic referenced by EN 13561. The plane leaves room for lighting or menu-board mounts. Roof care needs one visual check a year — keep the holes open and rinse with a hose. Edge profiles guide water down the facade and leave no stain marks. Where needed, the drainage plan is discussed with the survey crew on site. The outlet stays above ground level so the surface remains dry. In winter the snow load spreads evenly across the plane instead of concentrating on one point. Cable runs for evening lighting hide under the ceiling; the drip line stays outside the seating circle. Dust and limescale rinse off with a hose; no chemicals are required.

## What About the Railing and Safety?

The aluminium railing runs at an even height along every edge of the plan, shielding the seating area from cross-wind while keeping privacy inside. Height is confirmed at the survey against safety standards, preferring the dimension that supports the wrist when seated over a fixed catalogue number. Joints lock with cross-braces so the frame does not sway. The surface keeps the material\'s natural grain — smooth to the touch, with no splinters. Railing feet are lifted so water never wicks into the posts; all fixings are chosen for corrosion resistance. Vertical gaps are spaced to prevent hands or balls from jamming, while the top grip line stays flat for elderly residents. Where wheelchair access is needed, one entrance edge opens and leaves a clear passage. Cleaning takes a soft brush and lukewarm water; check the feet yearly. The material doesn\'t overheat in summer or feel cold in winter, and its texture matches the garden\'s palette. The railing can be painted on request; colour is decided at quote stage. Along the 3×3 plan it draws an unbroken safety line. A hidden channel under the top rail accepts a light strip or planter rail without visible screws.
',
                    'seo_baslik' => 'Square Aluminium Gazebo 3×3 Price List 2026 | Kamelya',
                    'seo_aciklama' => '9 m² Square gazebo seats 7 with flat roof, five-year warranty. Transparent m² pricing and free on-site survey. Book a visit Monday–Saturday 09:00–18:00.',
                    'seo_anahtar_kelimeler' => 'square aluminium gazebo, aluminium gazebo prices, 3x3 gazebo, municipality gazebo',
                    'cati_tipi_aciklama' => 'The flat roof meets the modern silhouette and garden line on the same plane; rainwater drains through controlled channels between panels and never drips onto the seating zone. A tiny slope, invisible to the eye, is planned into the mould so drainage is guaranteed and no ponding forms. Wind behaviour is assessed at project scale and settled at the survey against external envelope logic referenced by EN 13561. The plane leaves room for lighting or menu-board mounts. Roof care needs one visual check a year — keep the holes open and rinse with a hose. Edge profiles guide water down the facade and leave no stain marks. Where needed, the drainage plan is discussed with the survey crew on site. The outlet stays above ground level so the surface remains dry. In winter the snow load spreads evenly across the plane instead of concentrating on one point. Cable runs for evening lighting hide under the ceiling; the drip line stays outside the seating circle. Dust and limescale rinse off with a hose; no chemicals are required.',
                    'korkuluk_aciklama' => 'The aluminium railing runs at an even height along every edge of the plan, shielding the seating area from cross-wind while keeping privacy inside. Height is confirmed at the survey against safety standards, preferring the dimension that supports the wrist when seated over a fixed catalogue number. Joints lock with cross-braces so the frame does not sway. The surface keeps the material\'s natural grain — smooth to the touch, with no splinters. Railing feet are lifted so water never wicks into the posts; all fixings are chosen for corrosion resistance. Vertical gaps are spaced to prevent hands or balls from jamming, while the top grip line stays flat for elderly residents. Where wheelchair access is needed, one entrance edge opens and leaves a clear passage. Cleaning takes a soft brush and lukewarm water; check the feet yearly. The material doesn\'t overheat in summer or feel cold in winter, and its texture matches the garden\'s palette. The railing can be painted on request; colour is decided at quote stage. Along the 3×3 plan it draws an unbroken safety line. A hidden channel under the top rail accepts a light strip or planter rail without visible screws.',
                ],
                'de' => [
                    'baslik' => 'QuadratischerAluminium--Pavillon 3x3 Stadtplatz',
                    'slug' => 'quadratischer-aluminium-pavillon-3x3-011',
                    'kisa_aciklama' => 'Quadratischer Aluminium-Pavillon 3x3 für Stadtplätze: pulverbeschichtetes Aluminiumprofil, flaches Dach mit kontrollierter Entwässerung und klarer Sitzordnung. Platz für 7-8 Personen auf 9 m². Maße und Montageplan werden nach dem kostenlosen Aufmaß fixiert (Mo-Sa 09:00-18:00). 5 Jahre Garantie inklusive.',
                    'detayli_aciklama' => '**Kurz gefasst**
- 3×3 m (9 m²) quadratisch Fläche; 7-8-Personen-Sitzplatz für Stadtplätze.
- pulverbeschichtetes Aluminiumprofil, Flach, kontrollierte Entwässerung: ganzjährige Nutzung, 5 Jahre Garantie.
- Transparente m²-Rechnung: 9 × 2,50 EUR × 2.5 × 1 × 1.8 = 101.25 EUR.
- Maße und Montageplan werden beim kostenlosen Aufmaß fixiert (Mo-Sa 09:00-18:00).

## Technische Daten

| Merkmal | Wert |
| --- | --- |
| Bodenmaß | 3×3 m — 9 m² |
| Form | Quadratisch, vier Seiten |
| Hauptmaterial | pulverbeschichtetes Aluminiumprofil |
| Dach | Flach, kontrollierte Entwässerung |
| Geländer | Aluminiumgeländer, Höhe projektbezogen |
| Oberfläche | Pulverbeschichtung — Salzwasser- und UV-Beständigkeit |
| Befestigung | Korrosionsfeste Metallteile |
| Garantie | 5 Jahre |

## Für wen ist er geeignet?

Dieses Modell ist für Stadtplätze und Hausverwaltungen gebaut, die im Gartenmaß planen. Der 9 m²-quadratisch-Boden lässt Möbel frei stehen: eine 7-8-Personen-Aufteilung passt mühelos; Esstisch und ruhige Ecke teilen sich den Platz. Die Oberfläche spart Reinigung in der Hochsaison. Gäste finden einen übersichtlichen Treffpunkt; das Aluminiumgeländer schützt die Sitzfläche an der Windseite. Schmale Höfe und Pläne passen zum Maß, ohne die Landschaftslinie zu brechen. Projekte mit Quartiersbudget vermeiden Kostenüberraschungen durch den Standardgrundriss. Drei offene Seiten rahmen den Blick; die vierte bleibt Service oder Fußgverkehr vorbehalten.

## Kapazität

Die Kapazität folgt dem freigegebenen Faktor aus F15: 9 m² ÷ 1,20 m² pro Person ≈ 7,50 — bequem sitzen also 7-8 Personen. Der Faktor folgt der Nutzungsdichte; Privatsphäre und Bewegungsfreiheit stehen vor Dichte. Die Aufteilung ist ideal für 7-8 Personen; größere Feste wechseln zu breiteren Modellen. Die endgültige Aufstellung wird beim Aufmaß mit dem Möbelplan geprüft.

## Montage

Das Modell wird auf Bestellung gefertigt. Das kostenlose Aufmaß prüft Untergrund, Zufahrt und Strombedarf und fixiert Maße und Montageplan in diesem Termin. Termine laufen Montag bis Samstag 09:00-18:00 ohne Kosten. Am Montagetag wird die Verankerung für Platte, verdichteten Boden oder Holzdeck gewählt. Material geht direkt vom Fahrzeug zum Aufbau; Verschnitt bleibt minimal. Nach Abschluss wird die Oberfläche gereinigt und das Datenblatt übergeben.

## Pflege

Pflege ist eine Oberflächenkontrolle pro Jahr plus Schutzschicht bei Bedarf. Pulverbeschichtung — Salzwasser- und UV-Beständigkeit. Rinnen und Drainageöffnungen werden von Laub befreit; das flaches Dach hält kein Stauwasser. Geländerbeschläge werden einmal jährlich nachgezogen. Die 5-Jahres-Garantie deckt Material- und Fertigungsfehler; Aufmaß und Montageplan bleiben auf der Garantiekarte.

## Warum dieses Modell?

Dieses Modell hält Massstab und Pflegeaufwand für Stadtplätze in Balance. Der Standardgrundriss macht das Angebot kalkulierbar; das Aufmaß fixiert die Maße vor der Fertigung, damit die Baustelle ohne Nacharbeit auskommt. Quadratisch, vier Seiten fügt sich in die Gartenlinie ein, Flach, kontrollierte Entwässerung hält Regen fern, und die Oberfläche braucht im Jahr nur eine Sichtkontrolle. Die Preisliste folgt derselben Formel wie der Konfigurator: Fläche mal Grundpreis mal Material-, Form- und NutzungsFaktor — ohne versteckte Zuschläge. Fünf Jahre Garantie und kostenloses Aufmaß von Montag bis Samstag 09:00-18:00 senken das Projektrisiko. Wer mehr Platz braucht, greift zum größeren Modell derselben Baureihe.',
                    'seo_baslik' => 'QuadratischerAluminium--Pavillon 3x3 Preise 2026 | Kamelya',
                    'seo_aciklama' => 'QuadratischerAluminium--Pavillon 3x3: 9 m², für 7-8, flaches Dach, 5 Jahre Garantie. Transparente m²-Preise und kostenloses Aufmaß buchen. Details auf der',
                    'seo_anahtar_kelimeler' => 'quadratisch aluminium Pavillon, aluminium Pavillon Preise, 3x3 Pavillon, Pavillon Stadtplatz',
                    'cati_tipi_aciklama' => 'Das flache Dach trifft die quadratisch Silhouette und die Gartenlinie auf derselben Ebene; Regenwasser läuft durch kontrollierte Kanäle zwischen den Paneln ab und tropft nie auf die Sitzfläche. Eine winzige, unsichtbare Neigung ist in die Form geplant, damit kein Wasser stehen bleibt und die Entwässerung garantiert ist. Das Windverhalten wird projektmäßig bewertet und beim Aufmaß an der Logik externer Fassadenelemente mit EN-13561-Bezug abgeglichen. Die Fläche lässt Platz für Leuchten oder Menüschilder. Zur Dachpflege genügt eine Sichtkontrolle pro Jahr. Die Oberfläche widersteht dem Klima; anders als Holzdächer braucht sie keine Imprägnierung. Kantenprofile führen das Wasser kontrolliert an der Fassade hinab und hinterlassen keine Flecken. Bei Bedarf wird der Entwässerungsplan mit dem Aufmaß-Team vor Ort besprochen. Die Ablauföffnung bleibt über dem Bodenniveau. Im Winter verteilt sich die Schneelast gleichmäßig auf der Fläche statt an einem Punkt. Kabelwege für Abendbeleuchtung laufen unter der Decke; die Tropfzone bleibt außen. Kalk und Stahlspäne spülen mit dem Schlauch ab; Chemie ist nicht nötig.',
                    'korkuluk_aciklama' => 'Das Aluminiumgeländer läuft an jeder Kante des Grundrisses in gleicher Höhe weiter und schützt die Sitzfläche vor Durchzug, während innen Privatsphäre bleibt. Die Höhe wird beim Aufmaß anhand von Sicherheitsnormen bestätigt; statt einer festen Katalogzahl wird das Maß gewählt, das das Handgelenk im Sitzen stützt. Verbindungen werden mit Kreuzverband verriegelt. Die Oberfläche erhält eine Schutzschicht, die die natürliche Struktur bewahrt. Die Füße werden angehoben, damit bodennahes Wasser nicht in die Pfosten steigt; alle Beschläge sind korrosionsfest. In Familienbereichen balancieren Vertikalabstände Kindersicherheit; die obere Griffleitung bleibt gerade. Wo ein Rollstuhlvorgang nötig ist, öffnet sich eine Ecke und lässt freie Passage. Zur Reinigung genügen weiche Bürste und lauwarmes Wasser; Füße einmal jährlich prüfen. Das Material wird im Sommer nicht heiß und im Winter nicht eiskalt. Auf Wunsch anmalbar; die Farbe wird beim Angebot festgelegt. Auf dem 3x3-Grundriss zieht es eine durchgehende Sicherheitslinie und fasst die Möbel von innen ein. Ein versteckter Kanal unter der Oberkante nimmt Lichtband oder Pflanzschiene auf, ohne sichtbare Schrauben.',
                ],
                'fr' => [
                    'baslik' => 'Gazebo Carré Aluminium 3x3 Place de Ville',
                    'slug' => 'gazebo-carre-aluminium-3x3-011',
                    'kisa_aciklama' => 'Gazebo carré Aluminium de 3×3 m pour places de ville : profil aluminium thermolaqué, toit plat à évacuation contrôlée et assise dégagée. 7-8 personnes sur 9 m² pour un usage toute saison. Dimensions et plan de pose fixés après l’étude gratuite (lun.-sam. 09h00-18h00). Garantie 5 ans incluse.',
                    'detayli_aciklama' => '**En bref**
- Sol carré 3×3 m (9 m²) ; espace pour 7-8 personnes en places de ville.
- profil aluminium thermolaqué, Plat, drainage contrôlé : usage toute saison, garantie 5 ans.
- Calcul m² transparent : 9 × 2,50 EUR × 2.5 × 1 × 1.8 = 101.25 EUR.
- Dimensions et plan de pose fixés pendant l’étude gratuite (lun.-sam. 09h00-18h00).

## Caractéristiques techniques

| Caractéristique | Valeur |
| --- | --- |
| Emprise au sol | 3×3 m — 9 m² |
| Forme | Carré, quatre côtés |
| Matériau principal | profil aluminium thermolaqué |
| Toit | Plat, drainage contrôlé |
| Garde-corps | garde-corps en aluminium, hauteur définie par projet |
| Surface | Thermolaquage — résistance eau salée et UV |
| Fixations | Pièces métalliques résistantes à la corrosion |
| Garantie | 5 ans |

## Pour qui ?

Ce modèle est conçu pour les places de ville et syndics qui planifient à l’échelle du jardin. Le sol carré de 9 m² laisse le mobilier libre : une organisation 7-8 personnes tient sans effort ; table à manger et coin calme se partagent l’espace. La surface allège le nettoyage en haute saison. Les clients trouvent un point de rendez-vous ombragé ; le garde-corps en aluminium protège l’assise côté vent. Cours étroits et plans s’accordent à la mesure sans casser la ligne de paysage. Les projets de budget de quartier évitent les surprises de coût. Trois côtés ouverts cadrent la vue ; le quatrième reste au service ou au flux piéton.

## Capacité

La capacité suit le facteur approuvé en F15 : 9 m² ÷ 1,20 m² par personne ≈ 7,50 — soit confortablement 7-8 personnes. Le facteur suit l’intensité d’usage ; intimité et aisance passent avant la densité. L’organisation est idéale pour 7-8 personnes ; les réceptions plus larges passent à des modèles plus amples. Le placement final se vérifie pendant l’étude avec le plan de mobilier.

## Pose

Le gazebo est fabriqué sur commande. L’étude gratuite examine le sol, l’accès et le besoin électrique, et fixe dimensions et plan de pose. Les créneaux vont du lundi au samedi 09h00-18h00 sans frais. Le jour de pose, l’ancrage s’adapte à la dalle, au sol compacté ou au deck bois. Les matériaux passent du véhicule au chantier ; les chutes restent minimales. À la fin, la surface est nettoyée et la fiche d’usage remise.

## Entretien

L’entretien se limite à un contrôle de surface par un renouvellement protecteur si besoin. Thermolaquage — résistance eau salée et UV. Gouttières et orifices d’évacuation sont dégagés des feuilles ; le toit plat ne garde jamais d’eau stagnante. Les fixations du garde-corps se resserrent une fois par an. La garantie 5 ans couvre défauts de fabrication et de matière ; l’étude et le plan de pose restent sur la carte de garantie.

## Pourquoi ce modèle ?

Ce modèle équilibre échelle et entretien pour les places de ville ; l’empreinte standard garde le devis prévisible et l’étude fixe les dimensions avant fabrication.',
                    'seo_baslik' => 'Gazebo Carré Aluminium 3x3 Prix et Mesures 2026 | Kamelya',
                    'seo_aciklama' => 'Gazebo carré aluminium 3x3 : 9 m², 7-8 places, toit plat, garantie 5 ans. Prix au m² transparents, réservation d’une étude gratuite. Détails sur la',
                    'seo_anahtar_kelimeler' => 'gazebo carré aluminium, gazebo aluminium prix, gazebo 3x3, gazebo municipalité',
                    'cati_tipi_aciklama' => 'Le toit plat rejoint la silhouette carré et la ligne du jardin sur le même plan ; l’eau s’évacue par des canaux contrôlés entre panneaux et ne tombe jamais sur l’assise. Une pente minuscule, invisible à l’œil, est prévue dans le moule pour qu’aucune flaque ne subsiste. Le comportement du vent s’évalue à l’échelle du projet et se fixe à l’étude selon la logique des éléments d’enveloppe référencés par l’EN 13561. La surface laisse la place aux luminaires ou enseignes. L’entretien demande un contrôle visuel par an. Le revêtement résiste au climat ; contrairement au bois, aucun traitement n’est nécessaire. Les profils de bord guident l’eau vers la façade sans tache. Si besoin, le plan d’évacuation se discute avec l’équipe d’étude. La sortie reste au-dessus du sol. En hiver la neige se répartit également sur la forme plane. Les câbles d’éclairage du soir passent sous le plafond ; la ligne de gouttes reste hors de l’assise. La poussière et le calcaire partent au tuyau en quelques minutes, sans produit chimique.',
                    'korkuluk_aciklama' => 'Le garde-corps en aluminium court à hauteur égale sur chaque arête, protège l’assise des traversants et garde l’intimité. Sa hauteur se confirme à l’étude selon les normes, préférant la mesure qui soutient le poignet au chiffre de catalogue. Les assemblages se verrouillent par croisements. La surface reçoit une finition protectrice qui conserve le grain. Les pieds sont surélevés pour que l’eau du sol ne remonte pas ; les pièces d’attache résistent à la corrosion. Dans les espaces familiaux les entraxes empêchent les mains de se coincer ; la ligne de saisie reste droite. Là où le fauteuil est requis, un côté s’ouvre. Nettoyage : brosse douce et eau tiède ; contrôle annuel des pieds. Le matériau ne chauffe ni ne givre. Peignable sur demande. Sur le plan 3x3 il trace une ligne continue et cadre le mobilier de l’intérieur. Un canal discret sous le rail supérieur accueille bandeau lumineux ou corniche à plantes, sans vis apparente.',
                ],
                'it' => [
                    'baslik' => 'Gazebo Quadrato Alluminio 3x3 Piazza Comunale',
                    'slug' => 'gazebo-quadrato-alluminio-3x3-011',
                    'kisa_aciklama' => 'Gazebo quadrato in Alluminio da 3×3 m per piazze comunali: profilo in alluminio verniciato a polvere, tetto piatto a scarico controllato. 7-8 persone su 9 m². Misure e piano di posa fissati dopo il sopralluogo gratuito (lun.-sab. 09:00-18:00). Garanzia 5 anni.',
                    'detayli_aciklama' => '**In breve**
- Pavimento quadrato 3×3 m (9 m²) ; zona seduta per 7-8 persone in piazze comunali.
- profilo in alluminio verniciato a polvere, Piatto, scarico controllato : uso tutto l’anno, garanzia 5 anni.
- Calcolo m² trasparente : 9 × 2,50 EUR × 2.5 × 1 × 1.8 = 101.25 EUR.
- Misure e piano di posa fissati durante il sopralluogo gratuito (lun.-sab. 09:00-18:00).

## Specifiche tecniche

| Voce | Valore |
| --- | --- |
| Impronta a terra | 3×3 m — 9 m² |
| Forma | Quadrato, quattro lati |
| Materiale principale | profilo in alluminio verniciato a polvere |
| Tetto | Piatto, scarico controllato |
| Parapetto | parapetto in alluminio, altezza definita dal progetto |
| Superficie | Verniciatura a polvere — resistenza a sale e UV |
| Fissaggi | Parti metalliche resistenti alla corrosione |
| Garanzia | 5 anni |

## Per chi è?

Questo modello è pensato per piazze comunali e amministratori che progettano alla scala del giardino. Il pavimento quadrato da 9 m² lascia libero l’arredamento: una disposizione 7-8 persone entra con naturalezza; tavolo da pranzo e angolo relax condividono lo spazio. La superficie alleggerisce la pulizia in alta stagione. Gli ospiti trovano un punto d’incontro ombreggiato; il parapetto in alluminio ripara la seduta dal vento. Cortili stretti e piani si adattano alla misura senza spezzare la linea del paesaggio. I progetti con budget di quartiere evitano sorprese di costo. Tre lati aperti inquadrano la vista; il quarto resta a servizio o flusso pedonale.

## Capienza

La capienza segue il fattore approvato in F15: 9 m² ÷ 1,20 m² a persona ≈ 7,50 — quindi 7-8 persone comodamente. Il fattore segue l’intensità d’uso; privacy e libertà di movimento vengono prima della densità. La disposizione è ideale per 7-8 persone ; le feste più ampie passano a modelli più ampi. La posa finale si verifica al sopralluogo con il piano d’arredo.

## Posa

Il gazebo è prodotto su ordinazione. Il sopralluogo gratuito valuta terreno, accesso ed eventuale bisogno elettrico, e fissa misure e piano di posa. Gli appuntamenti vanno da lunedì a sabato 09:00-18:00 senza costi. Il giorno della posa si sceglie l’ancoraggio per la lastra, il terreno compatto o il deck in legno. I materiali passano dal veicolo al cantiere; gli scarti restano minimi. Al termine la superficie viene pulita e consegnata la scheda d’uso.

## Manutenzione

La manutenzione è un controllo di superficie all’anno e un rinnovo protettivo se serve. Verniciatura a polvere — resistenza a sale e UV. Gronde e bocchette si liberano dalle foglie; il tetto piatto non trattiene acqua. Le fissaggi del parapetto si stringono una volta l’anno. La garanzia 5 anni copre difetti di fabbricazione e materiale; sopralluogo e piano restano sulla garanzia.

## Perché questo modello?

Questo modello bilancia scala e manutenzione per piazze comunali. L’impronta standard tiene il preventivo prevedibile e il sopralluogo fissa le misure prima della produzione, così il cantiere procede senza rifacimenti. Quadrato, quattro lati si adatta alla linea del giardino, Piatto, scarico controllato allontana la pioggia dalla seduta, e la superficie richiede un solo controllo visivo all’anno. Il listino segue la stessa formula del configuratore: metri quadri per prezzo base per fattori di materiale, forma e uso — senza sorprese. Garanzia di 5 anni e sopralluogo gratuito da lunedì a sabato 09:00-18:00 riducono il rischio del progetto.',
                    'seo_baslik' => 'Gazebo Quadrato Alluminio 3x3 Prezzi e Misure 2026 | Kamelya',
                    'seo_aciklama' => 'Gazebo quadrato alluminio 3x3: 9 m², 7-8 posti, tetto piatto, garanzia 5 anni. Prezzi al m² trasparenti, sopralluogo gratuito su richiesta. Dettagli in',
                    'seo_anahtar_kelimeler' => 'gazebo quadrato alluminio, gazebo alluminio prezzi, gazebo 3x3, gazebo comune',
                    'cati_tipi_aciklama' => 'Il tetto piatto incontra la silhouette quadrato e la linea del giardino sullo stesso piano; l’acqua piovana si drena attraverso canali controllati tra i pannelli e non gocciola mai sulla seduta. Una pendenza minuscola, invisibile, è prevista nello stampo perché non resti pozzanghera. Il comportamento al vento si valuta a scala di progetto e si definisce al sopralluogo con riferimento EN 13561. La superficie lascia spazio a luci o tabelloni. La manutenzione richiede un controllo visivo all’anno. Il rivestimento resiste al clima; a differenza del legno non serve trattamento. I profili di bordo guidano l’acqua verso la facciata senza macchie. Se serve, il piano di scarico si discute con l’equipaggio. La bocchetta resta sopra il suolo. D’inverno la neve si riparte sul piano. I cavi per l’illuminazione serale passano sotto il soffitto; la linea di gocce resta fuori. Polvere e calcare si sciolgono al tubo in pochi minuti, senza prodotti chimici.',
                    'korkuluk_aciklama' => 'Il parapetto in alluminio prosegue ad altezza uguale su ogni spigolo, ripara la seduta dai venti traversi e mantiene la riservatezza. L’altezza si conferma al sopralluogo secondo le norme, preferendo la misura che sorregge il polso a un numero fisso di catalogo. Le giunzioni si bloccano con incroci. La superficie riceve una finitura protettiva che conserva la venatura. I piedi sono sollevati perché l’acqua del terreno non risalga; le ferramenta sono a prova di corrosione. Negli spazi familiari gli interassi impediscono che mani restino incastrate; la linea di presa resta dritta. Dove serve la sedia a rotelle, un lato si apre. Pulizia : spazzola morbida e acqua tiepida ; controllo annuale dei piedi. Il materiale non scalda né gela. Colorabile a richiesta. Sul piano 3x3 disegna una linea continua e incornicia l’arredo dall’interno. Un canale discreto sotto il binario superiore ospita fascia luminosa o mensola per piante, senza viti a vista.',
                ],
                'ar' => [
                    'baslik' => 'كوش مربع ألمنيوم 3x3 لـساحة بلدية',
                    'slug' => 'square-aluminium-kush-3x3-011',
                    'kisa_aciklama' => 'كوش مربع ألمنيوم بمقاس 3×3 متر لـساحات البلديات: بروفايل ألمنيوم مطلي بالبودرة، سقف مستوي بتصريف مضبوط ومساحة جلوس مريحة. يتسع لـ7-8 أشخاص على 9 م² لاستخدام على مدار السنة. تُثبَّت المقاسات وخطة التركيب بعد الاستشارة المجانية (الاثنين–السبت 09:00–18:00). ضمان 5 سنوات كامل.',
                    'detayli_aciklama' => '**باختصار**
- أرضية مربع 3×3 م (9 م²)؛ مساحة جلوس لـ7-8 أشخاص في ساحات البلديات.
- بروفايل ألمنيوم مطلي بالبودرة، مستوي، تصريف مضبوط: استخدام على مدار السنة، ضمان 5 سنوات.
- حساب شفاف للمتر: 9 × 3 USD × 2.5 × 1 × 1.8 = 121.5 USD.
- تُثبَّت المقاسات وخطة التركيب في الاستشارة المجانية (الاثنين–السبت 09:00–18:00).

## المواصفات التقنية

| البند | القيمة |
| --- | --- |
| مقاس الأرضية | 3×3 م — 9 م² |
| الشكل | مربع، أربعة أضلاع |
| المادة الأساسية | بروفايل ألمنيوم مطلي بالبودرة |
| السقف | مستوي، تصريف مضبوط |
| السور | سور ألمنيوم، الارتفاع حسب المشروع |
| السطح | طلاء بودرة — مقاومة الماء المالح والأشعة |
| التثبيت | قطع معدنية مقاومة للصدأ |
| الضمان | 5 سنوات |

## لمن صُمم؟

هذا الموديل لـساحات البلديات وإدارات تخطط على مقياس الحديقة. الأرضية مربع بمساحة 9 م² تترك الأثاث حرًا: ترتيب 7-8 أشخاص يدخل بيسر؛ طاولة الطعام وركن الهدوء يتقاسمان المساحة. السطح يخفف التنظيف في الموسم الذروة. الزائدون يجدون نقطة لقاء مظلولة؛ وسور ألمنيوم يحمي منطقة الجلوس من جهة الرياح. الأفنية الضيقة والخطط تتلاءم مع المقاس دون كسر خط المشهد. المشاريع ذات ميزانية الحي تتجنّب مفاجآت التكلفة. ثلاثة وجوه مفتوحة تؤطر المشهد؛ والرابع يبقى للخدمة أو حركة المشاة.

## السعة

تُحسب السعة ومعامل المعتمد من F15: 9 م² ÷ 1.20 م² للشخص ≈ 7.50 — أي 7-8 أشخاص براحة. المعامل يتبع كثافة الاستخدام؛ الخصوصية وحرية الحركة تأتي قبل الازدحام. الترتيب مثالي لـ7-8 أشخاص؛ والتجمعات الأكبر تنتقل لمقاسات أوسع. يُتحقق من التوضع النهائي أثناء الاستشارة مع خطة الأثاث.

## التركيب

يُصنع الموديل عند الطلب. في الاستشارة المجانية يُفحص الأرضية واتجاه الدخول وحاجة الكهرباء، وتُثبَّت المقاسات وخطة التركيب في ذلك الموعد. المواعيد من الاثنين إلى السبت 09:00–18:00 بدون رسوم. يوم التركيب تُختار طريقة التثبيت حسب اللوحة أو التربة المضغوطة أو السطح الخشبي. تُنقل المواد مباشرة من السيارة؛ ويبقى الهدر ضئيلًا. بعد الانتهاء يُنظَّف السطح وتُسلَّم ورقة الاستخدام.

## الصيانة

الصيانة فحص سطحي سنوي وتجديد واقع عند الحاجة. طلاء بودرة — مقاومة الماء المالح والأشعة. تُنظَّف المرزبات وفتحات التصريف من الأوراق؛ وسقف مستوي لا يترك ماءً راكدًا. تُشدّ مثبتات السور مرة في السنة. ضمان 5 سنوات يشمل عيوب التصنيع والمواد؛ والاستشارة وخطة التركيب تبقى في بطاقة الضمان.

## لماذا هذا الموديل؟

يوازن هذا الموديل بين المقياس وجدول الصيانة لـساحات البلديات؛ المقاس القياسي يبقي عرض السعر قابلًا للتوقع، والاستشارة تثبت المقاسات قبل بدء التصنيع حتى لا يُعاد العمل في الموقع. مربع، أربعة أضلاع يلائم خط المشهد، ومستوي، تصريف مضبوط يبعد المطر عن منطقة الجلوس، والسطح لا يحتاج إلا فحصًا بصريًا واحدًا في السنة. قائمة الأسعار تتبع المعادلة نفسها في الأداة: متر مربع في سعر الأساس في عوامل المادة والشكل والاستخدام — دون رسوم خفية. خمس سنوات ضمان واستشارة مجانية من الاثنين إلى السبت 09:00–18:00 تخفض مخاطر المشروع. الفرق بين هذا الموديل والمقاس المجاور يظهر في جدول السعة لا في بنود مخفية. من يحتاج مساحة أكبر ينتقل إلى الموديل الأكبر من العائلة نفسها، ومن يملك أرضية أضيق يبدأ بالمربع الصغير ثم يرقّى لاحقًا دون تغيير لغة العرض.',
                    'seo_baslik' => 'كوش مربع ألمنيوم 3x3 أسعار 2026 | Kamelya',
                    'seo_aciklama' => 'كوش مربع ألمنيوم 3x3: 9 م²، 7-8 أشخاص، سقف مستوي، ضمان 5 سنوات. أسعار المتر وحجز استشارة.',
                    'seo_anahtar_kelimeler' => 'كوش مربع ألمنيوم, أسعار الكوش ألمنيوم, كوش 3x3, كوش البلديات',
                    'cati_tipi_aciklama' => 'يلتقي السقف المستوي مع سيلويت مربع وخط الحديقة على المستوى نفسه؛ تُصرف مياه الأمطار عبر قنوات مضبوطة بين الألواح ولا تتقاطر أبدًا على منطقة الجلوس. يُخطط ميل صغير غير مرئي داخل القالب كي لا تتكوّن بركة ويكون التصريف مضمونًا. يُقيَّم سلوك الرياح على مستوى المشروع ويُحسم في الاستشارة وفق منطق عناصر الغلاف الخارجي المرجعية (EN 13561). تترك السطح مساحة للإضاءة أو لوحات القوائم. صيانة السقف فحص بصري سنوي. تكسية السطح تصمد أمام المناخ؛ بخلاف الخشب لا تحتاج معالجة. تقود الحواف المائية إلى الواجهة بشكل مضبوط دون أن تترك بقعًا. وعند الحاجة يُناقش خطة التصريف مع فريق الاستشارة في الموقع. تبقى فتحة التصريف فوق سطح الأرض. وفي الشتاء تتوزع حملة الثلج بالتساوي على السطح بدل أن تتركز في نقطة واحدة. مسارات كابلات الإضاءة المسائية تمر تحت السقف ويبقى خط التقطير خارج دائرة الجلوس. الغبار والترسّبات تُشطف بالخرطوم في دقائق دون مواد كيميائية. الحواف المعدنية تحمل شرائط تجميع مقاومة للصدأ ولا تترك صدأً على الأرضية.',
                    'korkuluk_aciklama' => 'يستمر سور ألمنيوم بارتفاع متساوٍ على كل حافة من حواف الخطة، يحمي منطقة الجلوس من الرياح المارّة مع إبقاء الخصوصية في الداخل. يُثبَّت الارتفاع في الاستشارة وفق معايير السلامة، مع تفضيل المقاس الذي يسند المعصم أثناء الجلوس على رقم كتالوج ثابت. تُقفل نقاط التقاء بتثبيتات متقاطعة تمنع الاهتزاز. يُجهَّز السطح بطبقة واقية تحافظ على نسيج المادة الطبيعي — ناعم عند اللمس دون شظايا. تُرفع القواعد كي لا يصعد ماء الأرض إلى الجذوع، وتُختار كل القطع المعدنية مقاومة للصدأ. في الأماكن العائلية تُضبط المسافات الرأسية بحيث لا تعلق اليد، ويبقى خط القبضة العلوي مستقيمًا. حيث يلزم مرور الكرسي المتحرك يُفتح جانب. التنظيف بفرشاة ناعمة ومياه فاترة؛ فحص القواعد سنويًا. لا يسخن الصيف ولا يُثلج الشتاء. يمكن طلبه باللون عند الطلب. وعلى خطة 3x3 يرسم خطًا أمنيًا متصلًا ويؤطر الأثاث من الداخل. قناة مخفية أسفل السطح العلوي تستقبل شريطًا ضوئيًا أو رف نباتات دون مسامير ظاهرة. تُراجع وصلات الربط مرة كل موسم مع فحص عام للهيكل كاملاً دون إهمال الزوايا.',
                ],
            ],
            'KML-ALU-MOD-012' => [
                'tr' => [
                    'baslik' => 'Modern Alüminyum Kamelya 6x4 Otel Bahçesi',
                    'slug' => 'modern-aluminyum-kamelya-6x4-otel-012',
                    'kisa_aciklama' => 'Modern alüminyum kamelya, 6×4 metre tabanıyla Otel Bahçesi için 8-9 kişilik dengeli bir oturma alanı sunar. Toz boyalı alüminyum profil öne çıkar; düz çatı yağmuru kontrollü tahliye eder. Elektrostatik toz boya, UV stabil ile dört mevsim kullanıma uygundur. Net ölçü ve montaj planı ücretsiz keşifte belirlenir; 5 yıl garantilidir.',
                    'detayli_aciklama' => '**TL;DR**
- 6×4 m (24 m²) modern taban; Otel Bahçesi için 8-9 kişilik ferah oturma alanı.
- toz boyalı alüminyum profil, Düz, kontrollü tahliye: dört mevsim kullanım, 5 yıl garanti.
- Şeffaf m² hesabı: 24 × 12.000 × 2.5 (Alüminyum) × 1.5 (modern) × 1.6 (otel) = 1.728.000 TL.
- Net ölçü ve montaj planı ücretsiz keşifte sabitlenir (Pzt–Cmt 09:00–18:00).

## Teknik Özellikleri Nelerdir?

| Alan | Değer |
| --- | --- |
| Taban ölçüsü | 6×4 m — 24 m² |
| Form | Modern, geniş açıklık |
| Ana malzeme | toz boyalı alüminyum profil |
| Çatı | Düz, kontrollü tahliye |
| Korkuluk | alüminyum korkuluk, proje bazlı yükseklik |
| Yüzey işlemi | Elektrostatik toz boya, UV stabil |
| Bağlantı | Korozyona dayanıklı metal elemanlar |
| Garanti | 5 yıl |

## Kimler İçin?

Bu model, Otel Bahçesi ölçeğinde çalışan işletmeler ve site yönetimleri için tasarlandı. 24 m² modern taban, mobilyayı serbestçe yerleştirir: 8-9 kişilik bir düzen rahatça kurulur; yemek masası ile dinlenme köşesi yan yana yerleşir.

## Kaç Kişiliktir?

Kapasite, F15 onaylı otel katsayısıyla hesaplanır: 24 m² ÷ 2,80 m²/kişi ≈ 8,57 — yani 8-9 kişi rahat oturur. Bu katsayı, kullanım yoğunluğuna göre seçilir; mahremiyet ve kol hareketi serbestliği, sıkışıklıktan önce gelir.

## Montaj Nasıl Yapılır?

Ürün sipariş üzerine üretilir. Ücretsiz keşifte zemin, giriş yönü ve varsa elektrik ihtiyacı değerlendirilir; net ölçü ile montaj planı bu görüşmede sabitlenir. Keşif randevuları Pzt–Cmt 09:00–18:00 arasındadır ve ücretsizdir.

## Bakımı Nasıl Yapılır?

Bakım, yılda bir yüzey kontrolü ve gerekirse koruyucu yenilemeden ibarettir. Elektrostatik toz boya, UV stabil. Çatı olukları ve tahliye açıklıkları yapraktan temizlenir; düz çatı üzerinde su birikintisi bırakılmaz. Korkuluk bağlantıları yılda bir sıkıştırılır.

## Neden Bu Model?

Bu model, Otel Bahçesi için doğru dengeyi kurar: 24 m² taban ne dar kalır ne de bakımı şişirir. Modern, geniş açıklık form, peyzaj çizgisine uyar; Düz, kontrollü tahliye yağmuru oturma alanından uzak tutar.

## Çatı Özellikleri Nelerdir?

Düz çatı, modern silueti ve bahçe çizgisiyle aynı düzlemde buluşur; yağmur suyu panel aralarındaki kontrollü kanallardan tahliye edilir ve oturma alanına damlamaz. toz boyalı alüminyum profil üzerinde su birikintisi oluşmaması için minik bir eğim kalıp içinde planlanır; bu eğim gözle görünmez ama tahliyeyi garanti eder. Rüzgâr yükü davranışı, proje ölçeğinde değerlendirilir ve alan, EN 13561 referansıyla tartışılan dış cephe elemanları mantığına göre keşifte netleşir. Düz yüzey, aydınlatma veya menü panosu montajına alan bırakır. Çatı bakımına yılda bir göz kontrolü yeterlidir; tahliye delikleri açık tutulur, yüzey hortumla yıkanır. Elektrostatik toz boya, UV stabil; ahşap çatının tersine emprenye döngüsü gerekmez. Kenar profilleri, suyun cepheye kontrollü inmesini sağlar ve leke izi bırakmaz. Gerektiğinde tahliye planı, keşif ekibiyle araziye göre konuşulur. Oluk çıkışı zemin kotunun üstünde tutulur; yüzey kuru kalır, su kontrollü iner. Kış aylarında kar yükü, düz planda eşit dağılır; tek noktaya toplanmaz. Gece aydınlatması eklenirse kablo tavan altından gizlenir, damla noktası dışarıda kalır. Kireç ve toz birikintisi hortum suyuyla birkaç dakikada akar; kimyasal gerekmez.

## Korkuluk ve Güvenlik Özellikleri Nelerdir?

alüminyum korkuluk, planın her kenarında eşit yükseklikte devam eder ve oturma alanını dışarıdan gelen rüzgâra karşı korurken içeride mahremiyet hissi bırakır. Yükseklik, güvenlik standartlarına ve proje koşullarına göre keşif sırasında netleşir; sabit bir katalog rakamı yerine, kullanıcının oturduğu anda bileğini destekleyen ölçü tercih edilir. Birleşim noktaları çapraz bağlantılarla kilitlenir, sallanma önlenir. Yüzey, malzemenin doğal dokusunu koruyan koruyucu kaplamayla hazırlanır; elle temas pürüzsüzdür, yonga bırakmaz. Korkuluk dip kısımları, zemine geçen suyu gövdeye taşımaması için yükseltilir ve bağlantı elemanları korozyona dayanıklı malzemeden seçilir. Çocuklu alanlarda dikey aralıklar, topun veya kolun sıkışmasını önleyecek şekilde düzenlenir; yaşlı sakinler için üst tutamak hattı düz ve kesintisizdir. Tekerlekli sandalye erişimi gereken giriş kenarında korkuluk açılıp geçiş bırakacak şekilde planlanır. Temizlikte yumuşak fırça ve ılık su yeterlidir; dip noktalar yılda bir kontrol edilir, gevşenen sıkma hemen yapılır. Malzeme, alternatife göre yazın ısınmaz, kışın soğuk tutmaz; dokusu bahçenin malzeme diliyle uyumludur. İstenirse boyanabilir; renk değişikliği teklif aşamasında belirlenir. Korkuluk, 6x4 planında kesintisiz bir güvenlik hattı çizer ve mobilya yerleşimini içeriden çerçeveler. Işık bandı veya saksı rafı istenirse üst profilin iç yüzüne gizli kanal açılır; görünür vida bırakılmaz.
',
                    'seo_baslik' => 'Modern Alüminyum Kamelya 6x4 Fiyatları 2026 | Kamelya',
                    'seo_aciklama' => 'Modern alüminyum kamelya 6x4: 24 m² alan, 8-9 kişilik otel bahçesi, düz çatı, 5 yıl garantili. Şeffaf m² fiyat listesi ve ücretsiz keşif randevusu.',
                    'seo_anahtar_kelimeler' => 'modern alüminyum kamelya, alüminyum kamelya fiyatları, 6x4 kamelya, otel kamelyası',
                    'cati_tipi_aciklama' => 'Düz çatı, modern silueti ve bahçe çizgisiyle aynı düzlemde buluşur; yağmur suyu panel aralarındaki kontrollü kanallardan tahliye edilir ve oturma alanına damlamaz. toz boyalı alüminyum profil üzerinde su birikintisi oluşmaması için minik bir eğim kalıp içinde planlanır; bu eğim gözle görünmez ama tahliyeyi garanti eder. Rüzgâr yükü davranışı, proje ölçeğinde değerlendirilir ve alan, EN 13561 referansıyla tartışılan dış cephe elemanları mantığına göre keşifte netleşir. Düz yüzey, aydınlatma veya menü panosu montajına alan bırakır. Çatı bakımına yılda bir göz kontrolü yeterlidir; tahliye delikleri açık tutulur, yüzey hortumla yıkanır. Elektrostatik toz boya, UV stabil; ahşap çatının tersine emprenye döngüsü gerekmez. Kenar profilleri, suyun cepheye kontrollü inmesini sağlar ve leke izi bırakmaz. Gerektiğinde tahliye planı, keşif ekibiyle araziye göre konuşulur. Oluk çıkışı zemin kotunun üstünde tutulur; yüzey kuru kalır, su kontrollü iner. Kış aylarında kar yükü, düz planda eşit dağılır; tek noktaya toplanmaz. Gece aydınlatması eklenirse kablo tavan altından gizlenir, damla noktası dışarıda kalır. Kireç ve toz birikintisi hortum suyuyla birkaç dakikada akar; kimyasal gerekmez.',
                    'korkuluk_aciklama' => 'alüminyum korkuluk, planın her kenarında eşit yükseklikte devam eder ve oturma alanını dışarıdan gelen rüzgâra karşı korurken içeride mahremiyet hissi bırakır. Yükseklik, güvenlik standartlarına ve proje koşullarına göre keşif sırasında netleşir; sabit bir katalog rakamı yerine, kullanıcının oturduğu anda bileğini destekleyen ölçü tercih edilir. Birleşim noktaları çapraz bağlantılarla kilitlenir, sallanma önlenir. Yüzey, malzemenin doğal dokusunu koruyan koruyucu kaplamayla hazırlanır; elle temas pürüzsüzdür, yonga bırakmaz. Korkuluk dip kısımları, zemine geçen suyu gövdeye taşımaması için yükseltilir ve bağlantı elemanları korozyona dayanıklı malzemeden seçilir. Çocuklu alanlarda dikey aralıklar, topun veya kolun sıkışmasını önleyecek şekilde düzenlenir; yaşlı sakinler için üst tutamak hattı düz ve kesintisizdir. Tekerlekli sandalye erişimi gereken giriş kenarında korkuluk açılıp geçiş bırakacak şekilde planlanır. Temizlikte yumuşak fırça ve ılık su yeterlidir; dip noktalar yılda bir kontrol edilir, gevşenen sıkma hemen yapılır. Malzeme, alternatife göre yazın ısınmaz, kışın soğuk tutmaz; dokusu bahçenin malzeme diliyle uyumludur. İstenirse boyanabilir; renk değişikliği teklif aşamasında belirlenir. Korkuluk, 6x4 planında kesintisiz bir güvenlik hattı çizer ve mobilya yerleşimini içeriden çerçeveler. Işık bandı veya saksı rafı istenirse üst profilin iç yüzüne gizli kanal açılır; görünür vida bırakılmaz.',
                ],
                'en' => [
                    'baslik' => 'Modern Aluminium Gazebo 6x4 Hotel Garden',
                    'slug' => 'modern-aluminium-gazebo-6x4-012',
                    'kisa_aciklama' => 'Modern aluminium gazebo, 6×4 footprint for hotel gardens. Powder coat with saltwater and UV resistance and flat roof keep four-season use simple. Seats 8-9 across 24 m². Measurements and install plan lock after the free survey (Mon–Sat 09:00–18:00). Five-year warranty.',
                    'detayli_aciklama' => '**TL;DR**
- 6×4 m (24 m²) Modern, open span floor; an 8-9-person seating area for hotel gardens.
- Powder coat with saltwater and UV resistance, Flat, controlled drainage: four-season use, five-year warranty.
- Transparent m² maths: 24 × 3 USD × 2.5 × 1.5 × 1.6 = 432.00 USD.
- Final measurements and the installation plan are fixed at the free survey (Mon-Sat 09:00-18:00).

## What Are the Technical Specifications?

| Item | Value |
| --- | --- |
| Floor size | 6×4 m — 24 m² |
| Shape | Modern, open span |
| Main material | aluminium |
| Roof | Flat, controlled drainage |
| Railing | aluminium railing, height set per project |
| Surface | Powder coat with saltwater and UV resistance |
| Warranty | 5 years |

## Who Is It For?

This model is built for hotel operators and site teams that plan at garden scale.

## How Many People Does It Seat?

Seating follows the approved hotel gardens ratio from F15: 24 m² ÷ 2.80 m² per person ≈ 8.57, so 8-9 people sit comfortably.

## How Is It Installed?

The product is made to order.

## How Is It Maintained?

Care is one surface check a year plus a protective refresh when needed.

## Why This Model?

This model balances scale and upkeep for hotel gardens.

## What About the Roof?

The flat roof meets the modern silhouette and garden line on the same plane; rainwater drains through controlled channels between panels and never drips onto the seating zone. A tiny slope, invisible to the eye, is planned into the mould so drainage is guaranteed and no ponding forms. Wind behaviour is assessed at project scale and settled at the survey against external envelope logic referenced by EN 13561. The plane leaves room for lighting or menu-board mounts. Roof care needs one visual check a year — keep the holes open and rinse with a hose. Edge profiles guide water down the facade and leave no stain marks. Where needed, the drainage plan is discussed with the survey crew on site. The outlet stays above ground level so the surface remains dry. In winter the snow load spreads evenly across the plane instead of concentrating on one point. Cable runs for evening lighting hide under the ceiling; the drip line stays outside the seating circle. Dust and limescale rinse off with a hose; no chemicals are required.

## What About the Railing and Safety?

The aluminium railing runs at an even height along every edge of the plan, shielding the seating area from cross-wind while keeping privacy inside. Height is confirmed at the survey against safety standards, preferring the dimension that supports the wrist when seated over a fixed catalogue number. Joints lock with cross-braces so the frame does not sway. The surface keeps the material\'s natural grain — smooth to the touch, with no splinters. Railing feet are lifted so water never wicks into the posts; all fixings are chosen for corrosion resistance. Vertical gaps are spaced to prevent hands or balls from jamming, while the top grip line stays flat for elderly residents. Where wheelchair access is needed, one entrance edge opens and leaves a clear passage. Cleaning takes a soft brush and lukewarm water; check the feet yearly. The material doesn\'t overheat in summer or feel cold in winter, and its texture matches the garden\'s palette. The railing can be painted on request; colour is decided at quote stage. Along the 6×4 plan it draws an unbroken safety line. A hidden channel under the top rail accepts a light strip or planter rail without visible screws.
',
                    'seo_baslik' => 'Modern Aluminium Gazebo 6×4 Price List 2026 | Kamelya',
                    'seo_aciklama' => '24 m² Modern gazebo seats 8-9 with flat roof, five-year warranty. Transparent m² pricing and free on-site survey. Book a visit Monday–Saturday — free.',
                    'seo_anahtar_kelimeler' => 'modern aluminium gazebo, aluminium gazebo prices, 6x4 gazebo, hotel gazebo',
                    'cati_tipi_aciklama' => 'The flat roof meets the modern silhouette and garden line on the same plane; rainwater drains through controlled channels between panels and never drips onto the seating zone. A tiny slope, invisible to the eye, is planned into the mould so drainage is guaranteed and no ponding forms. Wind behaviour is assessed at project scale and settled at the survey against external envelope logic referenced by EN 13561. The plane leaves room for lighting or menu-board mounts. Roof care needs one visual check a year — keep the holes open and rinse with a hose. Edge profiles guide water down the facade and leave no stain marks. Where needed, the drainage plan is discussed with the survey crew on site. The outlet stays above ground level so the surface remains dry. In winter the snow load spreads evenly across the plane instead of concentrating on one point. Cable runs for evening lighting hide under the ceiling; the drip line stays outside the seating circle. Dust and limescale rinse off with a hose; no chemicals are required.',
                    'korkuluk_aciklama' => 'The aluminium railing runs at an even height along every edge of the plan, shielding the seating area from cross-wind while keeping privacy inside. Height is confirmed at the survey against safety standards, preferring the dimension that supports the wrist when seated over a fixed catalogue number. Joints lock with cross-braces so the frame does not sway. The surface keeps the material\'s natural grain — smooth to the touch, with no splinters. Railing feet are lifted so water never wicks into the posts; all fixings are chosen for corrosion resistance. Vertical gaps are spaced to prevent hands or balls from jamming, while the top grip line stays flat for elderly residents. Where wheelchair access is needed, one entrance edge opens and leaves a clear passage. Cleaning takes a soft brush and lukewarm water; check the feet yearly. The material doesn\'t overheat in summer or feel cold in winter, and its texture matches the garden\'s palette. The railing can be painted on request; colour is decided at quote stage. Along the 6×4 plan it draws an unbroken safety line. A hidden channel under the top rail accepts a light strip or planter rail without visible screws.',
                ],
                'de' => [
                    'baslik' => 'ModernerAluminium--Pavillon 6x4 Hotelgarten',
                    'slug' => 'moderner-aluminium-pavillon-6x4-012',
                    'kisa_aciklama' => 'Moderner Aluminium-Pavillon 6x4 für Hotelgärten: pulverbeschichtetes Aluminiumprofil, flaches Dach mit kontrollierter Entwässerung und klarer Sitzordnung. Platz für 8-9 Personen auf 24 m². Maße und Montageplan werden nach dem kostenlosen Aufmaß fixiert (Mo-Sa 09:00-18:00). 5 Jahre Garantie inklusive.',
                    'detayli_aciklama' => '**Kurz gefasst**
- 6×4 m (24 m²) modern Fläche; 8-9-Personen-Sitzplatz für Hotelgärten.
- pulverbeschichtetes Aluminiumprofil, Flach, kontrollierte Entwässerung: ganzjährige Nutzung, 5 Jahre Garantie.
- Transparente m²-Rechnung: 24 × 2,50 EUR × 2.5 × 1.5 × 1.6 = 360 EUR.
- Maße und Montageplan werden beim kostenlosen Aufmaß fixiert (Mo-Sa 09:00-18:00).

## Technische Daten

| Merkmal | Wert |
| --- | --- |
| Bodenmaß | 6×4 m — 24 m² |
| Form | Modern, weite Spanne |
| Hauptmaterial | pulverbeschichtetes Aluminiumprofil |
| Dach | Flach, kontrollierte Entwässerung |
| Geländer | Aluminiumgeländer, Höhe projektbezogen |
| Oberfläche | Pulverbeschichtung — Salzwasser- und UV-Beständigkeit |
| Befestigung | Korrosionsfeste Metallteile |
| Garantie | 5 Jahre |

## Für wen ist er geeignet?

Dieses Modell ist für Hotelgärten und Hausverwaltungen gebaut, die im Gartenmaß planen. Der 24 m²-modern-Boden lässt Möbel frei stehen: eine 8-9-Personen-Aufteilung passt mühelos; Esstisch und ruhige Ecke teilen sich den Platz. Die Oberfläche spart Reinigung in der Hochsaison. Gäste finden einen übersichtlichen Treffpunkt; das Aluminiumgeländer schützt die Sitzfläche an der Windseite. Schmale Höfe und Pläne passen zum Maß, ohne die Landschaftslinie zu brechen. Projekte mit Quartiersbudget vermeiden Kostenüberraschungen durch den Standardgrundriss. Drei offene Seiten rahmen den Blick; die vierte bleibt Service oder Fußgverkehr vorbehalten.

## Kapazität

Die Kapazität folgt dem freigegebenen Faktor aus F15: 24 m² ÷ 2,80 m² pro Person ≈ 8,57 — bequem sitzen also 8-9 Personen. Der Faktor folgt der Nutzungsdichte; Privatsphäre und Bewegungsfreiheit stehen vor Dichte. Die Aufteilung ist ideal für 8-9 Personen; größere Feste wechseln zu breiteren Modellen. Die endgültige Aufstellung wird beim Aufmaß mit dem Möbelplan geprüft.

## Montage

Das Modell wird auf Bestellung gefertigt. Das kostenlose Aufmaß prüft Untergrund, Zufahrt und Strombedarf und fixiert Maße und Montageplan in diesem Termin. Termine laufen Montag bis Samstag 09:00-18:00 ohne Kosten. Am Montagetag wird die Verankerung für Platte, verdichteten Boden oder Holzdeck gewählt. Material geht direkt vom Fahrzeug zum Aufbau; Verschnitt bleibt minimal. Nach Abschluss wird die Oberfläche gereinigt und das Datenblatt übergeben.

## Pflege

Pflege ist eine Oberflächenkontrolle pro Jahr plus Schutzschicht bei Bedarf. Pulverbeschichtung — Salzwasser- und UV-Beständigkeit. Rinnen und Drainageöffnungen werden von Laub befreit; das flaches Dach hält kein Stauwasser. Geländerbeschläge werden einmal jährlich nachgezogen. Die 5-Jahres-Garantie deckt Material- und Fertigungsfehler; Aufmaß und Montageplan bleiben auf der Garantiekarte.

## Warum dieses Modell?

Dieses Modell hält Massstab und Pflegeaufwand für Hotelgärten in Balance. Der Standardgrundriss macht das Angebot kalkulierbar; das Aufmaß fixiert die Maße vor der Fertigung, damit die Baustelle ohne Nacharbeit auskommt. Modern, weite Spanne fügt sich in die Gartenlinie ein, Flach, kontrollierte Entwässerung hält Regen fern, und die Oberfläche braucht im Jahr nur eine Sichtkontrolle. Die Preisliste folgt derselben Formel wie der Konfigurator: Fläche mal Grundpreis mal Material-, Form- und NutzungsFaktor — ohne versteckte Zuschläge. Fünf Jahre Garantie und kostenloses Aufmaß von Montag bis Samstag 09:00-18:00 senken das Projektrisiko. Wer mehr Platz braucht, greift zum größeren Modell derselben Baureihe.',
                    'seo_baslik' => 'ModernerAluminium--Pavillon 6x4 Preise 2026 | Kamelya',
                    'seo_aciklama' => 'ModernerAluminium--Pavillon 6x4: 24 m², für 8-9, flaches Dach, 5 Jahre Garantie. Transparente m²-Preise und kostenloses Aufmaß buchen. Details auf der',
                    'seo_anahtar_kelimeler' => 'modern aluminium Pavillon, aluminium Pavillon Preise, 6x4 Pavillon, Pavillon Hotel',
                    'cati_tipi_aciklama' => 'Das flache Dach trifft die modern Silhouette und die Gartenlinie auf derselben Ebene; Regenwasser läuft durch kontrollierte Kanäle zwischen den Paneln ab und tropft nie auf die Sitzfläche. Eine winzige, unsichtbare Neigung ist in die Form geplant, damit kein Wasser stehen bleibt und die Entwässerung garantiert ist. Das Windverhalten wird projektmäßig bewertet und beim Aufmaß an der Logik externer Fassadenelemente mit EN-13561-Bezug abgeglichen. Die Fläche lässt Platz für Leuchten oder Menüschilder. Zur Dachpflege genügt eine Sichtkontrolle pro Jahr. Die Oberfläche widersteht dem Klima; anders als Holzdächer braucht sie keine Imprägnierung. Kantenprofile führen das Wasser kontrolliert an der Fassade hinab und hinterlassen keine Flecken. Bei Bedarf wird der Entwässerungsplan mit dem Aufmaß-Team vor Ort besprochen. Die Ablauföffnung bleibt über dem Bodenniveau. Im Winter verteilt sich die Schneelast gleichmäßig auf der Fläche statt an einem Punkt. Kabelwege für Abendbeleuchtung laufen unter der Decke; die Tropfzone bleibt außen. Kalk und Stahlspäne spülen mit dem Schlauch ab; Chemie ist nicht nötig.',
                    'korkuluk_aciklama' => 'Das Aluminiumgeländer läuft an jeder Kante des Grundrisses in gleicher Höhe weiter und schützt die Sitzfläche vor Durchzug, während innen Privatsphäre bleibt. Die Höhe wird beim Aufmaß anhand von Sicherheitsnormen bestätigt; statt einer festen Katalogzahl wird das Maß gewählt, das das Handgelenk im Sitzen stützt. Verbindungen werden mit Kreuzverband verriegelt. Die Oberfläche erhält eine Schutzschicht, die die natürliche Struktur bewahrt. Die Füße werden angehoben, damit bodennahes Wasser nicht in die Pfosten steigt; alle Beschläge sind korrosionsfest. In Familienbereichen balancieren Vertikalabstände Kindersicherheit; die obere Griffleitung bleibt gerade. Wo ein Rollstuhlvorgang nötig ist, öffnet sich eine Ecke und lässt freie Passage. Zur Reinigung genügen weiche Bürste und lauwarmes Wasser; Füße einmal jährlich prüfen. Das Material wird im Sommer nicht heiß und im Winter nicht eiskalt. Auf Wunsch anmalbar; die Farbe wird beim Angebot festgelegt. Auf dem 6x4-Grundriss zieht es eine durchgehende Sicherheitslinie und fasst die Möbel von innen ein. Ein versteckter Kanal unter der Oberkante nimmt Lichtband oder Pflanzschiene auf, ohne sichtbare Schrauben.',
                ],
                'fr' => [
                    'baslik' => 'Gazebo Moderne Aluminium 6x4 Jardin d\'Hôtel',
                    'slug' => 'gazebo-moderne-aluminium-6x4-012',
                    'kisa_aciklama' => 'Gazebo moderne Aluminium de 6×4 m pour jardins d’hôtel : profil aluminium thermolaqué, toit plat à évacuation contrôlée et assise dégagée. 8-9 personnes sur 24 m² pour un usage toute saison. Dimensions et plan de pose fixés après l’étude gratuite (lun.-sam. 09h00-18h00). Garantie 5 ans incluse.',
                    'detayli_aciklama' => '**En bref**
- Sol moderne 6×4 m (24 m²) ; espace pour 8-9 personnes en jardins d’hôtel.
- profil aluminium thermolaqué, Plat, drainage contrôlé : usage toute saison, garantie 5 ans.
- Calcul m² transparent : 24 × 2,50 EUR × 2.5 × 1.5 × 1.6 = 360 EUR.
- Dimensions et plan de pose fixés pendant l’étude gratuite (lun.-sam. 09h00-18h00).

## Caractéristiques techniques

| Caractéristique | Valeur |
| --- | --- |
| Emprise au sol | 6×4 m — 24 m² |
| Forme | Moderne, portée ouverte |
| Matériau principal | profil aluminium thermolaqué |
| Toit | Plat, drainage contrôlé |
| Garde-corps | garde-corps en aluminium, hauteur définie par projet |
| Surface | Thermolaquage — résistance eau salée et UV |
| Fixations | Pièces métalliques résistantes à la corrosion |
| Garantie | 5 ans |

## Pour qui ?

Ce modèle est conçu pour les jardins d’hôtel et syndics qui planifient à l’échelle du jardin. Le sol moderne de 24 m² laisse le mobilier libre : une organisation 8-9 personnes tient sans effort ; table à manger et coin calme se partagent l’espace. La surface allège le nettoyage en haute saison. Les clients trouvent un point de rendez-vous ombragé ; le garde-corps en aluminium protège l’assise côté vent. Cours étroits et plans s’accordent à la mesure sans casser la ligne de paysage. Les projets de budget de quartier évitent les surprises de coût. Trois côtés ouverts cadrent la vue ; le quatrième reste au service ou au flux piéton.

## Capacité

La capacité suit le facteur approuvé en F15 : 24 m² ÷ 2,80 m² par personne ≈ 8,57 — soit confortablement 8-9 personnes. Le facteur suit l’intensité d’usage ; intimité et aisance passent avant la densité. L’organisation est idéale pour 8-9 personnes ; les réceptions plus larges passent à des modèles plus amples. Le placement final se vérifie pendant l’étude avec le plan de mobilier.

## Pose

Le gazebo est fabriqué sur commande. L’étude gratuite examine le sol, l’accès et le besoin électrique, et fixe dimensions et plan de pose. Les créneaux vont du lundi au samedi 09h00-18h00 sans frais. Le jour de pose, l’ancrage s’adapte à la dalle, au sol compacté ou au deck bois. Les matériaux passent du véhicule au chantier ; les chutes restent minimales. À la fin, la surface est nettoyée et la fiche d’usage remise.

## Entretien

L’entretien se limite à un contrôle de surface par un renouvellement protecteur si besoin. Thermolaquage — résistance eau salée et UV. Gouttières et orifices d’évacuation sont dégagés des feuilles ; le toit plat ne garde jamais d’eau stagnante. Les fixations du garde-corps se resserrent une fois par an. La garantie 5 ans couvre défauts de fabrication et de matière ; l’étude et le plan de pose restent sur la carte de garantie.

## Pourquoi ce modèle ?

Ce modèle équilibre échelle et entretien pour les jardins d’hôtel ; l’empreinte standard garde le devis prévisible et l’étude fixe les dimensions avant fabrication.',
                    'seo_baslik' => 'Gazebo Moderne Aluminium 6x4 Prix et Mesures 2026 | Kamelya',
                    'seo_aciklama' => 'Gazebo moderne aluminium 6x4 : 24 m², 8-9 places, toit plat, garantie 5 ans. Prix au m² transparents, réservation d’une étude gratuite. Détails sur la',
                    'seo_anahtar_kelimeler' => 'gazebo moderne aluminium, gazebo aluminium prix, gazebo 6x4, gazebo hôtel',
                    'cati_tipi_aciklama' => 'Le toit plat rejoint la silhouette moderne et la ligne du jardin sur le même plan ; l’eau s’évacue par des canaux contrôlés entre panneaux et ne tombe jamais sur l’assise. Une pente minuscule, invisible à l’œil, est prévue dans le moule pour qu’aucune flaque ne subsiste. Le comportement du vent s’évalue à l’échelle du projet et se fixe à l’étude selon la logique des éléments d’enveloppe référencés par l’EN 13561. La surface laisse la place aux luminaires ou enseignes. L’entretien demande un contrôle visuel par an. Le revêtement résiste au climat ; contrairement au bois, aucun traitement n’est nécessaire. Les profils de bord guident l’eau vers la façade sans tache. Si besoin, le plan d’évacuation se discute avec l’équipe d’étude. La sortie reste au-dessus du sol. En hiver la neige se répartit également sur la forme plane. Les câbles d’éclairage du soir passent sous le plafond ; la ligne de gouttes reste hors de l’assise. La poussière et le calcaire partent au tuyau en quelques minutes, sans produit chimique.',
                    'korkuluk_aciklama' => 'Le garde-corps en aluminium court à hauteur égale sur chaque arête, protège l’assise des traversants et garde l’intimité. Sa hauteur se confirme à l’étude selon les normes, préférant la mesure qui soutient le poignet au chiffre de catalogue. Les assemblages se verrouillent par croisements. La surface reçoit une finition protectrice qui conserve le grain. Les pieds sont surélevés pour que l’eau du sol ne remonte pas ; les pièces d’attache résistent à la corrosion. Dans les espaces familiaux les entraxes empêchent les mains de se coincer ; la ligne de saisie reste droite. Là où le fauteuil est requis, un côté s’ouvre. Nettoyage : brosse douce et eau tiède ; contrôle annuel des pieds. Le matériau ne chauffe ni ne givre. Peignable sur demande. Sur le plan 6x4 il trace une ligne continue et cadre le mobilier de l’intérieur. Un canal discret sous le rail supérieur accueille bandeau lumineux ou corniche à plantes, sans vis apparente.',
                ],
                'it' => [
                    'baslik' => 'Gazebo Moderno Alluminio 6x4 Giardino Hotel',
                    'slug' => 'gazebo-moderno-alluminio-6x4-012',
                    'kisa_aciklama' => 'Gazebo moderno in Alluminio da 6×4 m per giardini di hotel: profilo in alluminio verniciato a polvere, tetto piatto a scarico controllato. 8-9 persone su 24 m². Misure e piano di posa fissati dopo il sopralluogo gratuito (lun.-sab. 09:00-18:00). Garanzia 5 anni.',
                    'detayli_aciklama' => '**In breve**
- Pavimento moderno 6×4 m (24 m²) ; zona seduta per 8-9 persone in giardini di hotel.
- profilo in alluminio verniciato a polvere, Piatto, scarico controllato : uso tutto l’anno, garanzia 5 anni.
- Calcolo m² trasparente : 24 × 2,50 EUR × 2.5 × 1.5 × 1.6 = 360 EUR.
- Misure e piano di posa fissati durante il sopralluogo gratuito (lun.-sab. 09:00-18:00).

## Specifiche tecniche

| Voce | Valore |
| --- | --- |
| Impronta a terra | 6×4 m — 24 m² |
| Forma | Moderno, luce ampia |
| Materiale principale | profilo in alluminio verniciato a polvere |
| Tetto | Piatto, scarico controllato |
| Parapetto | parapetto in alluminio, altezza definita dal progetto |
| Superficie | Verniciatura a polvere — resistenza a sale e UV |
| Fissaggi | Parti metalliche resistenti alla corrosione |
| Garanzia | 5 anni |

## Per chi è?

Questo modello è pensato per giardini di hotel e amministratori che progettano alla scala del giardino. Il pavimento moderno da 24 m² lascia libero l’arredamento: una disposizione 8-9 persone entra con naturalezza; tavolo da pranzo e angolo relax condividono lo spazio. La superficie alleggerisce la pulizia in alta stagione. Gli ospiti trovano un punto d’incontro ombreggiato; il parapetto in alluminio ripara la seduta dal vento. Cortili stretti e piani si adattano alla misura senza spezzare la linea del paesaggio. I progetti con budget di quartiere evitano sorprese di costo. Tre lati aperti inquadrano la vista; il quarto resta a servizio o flusso pedonale.

## Capienza

La capienza segue il fattore approvato in F15: 24 m² ÷ 2,80 m² a persona ≈ 8,57 — quindi 8-9 persone comodamente. Il fattore segue l’intensità d’uso; privacy e libertà di movimento vengono prima della densità. La disposizione è ideale per 8-9 persone ; le feste più ampie passano a modelli più ampi. La posa finale si verifica al sopralluogo con il piano d’arredo.

## Posa

Il gazebo è prodotto su ordinazione. Il sopralluogo gratuito valuta terreno, accesso ed eventuale bisogno elettrico, e fissa misure e piano di posa. Gli appuntamenti vanno da lunedì a sabato 09:00-18:00 senza costi. Il giorno della posa si sceglie l’ancoraggio per la lastra, il terreno compatto o il deck in legno. I materiali passano dal veicolo al cantiere; gli scarti restano minimi. Al termine la superficie viene pulita e consegnata la scheda d’uso.

## Manutenzione

La manutenzione è un controllo di superficie all’anno e un rinnovo protettivo se serve. Verniciatura a polvere — resistenza a sale e UV. Gronde e bocchette si liberano dalle foglie; il tetto piatto non trattiene acqua. Le fissaggi del parapetto si stringono una volta l’anno. La garanzia 5 anni copre difetti di fabbricazione e materiale; sopralluogo e piano restano sulla garanzia.

## Perché questo modello?

Questo modello bilancia scala e manutenzione per giardini di hotel. L’impronta standard tiene il preventivo prevedibile e il sopralluogo fissa le misure prima della produzione, così il cantiere procede senza rifacimenti. Moderno, luce ampia si adatta alla linea del giardino, Piatto, scarico controllato allontana la pioggia dalla seduta, e la superficie richiede un solo controllo visivo all’anno. Il listino segue la stessa formula del configuratore: metri quadri per prezzo base per fattori di materiale, forma e uso — senza sorprese. Garanzia di 5 anni e sopralluogo gratuito da lunedì a sabato 09:00-18:00 riducono il rischio del progetto.',
                    'seo_baslik' => 'Gazebo Moderno Alluminio 6x4 Prezzi e Misure 2026 | Kamelya',
                    'seo_aciklama' => 'Gazebo moderno alluminio 6x4: 24 m², 8-9 posti, tetto piatto, garanzia 5 anni. Prezzi al m² trasparenti, sopralluogo gratuito su richiesta. Dettagli in',
                    'seo_anahtar_kelimeler' => 'gazebo moderno alluminio, gazebo alluminio prezzi, gazebo 6x4, gazebo hotel',
                    'cati_tipi_aciklama' => 'Il tetto piatto incontra la silhouette moderno e la linea del giardino sullo stesso piano; l’acqua piovana si drena attraverso canali controllati tra i pannelli e non gocciola mai sulla seduta. Una pendenza minuscola, invisibile, è prevista nello stampo perché non resti pozzanghera. Il comportamento al vento si valuta a scala di progetto e si definisce al sopralluogo con riferimento EN 13561. La superficie lascia spazio a luci o tabelloni. La manutenzione richiede un controllo visivo all’anno. Il rivestimento resiste al clima; a differenza del legno non serve trattamento. I profili di bordo guidano l’acqua verso la facciata senza macchie. Se serve, il piano di scarico si discute con l’equipaggio. La bocchetta resta sopra il suolo. D’inverno la neve si riparte sul piano. I cavi per l’illuminazione serale passano sotto il soffitto; la linea di gocce resta fuori. Polvere e calcare si sciolgono al tubo in pochi minuti, senza prodotti chimici.',
                    'korkuluk_aciklama' => 'Il parapetto in alluminio prosegue ad altezza uguale su ogni spigolo, ripara la seduta dai venti traversi e mantiene la riservatezza. L’altezza si conferma al sopralluogo secondo le norme, preferendo la misura che sorregge il polso a un numero fisso di catalogo. Le giunzioni si bloccano con incroci. La superficie riceve una finitura protettiva che conserva la venatura. I piedi sono sollevati perché l’acqua del terreno non risalga; le ferramenta sono a prova di corrosione. Negli spazi familiari gli interassi impediscono che mani restino incastrate; la linea di presa resta dritta. Dove serve la sedia a rotelle, un lato si apre. Pulizia : spazzola morbida e acqua tiepida ; controllo annuale dei piedi. Il materiale non scalda né gela. Colorabile a richiesta. Sul piano 6x4 disegna una linea continua e incornicia l’arredo dall’interno. Un canale discreto sotto il binario superiore ospita fascia luminosa o mensola per piante, senza viti a vista.',
                ],
                'ar' => [
                    'baslik' => 'كوش عصري ألمنيوم 6x4 لـحديقة فندق',
                    'slug' => 'modern-aluminium-kush-6x4-012',
                    'kisa_aciklama' => 'كوش عصري ألمنيوم بمقاس 6×4 متر لـحدائق الفنادق: بروفايل ألمنيوم مطلي بالبودرة، سقف مستوي بتصريف مضبوط ومساحة جلوس مريحة. يتسع لـ8-9 أشخاص على 24 م² لاستخدام على مدار السنة. تُثبَّت المقاسات وخطة التركيب بعد الاستشارة المجانية (الاثنين–السبت 09:00–18:00). ضمان 5 سنوات كامل.',
                    'detayli_aciklama' => '**باختصار**
- أرضية عصري 6×4 م (24 م²)؛ مساحة جلوس لـ8-9 أشخاص في حدائق الفنادق.
- بروفايل ألمنيوم مطلي بالبودرة، مستوي، تصريف مضبوط: استخدام على مدار السنة، ضمان 5 سنوات.
- حساب شفاف للمتر: 24 × 3 USD × 2.5 × 1.5 × 1.6 = 432 USD.
- تُثبَّت المقاسات وخطة التركيب في الاستشارة المجانية (الاثنين–السبت 09:00–18:00).

## المواصفات التقنية

| البند | القيمة |
| --- | --- |
| مقاس الأرضية | 6×4 م — 24 م² |
| الشكل | عصري، امتداد واسع |
| المادة الأساسية | بروفايل ألمنيوم مطلي بالبودرة |
| السقف | مستوي، تصريف مضبوط |
| السور | سور ألمنيوم، الارتفاع حسب المشروع |
| السطح | طلاء بودرة — مقاومة الماء المالح والأشعة |
| التثبيت | قطع معدنية مقاومة للصدأ |
| الضمان | 5 سنوات |

## لمن صُمم؟

هذا الموديل لـحدائق الفنادق وإدارات تخطط على مقياس الحديقة. الأرضية عصري بمساحة 24 م² تترك الأثاث حرًا: ترتيب 8-9 أشخاص يدخل بيسر؛ طاولة الطعام وركن الهدوء يتقاسمان المساحة. السطح يخفف التنظيف في الموسم الذروة. الزائدون يجدون نقطة لقاء مظلولة؛ وسور ألمنيوم يحمي منطقة الجلوس من جهة الرياح. الأفنية الضيقة والخطط تتلاءم مع المقاس دون كسر خط المشهد. المشاريع ذات ميزانية الحي تتجنّب مفاجآت التكلفة. ثلاثة وجوه مفتوحة تؤطر المشهد؛ والرابع يبقى للخدمة أو حركة المشاة.

## السعة

تُحسب السعة ومعامل المعتمد من F15: 24 م² ÷ 2.80 م² للشخص ≈ 8.57 — أي 8-9 أشخاص براحة. المعامل يتبع كثافة الاستخدام؛ الخصوصية وحرية الحركة تأتي قبل الازدحام. الترتيب مثالي لـ8-9 أشخاص؛ والتجمعات الأكبر تنتقل لمقاسات أوسع. يُتحقق من التوضع النهائي أثناء الاستشارة مع خطة الأثاث.

## التركيب

يُصنع الموديل عند الطلب. في الاستشارة المجانية يُفحص الأرضية واتجاه الدخول وحاجة الكهرباء، وتُثبَّت المقاسات وخطة التركيب في ذلك الموعد. المواعيد من الاثنين إلى السبت 09:00–18:00 بدون رسوم. يوم التركيب تُختار طريقة التثبيت حسب اللوحة أو التربة المضغوطة أو السطح الخشبي. تُنقل المواد مباشرة من السيارة؛ ويبقى الهدر ضئيلًا. بعد الانتهاء يُنظَّف السطح وتُسلَّم ورقة الاستخدام.

## الصيانة

الصيانة فحص سطحي سنوي وتجديد واقع عند الحاجة. طلاء بودرة — مقاومة الماء المالح والأشعة. تُنظَّف المرزبات وفتحات التصريف من الأوراق؛ وسقف مستوي لا يترك ماءً راكدًا. تُشدّ مثبتات السور مرة في السنة. ضمان 5 سنوات يشمل عيوب التصنيع والمواد؛ والاستشارة وخطة التركيب تبقى في بطاقة الضمان.

## لماذا هذا الموديل؟

يوازن هذا الموديل بين المقياس وجدول الصيانة لـحدائق الفنادق؛ المقاس القياسي يبقي عرض السعر قابلًا للتوقع، والاستشارة تثبت المقاسات قبل بدء التصنيع حتى لا يُعاد العمل في الموقع. عصري، امتداد واسع يلائم خط المشهد، ومستوي، تصريف مضبوط يبعد المطر عن منطقة الجلوس، والسطح لا يحتاج إلا فحصًا بصريًا واحدًا في السنة. قائمة الأسعار تتبع المعادلة نفسها في الأداة: متر مربع في سعر الأساس في عوامل المادة والشكل والاستخدام — دون رسوم خفية. خمس سنوات ضمان واستشارة مجانية من الاثنين إلى السبت 09:00–18:00 تخفض مخاطر المشروع. الفرق بين هذا الموديل والمقاس المجاور يظهر في جدول السعة لا في بنود مخفية. من يحتاج مساحة أكبر ينتقل إلى الموديل الأكبر من العائلة نفسها، ومن يملك أرضية أضيق يبدأ بالمربع الصغير ثم يرقّى لاحقًا دون تغيير لغة العرض.',
                    'seo_baslik' => 'كوش عصري ألمنيوم 6x4 أسعار 2026 | Kamelya',
                    'seo_aciklama' => 'كوش عصري ألمنيوم 6x4: 24 م²، 8-9 أشخاص، سقف مستوي، ضمان 5 سنوات. أسعار المتر وحجز استشارة.',
                    'seo_anahtar_kelimeler' => 'كوش عصري ألمنيوم, أسعار الكوش ألمنيوم, كوش 6x4, كوش الفنادق',
                    'cati_tipi_aciklama' => 'يلتقي السقف المستوي مع سيلويت عصري وخط الحديقة على المستوى نفسه؛ تُصرف مياه الأمطار عبر قنوات مضبوطة بين الألواح ولا تتقاطر أبدًا على منطقة الجلوس. يُخطط ميل صغير غير مرئي داخل القالب كي لا تتكوّن بركة ويكون التصريف مضمونًا. يُقيَّم سلوك الرياح على مستوى المشروع ويُحسم في الاستشارة وفق منطق عناصر الغلاف الخارجي المرجعية (EN 13561). تترك السطح مساحة للإضاءة أو لوحات القوائم. صيانة السقف فحص بصري سنوي. تكسية السطح تصمد أمام المناخ؛ بخلاف الخشب لا تحتاج معالجة. تقود الحواف المائية إلى الواجهة بشكل مضبوط دون أن تترك بقعًا. وعند الحاجة يُناقش خطة التصريف مع فريق الاستشارة في الموقع. تبقى فتحة التصريف فوق سطح الأرض. وفي الشتاء تتوزع حملة الثلج بالتساوي على السطح بدل أن تتركز في نقطة واحدة. مسارات كابلات الإضاءة المسائية تمر تحت السقف ويبقى خط التقطير خارج دائرة الجلوس. الغبار والترسّبات تُشطف بالخرطوم في دقائق دون مواد كيميائية. الحواف المعدنية تحمل شرائط تجميع مقاومة للصدأ ولا تترك صدأً على الأرضية.',
                    'korkuluk_aciklama' => 'يستمر سور ألمنيوم بارتفاع متساوٍ على كل حافة من حواف الخطة، يحمي منطقة الجلوس من الرياح المارّة مع إبقاء الخصوصية في الداخل. يُثبَّت الارتفاع في الاستشارة وفق معايير السلامة، مع تفضيل المقاس الذي يسند المعصم أثناء الجلوس على رقم كتالوج ثابت. تُقفل نقاط التقاء بتثبيتات متقاطعة تمنع الاهتزاز. يُجهَّز السطح بطبقة واقية تحافظ على نسيج المادة الطبيعي — ناعم عند اللمس دون شظايا. تُرفع القواعد كي لا يصعد ماء الأرض إلى الجذوع، وتُختار كل القطع المعدنية مقاومة للصدأ. في الأماكن العائلية تُضبط المسافات الرأسية بحيث لا تعلق اليد، ويبقى خط القبضة العلوي مستقيمًا. حيث يلزم مرور الكرسي المتحرك يُفتح جانب. التنظيف بفرشاة ناعمة ومياه فاترة؛ فحص القواعد سنويًا. لا يسخن الصيف ولا يُثلج الشتاء. يمكن طلبه باللون عند الطلب. وعلى خطة 6x4 يرسم خطًا أمنيًا متصلًا ويؤطر الأثاث من الداخل. قناة مخفية أسفل السطح العلوي تستقبل شريطًا ضوئيًا أو رف نباتات دون مسامير ظاهرة. تُراجع وصلات الربط مرة كل موسم مع فحص عام للهيكل كاملاً دون إهمال الزوايا.',
                ],
            ],
        ];
    }
};
