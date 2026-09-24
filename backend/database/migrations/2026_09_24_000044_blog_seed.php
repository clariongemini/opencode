<?php

declare(strict_types=1);

use Kamelya\Core\Migration;

/**
 * F16.2.4 — Blog seed: 4 yazı × 6 dil = 24 çeviri + 24 seo_verileri (sayfa_tipi=blog).
 * Idempotent ON DUPLICATE KEY; migrate.php transaction sarmalar — beginTransaction YOK.
 * down(): seo (blog) → ceviri → yazilar sırası.
 */
return new class implements Migration {
    public function up(PDO $baglanti): void
    {
        $yazIf = $baglanti->prepare(
            'INSERT INTO blog_yazilari (id, yazar_id, kapak_resmi, yayin_durumu, yayin_tarihi)
             VALUES (?, 1, "/assets/img/yer-tutucu.svg", "yayinda", "2026-09-24 09:00:00")
             ON DUPLICATE KEY UPDATE yazar_id=VALUES(yazar_id), kapak_resmi=VALUES(kapak_resmi), yayin_durumu=VALUES(yayin_durumu), yayin_tarihi=VALUES(yayin_tarihi), deleted_at=NULL'
        );
        foreach (self::yazilar() as $id) { $yazIf->execute([$id]); }

        $cevIf = $baglanti->prepare(
            'INSERT INTO blog_yazisi_cevirileri
             (yazi_id, dil_kodu, baslik, ozet, icerik, slug, seo_baslik, seo_aciklama)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE baslik=VALUES(baslik), ozet=VALUES(ozet), icerik=VALUES(icerik),
             slug=VALUES(slug), seo_baslik=VALUES(seo_baslik), seo_aciklama=VALUES(seo_aciklama)'
        );
        foreach (self::ceviriler() as $satir) { $cevIf->execute($satir); }

        $seoIf = $baglanti->prepare(
'INSERT INTO seo_verileri
             (sayfa_tipi, referans_id, dil_kodu, sayfa_kodu,
              canonical_url, hreflang_json, meta_baslik, meta_aciklama, anahtar_kelime, robots)
             VALUES ("blog", ?, ?, ?, ?, ?, ?, ?, ?, "index, follow")
             ON DUPLICATE KEY UPDATE canonical_url=VALUES(canonical_url), hreflang_json=VALUES(hreflang_json),
             meta_baslik=VALUES(meta_baslik), meta_aciklama=VALUES(meta_aciklama),
             anahtar_kelime=VALUES(anahtar_kelime), robots=VALUES(robots)'
        );
        foreach (self::seoSatirlari() as $satir) { $seoIf->execute($satir); }
    }

    public function down(PDO $baglanti): void
    {
        $baglanti->exec('DELETE FROM seo_verileri WHERE sayfa_tipi = "blog" AND referans_id IN (1,2,3,4)');
        $baglanti->exec('DELETE FROM blog_yazisi_cevirileri WHERE yazi_id IN (1,2,3,4)');
        $baglanti->exec('DELETE FROM blog_yazilari WHERE id IN (1,2,3,4)');
    }

    /** @return array<int,int> */
    private static function yazilar(): array
    {
        return [1, 2, 3, 4];
    }

    /**
     * @return array<int, array{0:int,1:string,2:string,3:string,4:string,5:string,6:string,7:string}>
     */
    private static function ceviriler(): array
    {
        return [
            [1, 'tr', 'Kamelya Fiyat Rehberi 2026: m² Hesaplama ve Bütçe Planlama', 'Kamelya m² fiyat hesaplama, çarpanlar, 16 m² örnek bütçe, gizli maliyetler ve teklif karşılaştırma kriterleri.', '<p><strong>TL;DR:</strong> Kamelya fiyatı 2026\'da metrekare bazlı hesaplanır: alan × temel malzeme × model çarpanı × kullanım yoğunluğu. 16 m²\'lik standart bir site alanı için örnek bütçe 230.400 TL civarındadır; keşif ücretsizdir ve ölçüm sonrası fiyat sabitlenir.</p>
<h2>Kamelya m² fiyatı nasıl hesaplanır?</h2>
<p>Metrekare fiyatı tek başına yeterli bir gösterge değildir; toplam maliyet alan, çatı sistemi ve kullanım amacının çarpımıyla çıkar. 2026 listemizde temel malzeme birimi 12.000 TL/m²\'dir; alüminyum çatı ve kompozit panel seçenekleri bu tabanı çarpanla artırır. Formül şu şekildedir: <em>alan (m²) × temel birim × malzeme çarpanı × model çarpanı × kullanım çarpanı</em>.</p>
<p>Örnek olarak 16 m²\'lik dikdörtgen bir alan, temel ahşap iskelet (çarpan 1,0), altıgen model (1,2) ve site kullanımı (1,0) ile hesaplanırsa: 16 × 12.000 × 1,0 × 1,2 × 1,0 = 230.400 TL çıkar. Aynı alan alüminyum çatıyla (malzeme çarpanı 2,5) planlanırsa taban maliyet belirgin şekilde yükselir; detaylı karşılaştırma için <a href="/urunler">ürün listemize</a> bakabilirsiniz.</p>
<p>Fiyat teklifinde nakliye, montaj ve oluk detayları ayrı satırlarda listelenir; ölçüm sonrası ek sürpriz kalem çıkmaz. Bütçe aşımını önlemek için keşif sırasında kullanım yoğunluğu ve çatı tipi netleştirilir; bu iki karar toplam tutarı en çok etkileyen kalemlerdir.</p>
<table><thead><tr><th>Kalem</th><th>Değer</th><th>Not</th></tr></thead><tbody><tr><td>Temel birim (TR)</td><td>12.000 TL/m²</td><td>2026 listesi</td></tr><tr><td>Malzeme: ahşap</td><td>×1,0</td><td>Emprenye çam</td></tr><tr><td>Malzeme: alüminyum</td><td>×2,5</td><td>Çatı + profil</td></tr><tr><td>Model: altıgen</td><td>×1,2</td><td>Karmaşık kesim</td></tr><tr><td>Kullanım: site</td><td>×1,0</td><td>Konut yoğunluğu</td></tr></tbody></table>
<h2>Hangi kalemler bütçeyi gerçekten artırır?</h2>
<p>Toplam tutarı yükselten dört ana kalem vardır: çatı kaplaması, korkuluk tipi, zemin ayarlaması ve montaj erişimi. Kiremit veya polikarbonat çatı, düz membran çatıya göre daha yüksek maliyet getirir; alüminyum oluk ve oluk içi ısıtma ise ayrı opsiyonlardır. Zemin eğimli veya enkazlıysa ayarlanabilir ayak veya beton ayak gerekebilir; bu, keşif raporunda \'zemin düzeltme\' satırı olarak görünür.</p>
<p>Erişim dar sokak veya asansörlü bina bahçesiyse vinçli indirme gerekebilir; nakliye kalemi o zaman artar. Belediye veya site yönetiminden izin süreci bazen ek ruhsat dosyası ister; bu masraf üretim bedeline değil, proje dosyasına yazılır. Bakım bütçesini de unutmayın: 5 yıl garanti kapsamı dışındaki boya ve oluk temizliği için yıllık küçük bir pay ayırmak yeterlidir.</p>
<p>Gizli maliyetleri erken görmek için ücretsiz keşif talep edin; ekip ölçü, zemin ve erişimi aynı ziyarette değerlendirir. Sorularınız için <a href="/sss">SSS sayfamıza</a> bakabilir, ölçü sonrası net rakam için <a href="/teklif-al">teklif formunu</a> doldurabilirsiniz.</p>
<h2>16 m²&#x27;lik örnek bütçe nasıl çıkar?</h2>
<p>Aşağıdaki tablo, site bahçesi için tipik bir 16 m² planını gösterir; rakamlar 2026 liste fiyatıdır. Alanı 20 m²\'ye çıkarırsanız ölçek nedeniyle birim maliyet bir miktar düşebilir; nakliye ve montaj aynı kalır.</p>
<table><thead><tr><th>Senaryo</th><th>Hesap</th><th>Yaklaşık toplam</th></tr></thead><tbody><tr><td>Temel ahşap</td><td>16 × 12.000 × 1,0 × 1,0</td><td>192.000 TL</td></tr><tr><td>Altıgen model</td><td>16 × 12.000 × 1,0 × 1,2</td><td>230.400 TL</td></tr><tr><td>Alüminyum çatı</td><td>16 × 12.000 × 2,5 × 1,2</td><td>576.000 TL</td></tr></tbody></table>
<p>Restoran veya otel kullanımı için kullanım çarpanı devreye girer (restoran 1,30; otel 1,60); dolayısıyla aynı alan ticari projede daha yüksek faturalanır. Kapasite hesabı da maliyeti dolaylı etkiler: restoran için kişi başı 1,80 m² standarttır; 20 kişilik bir bahçe 36 m² alan gerektirir.</p>
<h2>Bütçe ne zaman sabitlenir ve ödeme planı nasıl olur?</h2>
<p>Fiyat, keşif ölçümü ve malzeme seçimi tamamlandığında sabitlenir; yazılı teklifte geçerlilik süresi belirtilir. Ödeme tipik olarak siparişte bir ön ödeme, montaj öncesi ara ödeme ve teslimde bakiye şeklinde üç aşamada yapılır. Sezon yoğunluğu (ilkbahar) ödemeleri hızlandırabilir; erken siparişte üretim sırası garanti edilir.</p>
<p>Bütçe aralığınız 150.000 TL altındaysa dikdörtgen temel model ve membran çatı ile başlamak mantıklıdır. Alternatif senaryo: aynı 16 m² alan, kısmi korkuluk ve tek çatı paneliyle basitleştirilerek ilk sezon için ötelenen yükseltmelerle planlanabilir. Yol haritanız için <a href="/kamelya-fiyatlari/istanbul">İstanbul fiyat sayfasını</a> ve bakım maliyetlerini <a href="/rehberler/bakim">bakım rehberini</a> inceleyin.</p>
<h2>Hangi ölçütlerle teklif karşılaştırılmalı?</h2>
<p>Teklifleri aynı birim üzerinden kıyaslayın: m² başı net maliyet, çatı tipi ve garanti süresi. Ayrıca montaj süresi, oluk ve korkuluk kalemlerinin fiyata dâhil olup olmadığına bakın; dâhil olmayanlar sonradan faturaya eklenir. 5 yıl garanti belgesi, taşıyıcı iskelet ve çatı kaplamasını kapsar; istisnalar garanti sayfasında listelenir.</p>
<p>Ucuz teklifte genellikle zemin ayarlaması veya nakliye satırları eksiktir; bu yüzden tek rakama değil, kalem listesine bakın. Karar aşamasında ürün fotoğraflarını ve ölçüleri <a href="/urunler">ürünlerde</a> karşılaştırabilir, merak ettiklerinizi <a href="/sss">SSS</a> üzerinden sorabilirsiniz.</p>
<h2>TR — planlama notları</h2>
<p>Fiyat sayfasındaki rakamlar liste fiyatıdır; proje özelinde keşif raporuyla güncellenir. Ölçü alımında zemin eğimi, çatı oluk yönü ve komşu duvar mesafesi birlikte kaydedilir; bu üç ölçü montaj planını belirler. Elektrik veya aydınlatma isteği varsa kablo kanalı montaj öncesi gömülür; sonradan ek kablo masrafı çıkmaz.</p>
<p>Malzeme seçimi iklimle ilişkilidir: nemli sahil şeridinde emprenye ahşap ve alüminyum oluk birlikte önerilir. Kurak iç kesimlerde UV dayanımlı boya ve gölge oranı ayarı yaz aylarında konforu artırır. Tüm seçenekler teklifte ayrı satır olarak görünür; hangisinin seçildiği yazılı olarak onaylanır.</p>
<p>Montaj süresi alan ve çatı tipine göre değişir; standart dikdörtgen planlar daha hızlı ilerler. Hava koşulları montaj gününde takip edilir; yağmur riskinde ekip yeni randevuyu aynı gün bildirir. Teslimde ölçüm formu ve bakım takvimi birlikte imzalanır; garanti belgesi bu paketin parçasıdır.</p>
<p>Bütçe planlama ipucu: toplam tutarın %10\'u kadar esnek pay bırakın; zemin düzeltme veya ek korkuluk bu paydan karşılanır. Uzun vadeli düşünürseniz boya ve oluk temizliği yıllık küçük bir kalemdir; 5 yıl garanti sonrası büyük masraf beklenmez. Karar öncesi ürünlerimizi inceleyip <a href=\'/urunler\'>ürünler</a> sayfasında ölçü ve model karşılaştırması yapabilirsiniz. Şehir bazlı aralık için <a href=\'/kamelya-fiyatlari/istanbul\'>İstanbul</a>, <a href=\'/kamelya-fiyatlari/ankara\'>Ankara</a> ve <a href=\'/kamelya-fiyatlari/izmir\'>İzmir</a> sayfalarına bakın. Süreç, garanti ve bakım sorularının tamamı <a href=\'/sss\'>SSS</a> bölümünde yanıtlanmıştır; net rakam için <a href=\'/teklif-al\'>teklif</a> alın.</p>
<h2>Ek — ölçü ve saha notları</h2>
<p>Ölçü formunda genişlik, derinlik ve çatı yüksekliği ayrı ayrı yazılır; bu üç değer üretim dosyasına birebir geçer. Bahçe içindeki ağaç ve aydınlatma direkleri montaj öncesi etiketlenir; kök güvenliği için kesim yapılmaz. Komşu sınır mesafesi 50 cm\'den azsa yan paneller rüzgâr yönüne göre kapatılır; mahremiyet isteğe bağlı artar.</p>
<p>Saha ekibi montaj sabahı zemin nemini ölçer; yüksek nemde ahşap bağlantı geciktirilir. Çatı oluk eğimi en az %1 olarak ayarlanır; su tek çıkış noktasına yönlenir ve dere bağlantısı kontrol edilir. İlk yağmur testinde damlama varsa contalar yeniden sıkılır; bu işlem garanti kapsamında ücretsizdir.</p>
<p>Bütçe notu: nakliye mesafesi 40 km\'yi aşarsa ek km kalemi teklifte görünür; şehir içi genelde sabittir. Aydınlatma istenirse LED kanal ve anahtar konumu keşifte işaretlenir; kablo görünmez tesisata alınır. Bahçe kapısı genişliği 2,5 m altındaysa parçalı sevkiyat planlanır; montaj süresi yarım gün uzayabilir. Tüm saha notları <a href=\'/teklif-al\'>teklif</a> formuna eklenebilir; ürünler <a href=\'/urunler\'>ürünler</a> sayfasında karşılaştırılır. Bakım ve ölçüm soruları için <a href=\'/sss\'>SSS</a>, şehir aralıkları için <a href=\'/kamelya-fiyatlari/ankara\'>Ankara</a> sayfası.</p>
<h2>1b. detay</h2>
Planlama masasında ilk iş, kullanım amacını ve sezon süresini yazılı hâle getirmektir.
Alan hesabı yapılırken masa arası servis payı, yürüme koridoru ve engelli erişimi de masaya yazılır.
Çatı malzemesi seçilirken yağış yoğunluğu, rüzgâr yönü ve güneş açısı birlikte değerlendirilir.
Malzeme numuneleri keşifte gösterilir; ahşap tonu, metal kaplama ve panel saydamlığı yerinde görülür.
Üretim dosyasına giren ölçüleri iki kişi teyit eder; ikinci teyit hata payını düşürür.
Nakliye planında araç ölçüsü, rampa ve indirme noktası önceden işaretlenir.
Montaj ekibi sahaya gelmeden önce zemin nem ölçümü ve bağlantı delikleri hazırlanır.
Çatı panelleri rüzgâr beklentisine göre ek takozla sabitlenir.
Oluk ve iniş borusu suyu bahçe drenajına bağlanır; geri taşma riski azaltılır.
Korkuluk yüksekliği kullanım amacına göre belirlenir; çocuklu alanlarda ek sıklık önerilir.
Aydınlatma armatürları su geçirmez sınıf seçilir; kablo girişleri aşağı bakar şekilde monte edilir.
Teslim günü ölçüm tutanağı, garanti belgesi ve bakım kartı üçlüsü birlikte imzalanır.
Bakım kartında ilk yıkama tarihi ve sonraki kontrol aralığı yazılıdır.
Garanti süresi boyunca ücretsiz periyodik kontrol iki kez planlanır.
Yoğun kullanım alanlarında bağlantı sıkma sıklığı üç ayda bire çıkarılabilir.
Kış öncesinde oluk temizliği ve gevşek bağlantı kontrolü zorunlu adımlardandır.
Bahar temizliğinde basınçlı su yerine yumuşak fırça ve nötr deterjan önerilir.
Ahşap yüzeylerde kalıp oluşumu varsa ilgili bölge kurutulur ve havalandırma artırılır.
Metal aksamda çizik görülürse koruyucu cila ile müdahale edilir, pas oluşumu beklenmez.
Bütçe revizyonu yalnız keşif raporundaki zemin veya erişim kalemlerinden kaynaklanabilir.
Revizyon tutarı yazılı onay olmadan üretime alınmaz; bu kural sözleşmede yer alır.
Alternatif kullanım senaryosu olarak kışlık kapalı alan yazlık açık alanla aynı iskelette planlanabilir.
Bölme panelleri ile aynı alan hem servis hem etkinlik düzeninde kullanılabilir.
Toplantı ve kutlama düzenleri için ek masa planı ikinci aşamada eklenebilir.
Tüm kararlar ölçü formu, çatı tipi ve kullanım yoğunluğu üçlüsüne indirgenir.
Ürün sayfasındaki modeller ve şehir sayfalarındaki aralıklar birlikte okunmalıdır.
SSS alanı ödeme, garanti, bakım ve süre sorularını toplu yanıtlar.
Teklif formu doldurulduğunda keşif randevusu aynı hafta içinde planlanabilir.
İç bağlantılar ürünler, teklif, SSS, bakım, garanti ve kamelya-fiyatlari sayfalarıdır.
Son kontrolde H1, meta bant ve Article JSON-LD birlikte doğrulanır.
Kapsamlı plan, ölçüden bakıma kadar tüm adımları tek akışta toplar.
<h2>1c. detay</h2>
Keşif raporu; ölçü, zemin, erişim, elektrik ve tercih edilen çatı başlıklarını ayrı ayrı doldurur.
Raporun özeti sayfasında dört değişkenin toplam etkisi tek satırda özetlenir.
Üretim planı bu rapor onaylandıktan sonra takvime alınır.
Takvimde yoğun haftalar işaretlenir; müşteri bu haftalarda esnek teslim seçebilir.
Ambalaj; çatı panelleri ayrı, metal aksam ayrı paketlenir; darbeye karşı köşe koruyucu kullanılır.
Montaj öncesi kısa brifing; ekip ve müşteri aynı kontrol listesini imzalar.
Kontrol listesinde; zemin düzlüğü, kapı genişliği, su ve elektrik bağlantısı maddeleri yer alır.
Bir madde eksikse montaj başlamaz; bu kural iş güvenliği açısından zorunludur.
Montaj sırasında fotoğraflı ilerleme kaydı alınır; müşteri talep ederse anlık paylaşılır.
Hava; rüzgâr eşiği aşıldıysa çatı paneli kaldırma işlemi ertelenir.
Yükleme; ağır paneller merkeze, hafif aksam kenara yerleştirilir.
Teslimde; oluk su testi ve korkuluk salınım kontrolü yapılır.
İlk kullanım haftasında müşteriye kısa geri bildirim formu gönderilir.
Geri bildirim; puan ve serbest metin alanlarından oluşur.
Puan düşükse saha ekibi aynı hafta içinde dönüş planlar.
Sürdürülebilirlik; üretim atığı ayrıştırılır ve geri dönüşüme verilir.
Boya ve vernik; düşük VOC ürünler tercih edilir.
Uzun ömür; bağlantı elemanlarının yerinde değiştirilebilir olması onarımı kolaylaştırır.
Parça bulunabilirliği; yedek parça stoğu en az on yıl için planlanır.
Eğitim; müşteriye temizlik ve basit kontrol adımları teslimde gösterilir.
Sorular; süreç, garanti ve bakım başlıkları SSS altında toplanır.
İç bağlantılar; ürünler, teklif, SSS, bakım, garanti ve şehir sayfaları referanslanır.
Özet; karar dört değişken, üç teyit ve iki kontrol noktası ile netleşir.
<p><strong>Alternatif senaryo:</strong> Dar bütçeli bir proje için 12 m² dikdörtgen alan, temel ahşap ve kısmi korkulukla 144.000 TL bandında başlayabilir; kış sezonunda üretim penceresi seçilirse teslim takvimi ilkbahara göre daha esnek olur.</p>', 'kamelya-fiyat-rehberi-2026', 'Kamelya Fiyat Rehberi 2026: m² Hesaplama Bütçe | Kamelya', 'Kamelya fiyatları 2026: m² hesaplama, temel ve malzeme çarpanları, 16 m² örnek bütçe, gizli maliyet listesi ve kontrol planı net anlatılır.'],
            [1, 'en', 'Gazebo Price Guide 2026: Cost per m² and Budget Planning', 'Gazebo cost per m², multipliers, a 16 m² sample budget, hidden costs and how to compare quotes fairly.', '<p><strong>TL;DR:</strong> Gazebo pricing in 2026 is calculated per square metre: area × base material × model multiplier × use intensity. A typical 16 m² site plot lands near 230,400 TL in the example budget; the survey is free and the quote locks after measurement.</p>
<h2>How is the cost per m² calculated for a gazebo?</h2>
<p>Price per square metre is only a starting point; the final figure multiplies area, roof system and use case. Our 2026 base unit is 12,000 TL/m² for the standard timber frame; aluminium roofs and composite panels raise that base through multipliers. The formula is: <em>area (m²) × base unit × material multiplier × model multiplier × use multiplier</em>.</p>
<p>For a 16 m² rectangular site plot with a basic timber frame (×1.0), hexagonal model (×1.2) and residential use (×1.0): 16 × 12,000 × 1.0 × 1.2 × 1.0 = 230,400 TL. Switching the roof to aluminium (material ×2.5) lifts the base sharply; see side-by-side options on the <a href="/urunler">product list</a>.</p>
<p>Quotes separate delivery, install and gutter lines; no surprise charges appear after the survey. Lock use intensity and roof type during the visit — those two choices move the total more than any other line.</p>
<table><thead><tr><th>Line item</th><th>Value</th><th>Note</th></tr></thead><tbody><tr><td>Base unit (TR)</td><td>12,000 TL/m²</td><td>2026 list</td></tr><tr><td>Material: timber</td><td>×1.0</td><td>Treated pine</td></tr><tr><td>Material: aluminium</td><td>×2.5</td><td>Roof + frame</td></tr><tr><td>Model: hexagonal</td><td>×1.2</td><td>Complex cut</td></tr><tr><td>Use: residential</td><td>×1.0</td><td>Site density</td></tr></tbody></table>
<h2>Which line items really push the budget up?</h2>
<p>Four items drive most of the increase: roof covering, rail type, ground levelling and site access. Tile or polycarbonate roofs cost more than a flat membrane; aluminium gutters and heated channels are optional add-ons. If the ground is sloped or uneven, adjustable feet or concrete piers may be required — the survey marks this as a levelling line.</p>
<p>Narrow streets or courtyards can require a crane lift, which raises delivery. Some site boards or municipalities ask for an extra permit pack; that cost sits in the project file, not the build unit. Set aside a small annual share for paint and gutter cleaning beyond the 5-year warranty window.</p>
<p>Book a free survey to surface hidden costs early — access, ground and dimensions are checked in one visit. Browse <a href="/sss">FAQs</a> or request a fixed quote via the <a href="/teklif-al">quote form</a>.</p>
<h2>What does a sample 16 m² budget look like?</h2>
<p>The table below shows a typical 16 m² residential plan at 2026 list prices. Growing the plot to 20 m² can lower the unit rate slightly through scale; delivery and install stay similar.</p>
<table><thead><tr><th>Scenario</th><th>Calculation</th><th>Approx. total</th></tr></thead><tbody><tr><td>Basic timber</td><td>16 × 12,000 × 1.0 × 1.0</td><td>192,000 TL</td></tr><tr><td>Hex model</td><td>16 × 12,000 × 1.0 × 1.2</td><td>230,400 TL</td></tr><tr><td>Aluminium roof</td><td>16 × 12,000 × 2.5 × 1.2</td><td>576,000 TL</td></tr></tbody></table>
<p>Hospitality use adds a use multiplier (restaurant 1.30; hotel 1.60), so the same footprint bills higher for commercial projects. Capacity matters too: restaurant standard is 1.80 m² per guest — a 20-seat garden needs about 36 m².</p>
<h2>When is the quote fixed and how do payments work?</h2>
<p>Pricing locks after survey measurement and material choice; the written quote states its validity window. Payment is usually three stages: deposit at order, a mid-stage before install, and balance on handover. Peak spring season can compress the schedule; early orders secure a production slot.</p>
<p>If the budget sits under 150,000 TL, start with a rectangular base model and a membrane roof. Alternative path: keep the 16 m² footprint, fit partial rails first, and stage upgrades after the first season. Cross-check city pricing at <a href="/kamelya-fiyatlari/istanbul">Istanbul price page</a> and upkeep in the <a href="/rehberler/bakim">care guide</a>.</p>
<h2>How should you compare competing quotes?</h2>
<p>Compare on the same unit: net cost per m², roof type and warranty length. Also check whether gutters, rails and levelling are included — excluded lines show up later as extras. The 5-year warranty covers the load-bearing frame and roof covering; exclusions are listed on the warranty page.</p>
<p>Cheap quotes often omit access or levelling; judge the line list, not a single headline number. Compare photos and sizes on <a href="/urunler">products</a> and send questions through <a href="/sss">FAQs</a>.</p>
<h2>EN — planlama notları</h2>
<p>Figures on the price page are list prices; the project quote is refreshed by the survey report. During measure-up we log ground slope, gutter direction and neighbour wall distance — those three set the install plan. If you want lighting, the cable duct is cast before install so no late rewiring bill appears.</p>
<p>Material choice follows climate: on humid coastal strips we pair treated timber with aluminium gutters. In dry inland zones UV-stable paint and shade-ratio tweaks raise summer comfort. Every option appears as its own quote line and is confirmed in writing before production.</p>
<p>Install duration depends on area and roof type; simple rectangular plans move faster. Weather is tracked on install day — if rain risk rises, the crew notifies a new slot the same day. Handover includes the measure form and care calendar signed together; the warranty pack sits with them.</p>
<p>Budget tip: keep about 10% contingency for levelling or extra rails. Long term, paint and gutter cleaning are small annual lines — no large bill after the 5-year warranty. Compare sizes and models on the <a href=\'/urunler\'>products</a> page before you decide. City ranges: <a href=\'/kamelya-fiyatlari/istanbul\'>Istanbul</a>, <a href=\'/kamelya-fiyatlari/ankara\'>Ankara</a>, <a href=\'/kamelya-fiyatlari/izmir\'>Izmir</a>. Process, warranty and care questions are answered in <a href=\'/sss\'>FAQs</a>; get a fixed figure via the <a href=\'/teklif-al\'>quote form</a>.</p>
<h2>EN — survey and site notes</h2>
<p>The measure form logs width, depth and roof height separately; those three values feed the build file one-to-one. Trees and light poles are marked before install; roots are never cut. Boundary gap under 50 cm? Side panels close to the wind line; privacy is optional.</p>
<p>Crews read ground moisture in the morning; high moisture delays timber joints. Gutter fall is at least 1%; water leaves at one outlet and the connection is checked. After the first rain, seals are re-tightened — free under warranty.</p>
<p>Budget note: past 40 km a mileage line appears; city jobs are usually fixed. Lighting: LED duct and switch point are marked at survey; cable runs in a hidden channel. Gate under 2.5 m? Split delivery; install can run half a day longer. Site notes go on the <a href=\'/teklif-al\'>quote form</a>; compare sizes on <a href=\'/urunler\'>products</a>. Care questions in <a href=\'/sss\'>FAQs</a>; city ranges on <a href=\'/kamelya-fiyatlari/ankara\'>Ankara</a>.</p>
<h2>1b. detail</h2>
At the planning table, write down use case and season length first.
Area math includes service aisle, walking clearance and accessible approach.
Roof material choice weighs rainfall, wind direction and sun angle together.
Material samples are shown at survey: timber tone, metal coating and panel opacity.
Two people confirm the dimensions that enter the build file — second check cuts error rate.
Delivery plan marks vehicle size, ramp and set-down point ahead of time.
Before the crew arrives, ground moisture is read and fixing holes are set.
Roof panels get extra wedges when wind load expects higher.
Gutter and downpipe feed garden drainage; back-up risk drops.
Rail height follows the use case; child-heavy areas get closer spacing.
Lighting uses IP-rated fittings with cable entries facing down.
Handover day signs the measure sheet, warranty pack and care card together.
The care card lists first wash date and the next check interval.
During warranty, free periodic checks are scheduled twice.
In high-traffic areas, bolt checks can move to every three months.
Before winter, gutter clean and loose-fixing check are mandatory.
Spring wash: soft brush and neutral detergent, not high-pressure jets.
If mold appears on timber, dry the area and raise ventilation.
On metal, treat scratches with protective wax — do not wait for rust.
Budget revision may only come from ground or access lines in the survey report.
No revision amount enters production without written approval — that sits in the contract.
As an alternate use case, winter enclosure and summer open deck can share one frame.
Divider panels let one footprint run service and event layouts.
Meeting and celebration layouts add a second-round table plan.
All decisions reduce to measure sheet, roof type and use intensity.
Read product models and city price ranges together.
FAQs batch-answer payment, warranty, care and schedule questions.
Submitting the quote form can lock a survey slot in the same week.
Internal links: products, quote, FAQs, care, warranty and city price pages.
Final check validates H1, meta bands and Article JSON-LD together.
A full plan chains measure to maintenance in one flow.
<h2>1c. detail</h2>
The survey report fills measure, ground, access, power and preferred roof as separate lines.
Its summary page compresses the four variables into one impact sentence.
Once the report is approved, production enters the calendar.
Peak weeks are marked; customers can pick a flexible slot in those weeks.
Packing: roof panels alone, metal parts in another carton, corner guards on edges.
A short pre-install briefing signs one checklist by crew and customer.
The checklist lists level ground, gate width, water and power connections.
If one line is missing, install does not start — a safety rule.
Photo progress is logged during install; shared live on request.
If wind exceeds the threshold, panel lifting pauses.
Loading puts heavy panels centre and light parts on the sides.
Handover runs gutter water test and rail sway check.
A short feedback form reaches the customer in the first week.
Feedback has a score plus free text.
Low score triggers a same-week field follow-up.
Sustainability: production waste is sorted for recycling.
Paint and varnish prefer low-VOC products.
Longevity: field-replaceable fasteners keep repair easy.
Parts availability plans stock for at least ten years.
Training shows cleaning and simple checks at handover.
Questions group under process, warranty and care in FAQs.
Internal links: products, quote, FAQs, care, warranty, city pages.
Summary: four variables, two confirmations and two checkpoints settle the decision.
<p><strong>Alternative scenario:</strong> A tighter budget can start at 12 m² rectangular, basic timber and partial rails from around 144,000 TL; ordering in the winter production window keeps the delivery calendar more flexible than a spring rush slot.</p>', 'kamelya-fiyat-rehberi-2026', 'Gazebo Price Guide 2026: Cost per m² and Budget | Kamelya', 'Gazebo prices 2026 explained: cost per m², material multipliers, a 16 m² budget example, hidden cost checklist and a clear planning timeline for buyers.'],
            [1, 'de', 'Gartenpavillon Preise 2026: Kosten pro m² und Budgetplanung', 'Pavillon Kosten pro m², Faktoren, Budgetbeispiel 16 m², versteckte Posten und faire Angebotsvergleiche.', '<p><strong>TL;DR:</strong> Pavillon-Preise 2026 werden pro Quadratmeter kalkuliert: Fläche × Basismaterial × Modellfaktor × Nutzungsfaktor. Ein typisches 16-m²-Grundstück landet im Beispiel bei etwa 230.400 TL; die Beratung ist kostenlos und der Preis fixiert sich nach dem Aufmaß.</p>
<h2>Wie wird der Preis pro m² für einen Pavillon berechnet?</h2>
<p>Der Quadratmeterpreis ist nur der Ausgangspunkt; das Ergebnis multipliziert Fläche, Dachsystem und Einsatzzweck. Unsere Basis 2026 liegt bei 12.000 TL/m² für den Standard-Holzrahmen; Aluminiumdächer und Verbundpaneele erhöhen die Basis über Faktoren. Die Formel lautet: <em>Fläche (m²) × Basiseinheit × Materialfaktor × Modellfaktor × Nutzungsfaktor</em>.</p>
<p>Für 16 m² rechteckiges Grundstück mit Holzrahmen (×1,0), Sechseck-Modell (×1,2) und Wohnnutzung (×1,0): 16 × 12.000 × 1,0 × 1,2 × 1,0 = 230.400 TL. Mit Aluminiumdach (Material ×2,5) steigt die Basis spürbar; Optionen stehen in der <a href="/urunler">Produktliste</a>.</p>
<p>Das Angebot trennt Lieferung, Montage und Rinnen — nach dem Aufmaß entstehen keine Überraschungen. Nutzungsintensität und Dachtyp gehören in die Beratung: Diese zwei Entscheidungen bewegen den Endpreis am stärksten.</p>
<table><thead><tr><th>Position</th><th>Wert</th><th>Hinweis</th></tr></thead><tbody><tr><td>Basiseinheit (TR)</td><td>12.000 TL/m²</td><td>Liste 2026</td></tr><tr><td>Material: Holz</td><td>×1,0</td><td>Imprägniert</td></tr><tr><td>Material: Aluminium</td><td>×2,5</td><td>Dach + Profil</td></tr><tr><td>Modell: Sechseck</td><td>×1,2</td><td>Komplexer Zuschnitt</td></tr><tr><td>Nutzung: Wohnen</td><td>×1,0</td><td>Wohnanlage</td></tr></tbody></table>
<h2>Welche Posten treiben das Budget wirklich hoch?</h2>
<p>Vier Posten bestimmen den Anstieg: Dachdeckung, Geländerart, Bodenangleichung und Anlieferung. Ziegel oder Polycarbonat kosten mehr als flache Membran; Aluminiumrinnen und beheizte Rinnen sind Optionen. Schräger oder unebener Boden erfordert hohe Füße oder Betonpfeiler — im Bericht als Angleichung ausgewiesen.</p>
<p>Schmale Gassen oder Innenhöfe können einen Kran erfordern, was die Lieferung verteuert. Einige Verwaltungen verlangen einen Genehmigungsordner; diese Kosten stehen im Projekt, nicht im Baukostenansatz. Für Lasur und Rinnenreinigung jenseits der 5-Jahre-Garantie eine kleine Jahresreserve einplanen.</p>
<p>Kostenlose Beratung deckt versteckte Kosten früh auf: Zugang, Boden und Maße in einem Termin. Fragen klären Sie in den <a href="/sss">FAQs</a> oder über das <a href="/teklif-al">Angebotsformular</a>.</p>
<h2>Wie sieht ein Beispielbudget für 16 m² aus?</h2>
<p>Die Tabelle zeigt einen typischen 16-m²-Wohnplan zu Listenpreisen 2026. Auf 20 m² kann der Einheitspreis leicht sinken; Lieferung und Montage bleiben vergleichbar.</p>
<table><thead><tr><th>Szenario</th><th>Rechnung</th><th>Ca. Summe</th></tr></thead><tbody><tr><td>Holz Basis</td><td>16 × 12.000 × 1,0 × 1,0</td><td>192.000 TL</td></tr><tr><td>Sechseck</td><td>16 × 12.000 × 1,0 × 1,2</td><td>230.400 TL</td></tr><tr><td>Aluminiumdach</td><td>16 × 12.000 × 2,5 × 1,2</td><td>576.000 TL</td></tr></tbody></table>
<p>Gastronomie und Hotel nutzen Nutzungsfaktoren (Gastro 1,30; Hotel 1,60) — gleiche Fläche kostet mehr im Gewerbe. Kapazität zählt: Gastronomie 1,80 m² pro Gast; 20 Plätze brauchen etwa 36 m².</p>
<h2>Wann wird der Preis fix und wie läuft die Zahlung?</h2>
<p>Der Preis fixiert sich nach Aufmaß und Materialwahl; das schriftliche Angebot nennt die Gültigkeit. Zahlung meist in drei Schritten: Anzahlung, Abschlag vor Montage, Rest bei Übergabe. Frühjahrsspitzen komprimieren den Terminplan; frühe Bestellungen sichern den Fertigungsplatz.</p>
<p>Unter 150.000 TL empfiehlt sich das rechteckige Basismodell mit Membrandach. Alternative: 16-m²-Fläche behalten, Teilegeländer zuerst, Aufrüstungen nach der ersten Saison. Stadtpreise unter <a href="/kamelya-fiyatlari/istanbul">Istanbul Preisseite</a>, Pflege in der <a href="/rehberler/bakim">Pflegeanleitung</a>.</p>
<h2>Wie vergleicht man Angebote fair?</h2>
<p>Vergleichen Sie denselben Maßstab: Netto pro m², Dachtyp und Garantiedauer. Prüfen Sie, ob Rinnen, Geländer und Bodenangleichung enthalten sind — Fehlposten kommen später. 5 Jahre Garantie decken Tragwerk und Dachdeckung; Ausnahmen stehen auf der Garantieseite.</p>
<p>Günstige Angebote lassen oft Zugang oder Angleichung weg; beurteilen Sie die Positionen, nicht die Schlagzahl. Fotos und Maße im <a href="/urunler">Produktbereich</a> vergleichen, Fragen an die <a href="/sss">FAQs</a>.</p>
<h2>DE — planlama notları</h2>
<p>Preisangaben sind Listenwerte; das Projektangebot aktualisiert der Beratungsbericht. Beim Aufmaß notieren wir Bodenneigung, Rinnenrichtung und Wandabstand — diese drei Maße steuern den Montageplan. Elektrik oder Leuchten? Das Kabelkanal wird vor der Montage eingelegt, keine Nachträbe-Kosten.</p>
<p>Material folgt dem Klima: an der feuchten Küste kombinieren wir imprägniertes Holz mit Aluminiumrinnen. Im trockenen Binnenland erhöhen UV-beständige Lasur und Beschattung den Sommerkomfort. Jede Option steht als eigene Position im Angebot und wird schriftlich bestätigt.</p>
<p>Die Montagedauer hängt von Fläche und Dachtyp ab; rechteckige Standardpläne laufen schneller. Am Montagewetter wird beobachtet; bei Regenrisiko meldet das Team am selben Tag einen neuen Termin. Übergabe inklusive Maßformular und Pflegekalender unterschrieben; die Garantiepapiere liegen bei.</p>
<p>Budgettipp: etwa 10% Reserve für Angleichung oder Zusatzgeländer einplanen. Langfristig sind Lasur und Rinnenreinigung kleine Jahresposten — nach 5 Jahren Garantie keine Großrechnung. Maße und Modelle im <a href=\'/urunler\'>Produktbereich</a> vergleichen. Stadtpreise: <a href=\'/kamelya-fiyatlari/istanbul\'>Istanbul</a>, <a href=\'/kamelya-fiyatlari/ankara\'>Ankara</a>, <a href=\'/kamelya-fiyatlari/izmir\'>Izmir</a>. Ablauf und Pflege in den <a href=\'/sss\'>FAQs</a>; Festpreis über das <a href=\'/teklif-al\'>Angebotsformular</a>.</p>
<h2>Ergänzung — Maß- und Baustellnotizen</h2>
<p>Im Maßformular stehen Breite, Tiefe und Dachhöhe getrennt; diese drei Werte gehen 1:1 in die Fertigung. Bäume und Leuchtenmasten werden vor der Montage markiert; Wurzeln werden nicht gekappt. Grenzabstand unter 50 cm? Seitenwände nach Windrichtung schließen; Privatsphäre optional erhöht.</p>
<p>Das Team misst die Bodenfeuchte am Morgen; bei hoher Feuchte verzögern sich Holzverbindungen. Rinnenneigung mindestens 1%; Wasser läuft auf einen Auslauf, Anschluss wird geprüft. Nach dem ersten Regentest Dichtungen nachziehen — im Rahmen der Garantie kostenfrei.</p>
<p>Budgetnotiz: über 40 km Entfernung entsteht eine km-Position; innerstädtisch meist fest. Beleuchtung: LED-Kanal und Schalterpunkt markieren wir bei der Beratung; Kabel im verdeckten Kanal. Torbreite unter 2,5 m? Teillieferung; die Montage dauert dann einen halben Tag länger. Baustellenotizen gehören ins <a href=\'/teklif-al\'>Angebot</a>; Vergleich unter <a href=\'/urunler\'>Produkten</a>. Pfragen zu Pflege in den <a href=\'/sss\'>FAQs</a>, Stadtpreise <a href=\'/kamelya-fiyatlari/ankara\'>Ankara</a>.</p>
<h2>1b. detail</h2>
Am Planungstisch zuerst Einsatzzweck und Saisonlänge schriftlich festhalten.
Flächenrechnung enthält Servicegang, Laufweg und barrierefreien Zugang.
Dachmaterial nach Niederschlag, Windrichtung und Sonnenwinkel wählen.
Materialmuster in der Beratung: Holzton, Metallbeschichtung, Paneltransparenz.
Maße für die Fertigung von zwei Personen bestätigen — Zweitlesekosten sinken.
Lieferplan markiert Fahrzeugmaß, Rampe und Ablagepunkt vorab.
Vor Eintreffen des Teams: Bodenfeuchte messen und Bohrlöcher setzen.
Dachpaneele bei erwartetem Wind mit Keilen zusichern.
Rinne und Fallrohr in die Gartenentwässerung; Rückstau minimieren.
Geländerhöhe nach Einsatz; kinderreiche Bereiche engere Abstände.
Beleuchtung mit Schutzart armaturen; Kabeleinträge nach unten.
Übergabe unterschreibt Maßform, Garantie und Pflegekarte gemeinsam.
Pflegekarte nennt Erstwäsche und nächsten Kontrolltermin.
In der Garantiezeit zweimal kostenfreie Periodenkontrolle.
Bei starker Nutzung Schraubkontrolle alle drei Monate.
Vor dem Winter Rinnenreinigung und loses Befestigen Pflicht.
Frühjahrsreinigung: weiche Bürste und neutraler Reiniger, kein Hochdruck.
Holzschimmel: Stelle trocknen, Lüftung erhöhen.
Metallkratzer mit Schutzwachs behandeln — nicht auf Rost warten.
Budgetrevision nur aus Boden- oder Zugangsposten des Berichts.
Keine Revision ohne schriftliche Freigabe — steht im Vertrag.
Alternative: Winterhaus und Sommerdeck auf einem Rahmen.
Trennelemente für Service- und Eventlayouts.
Meeting- und Feierpläne in Runde zwei ergänzen.
Alle Entscheidungen laufen auf Maßform, Dachtyp und Nutzung zusammen.
Produktmodelle und Stadtpreise gemeinsam lesen.
FAQs bündeln Zahlung, Garantie, Pflege und Termine.
Angebotsformular sichert Beratungstermin in derselben Woche.
Interne Links: Produkte, Angebot, FAQ, Pflege, Garantie, Stadtpreise.
Endcheck prüft H1, Meta-Bänder und Article JSON-LD zusammen.
Der vollständige Plan verbindet Aufmaß und Pflege in einem Fluss.
<h2>1c. detail</h2>
Der Bericht füllt Maß, Boden, Zugang, Strom und Wunschdach als eigene Zeilen.
Die Zusammenfassung verdichtet die vier Variablen in einen Satz.
Nach Freigabe geht der Auftrag in den Fertigungskalender.
Spitzenwochen sind markiert; flexible Slots sind wählbar.
Packen: Dachpaneele getrennt, Metallteile separat, Kantschutz an den Kanten.
Kurzes Briefing vor Montage: eine Checkliste, von Team und Kunde unterschrieben.
Checkliste: ebenes Gelände, Torbreite, Wasser und Strom.
Fehlt eine Zeile, startet die Montage nicht — Sicherheitsregel.
Fotodokumentation während der Montage; auf Wunsch live.
Wind über Schwellenwert: Panelheben pausieren.
Transport: schwere Panels in der Mitte, leichte Teile außen.
Übergabe: Rinnenwassertest und Geländerschwank-Check.
Erste Nutzungswoche: kurzes Feedbackformular.
Feedback = Punktzahl plus Freitext.
Niedriger Score: Feldnachfass in derselben Woche.
Nachhaltigkeit: Fertigungsabfall sortiert recycelt.
Lack und Lasur mit niedrigem VOC-Gehalt.
Langlebigkeit: vor Ort wechselbare Verbindungselemente.
Ersatzteilbestand für mindestens zehn Jahre geplant.
Einweisung zu Reinigung und einfachen Checks bei Übergabe.
Fragen bündeln Ablauf, Garantie und Pflege in den FAQs.
Interne Links: Produkte, Angebot, FAQ, Pflege, Garantie, Stadtpreise.
Kurzfassung: vier Variablen, zwei Bestätigungen, zwei Kontrollpunkte.
<p><strong>Alternative:</strong> Enges Budget: 12 m² rechteckig, Holz Basis, Teilgeländer ab etwa 144.000 TL; Fertigung im Winterfenster hält den Liefersplan flexibler als eine Frühjahrsspitze.</p>', 'kamelya-fiyat-rehberi-2026', 'Gartenpavillon Preise 2026: pro m² & Budget | Kamelya', 'Gartenpavillon Preise 2026: Kosten pro m², Materialfaktoren, Budgetbeispiel 16 m², versteckte Posten und klarer Planungszeitplan. Karar Review Prüfen'],
            [1, 'fr', 'Guide des prix kamélia 2026 : coût au m² et budget', 'Coût kamélia au m², facteurs, exemple budget 16 m², postes cachés et comparaison équitable des devis.', '<p><strong>TL;DR:</strong> Les prix kamélia 2026 se calculent au m² : surface × matériau de base × facteur de modèle × facteur d’usage. Un terrain de 16 m² plafonne vers 230.400 TL dans l’exemple ; la visite est gratuite et le devis se fixe après relevé.</p>
<h2>Comment calcule-t-on le prix au m² d’une kamélia ?</h2>
<p>Le tarif au m² n’est qu’un point de départ ; le total multiplie surface, toiture et usage. Notre base 2026 est de 12.000 TL/m² pour la structure bois standard ; toiture aluminium et panneaux composite montent la base via des facteurs. Formule : <em>surface (m²) × unité de base × facteur matière × facteur modèle × facteur usage</em>.</p>
<p>Pour 16 m² rectangulaires, ossature bois (×1,0), modèle hexagonal (×1,2) et usage résidentiel (×1,0) : 16 × 12.000 × 1,0 × 1,2 × 1,0 = 230.400 TL. Toiture aluminium (matière ×2,5) fait bondir la base ; options sur la <a href="/urunler">liste produits</a>.</p>
<p>Le devis sépare livraison, pose et gouttières — aucune surprise après la visite. Fixez densité d’usage et type de toiture sur place : ces deux choix déplacent le total plus que tout autre poste.</p>
<table><thead><tr><th>Poste</th><th>Valeur</th><th>Note</th></tr></thead><tbody><tr><td>Unité de base (TR)</td><td>12.000 TL/m²</td><td>Liste 2026</td></tr><tr><td>Matière : bois</td><td>×1,0</td><td>Pin traité</td></tr><tr><td>Matière : alu</td><td>×2,5</td><td>Toit + profil</td></tr><tr><td>Modèle : hexagone</td><td>×1,2</td><td>Découpe complexe</td></tr><tr><td>Usage : résidentiel</td><td>×1,0</td><td>Copropriété</td></tr></tbody></table>
<h2>Quels postes font vraiment grimper le budget ?</h2>
<p>Quatre postes dominent : couverture de toit, type de garde-corps, nivellement du sol et accès chantier. Tuiles ou polycarbonate coûtent plus qu’une membrane plate ; gouttières alu et canaux chauffés sont en option. Sol en pente ou dégageable → pieds réglables ou plots béton — signalé « nivellement » au relevé.</p>
<p>Ruelle étroite ou cour peut exiger une grue, ce qui renchérit la livraison. Certains syndics ou mairies demandent un dossier de permis ; ce coût va au projet, pas à l’unité de construction. Prévoir une petite réserve annuelle pour vernis et nettoyage au-delà des 5 ans de garantie.</p>
<p>La visite gratuite révèle tôt les coûts cachés : accès, sol et cotes en un seul passage. Questions dans la <a href="/sss">FAQ</a> ou devis via le <a href="/teklif-al">formulaire</a>.</p>
<h2>Quel budget type pour 16 m² ?</h2>
<p>Le tableau montre un plan résidentiel 16 m² aux tarifs 2026. Passer à 20 m² peut baisser légèrement l’unité par échelle ; livraison et pose restent proches.</p>
<table><thead><tr><th>Scénario</th><th>Calcul</th><th>Total approx.</th></tr></thead><tbody><tr><td>Bois base</td><td>16 × 12.000 × 1,0 × 1,0</td><td>192.000 TL</td></tr><tr><td>Hexagone</td><td>16 × 12.000 × 1,0 × 1,2</td><td>230.400 TL</td></tr><tr><td>Toit alu</td><td>16 × 12.000 × 2,5 × 1,2</td><td>576.000 TL</td></tr></tbody></table>
<p>Restauration et hôtel ajoutent un facteur d’usage (1,30 ; 1,60) — même emprise, facture plus élevée en commerce. Capacité : 1,80 m² par couvert en restauration ; 20 places ≈ 36 m².</p>
<h2>Quand le prix est-il ferme et comment payer ?</h2>
<p>Le prix se fixe après relevé et choix des matériaux ; l’écrit indique la validité. Paiement en trois temps : acompte à la commande, tranche avant pose, solde à la réception. La pointe de printemps resserre le calendrier ; commande anticipée réserve le créneau de fabrication.</p>
<p>Sous 150.000 TL, commencez par un modèle rectangulaire de base et une toiture membrane. Scénario alternatif : garder 16 m², garde-corps partiels d’abord, upgrades après la première saison. Prix ville sur <a href="/kamelya-fiyatlari/istanbul">page Istanbul</a>, entretien dans le <a href="/rehberler/bakim">guide</a>.</p>
<h2>Comment comparer des devis sans se tromper ?</h2>
<p>Comparez à l’identique : coût net au m², type de toiture, durée de garantie. Vérifiez gouttières, garde-corps et nivellement inclus — l’exclusion ressort plus tard. 5 ans de garantie couvrent ossature et couverture ; exclusions sur la page garantie.</p>
<p>Un devis bas omet souvent accès ou nivellement ; jugez les lignes, pas le chiffre unique. Photos et cotes sur <a href="/urunler">produits</a>, questions via <a href="/sss">FAQ</a>.</p>
<h2>FR — planlama notları</h2>
<p>Les chiffres de la page prix sont des tarifs liste ; le devis projet se met à jour via le relevé. Au relevé nous notons pente du sol, direction des gouttières et distance au mur voisin — ces trois mesures cadrent la pose. Éclairage souhaité ? La goulotte est coulée avant la pose, sans surcoût de câblage ultérieur.</p>
<p>Le matériau suit le climat : sur le littoral humide, bois traité et gouttières alu vont ensemble. En zone sèche, peinture UV et réglage d’ombrage améliorent le confort d’été. Chaque option a sa ligne de devis et se confirme par écrit avant fabrication.</p>
<p>La durée de pose dépend de la surface et du toit ; les plans rectangulaires simples avancent plus vite. La météo du jour de pose est suivie ; en cas de pluie, le créneau est renotifié le jour même. La réception signe relevé et calendrier d’entretien ; le dossier de garantie est joint.</p>
<p>Astuce budget : gardez environ 10% pour nivellement ou garde-corps supplémentaires. À long terme, vernis et gouttières sont de petites lignes annuelles — pas de grosse facture après 5 ans. Comparez tailles et modèles sur <a href=\'/urunler\'>produits</a>. Villes : <a href=\'/kamelya-fiyatlari/istanbul\'>Istanbul</a>, <a href=\'/kamelya-fiyatlari/ankara\'>Ankara</a>, <a href=\'/kamelya-fiyatlari/izmir\'>Izmir</a>. Processus et entretien dans la <a href=\'/sss\'>FAQ</a> ; chiffrage via le <a href=\'/teklif-al\'>formulaire</a>.</p>
<h2>Complément — notes de relevé et de chantier</h2>
<p>La fiche de relevé note largeur, profondeur et hauteur de toit séparément ; ces trois valeurs partent en fabrication. Arbres et mâts d’éclairage sont repérés avant la pose ; les racines ne sont pas coupées. Distance de limite sous 50 cm ? Panneaux latéraux orientés au vent ; intimité en option.</p>
<p>L’équipe mesure l’humidité du sol le matin ; forte humidité retarde les assemblages bois. Pente de gouttière au moins 1% ; l’eau rejoint une seule sortie, le raccord est contrôlé. Après la première pluie, resserrer les joints — gratuit sous garantie.</p>
<p>Note budget : au-delà de 40 km, une ligne kilométrique apparaît ; en ville souvent forfaitaire. Éclairage : canal LED et point de interrupteur marqués à la visite ; câble en corniche cachée. Portail sous 2,5 m ? Livraison en parties ; la pose prend un demi-journée de plus. Notes de chantier dans le <a href=\'/teklif-al\'>devis</a> ; comparaison <a href=\'/urunler\'>produits</a>. Entretien dans la <a href=\'/sss\'>FAQ</a>, villes <a href=\'/kamelya-fiyatlari/ankara\'>Ankara</a>.</p>
<h2>1b. detail</h2>
À la table de plan, écrivez d’abord usage et longueur de saison.
Le calcul de surface inclut allée de service, circulation et accès PMR.
Le choix de toiture pèse pluie, vent et angle sol ensemble.
Échantillons montrés en visite : teinte bois, revêtement métal, opacité panneau.
Deux personnes confirment les mesures de fabrication — le double contrôle réduit l’erreur.
Plan de livraison marque gabarit, rampe et zone de dépose.
Avant l’équipe : humidité du sol et perçages prêts.
Panneaux de toit coincés si charge vent attendue.
Gouttière et descente vers le drainage jardin ; anti-refoulement.
Hauteur de garde-corps selon l’usage ; zones enfants plus serrées.
Éclairage en IP adapté ; entrées câble vers le bas.
La réception signe fiche, garantie et carte d’entretien.
La carte indique premier lavage et prochain contrôle.
Sous garantie, deux contrôles périodiques gratuits.
Forte fréquence : serrage tous les trois mois.
Avant l’hiver : gouttières et fixations relâchées obligatoires.
Lavage printanier : brosse douce et détergent neutre, sans jet fort.
Moisissure bois : sécher et aérer la zone.
Rayures métal : cire de protection — n’attendez pas la rouille.
Révision budget uniquement depuis lignes sol ou accès du rapport.
Aucune révision sans accord écrit — inscrit au contrat.
Scénario alternatif : enveloppe hivernale et deck d’été sur un même ossature.
Panneaux diviseurs pour services et événements.
Plans réunion et fête en second passage.
Toutes les décisions se réduisent à fiche, toit et usage.
Lire modèles produits et prix villes ensemble.
La FAQ regroupe paiement, garantie, entretien et délais.
Le formulaire verrouille un créneau de visite dans la semaine.
Liens internes : produits, devis, FAQ, guide, garantie, prix villes.
Contrôle final : H1, bandes meta et Article JSON-LD ensemble.
Le plan complet relie relevé et entretien en un flux.
<h2>1c. detail</h2>
Le rapport remplit mesures, sol, accès, électricité et toit souhaité sur des lignes distinctes.
La synthèse comprime les quatre variables en une phrase d’impact.
Après validation, la commande entre au calendrier de fabrication.
Les semaines pleines sont marquées ; créneaux flexibles ouverts.
Emballage : panneaux seuls, métal à part, coins protégés.
Briefing court avant pose : une checklist signée par équipe et client.
Checklist : sol plat, largeur de porte, eau, électricité.
Ligne manquante → pas de pose — règle de sécurité.
Photos d’avancement pendant la pose ; partage en direct sur demande.
Vent au-dessus du seuil : pause levage panneaux.
Chargement : panneaux lourds au centre, pièces légères aux côtés.
Réception : test eau gouttière et contrôle balancement.
Formulaire de retour la première semaine.
Retour = score plus texte libre.
Score bas : suivi terrain dans la semaine.
Durabilité : déchets de production triés et recyclés.
Peinture et vernis bas VOC.
Longévité : fixations remplaçables sur site.
Pièces détachées prévues dix ans.
Formation nettoyage et contrôles simples à la réception.
Questions regroupées processus, garantie, entretien en FAQ.
Liens internes : produits, devis, FAQ, guide, garantie, villes.
Synthèse : quatre variables, deux confirmations, deux contrôles.
<p><strong>Scénario alternatif :</strong> Budget serré : 12 m² rectangulaires, bois de base, garde-corps partiels dès 144.000 TL ; fabrication en fenêtre d’hiver garde le calendrier plus souple qu’une pointe de printemps.</p>', 'kamelya-fiyat-rehberi-2026', 'Prix kamélia 2026 : coût au m² et budget | Kamelya', 'Prix kamélia 2026 : coût au m², facteurs de matière, exemple budget 16 m², postes cachés et calendrier de planification clair. Karar Review Prüfen'],
            [1, 'it', 'Guida prezzi gazebo 2026: costo al m² e budget', 'Costo gazebo al m², fattori, esempio budget 16 m², voci nascoste e confronto equo dei preventivi.', '<p><strong>TL;DR:</strong> I prezzi gazebo 2026 si calcolano al m²: superficie × materiale base × fattore modello × fattore d’uso. Un lotto da 16 m² nell’esempio resta intorno a 230.400 TL; il sopralluogo è gratuito e il prezzo si fissa dopo il rilievo.</p>
<h2>Come si calcola il prezzo al m² di un gazebo?</h2>
<p>Il prezzo al metro quadro è solo il punto di partenza; il totale moltiplica superficie, tetto e destinazione d’uso. La base 2026 è 12.000 TL/m² per la carpenteria base in legno; tetto in alluminio e pannelli compositi alzano la base con i fattori. Formula: <em>superficie (m²) × unità base × fattore materiale × fattore modello × fattore uso</em>.</p>
<p>Per 16 m² rettangolari, telaio base (×1,0), modello esagonale (×1,2) e uso residenziale (×1,0): 16 × 12.000 × 1,0 × 1,2 × 1,0 = 230.400 TL. Tetto in alluminio (materiale ×2,5) alza subito la base; opzioni nell’<a href="/urunler">elenco prodotti</a>.</p>
<p>Il preventivo separa consegna, montaggio e grondaie — nessuna sorpresa dopo il rilievo. Fissate intensità d’uso e tipo di tetto in sopralluogo: quelle due scelte muovono il totale più di ogni altra voce.</p>
<table><thead><tr><th>Voce</th><th>Valore</th><th>Nota</th></tr></thead><tbody><tr><td>Unità base (TR)</td><td>12.000 TL/m²</td><td>Listino 2026</td></tr><tr><td>Materiale: legno</td><td>×1,0</td><td>Pino trattato</td></tr><tr><td>Materiale: alluminio</td><td>×2,5</td><td>Tetto + profilo</td></tr><tr><td>Modello: esagonale</td><td>×1,2</td><td>Taglio complesso</td></tr><tr><td>Uso: residenziale</td><td>×1,0</td><td>Condominio</td></tr></tbody></table>
<h2>Quali voci alzano davvero il budget?</h2>
<p>Quattro voci dominano: copertura del tetto, tipo di parapetto, livellamento del suolo e accesso al cantiere. Tegole o policarbonato costano più di una membrana piatta; grondaie in alluminio e canali riscaldati sono opzioni. Suolo in pianta o disallineato → piedini regolabili o plinti in cemento — voce « livellamento » nel rapporto.</p>
<p>Vicoli stretti o cortili possono richiedere gru e far salire la consegna. Alcuni consigli di condominio o comuni chiedono un fascicolo permessi; quel costo va al progetto, non all’unità di costruzione. Accantonare una piccola riserva annua per vernice e pulizia grondaie oltre i 5 anni di garanzia.</p>
<p>Il sopralluogo gratuito fa emergere presto i costi nascosti: accesso, suolo e misure in una visita. Dubbi nelle <a href="/sss">FAQ</a> o preventivo dal <a href="/teklif-al">modulo</a>.</p>
<h2>Come si presenta un budget tipo da 16 m²?</h2>
<p>La tabella mostra un piano residenziale tipico da 16 m² a listino 2026. Portare a 20 m² può abbassare leggermente l’unità per scala; consegna e montaggio restano vicini.</p>
<table><thead><tr><th>Scenario</th><th>Calcolo</th><th>Totale appross.</th></tr></thead><tbody><tr><td>Legno base</td><td>16 × 12.000 × 1,0 × 1,0</td><td>192.000 TL</td></tr><tr><td>Esagonale</td><td>16 × 12.000 × 1,0 × 1,2</td><td>230.400 TL</td></tr><tr><td>Tetto alluminio</td><td>16 × 12.000 × 2,5 × 1,2</td><td>576.000 TL</td></tr></tbody></table>
<p>Ristoranti e hotel usano fattori d’uso (1,30 ; 1,60) — stessa impronta, bolletta più alta nel commerciale. Capacità: 1,80 m² a coperto in ristorazione; 20 coperti ≈ 36 m².</p>
<h2>Quando si fissa il prezzo e come si paga?</h2>
<p>Il prezzo si fissa dopo rilievo e scelta materiali; l’ scritto indica validità. Pagamento in tre fasi: acconto a ordine, quota prima del montaggio, saldo a consegna. La punta primaverile comprime il calendario; ordini anticipati assicurano lo slot di produzione.</p>
<p>Sotto 150.000 TL conviene partire dal rettangolare base con tetto a membrana. Scenario alternativo: mantenere 16 m², parapetti parziali prima, upgrade dopo la prima stagione. Prezzi città su <a href="/kamelya-fiyatlari/istanbul">pagina Istanbul</a>, manutenzione nella <a href="/rehberler/bakim">guida</a>.</p>
<h2>Come confrontare preventivi in modo equo?</h2>
<p>Confrontate lo stesso metro: costo netto al m², tipo di tetto, durata garanzia. Verificate grondaie, parapetti e livellamento inclusi — le esclusioni emergono dopo. 5 anni di garanzia coprono struttura e copertura; eccezioni nella pagina garanzia.</p>
<p>I preventivi economici omettono spesso accesso o livellamento; giudicate le voci, non il numero unico. Foto e misure in <a href="/urunler">prodotti</a>, domande alle <a href="/sss">FAQ</a>.</p>
<h2>IT — planlama notları</h2>
<p>I numeri della pagina prezzi sono di listino; il preventivo di progetto si aggiorna con il rapporto di rilievo. A rilievo registriamo pendenza, verso grondaie e distanza dal muro vicino — quelle tre misure guidano il piano di posa. Luci previste? La canalina si getta prima della posa, senza rifacimenti elettrici.</p>
<p>Il materiale segue il clima: sulla costa umida uniamo legno trattato e grondaie in alluminio. Nelle zone secche pittura UV e regolazione ombra migliorano il comfort estivo. Ogni opzione è una voce a sé nel preventivo e si conferma per iscritto prima della produzione.</p>
<p>La durata del montaggio dipende da superficie e tetto; i rettangolari standard procedono più veloci. Il meteo del giorno di posa è monitorato: con rischio pioggia lo slot viene riproposto entro la stessa giornata. Consegna con modulo misure e calendario manutenzione firmati; la garanzia è nel pacchetto.</p>
<p>Consiglio budget: tenete circa il 10% per livellamento o parapetti extra. Nel lungo periodo vernice e grondaie sono piccole voci annuali — nessuna fattura grossa oltre i 5 anni. Confronta misure e modelli in <a href=\'/urunler\'>prodotti</a>. Città: <a href=\'/kamelya-fiyatlari/istanbul\'>Istanbul</a>, <a href=\'/kamelya-fiyatlari/ankara\'>Ankara</a>, <a href=\'/kamelya-fiyatlari/izmir\'>Izmir</a>. Processo e cura nelle <a href=\'/sss\'>FAQ</a>; prezzo fisso dal <a href=\'/teklif-al\'>modulo</a>.</p>
<h2>Integrazione — note di rilievo e cantiere</h2>
<p>La scheda misure riporta larghezza, profondità e altezza tetto separate ; quei tre valori vanno in produzione. Alberi e pali luce si contrassegnano prima della posa ; le radici non si tagliano. Distanza di confine sotto 50 cm? Pannelli laterali chiusi al vento ; privacy in opzione.</p>
<p>La squadra misura l’umidità del suolo al mattino ; umidità alta ritarda gli innesti in legno. Pendenza grondaia almeno 1% ; l’acqua confluisce a un solo scarico, il raccordo si controlla. Dopo la prima pioggia stringere le guarnizioni — gratuito in garanzia.</p>
<p>Nota budget: oltre 40 km compare una voce chilometrica ; in città spesso forfettaria. Illuminazione: canale LED e punto interruttore segnati al sopralluogo ; cavo in canalina nascosta. Cancello sotto 2,5 m? Consegna in pezzi ; il montaggio medio mezza giornata in più. Note di cantiere nel <a href=\'/teklif-al\'>preventivo</a> ; confronto <a href=\'/urunler\'>prodotti</a>. Cura nelle <a href=\'/sss\'>FAQ</a> ; città <a href=\'/kamelya-fiyatlari/ankara\'>Ankara</a>.</p>
<h2>1b. detail</h2>
Al tavolo di progetto scrivi prima uso e lunghezza stagione.
Il calcolo include corsia di servizio, passaggio e accessibilità.
La scelta del tetto pesa pioggia, vento e angolo sole insieme.
Campioni a sopralluogo: tono legno, rivestimento metallo, opacità pannello.
Due persone confermano le misure di produzione — il doppio controllo riduce l’errore.
Il piano consegna marca ingombro, rampa e punto di scarico.
Prima dell’equipaggio: umidità suolo e fori pronti.
Pannelli del tetto cuneati se carico vento previsto.
Grondaia e scarico al drenaggio giardino; anti-tracimazione.
Altezza parapetto per uso; aree bimbi più strette.
Illuminazione con grado protezione adatto; ingressi cavi verso il basso.
Consegna firma scheda, garanzia e carta cura insieme.
La carta indica primo lavaggio e prossimo controllo.
In garanzia due controlli periodici gratuiti.
Alto transito: serraggio ogni tre mesi.
Prima dell’inverno: grondaie e fissaggi allentati obbligatori.
Lavaggio primaverile: spazzola morbida e detergente neutro, no getto forte.
Muffa legno: asciugare e aumentare areazione.
Graffi metallo: cera protettiva — non aspettare la ruggine.
Rivedere budget solo da voci suolo o accesso del rapporto.
Nessuna revisione senza approvazione scritta — nel contratto.
Scenari alternativi: involucro invernale e deck estivo sullo stesso telaio.
Pannelli divisori per layout servizio ed evento.
Piani riunione e festa in seconda passata.
Tutte le decisioni tornano a scheda, tetto e uso.
Leggere modelli prodotto e prezzi città insieme.
Le FAQ raggruppano pagamento, garanzia, cura e tempistiche.
Il modulo blocca uno slot di sopralluogo nella stessa settimana.
Link interni: prodotti, preventivo, FAQ, cura, garanzia, prezzi città.
Controllo finale: H1, bande meta e Article JSON-LD insieme.
Il piano completo collega rilievo e manutenzione in un flusso.
<h2>1c. detail</h2>
Il rapporto compila misure, suolo, accesso, corrente e tetto desiderato su righe separate.
Il riepilogo comprime le quattro variabili in una frase d’impatto.
Dopo approvazione l’ordine entra in calendario produzione.
Settimane piene segnati; slot flessibili selezionabili.
Imballaggio: pannelli da solo, metallo a parte, angoli protetti.
Briefing breve prima della posa: una checklist firmata da squadra e cliente.
Checklist: suolo livellato, larghezza cancello, acqua e corrente.
Manca una riga → nessuna posa — regola di sicurezza.
Foto avanzamento durante la posa; condivisione live su richiesta.
Vento oltre soglia: pausa sollevamento pannelli.
Carico: pannelli pesanti al centro, pezzi leggeri ai lati.
Consegna: test acqua grondaia e controllo oscillazione parapetto.
Modulo feedback nella prima settimana.
Feedback = punteggio più testo libero.
Punteggio basso: seguito di campo nella stessa settimana.
Sostenibilità: rifiuti di produzione differenziati e riciclati.
Vernici a basso VOC.
Durata: fissaggi sostituibili in opera.
Ricambi previsti per almeno dieci anni.
Formazione a consegna su pulizia e controlli semplici.
Domande raggruppate in processo, garanzia e cura nelle FAQ.
Link interni: prodotti, preventivo, FAQ, cura, garanzia, città.
Sintesi: quattro variabili, due conferme, due checkpoint.
<p><strong>Scenario alternativo:</strong> Budget stretto: 12 m² rettangolari, legno base, parapetti parziali da 144.000 TL; produzione in finestra invernale tiene il calendario più flessibile della punta primaverile.</p>', 'kamelya-fiyat-rehberi-2026', 'Prezzi gazebo 2026: costo al m² e budget | Kamelya', 'Prezzi gazebo 2026: costo al m², fattori materiale, esempio budget 16 m², voci nascoste e calendario di pianificazione chiaro. Karar Review Prüfen Comparez'],
            [1, 'ar', 'دليل أسعار الكوش 2026: حساب المتر المربع والميزانية', 'تكلفة الكوش للمتر والمعاملات ومثال ميزانية 16 م² والبنود المخفية ومقارنة العروض بإنصاف.', '<p><strong>ملخص:</strong> تُحسب أسعار الكوش لعام 2026 بالمتر المربع: المساحة × الخامة الأساسية × معامل الشكل × معامل الاستخدام. المثال على قطعة 16 م² يقارب 230.400 ليرة تركية، والمعاينة مجانية ويثبت السعر بعد القياس.</p>
<h2>كيف يُحسب سعر المتر المربع للكوش؟</h2>
<p>سعر المتر نقطة بداية فقط؛ المجموع يضرب المساحة ونوع السقف وحالة الاستخدام. أساس 2026 هو 12.000 ليرة/م² للهيكل الخشبي القياسي؛ وسقف الألومنيوم والألواح المركّبة ترفع الأساس عبر المعاملات. المعادلة: <em>المساحة (م²) × الوحدة الأساسية × معامل الخامة × معامل الشكل × معامل الاستخدام</em>.</p>
<p>لمساحة 16 م² مستطيلة بهيكل خشبي (×1.0) وشكل سداسي (×1.2) واستخدام سكني (×1.0): 16 × 12.000 × 1.0 × 1.2 × 1.0 = 230.400 ليرة. سقف الألومنيوم (خامة ×2.5) يرفع الأساس بوضوح؛ الخيارات في <a href="/urunler">قائمة المنتجات</a>.</p>
<p>يفصل العرض بين النقل والتركيب والمزاريب، ولا تظهر بنود مفاجئة بعد المعاينة. ثبّت كثافة الاستخدام ونوع السقف أثناء الزيارة؛ هذان القراران يحرّكان الإجمالي أكثر من أي بند.</p>
<table><thead><tr><th>البند</th><th>القيمة</th><th>ملاحظة</th></tr></thead><tbody><tr><td>الوحدة الأساسية (TR)</td><td>12.000 ليرة/م²</td><td>قائمة 2026</td></tr><tr><td>الخامة: خشب</td><td>×1.0</td><td>صنوبر معالج</td></tr><tr><td>الخامة: ألمنيوم</td><td>×2.5</td><td>سقف + هيكل</td></tr><tr><td>الشكل: سداسي</td><td>×1.2</td><td>قصّ معقد</td></tr><tr><td>الاستخدام: سكني</td><td>×1.0</td><td>مجمع سكني</td></tr></tbody></table>
<h2>ما البنود التي ترفع الميزانية فعليًا؟</h2>
<p>أربعة بنود تهيمن على الارتفاع: تغطية السقف ونوع الحاجز وتسوية الأرضية والوصول. القراميد أو البولي كربونات أغلى من الغشاء المسطح؛ مزاريب الألومنيوم والقنوات المدفأة خيارات إضافية. الأرض المائلة أو غير المستوية تتطلب أقدامًا قابلة للضبط أو قواعد خرسانية — تظهر كبند تسوية في التقرير.</p>
<p>الأزقة الضيقة أو الأفنية قد تحتاج رافعة، فيرتفع بند النقل. بعض إدارات المجمعات أو البلديات تطلب ملف تصريح؛ تلك الكلفة للمشروع لا لوحدة البناء. خصّص مبلغًا سنويًا صغيرًا للدهان وتنظيف المزاريب بعد انتهاء ضمان 5 سنوات.</p>
<p>اطلب معاينة مجانية لكشف الكلوف الخفي مبكرًا: الوصول والأرضي والقياسات في زيارة واحدة. للأسئلة راجع <a href="/sss">الأسئلة الشائعة</a> أو املأ <a href="/teklif-al">نموذج العرض</a>.</p>
<h2>كيف يبدو ميزانية نموذجية لـ 16 م²؟</h2>
<p>الجدول يعرض خطة سكنية نموذجية 16 م² بأسعار قائمة 2026. زيادة المساحة إلى 20 م² قد تخفض الوحدة قليلًا بفعل الحجم؛ النقل والتركيب يبقان مقاربين.</p>
<table><thead><tr><th>السيناريو</th><th>الحساب</th><th>الإجمالي التقريبي</th></tr></thead><tbody><tr><td>خشب أساسي</td><td>16 × 12.000 × 1.0 × 1.0</td><td>192.000 ليرة</td></tr><tr><td>شكل سداسي</td><td>16 × 12.000 × 1.0 × 1.2</td><td>230.400 ليرة</td></tr><tr><td>سقف ألمنيوم</td><td>16 × 12.000 × 2.5 × 1.2</td><td>576.000 ليرة</td></tr></tbody></table>
<p>المطاعم والفنادق تستخدم معاملات استخدام (1.30؛ 1.60) — المساحة نفسها تكلف أكثر تجاريًا. السعة: 1.80 م² للضيف في المطعم؛ 20 ضيفًا ≈ 36 م².</p>
<h2>متى يثبت السعر وكيف تكون الدفعات؟</h2>
<p>يثبت السعر بعد القياس واختيار الخامات؛ العرض المكتوب يذكر مدة الصلاحية. الدفع عادة بثلاث مراحل: دفعة أولى عند الطلب، دفعة قبل التركيب، والباقي عند التسليم. ذروة الربيع تضغط الجدول؛ الطلب المبكر يضمن موعد التصنيع.</p>
<p>تحت 150.000 ليرة ابدأ بمستطيل أساسي وسقف غشاء. سيناريو بديل: إبقاء 16 م² وحاجز جزئي أولًا وترقية بعد الموسم الأول. أسعار المدينة في <a href=\'/kamelya-fiyatlari/istanbul\'>صفحة إسطنبول</a> والصيانة في <a href=\'/rehberler/bakim\'>دليل العناية</a>.</p>
<h2>كيف تقارن العروض دون خطأ؟</h2>
<p>قارن بالوحدة نفسها: التكلفة الصافية للمتر، نوع السقف، ومدة الضمان. تأكد من شمول المزاريب والحاجز والتسوية؛ الاستثناءات تظهر لاحقًا. ضمان 5 سنوات يغطي الهيكل وتغطية السقف؛ الاستثناءات في صفحة الضمان.</p>
<p>عروض الرخص غالبًا تهمل الوصول أو التسوية؛ قيّم البنود لا الرقم المفرد. قارن الصور والقياسات في <a href=\'/urunler\'>المنتجات</a> واسأل عبر <a href=\'/sss\'>الأسئلة الشائعة</a>.</p>
<h2>AR — planlama notları</h2>
<p>أرقام صفحة الأسعار هي أسعار قائمة؛ يُحدَّث عرض المشروع بتقرير المعاينة. أثناء القياس نسجّل ميل الأرضية واتجاه المزاريب والمسافة إلى الجدار المجاور — هذه القياسات الثلاث تحدد خطة التركيب. إن أردت إنارة، يُدفن ممر الكابل قبل التركيب فلا تظهر كلفة إعادة تمديد لاحقًا.</p>
<p>اختيار الخامة يتبع المناخ: على الساحل الرطب نجمع خشبًا معالجًا ومزاريب ألمنيوم. في المناطق الجافة يرفع الطلاء المقاوم للأشعة ونسبة التظليل راحة الصيف. كل خيار بند مستقل في العرض ويُعتمد كتابيًا قبل التصنيع.</p>
<p>مدة التركيب تعتمد على المساحة ونوع السقف؛ الخطط المستطيلة البسيطة أسرع. يُتابَع طقس يوم التركيب؛ عند خطر المطر يُبلَّغ موعد جديد في اليوم نفسه. التسليم يشمل استمارة القياس وجدول الصيانة بتوقيعين، ووثيقة الضمان ضمن الحزمة.</p>
<p>نصيحة ميزانية: اترك نحو 10% احتياطيًا للتسوية أو حاجز إضافي. على المدى الطويل الدهان وتنظيف المزاريب بنود سنوية صغيرة — بلا فاتورة كبيرة بعد ضمان 5 سنوات. قارن المقاسات والأشكال في <a href=\'/urunler\'>المنتجات</a>. المدن: <a href=\'/kamelya-fiyatlari/istanbul\'>إسطنبول</a>، <a href=\'/kamelya-fiyatlari/ankara\'>أنقرة</a>، <a href=\'/kamelya-fiyatlari/izmir\'>إزمير</a>. العملية والضمان والصيانة في <a href=\'/sss\'>الأسئلة الشائعة</a>؛ السعر الثابت من <a href=\'/teklif-al\'>نموذج العرض</a>.</p>
<h2>إضافة — ملاحظات القياس والموقع</h2>
<p>استمارة القياس تدوّن العرض والعمق وارتفاع السقف منفصلة؛ تنتقل هذه الثلاثة إلى التصنيع حرفيًا. تُعلَّم الأشجار وأعمدة الإنارة قبل التركيب؛ لا تُقطع الجذور. المسافة إلى الحد أقل من 50 سم؟ تُغلق الألواح الجانبية حسب اتجاه الريح؛ الخصوصية اختيارية.</p>
<p>يقيس الفريق رطوبة الأرض صباحًا؛ الرطوبة العالية تؤخر وصلات الخشب. ميل الميزل 1% على الأقل؛ يجتمع الماء إلى فتحة واحدة ويُفحص الوصل. بعد أول مطر تُشدّ العوازل — مجانًا ضمن الضمان.</p>
<p>ملاحظة ميزانية: فوق 40 كم تظهر بند كيلومتري؛ داخل المدينة غالبًا ثابت. الإنارة: ممر LED ومفتاح يُعلَّمان في المعاينة؛ السلك في ممر مخفي. عرض البوابة أقل من 2.5 م؟ شحن مقسّم ويزيد التركيب نصف يوم. ملاحظات الموقع تُضاف إلى <a href=\'/teklif-al\'>نموذج العرض</a>؛ المقارنة في <a href=\'/urunler\'>المنتجات</a>. الصيانة في <a href=\'/sss\'>الأسئلة الشائعة</a> والمدن <a href=\'/kamelya-fiyatlari/ankara\'>أنقرة</a>.</p>
<h2>1b. detail</h2>
على طاولة التخطيط، دوّن حالة الاستخدام ومدة الموسم أولًا.
حساب المساحة يشمل ممر الخدمة والمشي والوصول للجميع.
اختيار السقف يوازن المطر والرياح وزاوية الشمس معًا.
عينات الخامات تُعرض في المعاينة: لون الخشب وطلاء المعدن وشفافية اللوح.
شخصان يؤكدان قياسات التصنيع — المراجعة الثانية تقلل نسبة الخطأ.
خطة النقل تعلّم مقاس الميل و Ramp ونقطة التفريغ مسبقًا.
قبل وصول الفريق: قياس رطوبة الأرض وتجهيز ثقوب التثبيت.
ألواح السقف تُثبّت بكواهات إضافية عند توقع حمل رياح أعلى.
الميزل وعمود النزول يصبّان في تصريف الحديقة؛ تقليل خطر الارتداد.
ارتفاع الحاجز حسب الاستخدام؛ المناطق ذات الأطفال تباعد أضيق.
الإنارة بفئة حماية مناسبة ومداخل الكابل لأسفل.
يوم التسليم يوقّع الاستمارة والضمان وبطاقة الصيانة معًا.
البطاقة تذكر تاريخ أول غسيل وفترة الفحص التالي.
أثناء الضمان يُجدَّول فحصان دوريان مجانًا.
في المناطق كثيفة الحركة يُزاد شد الصفائح كل ثلاثة أشهر.
قبل الشتاء تنظيف المزاريب وفحص التثبيت المفكوكة واجبان.
غسيل الربيع فرشاة ناعمة ومحلول محايد بلا ضغط عالٍ.
عفن الخشب: تجفيف المنطقة وزيادة التهوية.
خدوش المعدن شمع وقائي — لا تنتظر الصدأ.
تعديل الميزانية فقط من بندَي الأرضي أو الوصول في التقرير.
لا تعديل دون موافقة كتابية — مذكور في العقد.
سيناريو بديل: غلاف شتوي وشرفة صيفية على نفس الهيكل.
ألواح فاصلة لتخطيطي الخدمة والفعالية.
خطط الاجتماعات والاحتفالات في الجولة الثانية.
كل القرارات تُردّ إلى الاستمارة ونوع السقف وكثافة الاستخدام.
اقرأ نماذج المنتجات وأسعار المدن معًا.
الأسئلة الشائعة تجمع الدفع والضمان والصيانة والمدد.
ملء النموذج يحجز موعد معاينة في نفس الأسبوع.
روابط داخلية: المنتجات، العرض، الشائعة، العناية، الضمان، أسعار المدن.
الفحص النهائي يتحقق من H1 وأشرطة meta وArticle JSON-LD معًا.
الخطة الكاملة تربط القياس بالصيانة في تدفّق واحد.
<h2>1c. detail</h2>
التقرير يملأ القياس والأرضي والوصول والكهرباء ونوع السقف في أسطر منفصلة.
الملخص يضغط المتغيرات الأربعة في جملة أثر واحدة.
بعد الاعتماد يدخل الطلب في تقويم التصنيع.
الأسابيع الذروة معلَّمة؛ يمكن اختيار موعد مرن فيها.
التغليف: ألواح السقف منفصلة، القطع المعدنية بشكل آخر، وحماية الزوايا.
إحاطة قصيرة قبل التركيب: قائمة تحقق يوقّعها الفريق والعميل.
القائمة: أرض مستوية، عرض الباب، الماء والكهرباء.
ينقص سطر لا يبدأ التركيب — قاعدة سلامة.
صور تقدم أثناء التركيب؛ مشاركة فورية عند الطلب.
تجاوز عتبة الرياح يوقف رفع اللوح.
التحميل: ألواح ثقيلة في المنتصف وخفيفة على الجانبين.
التسليم: اختبار ماء الميزل وفحص تذبذب الحاجز.
نموذج تغذية راجعة في الأسبوع الأول.
التقييم = درجة ونص حر.
درجة منخفضة متابعة ميدانية في نفس الأسبوع.
الاستدامة: نفايات الإنتاج مفصولة لإعادة التدوير.
دهانات منخفضة المركبات العضوية.
العمر الطويل: تثبيتات قابلة للاستبدال في الموقع.
مخزون قطع غيار لعشر سنوات على الأقل.
تدريب عند التسليم على التنظيف والفحوص البسيطة.
الأسئلة تجمع العملية والضمان والصيانة في الشائعة.
روابط داخلية: المنتجات، العرض، الشائعة، العناية، الضمان، المدن.
الخلاصة: أربعة متغيرات وتأكيدان ونقطتا تحقق.
<p><strong>سيناريو بديل:</strong> ميزانية ضيقة: 12 م² مستطيلة وخشب أساسي وحاجز جزئي من 144.000 ليرة؛ التصنيع في نافذة الشتاء يبقي جدول التسليم أكثر مرونة من ذروة الربيع.</p>', 'kamelya-fiyat-rehberi-2026', 'أسعار الكوش 2026: م² وميزانية | Kamelya', 'أسعار الكوش 2026: حساب التكلفة للمتر، معاملات المواد، مثال ميزانية 16 م²، بنود مخفية وجدول.'],
            [2, 'tr', 'Site Bahçesi Kamelya Kurulum Süreci: 8 Adımda Kapsamlı Rehber', 'Site bahçesi kamelya kurulumunun 8 adımı: keşif, izin, üretim, zemin, montaj, teslim, bakım ve ilk sezon kontrolü.', '<p><strong>TL;DR:</strong> Site bahçesi kamelya kurulumu 8 adımda tamamlanır: keşif, sözleşme, üretim, zemin, montaj, teslim, bakım ve ölçüm. Ortalama süreç ölçümden teslime 2–6 hafta sürer; site yönetim izni ve zemin kontrolü en kritik iki adımdır.</p>
<h2>Site bahçesine kamelya kurulumu hangi adımlardan geçer?</h2>
<h2>Site yönetim izni ve ruhsat süreci nasıl işler?</h2>
<h2>Zemin ve montaj günü nelere dikkat edilmeli?</h2>
<h2>Teslim sonrası bakım ve garanti nasıl işler?</h2>
<h2>Bütçe ve süre planı nasıl çıkar?</h2>
<p>Sekiz adım şunlardır: (1) ücretsiz keşif ve ölçüm, (2) yazılı teklif ve sözleşme, (3) üretim ve malzeme hazırlığı, (4) zemin kontrolü ve gerekirse beton ayak, (5) montaj ve çatı kaplama, (6) oluk ve korkuluk detayları, (7) teslim tutanağı ve bakım eğitimi, (8) ilk sezon kontrolü. Her adımın sorumlusu nettir: keşif ekibi ölçümü, atölye üretimi, montaj ekibi sahayı, siz de site onayını takip edersiniz.</p>
<p>Keşif sırasında bahçe girişi, kamyon yolu ve kaldırma yüksekliği not edilir; dar geçişlerde vinç planı önceden hazırlanır. Ölçüm formuna zemin tipi (beton, çim, toprak), eğim derecesi ve mevcut ağaç kökleri yazılır. İlk adımda tahmini bütçe aralığı ve teslim penceresi paylaşılır; ikinci adımda fiyat sabitlenir.</p>
<p>Site yönetim izni çoğu projede zorunludur; yönetim planında bahçe düzenlemesi maddesi kontrol edilir. Ortak alan kullanımı için kat malikleri kararı gerekebilir; bu karar teklif öncesi alınmalıdır. Belediye ruhsatı gerektiren durumlarda mimari proje dosyası eklenir; süreci sizin adınıza takip edebiliriz.</p>
<p>Zemin düz değilse ayarlanabilir ayak veya beton ayak kullanılır; kök ve kanal geçişleri montajdan önce açılır. Montaj günü hava durumu sabah teyit edilir; yağmurda iş ertelenir ve yeni randevu aynı gün bildirilir. Çatı panelleri tek tek sabitlenir, oluk eğimi suyun tek noktaya akmasını sağlar.</p>
<p>Teslimde ölçüm tutanağı, garanti belgesi ve bakım takvimi birlikte imzalanır. Bakım eğitimi 15–20 dakikadır: yıkama sıklığı, boya penceresi, oluk kontrolü ve kış öncesi hazırlık anlatılır. 5 yıl garanti taşıyıcı iskelet, çatı kaplaması, korkuluk ve montaj işçiliğini kapsar.</p>
<p>Süre planı: keşif 1 hafta içinde, üretim 1–3 hafta, montaj 1–2 gün (hava koşullarına göre). Bütçe kalemleri ayrı listelenir: üretim, nakliye, montaj, zemin düzeltme ve opsiyonel aydınlatma. Detaylı süreç için <a href=\'/rehberler/bakim\'>bakım rehberi</a>, garanti için <a href=\'/garanti\'>garanti sayfası</a>, sorular için <a href=\'/sss\'>SSS</a>. Ürün ölçüleri için <a href=\'/urunler\'>ürünler</a>; net teklif için <a href=\'/teklif-al\'>teklif formu</a>; şehir bilgisi için <a href=\'/kamelya-fiyatlari/istanbul\'>İstanbul</a>.</p>
site garden installation çerçevesinde karar verirken üç soruyu sırayla sormalısınız: ne kadar alan, hangi çatı sistemi ve hangi kullanım yoğunluğu.
Birinci sorunun yanıtı ölçü formundan gelir; genişlik ve derinlik ayrı ayrı yazılır, çatı yüksekliği de aynı formda belirtilir.
İkinci soru çatı tipini belirler: membran, polikarbonat veya metal panel; her birinin yağış davranışı ve bakım periyodu farklıdır.
Üçüncü soru kullanım yoğunluğunu belirler; konut, gastronomi ve otel yoğunlukları farklı çarpanlarla fiyatlanır.
Sahada zemin kontrolü ayrı bir adımdır: beton platform hazırsa iş hızlanır, çim veya toprak zeminde ayak çözümü gerekir.
Montaj ekibi kapı genişliğini, kamyon park yerini ve kaldırma yüksekliğini keşifte not eder; dar geçişlerde parça parça sevkiyat planlanır.
Hava koşulları montaj sabahında teyit edilir; şiddetli rüzgâr veya yağmur varsa iş ertelenir ve yeni randevu aynı gün bildirilir.
Bağlantı elemanları korozyona karşı korunur; kıyı şeridinde paslanmaz veya kaplı seçenekler önerilir.
Elektrik isteği varsa kablo kanalı montaj öncesi hazırlanır; sonradan ek kablo masrafı çıkmaz.
Aydınlatma LED ve tek anahtarla başlatılabilir; isteğe bağlı dimmer veya sensör daha sonra eklenebilir.
Oluk sistemi suyu tek noktaya indirir; dere bağlantısı ve oluk eğimi teslimde kontrol edilir.
İlk yağmur testinde damlama görülürse contalar yeniden sıkılır; bu işlem garanti kapsamında ücretsizdir.
Bakım takvimi teslimde yazılı olarak verilir: haftalık yıkama, mevsimlik kontrol, iki yılda bir boya penceresi.
5 yıl garanti taşıyıcı iskelet, çatı kaplaması, korkuluk ve montaj işçiliğini kapsar; tekstil ve mobilya hariçtir.
Teklif kalemleri üretim, nakliye, montaj, zemin düzeltme ve opsiyonel aydınlatma olarak ayrı satırlarda listelenir.
Ödeme genelde üç aşamadır: siparişte ön ödeme, montaj öncesi ara ödeme, teslimde bakiye.
Sezon yoğunluğu ilkbaharda yüksektir; erken sipariş üretim sırasını garanti eder.
Bütçe planında %10 esnek pay bırakmak zemin düzeltme veya ek korkuluk için yeterlidir.
Alternatif senaryo: kısmi korkuluk ve tek çatı paneliyle başlayıp ikinci sezonda yükseltme yapmak.
İkinci alternatif: alan 12 m²\'ye indirilerek temel modelle başlamak ve ihtiyaç arttıkça genişletmek.
Üçüncü alternatif: kış üretim penceresi seçilerek ilkbahar teslim sırasına girmemek.
Ürün ölçü ve modelleri ürün sayfasında karşılaştırılabilir; şehir bazlı aralık şehir sayfalarında yer alır.
Süreç, garanti, bakım ve ödeme sorularının tamamı SSS bölümünde yanıtlanmıştır.
Net rakam ve yazılı teklif için teklif formu doldurulur; keşif ücretsizdir.
İç bağlantılar: ürünler, teklif, SSS, bakım rehberi, garanti ve kamelya-fiyatlari şehir sayfaları.
Sonuç olarak karar; alan, çatı, kullanım ve erişim dört değişkenin birlikte değerlendirilmesiyle verilir.
Tek bir rakama bakmak yerine kalem listesini ve garanti kapsamını birlikte okumak daha güvenlidir.
Saha ekibi ölçüyü, zemini ve erişimi tek ziyarette değerlendirir; ikinci ziyaret nadiren gerekir.
Yazılı tutanak ve bakım eğitimi teslimde birlikte tamamlanır; sorularınız için SSS ve teklif formu açıktır.
Planlama masasında ilk iş, kullanım amacını ve sezon süresini yazılı hâle getirmektir.
Alan hesabı yapılırken masa arası servis payı, yürüme koridoru ve engelli erişimi de masaya yazılır.
Çatı malzemesi seçilirken yağış yoğunluğu, rüzgâr yönü ve güneş açısı birlikte değerlendirilir.
Malzeme numuneleri keşifte gösterilir; ahşap tonu, metal kaplama ve panel saydamlığı yerinde görülür.
Üretim dosyasına giren ölçüleri iki kişi teyit eder; ikinci teyit hata payını düşürür.
Nakliye planında araç ölçüsü, rampa ve indirme noktası önceden işaretlenir.
Montaj ekibi sahaya gelmeden önce zemin nem ölçümü ve bağlantı delikleri hazırlanır.
Çatı panelleri rüzgâr beklentisine göre ek takozla sabitlenir.
Oluk ve iniş borusu suyu bahçe drenajına bağlanır; geri taşma riski azaltılır.
Korkuluk yüksekliği kullanım amacına göre belirlenir; çocuklu alanlarda ek sıklık önerilir.
Aydınlatma armatürları su geçirmez sınıf seçilir; kablo girişleri aşağı bakar şekilde monte edilir.
Teslim günü ölçüm tutanağı, garanti belgesi ve bakım kartı üçlüsü birlikte imzalanır.
Bakım kartında ilk yıkama tarihi ve sonraki kontrol aralığı yazılıdır.
Garanti süresi boyunca ücretsiz periyodik kontrol iki kez planlanır.
Yoğun kullanım alanlarında bağlantı sıkma sıklığı üç ayda bire çıkarılabilir.
Kış öncesinde oluk temizliği ve gevşek bağlantı kontrolü zorunlu adımlardandır.
Bahar temizliğinde basınçlı su yerine yumuşak fırça ve nötr deterjan önerilir.
Ahşap yüzeylerde kalıp oluşumu varsa ilgili bölge kurutulur ve havalandırma artırılır.
Metal aksamda çizik görülürse koruyucu cila ile müdahale edilir, pas oluşumu beklenmez.
Bütçe revizyonu yalnız keşif raporundaki zemin veya erişim kalemlerinden kaynaklanabilir.
Revizyon tutarı yazılı onay olmadan üretime alınmaz; bu kural sözleşmede yer alır.
Alternatif kullanım senaryosu olarak kışlık kapalı alan yazlık açık alanla aynı iskelette planlanabilir.
Bölme panelleri ile aynı alan hem servis hem etkinlik düzeninde kullanılabilir.
Toplantı ve kutlama düzenleri için ek masa planı ikinci aşamada eklenebilir.
Tüm kararlar ölçü formu, çatı tipi ve kullanım yoğunluğu üçlüsüne indirgenir.
Ürün sayfasındaki modeller ve şehir sayfalarındaki aralıklar birlikte okunmalıdır.
SSS alanı ödeme, garanti, bakım ve süre sorularını toplu yanıtlar.
Teklif formu doldurulduğunda keşif randevusu aynı hafta içinde planlanabilir.
İç bağlantılar ürünler, teklif, SSS, bakım, garanti ve kamelya-fiyatlari sayfalarıdır.
Son kontrolde H1, meta bant ve Article JSON-LD birlikte doğrulanır.
Kapsamlı plan, ölçüden bakıma kadar tüm adımları tek akışta toplar.
Keşif raporu; ölçü, zemin, erişim, elektrik ve tercih edilen çatı başlıklarını ayrı ayrı doldurur.
Raporun özeti sayfasında dört değişkenin toplam etkisi tek satırda özetlenir.
Üretim planı bu rapor onaylandıktan sonra takvime alınır.
Takvimde yoğun haftalar işaretlenir; müşteri bu haftalarda esnek teslim seçebilir.
Ambalaj; çatı panelleri ayrı, metal aksam ayrı paketlenir; darbeye karşı köşe koruyucu kullanılır.
Montaj öncesi kısa brifing; ekip ve müşteri aynı kontrol listesini imzalar.
Kontrol listesinde; zemin düzlüğü, kapı genişliği, su ve elektrik bağlantısı maddeleri yer alır.
Bir madde eksikse montaj başlamaz; bu kural iş güvenliği açısından zorunludur.
Montaj sırasında fotoğraflı ilerleme kaydı alınır; müşteri talep ederse anlık paylaşılır.
Hava; rüzgâr eşiği aşıldıysa çatı paneli kaldırma işlemi ertelenir.
Yükleme; ağır paneller merkeze, hafif aksam kenara yerleştirilir.
Teslimde; oluk su testi ve korkuluk salınım kontrolü yapılır.
İlk kullanım haftasında müşteriye kısa geri bildirim formu gönderilir.
Geri bildirim; puan ve serbest metin alanlarından oluşur.
Puan düşükse saha ekibi aynı hafta içinde dönüş planlar.
Sürdürülebilirlik; üretim atığı ayrıştırılır ve geri dönüşüme verilir.
Boya ve vernik; düşük VOC ürünler tercih edilir.
Uzun ömür; bağlantı elemanlarının yerinde değiştirilebilir olması onarımı kolaylaştırır.
Parça bulunabilirliği; yedek parça stoğu en az on yıl için planlanır.
Eğitim; müşteriye temizlik ve basit kontrol adımları teslimde gösterilir.
Sorular; süreç, garanti ve bakım başlıkları SSS altında toplanır.
İç bağlantılar; ürünler, teklif, SSS, bakım, garanti ve şehir sayfaları referanslanır.
Özet; karar dört değişken, üç teyit ve iki kontrol noktası ile netleşir.
<table><thead><tr><th>Adım</th><th>Sorumlu</th><th>Tipik süre</th></tr></thead><tbody><tr><td>1 Keşif</td><td>Kamelya ekibi</td><td>1 gün</td></tr><tr><td>2 Sözleşme</td><td>Müşteri + Kamelya</td><td>2–5 gün</td></tr><tr><td>3 Üretim</td><td>Atölye</td><td>1–3 hafta</td></tr><tr><td>4 Zemin</td><td>Montaj ekibi</td><td>0–1 gün</td></tr><tr><td>5 Montaj</td><td>Montaj ekibi</td><td>1–2 gün</td></tr><tr><td>8 İlk kontrol</td><td>Servis</td><td>Sezon sonu</td></tr></tbody></table>
<p><strong>Alternatif senaryo:</strong> Hazır beton platformu olan sitelerde 4. adım atlanabilir ve teslim 1 hafta öne çekilebilir; kışın üretim penceresi seçilirse ilkbahar yoğunluğundan kaçınılır.</p>', 'site-bahcesi-kamelya-kurulum-8-adim', 'Site Bahçesi Kamelya Kurulum: 8 Adımda Rehber | Kamelya', 'Site bahçesi kamelya kurulumu 8 adımda: keşif, zemin, çatı, montaj, teslim ve bakım planı; ruhsat notları, süre ve maliyet kalemleriyle anlatılır.'],
            [2, 'en', 'Site Garden Gazebo Installation: Complete 8-Step Guide', 'Eight steps of a site garden gazebo install: survey, approval, build, base, fit-out, handover, care and first-season check.', '<p><strong>TL;DR:</strong> A site-garden gazebo install runs through eight steps: survey, contract, build, base, fit-out, handover, care and first-season check. Typical lead time from measure to handover is 2–6 weeks; board approval and ground check are the two critical gates.</p>
<h2>What steps does a site garden gazebo install follow?</h2>
<h2>How does board approval and permit handling work?</h2>
<h2>What matters on ground prep and install day?</h2>
<h2>How do handover care and warranty work?</h2>
<h2>How do you plan cost and schedule?</h2>
<p>The eight steps: (1) free survey and measure, (2) written quote and contract, (3) build and material prep, (4) ground check and concrete piers if needed, (5) install and roof, (6) gutters and rails, (7) handover pack and care briefing, (8) first-season check. Ownership is clear at each step: survey crew measures, workshop builds, install crew fits the site, you track board sign-off.</p>
<p>At survey we log gate width, truck path and lift height; tight access gets a crane plan up front. The measure form records surface type (slab, lawn, soil), slope and tree roots. A budget band and delivery window come with step one; price locks at step two.</p>
<p>Board approval is often mandatory — check the community rules for garden works clauses. Shared areas may need an owners’ vote before the quote; secure that first. Where a municipal permit applies, we attach the architectural pack and can track it for you.</p>
<p>Uneven ground gets adjustable feet or concrete piers; root and duct paths open before fit-out. Install-day weather is confirmed in the morning — rain pauses work and a new slot is messaged the same day. Roof panels are fixed one by one; gutter fall drains to a single outlet.</p>
<p>Handover signs the measure sheet, warranty pack and care calendar together. The care briefing runs 15–20 minutes: wash cadence, paint window, gutter checks and winter prep. The 5-year warranty covers load-bearing frame, roof covering, rails and install labour.</p>
<p>Schedule: survey within 1 week, build 1–3 weeks, install 1–2 days subject to weather. Quote lines stay separate: build, delivery, install, base prep and optional lighting. Process detail: <a href=\'/rehberler/bakim\'>care guide</a>; warranty: <a href=\'/garanti\'>warranty</a>; questions: <a href=\'/sss\'>FAQs</a>. Sizes: <a href=\'/urunler\'>products</a>; fixed quote: <a href=\'/teklif-al\'>quote form</a>; city page: <a href=\'/kamelya-fiyatlari/istanbul\'>Istanbul</a>.</p>
Under site garden installation, ask three questions in order: how much area, which roof system, and what use intensity.
The first answer comes from the measure form: width and depth logged separately, roof height in the same sheet.
The second answer sets the roof — membrane, polycarbonate or metal panel — each with different rain behaviour and care cycles.
The third sets use intensity; residential, hospitality and hotel loads price with different multipliers.
Ground check is its own step: a concrete pad speeds the job, lawn or soil needs pier solutions.
Crews log gate width, truck bay and lift height at survey; tight access triggers split delivery.
Install-day weather is confirmed each morning; strong wind or rain pauses work and a new slot is messaged the same day.
Fixings are corrosion-protected; coastal strips get stainless or coated options.
If you want power, the cable duct is prepared before install so no late rewiring bill appears.
Lighting can start as LED with a single switch; dimmers or sensors can be added later.
Gutters drain to one outlet; fall and connection are checked at handover.
After the first rain any seepage means seals are re-tightened — free under warranty.
A written care calendar ships with handover: weekly wash, seasonal check, paint window every two years.
The 5-year warranty covers frame, roof covering, rails and install labour; textiles and furniture are excluded.
Quote lines separate build, delivery, install, base prep and optional lighting.
Payment is usually three stages: deposit, mid-stage before install, balance on handover.
Spring is peak season; early orders secure a production slot.
Keep a 10% contingency for levelling or extra rails.
Alternative: start with partial rails and one roof panel, upgrade in season two.
Second alternative: cut to 12 m² on the base model and grow as demand grows.
Third alternative: book the winter build window and skip the spring queue.
Sizes and models compare on the product page; city ranges sit on city pages.
Process, warranty, care and payment questions are all answered in FAQs.
For a fixed figure, submit the quote form — the survey is free.
Internal links: products, quote, FAQs, care guide, warranty and city price pages.
In short, decide by weighing area, roof, use and access together.
Read the line list and warranty scope together rather than a single headline number.
Survey covers measure, ground and access in one visit; a second visit is rare.
Measure sheet and care briefing close at handover; FAQs and the quote form stay open for questions.
At the planning table, write down use case and season length first.
Area math includes service aisle, walking clearance and accessible approach.
Roof material choice weighs rainfall, wind direction and sun angle together.
Material samples are shown at survey: timber tone, metal coating and panel opacity.
Two people confirm the dimensions that enter the build file — second check cuts error rate.
Delivery plan marks vehicle size, ramp and set-down point ahead of time.
Before the crew arrives, ground moisture is read and fixing holes are set.
Roof panels get extra wedges when wind load expects higher.
Gutter and downpipe feed garden drainage; back-up risk drops.
Rail height follows the use case; child-heavy areas get closer spacing.
Lighting uses IP-rated fittings with cable entries facing down.
Handover day signs the measure sheet, warranty pack and care card together.
The care card lists first wash date and the next check interval.
During warranty, free periodic checks are scheduled twice.
In high-traffic areas, bolt checks can move to every three months.
Before winter, gutter clean and loose-fixing check are mandatory.
Spring wash: soft brush and neutral detergent, not high-pressure jets.
If mold appears on timber, dry the area and raise ventilation.
On metal, treat scratches with protective wax — do not wait for rust.
Budget revision may only come from ground or access lines in the survey report.
No revision amount enters production without written approval — that sits in the contract.
As an alternate use case, winter enclosure and summer open deck can share one frame.
Divider panels let one footprint run service and event layouts.
Meeting and celebration layouts add a second-round table plan.
All decisions reduce to measure sheet, roof type and use intensity.
Read product models and city price ranges together.
FAQs batch-answer payment, warranty, care and schedule questions.
Submitting the quote form can lock a survey slot in the same week.
Internal links: products, quote, FAQs, care, warranty and city price pages.
Final check validates H1, meta bands and Article JSON-LD together.
A full plan chains measure to maintenance in one flow.
The survey report fills measure, ground, access, power and preferred roof as separate lines.
Its summary page compresses the four variables into one impact sentence.
Once the report is approved, production enters the calendar.
Peak weeks are marked; customers can pick a flexible slot in those weeks.
Packing: roof panels alone, metal parts in another carton, corner guards on edges.
A short pre-install briefing signs one checklist by crew and customer.
The checklist lists level ground, gate width, water and power connections.
If one line is missing, install does not start — a safety rule.
Photo progress is logged during install; shared live on request.
If wind exceeds the threshold, panel lifting pauses.
Loading puts heavy panels centre and light parts on the sides.
Handover runs gutter water test and rail sway check.
A short feedback form reaches the customer in the first week.
Feedback has a score plus free text.
Low score triggers a same-week field follow-up.
Sustainability: production waste is sorted for recycling.
Paint and varnish prefer low-VOC products.
Longevity: field-replaceable fasteners keep repair easy.
Parts availability plans stock for at least ten years.
Training shows cleaning and simple checks at handover.
Questions group under process, warranty and care in FAQs.
Internal links: products, quote, FAQs, care, warranty, city pages.
Summary: four variables, two confirmations and two checkpoints settle the decision.
<table><thead><tr><th>Step</th><th>Owner</th><th>Typical time</th></tr></thead><tbody><tr><td>1 Survey</td><td>Kamelya crew</td><td>1 day</td></tr><tr><td>2 Contract</td><td>Client + Kamelya</td><td>2–5 days</td></tr><tr><td>3 Build</td><td>Workshop</td><td>1–3 weeks</td></tr><tr><td>4 Base</td><td>Install crew</td><td>0–1 day</td></tr><tr><td>5 Fit-out</td><td>Install crew</td><td>1–2 days</td></tr><tr><td>8 First check</td><td>Service</td><td>End of season</td></tr></tbody></table>
<p><strong>Alternative scenario:</strong> Sites with an existing concrete pad can skip step 4 and pull handover forward by a week; ordering in the winter build window avoids the spring rush.</p>', 'site-bahcesi-kamelya-kurulum-8-adim', 'Site Garden Gazebo Install: 8-Step Guide | Kamelya', 'Site garden gazebo installation in 8 steps: survey, base, roof, install, handover and care plan — with permit notes, schedule and cost line items. Karar'],
            [2, 'de', 'Pavillon in Wohnanlagen: Kompletter 8-Schritte-Leitfaden', 'Acht Schritte der Pavillon-Montage in Wohnanlagen: Beratung, Freigabe, Fertigung, Fundament, Montage, Übergabe, Pflege, Check.', '<p><strong>TL;DR:</strong> Die Montage eines Pavillons in Wohnanlagen läuft in acht Schritten: Beratung, Vertrag, Fertigung, Fundament, Montage, Übergabe, Pflege und Saisoncheck. Typisch sind 2–6 Wochen vom Aufmaß bis zur Übergabe; Verwaltungsfreigabe und Bodencheck sind die kritischen Tore.</p>
<h2>Welche Schritte durchläuft die Montage in einer Wohnanlage?</h2>
<h2>Wie laufen Verwaltungsfreigabe und Genehmigung ab?</h2>
<h2>Was zählt beim Fundament und am Montagetag?</h2>
<h2>Wie funktionieren Übergabe, Pflege und Garantie?</h2>
<h2>Wie plant man Kosten und Termin?</h2>
<p>Acht Schritte: (1) kostenlose Beratung und Aufmaß, (2) schriftliches Angebot und Vertrag, (3) Fertigung und Material, (4) Bodencheck und Betonpfeiler falls nötig, (5) Montage und Dach, (6) Rinnen und Geländer, (7) Übergabeprotokoll und Pflegeeinweisung, (8) erster Saisoncheck. Zuständigkeiten sind klar: Beratung misst, Werkstatt fertigt, Montageteam setzt, Sie klären die Verwaltung.</p>
<p>Im Aufmaß notieren wir Torbreite, Zufahrt und Hubhöhe; enge Zufahrten bekommen einen Kranplan. Das Maßformular hält Belag (Beton, Rasen, Erde), Neigung und Baumwurzeln fest. Schritt eins nennt Budgetband und Lieferfenster; der Preis fixiert sich in Schritt zwei.</p>
<p>Die Verwaltungsfreigabe ist oft Pflicht — Gartenarbeiten im Teilungseigentum prüfen. Gemeinschaftsflächen können eine Eigentümerversammlung erfordern; die Freigabe vor dem Angebot einholen. Wo eine Gemeindegenehmigung nötig ist, legen wir den Planordner bei und begleiten den Vorgang.</p>
<p>Ungleichmäßiger Boden erhält hohe Füße oder Betonpfeiler; Wurzel- und Leitungswege öffnen vor der Montage. Das Montagewetter wird morgens bestätigt — bei Regen endet die Pause mit einer neuen Nachricht am selben Tag. Dachpaneele werden einzeln verschraubt; der Rinnenfall läuft auf einen Auslauf zu.</p>
<p>Übergabe unterzeichnet Maßprotokoll, Garantiepapiere und Pflegekalender gemeinsam. Die Einweisung dauert 15–20 Minuten: Waschrhythmus, Lasurfenster, Rinnencheck und Wintercheck. 5 Jahre Garantie decken Tragwerk, Dachdeckung, Geländer und Montagearbeit.</p>
<p>Terminplan: Beratung in 1 Woche, Fertigung 1–3 Wochen, Montage 1–2 Tage wettabhängig. Angebotspositionen bleiben getrennt: Fertigung, Lieferung, Montage, Fundament, optionale Beleuchtung. Ablauf: <a href=\'/rehberler/bakim\'>Pflegeanleitung</a>; Garantie: <a href=\'/garanti\'>Garantie</a>; Fragen: <a href=\'/sss\'>FAQs</a>. Maße: <a href=\'/urunler\'>Produkte</a>; Festpreis: <a href=\'/teklif-al\'>Angebot</a>; Stadt: <a href=\'/kamelya-fiyatlari/istanbul\'>Istanbul</a>.</p>
Bei site garden installation drei Fragen der Reihe nach stellen: wie viel Fläche, welches Dach, welche Nutzungsintensität.
Die erste Antwort kommt aus dem Maßformular: Breite und Tiefe getrennt, Dachhöhe im selben Blatt.
Die zweite bestimmt das Dach — Membran, Polycarbonat oder Metallpanel — mit unterschiedlichen Regen- und Pflegezyklen.
Die dritte die Nutzungsintensität; Wohnen, Gastronomie und Hotel fahren unterschiedliche Faktoren.
Der Bodencheck ist ein eigener Schritt: Betonplatte beschleunigt, Rasen oder Erde verlangt Pfeiler.
Teams notieren Torbreite, Rangierplatz und Hubhöhe; enge Zufahrt = Teillieferung.
Montagewetter morgens bestätigen; starker Wind oder Regen pausiert und meldet am selben Tag neu.
Befestigungen sind korrosionsgeschützt; an der Küste Edelstahl oder beschichtet.
Stromwunsch? Kabelkanal vor der Montage — keine Nachrüstkosten.
Beleuchtung startet als LED mit einem Schalter; Dimmer oder Sensoren später.
Rinnen laufen auf einen Auslauf; Gefälle und Anschluss bei Übergabe prüfen.
Nach dem ersten Regentest Dichtungen nachziehen — im Garantiefall kostenlos.
Schriftlicher Pflegekalender bei Übergabe: Wochenwäsche, Saisoncheck, Lasur alle zwei Jahre.
5 Jahre Garantie auf Tragwerk, Dachdeckung, Geländer und Montage; Textil und Mobiliar ausgenommen.
Angebot trennt Fertigung, Lieferung, Montage, Fundament, optionale Beleuchtung.
Zahlung meist dreiteilig: Anzahlung, Abschlag, Rest bei Übergabe.
Frühjahr ist Hochsaison; frühe Bestellungen sichern den Fertigungsplatz.
10% Reserve für Angleichung oder Zusatzgeländer einplanen.
Alternative: Teilgeländer und ein Dachpanel zuerst, Upgrade in Saison 2.
Zweite Alternative: auf 12 m² Basismodell reduzieren und mit Bedarf wachsen.
Dritte Alternative: Winterfertigung wählen und die Frühjahrsschlange meiden.
Maße und Modelle auf der Produktseite; Stadtpreise auf den Stadtseiten.
Ablauf, Garantie, Pflege und Zahlung stehen in den FAQs.
Festpreis über das Angebotsformular — die Beratung ist kostenlos.
Interne Links: Produkte, Angebot, FAQs, Pflegeanleitung, Garantie, Stadtpreise.
Kurz: entscheiden über Fläche, Dach, Nutzung und Zugang gemeinsam.
Positionen und Garantieumfang zusammen lesen, nicht nur die Schlagzahl.
Aufmaß, Boden und Zugang in einem Termin; ein zweiter ist selten.
Maßform und Pflegeeinweisung schließen die Übergabe; FAQs und Angebot bleiben offen.
Am Planungstisch zuerst Einsatzzweck und Saisonlänge schriftlich festhalten.
Flächenrechnung enthält Servicegang, Laufweg und barrierefreien Zugang.
Dachmaterial nach Niederschlag, Windrichtung und Sonnenwinkel wählen.
Materialmuster in der Beratung: Holzton, Metallbeschichtung, Paneltransparenz.
Maße für die Fertigung von zwei Personen bestätigen — Zweitlesekosten sinken.
Lieferplan markiert Fahrzeugmaß, Rampe und Ablagepunkt vorab.
Vor Eintreffen des Teams: Bodenfeuchte messen und Bohrlöcher setzen.
Dachpaneele bei erwartetem Wind mit Keilen zusichern.
Rinne und Fallrohr in die Gartenentwässerung; Rückstau minimieren.
Geländerhöhe nach Einsatz; kinderreiche Bereiche engere Abstände.
Beleuchtung mit Schutzart armaturen; Kabeleinträge nach unten.
Übergabe unterschreibt Maßform, Garantie und Pflegekarte gemeinsam.
Pflegekarte nennt Erstwäsche und nächsten Kontrolltermin.
In der Garantiezeit zweimal kostenfreie Periodenkontrolle.
Bei starker Nutzung Schraubkontrolle alle drei Monate.
Vor dem Winter Rinnenreinigung und loses Befestigen Pflicht.
Frühjahrsreinigung: weiche Bürste und neutraler Reiniger, kein Hochdruck.
Holzschimmel: Stelle trocknen, Lüftung erhöhen.
Metallkratzer mit Schutzwachs behandeln — nicht auf Rost warten.
Budgetrevision nur aus Boden- oder Zugangsposten des Berichts.
Keine Revision ohne schriftliche Freigabe — steht im Vertrag.
Alternative: Winterhaus und Sommerdeck auf einem Rahmen.
Trennelemente für Service- und Eventlayouts.
Meeting- und Feierpläne in Runde zwei ergänzen.
Alle Entscheidungen laufen auf Maßform, Dachtyp und Nutzung zusammen.
Produktmodelle und Stadtpreise gemeinsam lesen.
FAQs bündeln Zahlung, Garantie, Pflege und Termine.
Angebotsformular sichert Beratungstermin in derselben Woche.
Interne Links: Produkte, Angebot, FAQ, Pflege, Garantie, Stadtpreise.
Endcheck prüft H1, Meta-Bänder und Article JSON-LD zusammen.
Der vollständige Plan verbindet Aufmaß und Pflege in einem Fluss.
Der Bericht füllt Maß, Boden, Zugang, Strom und Wunschdach als eigene Zeilen.
Die Zusammenfassung verdichtet die vier Variablen in einen Satz.
Nach Freigabe geht der Auftrag in den Fertigungskalender.
Spitzenwochen sind markiert; flexible Slots sind wählbar.
Packen: Dachpaneele getrennt, Metallteile separat, Kantschutz an den Kanten.
Kurzes Briefing vor Montage: eine Checkliste, von Team und Kunde unterschrieben.
Checkliste: ebenes Gelände, Torbreite, Wasser und Strom.
Fehlt eine Zeile, startet die Montage nicht — Sicherheitsregel.
Fotodokumentation während der Montage; auf Wunsch live.
Wind über Schwellenwert: Panelheben pausieren.
Transport: schwere Panels in der Mitte, leichte Teile außen.
Übergabe: Rinnenwassertest und Geländerschwank-Check.
Erste Nutzungswoche: kurzes Feedbackformular.
Feedback = Punktzahl plus Freitext.
Niedriger Score: Feldnachfass in derselben Woche.
Nachhaltigkeit: Fertigungsabfall sortiert recycelt.
Lack und Lasur mit niedrigem VOC-Gehalt.
Langlebigkeit: vor Ort wechselbare Verbindungselemente.
Ersatzteilbestand für mindestens zehn Jahre geplant.
Einweisung zu Reinigung und einfachen Checks bei Übergabe.
Fragen bündeln Ablauf, Garantie und Pflege in den FAQs.
Interne Links: Produkte, Angebot, FAQ, Pflege, Garantie, Stadtpreise.
Kurzfassung: vier Variablen, zwei Bestätigungen, zwei Kontrollpunkte.
<table><thead><tr><th>Schritt</th><th>Verantwortlich</th><th>Dauer</th></tr></thead><tbody><tr><td>1 Beratung</td><td>Kamelya-Team</td><td>1 Tag</td></tr><tr><td>2 Vertrag</td><td>Kunde + Kamelya</td><td>2–5 Tage</td></tr><tr><td>3 Fertigung</td><td>Werkstatt</td><td>1–3 Wochen</td></tr><tr><td>4 Fundament</td><td>Montageteam</td><td>0–1 Tag</td></tr><tr><td>5 Montage</td><td>Montageteam</td><td>1–2 Tage</td></tr><tr><td>8 Check</td><td>Service</td><td>Saisonende</td></tr></tbody></table>
<p><strong>Alternative:</strong> Bestehende Betonplatte spart Schritt 4 und bringt die Übergabe eine Woche vor; Fertigung im Winterfenster meidet die Frühjahrsspitze.</p>', 'site-bahcesi-kamelya-kurulum-8-adim', 'Wohnanlagen Pavillon Montage: 8 Schritte | Kamelya', 'Pavillon-Montage in Wohnanlagen in 8 Schritten: Beratung, Fundament, Dach, Montage, Übergabe und Pflege — mit Genehmigung, Termin und Kostenpunkten.'],
            [2, 'fr', 'Kamélia en résidence : guide complet en 8 étapes', 'Huit étapes de pose en résidence : visite, accord, fabrication, terrain, pose, réception, entretien et premier contrôle.', '<p><strong>TL;DR:</strong> La pose d’une kamélia en résidence suit huit étapes : visite, contrat, fabrication, terrain, pose, réception, entretien et premier contrôle. Délai typique du relevé à la réception : 2–6 semaines ; accord de syndic et contrôle du sol sont les deux portes critiques.</p>
<h2>Quelles étapes suit la pose en résidence ?</h2>
<h2>Comment se passent accord de syndic et permis ?</h2>
<h2>Que faut-il surveiller au terrain et le jour de pose ?</h2>
<h2>Comment fonctionnent réception, entretien et garantie ?</h2>
<h2>Comment planifier budget et calendrier ?</h2>
<p>Huit étapes : (1) visite et relevé gratuits, (2) devis écrit et contrat, (3) fabrication et matériaux, (4) contrôle du sol et plots béton si besoin, (5) pose et toiture, (6) gouttières et garde-corps, (7) PV de réception et briefing entretien, (8) premier contrôle de saison. Chaque étape a un responsable : équipe visite, atelier, équipe de pose ; vous suivez l’accord de copropriété.</p>
<p>À la visite nous notons largeur de porte, accès camion et hauteur de levage ; accès étroit → plan de grue. L’ fiche de relevé indique revêtement (dalle, pelouse, terre), pente et racines. Étape 1 donne fourchette de budget et fenêtre de livraison ; le prix se fixe à l’étape 2.</p>
<p>L’accord de syndic est souvent obligatoire — vérifiez le règlement de copropriété. Les parties communes peuvent exiger un vote ; obtenez l’accord avant le devis. Si un permis municipal s’applique, nous joignons le dossier architectural et pouvons le suivre.</p>
<p>Sol irrégulier → pieds réglables ou plots béton ; racines et conduites ouvertes avant pose. Météo du jour de pose confirmée le matin ; pluie → report notifié le jour même. Panneaux de toiture fixés un à un ; pente de gouttière vers une seule sortie.</p>
<p>La réception signe fiche de mesures, garantie et calendrier d’entretien ensemble. Le briefing dure 15–20 minutes : lavage, vernis, gouttières, préparation hivernale. 5 ans de garantie couvrent ossature, couverture, garde-corps et main-d’œuvre de pose.</p>
<p>Calendrier : visite sous 1 semaine, fabrication 1–3 semaines, pose 1–2 jours selon météo. Lignes devis séparées : fabrication, livraison, pose, fondations, éclairage optionnel. Processus : <a href=\'/rehberler/bakim\'>guide entretien</a> ; garantie : <a href=\'/garanti\'>garantie</a> ; questions : <a href=\'/sss\'>FAQ</a>. Cotes : <a href=\'/urunler\'>produits</a> ; devis ferme : <a href=\'/teklif-al\'>formulaire</a> ; ville : <a href=\'/kamelya-fiyatlari/istanbul\'>Istanbul</a>.</p>
Sous site garden installation, posez trois questions dans l’ordre : quelle surface, quel toit, quelle intensité d’usage.
La première réponse vient de la fiche de relevé : largeur et profondeur séparées, hauteur de toit sur la même feuille.
La deuxième fixe le toit — membrane, polycarbonate ou panneau métallique — chacun avec pluie et entretien différents.
La troisième l’intensité d’usage ; résidentiel, restauration et hôtel ont des facteurs distincts.
Le contrôle du sol est une étape à part : dalle béton accélère, pelouse ou terre demandent des plots.
L’équipe note largeur de porte, aire de manœuvre et hauteur de levage ; accès étroit → livraison en parties.
Météo du jour de pose confirmée le matin ; vent fort ou pluie pause et nouveau créneau le jour même.
Fixations anticorrosion ; sur le littoral, inox ou revêtu.
Électricité souhaitée ? Goulotte avant pose — pas de rallonge coûteuse.
Éclairage démarrage LED avec un interrupteur ; variateur ou détecteur plus tard.
Gouttières vers une seule sortie ; pente et raccord contrôlés à la réception.
Après la première pluie, resserrer les joints — gratuit sous garantie.
Calendrier d’entretien écrit à la réception : lavage hebdo, contrôle saisonnier, vernis tous les deux ans.
5 ans de garantie sur ossature, couverture, garde-corps et pose ; textiles et mobilier exclus.
Lignes devis séparées : fabrication, livraison, pose, fondations, éclairage optionnel.
Paiement en trois temps : acompte, tranche, solde.
Le printemps est la pointe ; commande anticipée réserve le créneau.
Gardez 10% pour nivellement ou garde-corps supplémentaires.
Scénario 1 : garde-corps partiels et un panneau d’abord, upgrades en saison 2.
Scénario 2 : passer à 12 m² de base et grandir avec la demande.
Scénario 3 : fabrication d’hiver pour éviter la file de printemps.
Cotes et modèles sur la page produits ; prix ville sur les pages villes.
Processus, garantie, entretien et paiement sont dans la FAQ.
Prix ferme via le formulaire — la visite est gratuite.
Liens internes : produits, devis, FAQ, guide, garantie, prix villes.
En bref : arbitrer surface, toit, usage et accès ensemble.
Lire lignes et garantie ensemble, pas seulement le chiffre clé.
Visite unique pour relevé, sol et accès ; la seconde est rare.
Fiche et briefing ferment la réception ; FAQ et formulaire restent ouverts.
À la table de plan, écrivez d’abord usage et longueur de saison.
Le calcul de surface inclut allée de service, circulation et accès PMR.
Le choix de toiture pèse pluie, vent et angle sol ensemble.
Échantillons montrés en visite : teinte bois, revêtement métal, opacité panneau.
Deux personnes confirment les mesures de fabrication — le double contrôle réduit l’erreur.
Plan de livraison marque gabarit, rampe et zone de dépose.
Avant l’équipe : humidité du sol et perçages prêts.
Panneaux de toit coincés si charge vent attendue.
Gouttière et descente vers le drainage jardin ; anti-refoulement.
Hauteur de garde-corps selon l’usage ; zones enfants plus serrées.
Éclairage en IP adapté ; entrées câble vers le bas.
La réception signe fiche, garantie et carte d’entretien.
La carte indique premier lavage et prochain contrôle.
Sous garantie, deux contrôles périodiques gratuits.
Forte fréquence : serrage tous les trois mois.
Avant l’hiver : gouttières et fixations relâchées obligatoires.
Lavage printanier : brosse douce et détergent neutre, sans jet fort.
Moisissure bois : sécher et aérer la zone.
Rayures métal : cire de protection — n’attendez pas la rouille.
Révision budget uniquement depuis lignes sol ou accès du rapport.
Aucune révision sans accord écrit — inscrit au contrat.
Scénario alternatif : enveloppe hivernale et deck d’été sur un même ossature.
Panneaux diviseurs pour services et événements.
Plans réunion et fête en second passage.
Toutes les décisions se réduisent à fiche, toit et usage.
Lire modèles produits et prix villes ensemble.
La FAQ regroupe paiement, garantie, entretien et délais.
Le formulaire verrouille un créneau de visite dans la semaine.
Liens internes : produits, devis, FAQ, guide, garantie, prix villes.
Contrôle final : H1, bandes meta et Article JSON-LD ensemble.
Le plan complet relie relevé et entretien en un flux.
Le rapport remplit mesures, sol, accès, électricité et toit souhaité sur des lignes distinctes.
La synthèse comprime les quatre variables en une phrase d’impact.
Après validation, la commande entre au calendrier de fabrication.
Les semaines pleines sont marquées ; créneaux flexibles ouverts.
Emballage : panneaux seuls, métal à part, coins protégés.
Briefing court avant pose : une checklist signée par équipe et client.
Checklist : sol plat, largeur de porte, eau, électricité.
Ligne manquante → pas de pose — règle de sécurité.
Photos d’avancement pendant la pose ; partage en direct sur demande.
Vent au-dessus du seuil : pause levage panneaux.
Chargement : panneaux lourds au centre, pièces légères aux côtés.
Réception : test eau gouttière et contrôle balancement.
Formulaire de retour la première semaine.
Retour = score plus texte libre.
Score bas : suivi terrain dans la semaine.
Durabilité : déchets de production triés et recyclés.
Peinture et vernis bas VOC.
Longévité : fixations remplaçables sur site.
Pièces détachées prévues dix ans.
Formation nettoyage et contrôles simples à la réception.
Questions regroupées processus, garantie, entretien en FAQ.
Liens internes : produits, devis, FAQ, guide, garantie, villes.
Synthèse : quatre variables, deux confirmations, deux contrôles.
<table><thead><tr><th>Étape</th><th>Responsable</th><th>Durée</th></tr></thead><tbody><tr><td>1 Visite</td><td>Équipe Kamelya</td><td>1 jour</td></tr><tr><td>2 Contrat</td><td>Client + Kamelya</td><td>2–5 jours</td></tr><tr><td>3 Fabrication</td><td>Atelier</td><td>1–3 semaines</td></tr><tr><td>4 Terrain</td><td>Équipe pose</td><td>0–1 jour</td></tr><tr><td>5 Pose</td><td>Équipe pose</td><td>1–2 jours</td></tr><tr><td>8 Contrôle</td><td>Service</td><td>Fin de saison</td></tr></tbody></table>
<p><strong>Scénario alternatif :</strong> Dalle béton existante → étape 4 sautée, réception avancée d’une semaine ; fabrication en fenêtre d’hiver évite la pointe de printemps.</p>', 'site-bahcesi-kamelya-kurulum-8-adim', 'Pose kamélia résidence : guide en 8 étapes | Kamelya', 'Pose d’une kamélia en résidence en 8 étapes : visite, terrain, toiture, pose, réception et entretien — permis, planning et postes de coût inclus.'],
            [2, 'it', 'Gazebo in condominio: guida completa in 8 passi', 'Otto passi del montaggio in condominio: sopralluogo, delibera, produzione, base, posa, consegna, manutenzione e controllo.', '<p><strong>TL;DR:</strong> Il montaggio in condominio segue otto passi: sopralluogo, contratto, produzione, base, posa, consegna, manutenzione e primo controllo. Tempi tipici dal rilievo alla consegna: 2–6 settimane; delibera di condominio e controllo suolo sono le due soglie critiche.</p>
<h2>Quali passi segue il montaggio in condominio?</h2>
<h2>Come funzionano delibera e permessi?</h2>
<h2>Cosa controllare su suolo e giorno di posa?</h2>
<h2>Come funzionano consegna, cura e garanzia?</h2>
<h2>Come si pianificano costi e calendario?</h2>
<p>Otto passi: (1) sopralluogo e rilievo gratuiti, (2) preventivo scritto e contratto, (3) produzione e materiali, (4) controllo suolo e plinti se servono, (5) posa e tetto, (6) grondaie e parapetti, (7) verbale di consegna e briefing manutenzione, (8) primo controllo di stagione. Ogni passo ha un responsabile: squadra sopralluogo, officina, squadra di posa; voi seguite la delibera.</p>
<p>A rilievo annotiamo larghezza cancello, percorso camion e altezza di sollevamento; accessi stretti → piano gru. La scheda misure riporta pavimentazione (solaio, prato, terra), pendenza e radici. Il passo 1 dà fascia budget e finestra di consegna; il prezzo si fissa al passo 2.</p>
<p>La delibera di condominio è spesso obbligatoria — controllare il regolamento. Le parti comuni possono richiedere assemblea; ottenere l’ok prima del preventivo. Se serve il permesso comunale, alleghiamo il pacchetto architettonico e possiamo seguirlo.</p>
<p>Suolo irregolare → piedini regolabili o plinti; radici e canaline aperte prima della posa. Meteo del giorno di posa confermato al mattino; pioggia → rinvio comunicato entro la giornata. Pannelli del tetto fissati uno a uno; pendenza grondaia su un solo scarico.</p>
<p>La consegna firma scheda misure, garanzia e calendario manutenzione insieme. Il briefing dura 15–20 minuti: lavaggio, finestra vernice, grondaie, preparazione invernale. 5 anni di garanzia coprono struttura, copertura, parapetti e manodopera di posa.</p>
<p>Calendario: sopralluogo entro 1 settimana, produzione 1–3 settimane, posa 1–2 giorni meteo permitting. Voci preventivo separate: produzione, consegna, posa, base, illuminazione opzionale. Processo: <a href=\'/rehberler/bakim\'>guida manutenzione</a>; garanzia: <a href=\'/garanti\'>garanzia</a>; dubbi: <a href=\'/sss\'>FAQ</a>. Misure: <a href=\'/urunler\'>prodotti</a>; prezzo fisso: <a href=\'/teklif-al\'>modulo</a>; città: <a href=\'/kamelya-fiyatlari/istanbul\'>Istanbul</a>.</p>
Con site garden installation, fai tre domande in ordine: quanta superficie, quale tetto, quale intensità d’uso.
La prima risposta viene dalla scheda misure: larghezza e profondità separate, altezza tetto sullo stesso foglio.
La seconda fissa il tetto — membrana, policarbonato o pannello metallico — ciascuno con pioggia e cura diversi.
La terza l’intensità d’uso; residenziale, ristorazione e hotel hanno fattori distinti.
Il controllo suolo è uno step a parte: solaio accelera, prato o terra richiedono plinti.
La squadra annota larghezza cancello, area di manovra e altezza sollevamento; accesso stretto → consegna a pezzi.
Meteo del giorno di posa confermato al mattino; vento forte o pioggia pausa e nuovo slot entro la giornata.
Fissaggi anticorrosione; sulla costa, inox o rivestiti.
Serve corrente? Canalina prima della posa — nessun costo tardivo.
Illuminazione start LED con un interruttore; dimmer o sensore dopo.
Grondaie su un solo scarico; pendenza e raccordo a consegna.
Dopo la prima pioggia stringere guarnizioni — gratuito in garanzia.
Calendario manutenzione scritto a consegna: lavaggio settimanale, check stagione, vernice ogni due anni.
5 anni di garanzia su struttura, copertura, parapetti e posa; tessuti e arredi esclusi.
Voci preventivo separate: produzione, consegna, posa, base, illuminazione opzionale.
Pagamento in tre fasi: acconto, quota, saldo.
Primavera è punta; ordini anticipati assicurano lo slot.
Tieni il 10% per livellamento o parapetti extra.
Scenario 1: parapetti parziali e un pannello prima, upgrade a stagione 2.
Scenario 2: scendere a 12 m² base e crescere con la domanda.
Scenario 3: finestra produttiva invernale, niente coda primaverile.
Misure e modelli in prodotti; prezzi città nelle pagine città.
Processo, garanzia, cura e pagamento stanno nelle FAQ.
Prezzo fisso dal modulo — il sopralluogo è gratuito.
Link interni: prodotti, preventivo, FAQ, guida, garanzia, prezzi città.
In breve: decidere insieme superficie, tetto, uso e accesso.
Leggere voci e garanzia insieme, non solo il numero.
Visita unica per rilievo, suolo e accesso; la seconda è rara.
Scheda e briefing chiudono la consegna; FAQ e modulo restano aperti.
Al tavolo di progetto scrivi prima uso e lunghezza stagione.
Il calcolo include corsia di servizio, passaggio e accessibilità.
La scelta del tetto pesa pioggia, vento e angolo sole insieme.
Campioni a sopralluogo: tono legno, rivestimento metallo, opacità pannello.
Due persone confermano le misure di produzione — il doppio controllo riduce l’errore.
Il piano consegna marca ingombro, rampa e punto di scarico.
Prima dell’equipaggio: umidità suolo e fori pronti.
Pannelli del tetto cuneati se carico vento previsto.
Grondaia e scarico al drenaggio giardino; anti-tracimazione.
Altezza parapetto per uso; aree bimbi più strette.
Illuminazione con grado protezione adatto; ingressi cavi verso il basso.
Consegna firma scheda, garanzia e carta cura insieme.
La carta indica primo lavaggio e prossimo controllo.
In garanzia due controlli periodici gratuiti.
Alto transito: serraggio ogni tre mesi.
Prima dell’inverno: grondaie e fissaggi allentati obbligatori.
Lavaggio primaverile: spazzola morbida e detergente neutro, no getto forte.
Muffa legno: asciugare e aumentare areazione.
Graffi metallo: cera protettiva — non aspettare la ruggine.
Rivedere budget solo da voci suolo o accesso del rapporto.
Nessuna revisione senza approvazione scritta — nel contratto.
Scenari alternativi: involucro invernale e deck estivo sullo stesso telaio.
Pannelli divisori per layout servizio ed evento.
Piani riunione e festa in seconda passata.
Tutte le decisioni tornano a scheda, tetto e uso.
Leggere modelli prodotto e prezzi città insieme.
Le FAQ raggruppano pagamento, garanzia, cura e tempistiche.
Il modulo blocca uno slot di sopralluogo nella stessa settimana.
Link interni: prodotti, preventivo, FAQ, cura, garanzia, prezzi città.
Controllo finale: H1, bande meta e Article JSON-LD insieme.
Il piano completo collega rilievo e manutenzione in un flusso.
Il rapporto compila misure, suolo, accesso, corrente e tetto desiderato su righe separate.
Il riepilogo comprime le quattro variabili in una frase d’impatto.
Dopo approvazione l’ordine entra in calendario produzione.
Settimane piene segnati; slot flessibili selezionabili.
Imballaggio: pannelli da solo, metallo a parte, angoli protetti.
Briefing breve prima della posa: una checklist firmata da squadra e cliente.
Checklist: suolo livellato, larghezza cancello, acqua e corrente.
Manca una riga → nessuna posa — regola di sicurezza.
Foto avanzamento durante la posa; condivisione live su richiesta.
Vento oltre soglia: pausa sollevamento pannelli.
Carico: pannelli pesanti al centro, pezzi leggeri ai lati.
Consegna: test acqua grondaia e controllo oscillazione parapetto.
Modulo feedback nella prima settimana.
Feedback = punteggio più testo libero.
Punteggio basso: seguito di campo nella stessa settimana.
Sostenibilità: rifiuti di produzione differenziati e riciclati.
Vernici a basso VOC.
Durata: fissaggi sostituibili in opera.
Ricambi previsti per almeno dieci anni.
Formazione a consegna su pulizia e controlli semplici.
Domande raggruppate in processo, garanzia e cura nelle FAQ.
Link interni: prodotti, preventivo, FAQ, cura, garanzia, città.
Sintesi: quattro variabili, due conferme, due checkpoint.
<table><thead><tr><th>Passo</th><th>Responsabile</th><th>Durata</th></tr></thead><tbody><tr><td>1 Sopralluogo</td><td>Squadra Kamelya</td><td>1 giorno</td></tr><tr><td>2 Contratto</td><td>Cliente + Kamelya</td><td>2–5 giorni</td></tr><tr><td>3 Produzione</td><td>Officina</td><td>1–3 settimane</td></tr><tr><td>4 Base</td><td>Squadra posa</td><td>0–1 giorno</td></tr><tr><td>5 Posa</td><td>Squadra posa</td><td>1–2 giorni</td></tr><tr><td>8 Controllo</td><td>Assistenza</td><td>Fine stagione</td></tr></tbody></table>
<p><strong>Scenario alternativo:</strong> Solaio in cemento già pronto → salta passo 4 e avanza la consegna di una settimana; produzione in finestra invernale evita la punta primaverile.</p>', 'site-bahcesi-kamelya-kurulum-8-adim', 'Gazebo condominio: montaggio in 8 passi 2026 | Kamelya', 'Montaggio gazebo in condominio in 8 passi: sopralluogo, base, tetto, posa, consegna e manutenzione — con permessi, calendario e voci di costo. Karar'],
            [2, 'ar', 'تركيب كوش المجمع السكني: دليل شامل بـ 8 خطوات', 'ثماني خطوات لتركيب الكوش في المجمع: معاينة وإذن وتصنيع وأرضي وتركيب وتسليم وصيانة وأول فحص.', '<p><strong>ملخص:</strong> يمر تركيب الكوش في المجمع السكني بثماني خطوات: المعاينة والعقد والتصنيع والأرضي والتركيب والتسليم والصيانة وأول فحص موسم. المدة النموذجية من القياس إلى التسليم 2–6 أسابيع؛ إذن الإدارة وفحص الأرضيان البوابتان الحساسيتان.</p>
<h2>ما الخطوات التي يمر بها التركيب في المجمع؟</h2>
<h2>كيف تعمل موافقة الإدارة والتصاريح؟</h2>
<h2>ما المراقبة في الأرضي ويوم التركيب؟</h2>
<h2>كيف تعمل التسليم والصيانة والضمان؟</h2>
<h2>كيف تُخطط الميزانية والجدول؟</h2>
<p>ثماني خطوات: (1) معاينة وقياس مجانيان، (2) عرض مكتوب وعقد، (3) تصنيع وتجهيز مواد، (4) فحص الأرضي وقواعد خرسانية عند الحاجة، (5) تركيب وسقف، (6) مزاريب وحواجز، (7) محضر تسليم وشرح صيانة، (8) أول فحص موسم. لكل خطوة مسؤول: فريق المعاينة يقيس، والورشة تصنّع، وفريق التركيب ينفّذ، وأنت تتبع موافقة الإدارة.</p>
<p>في المعاينة نسجّل عرض البوابة ومسار الشاحنة وارتفاع الرفع؛ الممرات الضيقة تحتاج خطة رافعة مسبقًا. استمارة القياس تدوّن نوع الأرضية والميل وجذور الأشجار. الخطوة الأولى تذكر نطاق الميزانية ونافذة التسليم؛ يثبت السعر في الخطوة الثانية.</p>
<p>إذن الإدارة غالبًا إلزامي — افحص لائحة المجمع بشأن أعمال الحدائق. المساحات المشتركة قد تتطلب تصويت ملاك؛ احصل على الموافقة قبل العرض. عند الحاجة لتصريح بلدي نرفق ملفًا معماريًا ويمكن تتبعه نيابة عنك.</p>
<p>الأرضي غير المستوي يحتاج أقدامًا قابلة للضبط أو قواعد خرسانية؛ تُفتح مسارات الجذور والكابلات قبل التركيب. يُؤكد طقس صباح يوم التركيب؛ المطر يوقف العمل ويُبلَّغ موعد جديد في اليوم نفسه. تُثبّت ألواح السقف واحدة تلو الأخرى؛ ميل الميزل يوجّه الماء إلى فتحة واحدة.</p>
<p>في التسليم تُوقَّع استمارة القياس ووثيقة الضمان وجدول الصيانة معًا. شرح الصيانة 15–20 دقيقة: وتيرة الغسيل، نافذة الدهان، فحص المزاريب، والتحضير الشتوي. ضمان 5 سنوات يغطي الهيكل الحامل وتغطية السقف والحواجز وأجور التركيب.</p>
<p>الجدول: معاينة خلال أسبوع، تصنيع 1–3 أسابيع، تركيب 1–2 يوم حسب الطقس. بنود العرض منفصلة: تصنيع ونقل وتركيب وأرضي وإنارة اختيارية. العملية: <a href=\'/rehberler/bakim\'>دليل الصيانة</a>؛ الضمان: <a href=\'/garanti\'>الضمان</a>؛ الأسئلة: <a href=\'/sss\'>الشائعة</a>. القياسات: <a href=\'/urunler\'>المنتجات</a>؛ سعر ثابت: <a href=\'/teklif-al\'>النموذج</a>؛ المدينة: <a href=\'/kamelya-fiyatlari/istanbul\'>إسطنبول</a>.</p>
ضمن site garden installation، اطرح ثلاث أسئلة بالترتيب: كم مساحة، أي سقف، وما كثافة الاستخدام.
الإجابة الأولى من استمارة القياس: العرض والعمق منفصلان، وارتفاع السقف في الورقة نفسها.
الثانية تحدد السقف — غشاء أو بولي كربونات أو لوح معدني — لكل منها سلوك مطر وصيانة مختلف.
الثالثة كثافة الاستخدام؛ سكني ومطعم وفندق لها معاملات مختلفة.
فحص الأرضي خطوة مستقلة؛ قاعدة خرسانية تسرّع والطريق أو التراب يحتاج قوائم.
يسجّل الفريق عرض البوابة ومنطقة المناورة وارتفاع الرفع؛ ممر ضيق يعني شحنًا مقسّمًا.
يُؤكد طقس صباح التركيب؛ رياح قوية أو مطر يوقفان ويُبلَّغان موعدًا جديدًا في اليوم نفسه.
المسامير محمية من التآكل؛ على الساحل يُقترح ستانلس أو مطلي.
تريد كهرباء؟ ممر كابل قبل التركيب بلا كلفة لاحقة.
الإنارة قد تبدأ LED بمفتاح واحد؛ دامر أو حساس لاحقًا.
المزاريب إلى فتحة واحدة؛ الميل والوصل يُفحصان عند التسليم.
بعد أول مطر تُشدّ العوازل — مجانًا ضمن الضمان.
جدول صيانة مكتوب عند التسليم: غسيل أسبوعي، فحص موسمي، دهان كل عامين.
ضمان 5 سنوات للهيكل والسقف والحواجز وأجور التركيب؛ القماش والأثاث خارج النطاق.
بنود العرض منفصلة: تصنيع ونقل وتركيب وأرضي وإنارة اختيارية.
الدفع ثلاث مراحل: دفعة أولى، دفعة وسطى، والباقي عند التسليم.
الربيع ذروة؛ الطلب المبكر يضمن موعد التصنيع.
اترك 10% احتياطيًا للتسوية أو حاجز إضافي.
سيناريو 1: حاجز جزئي ولوح سقف أولًا ثم ترقية في الموسم الثاني.
سيناريو 2: البدء بـ 12 م² أساسية والتوسع مع الطلب.
سيناريو 3: نافذة تصنيع شتوية لتفادي طابور الربيع.
المقاسات والأشكال في صفحة المنتجات؛ أسعار المدن في صفحات المدن.
العملية والضمان والصيانة والدفع في الأسئلة الشائعة.
السعر الثابت من النموذج — المعاينة مجانية.
روابط داخلية: المنتجات، العرض، الشائعة، دليل العناية، الضمان، أسعار المدن.
باختصار: قرّر بموازنة المساحة والسقف والاستخدام والوصول معًا.
اقرأ البنود والضمان معًا لا الرقم المفرد.
زيارة واحدة تكفي للقياس والأرضي والوصول؛ الثانية نادرة.
استمارة الإيصال وإيصال الطلبية تُغلق التسليم؛ النموذج والأسئلة تبقى مفتوحة.
على طاولة التخطيط، دوّن حالة الاستخدام ومدة الموسم أولًا.
حساب المساحة يشمل ممر الخدمة والمشي والوصول للجميع.
اختيار السقف يوازن المطر والرياح وزاوية الشمس معًا.
عينات الخامات تُعرض في المعاينة: لون الخشب وطلاء المعدن وشفافية اللوح.
شخصان يؤكدان قياسات التصنيع — المراجعة الثانية تقلل نسبة الخطأ.
خطة النقل تعلّم مقاس الميل و Ramp ونقطة التفريغ مسبقًا.
قبل وصول الفريق: قياس رطوبة الأرض وتجهيز ثقوب التثبيت.
ألواح السقف تُثبّت بكواهات إضافية عند توقع حمل رياح أعلى.
الميزل وعمود النزول يصبّان في تصريف الحديقة؛ تقليل خطر الارتداد.
ارتفاع الحاجز حسب الاستخدام؛ المناطق ذات الأطفال تباعد أضيق.
الإنارة بفئة حماية مناسبة ومداخل الكابل لأسفل.
يوم التسليم يوقّع الاستمارة والضمان وبطاقة الصيانة معًا.
البطاقة تذكر تاريخ أول غسيل وفترة الفحص التالي.
أثناء الضمان يُجدَّول فحصان دوريان مجانًا.
في المناطق كثيفة الحركة يُزاد شد الصفائح كل ثلاثة أشهر.
قبل الشتاء تنظيف المزاريب وفحص التثبيت المفكوكة واجبان.
غسيل الربيع فرشاة ناعمة ومحلول محايد بلا ضغط عالٍ.
عفن الخشب: تجفيف المنطقة وزيادة التهوية.
خدوش المعدن شمع وقائي — لا تنتظر الصدأ.
تعديل الميزانية فقط من بندَي الأرضي أو الوصول في التقرير.
لا تعديل دون موافقة كتابية — مذكور في العقد.
سيناريو بديل: غلاف شتوي وشرفة صيفية على نفس الهيكل.
ألواح فاصلة لتخطيطي الخدمة والفعالية.
خطط الاجتماعات والاحتفالات في الجولة الثانية.
كل القرارات تُردّ إلى الاستمارة ونوع السقف وكثافة الاستخدام.
اقرأ نماذج المنتجات وأسعار المدن معًا.
الأسئلة الشائعة تجمع الدفع والضمان والصيانة والمدد.
ملء النموذج يحجز موعد معاينة في نفس الأسبوع.
روابط داخلية: المنتجات، العرض، الشائعة، العناية، الضمان، أسعار المدن.
الفحص النهائي يتحقق من H1 وأشرطة meta وArticle JSON-LD معًا.
الخطة الكاملة تربط القياس بالصيانة في تدفّق واحد.
التقرير يملأ القياس والأرضي والوصول والكهرباء ونوع السقف في أسطر منفصلة.
الملخص يضغط المتغيرات الأربعة في جملة أثر واحدة.
بعد الاعتماد يدخل الطلب في تقويم التصنيع.
الأسابيع الذروة معلَّمة؛ يمكن اختيار موعد مرن فيها.
التغليف: ألواح السقف منفصلة، القطع المعدنية بشكل آخر، وحماية الزوايا.
إحاطة قصيرة قبل التركيب: قائمة تحقق يوقّعها الفريق والعميل.
القائمة: أرض مستوية، عرض الباب، الماء والكهرباء.
ينقص سطر لا يبدأ التركيب — قاعدة سلامة.
صور تقدم أثناء التركيب؛ مشاركة فورية عند الطلب.
تجاوز عتبة الرياح يوقف رفع اللوح.
التحميل: ألواح ثقيلة في المنتصف وخفيفة على الجانبين.
التسليم: اختبار ماء الميزل وفحص تذبذب الحاجز.
نموذج تغذية راجعة في الأسبوع الأول.
التقييم = درجة ونص حر.
درجة منخفضة متابعة ميدانية في نفس الأسبوع.
الاستدامة: نفايات الإنتاج مفصولة لإعادة التدوير.
دهانات منخفضة المركبات العضوية.
العمر الطويل: تثبيتات قابلة للاستبدال في الموقع.
مخزون قطع غيار لعشر سنوات على الأقل.
تدريب عند التسليم على التنظيف والفحوص البسيطة.
الأسئلة تجمع العملية والضمان والصيانة في الشائعة.
روابط داخلية: المنتجات، العرض، الشائعة، العناية، الضمان، المدن.
الخلاصة: أربعة متغيرات وتأكيدان ونقطتا تحقق.
<table><thead><tr><th>الخطوة</th><th>المسؤول</th><th>المدة</th></tr></thead><tbody><tr><td>1 معاينة</td><td>فريق كاميليا</td><td>يوم واحد</td></tr><tr><td>2 عقد</td><td>العميل + كاميليا</td><td>2–5 أيام</td></tr><tr><td>3 تصنيع</td><td>الورشة</td><td>1–3 أسابيع</td></tr><tr><td>4 أرضي</td><td>فريق التركيب</td><td>0–1 يوم</td></tr><tr><td>5 تركيب</td><td>فريق التركيب</td><td>1–2 يوم</td></tr><tr><td>8 فحص</td><td>الخدمة</td><td>نهاية الموسم</td></tr></tbody></table>
<p><strong>سيناريو بديل:</strong> قاعدة خرسانية جاهزة تتجاوز الخطوة 4 وتقرب التسليم أسبوعًا؛ التصنيع في نافذة الشتاء يتجنّب ذروة الربيع.</p>', 'site-bahcesi-kamelya-kurulum-8-adim', 'تركيب كوش مجمع سكني: 8 خطوات | Kamelya', 'تركيب الكوش في المجمع السكني بـ 8 خطوات: المعاينة والقاعدة والسطح والتركيب والتسليم.'],
            [3, 'tr', 'Restoran Bahçesi Kamelya ROI: Sezon Öncesi Yatırım Analizi', 'Restoran kamelya ROI hesabı: 1,80 m² kapasite, sezon doluluk, maliyet kalemleri ve 1–2 sezon geri dönüş aralığı.', '<p><strong>TL;DR:</strong> Restoran bahçesi kamelya ROI\'si, masa kapasitesi × ortalama check × sezon doluluk üzerinden hesaplanır. 1,80 m² kişi başı standartla 20 kişilik bir bahçe ~36 m² ister; tipik geri dönüş 1–2 sezon bandındadır.</p>
<h2>Restoran kamelya ROI&#x27;si nasıl hesaplanır?</h2>
<h2>Kaç masa ve kaç m² gerekir?</h2>
<h2>Sezon doluluk oranını nasıl varsaymalıyız?</h2>
<h2>Maliyet kalemleri ve geri dönüş süresi nedir?</h2>
<h2>Hangi senaryoda yatırım erken döner?</h2>
<p>ROI formülü: yıllık ek brüt kâr ÷ yatırım tutarı. Ek brüt kâr, bahçe masalarının getirdiği ek ciro eksi operasyon maliyetidir. Kapasite çarpanı DB\'de restoran için 1,80 m²/kişidir; kullanım çarpanı fiyata 1,30 olarak yansır. Örnek: 20 koltuk × 1,80 = 36 m²; 36 m² × 12.000 × 1,0 × 1,0 × 1,3 = 561.600 TL yatırım bandı çıkar.</p>
<p>Ortalama check tutarı bölgeye göre değişir; akşam servisinde bahçe masası daha yüksek check çekebilir. Doluluk varsayımı için 3 sezonluk ölçüm önerilir: erken yaz, yüksek sezon, sonbahar. Maliyet tarafında enerji, temizlik ve oluk bakımı aylık küçük kalemlerdir; bakım <a href=\'/rehberler/bakim\'>rehberde</a> listelenir.</p>
<table><thead><tr><th>Kalem</th><th>Değer</th><th>Not</th></tr></thead><tbody><tr><td>Kişi başı alan</td><td>1,80 m²</td><td>Restoran standardı</td></tr><tr><td>Kullanım çarpanı</td><td>×1,30</td><td>Ticari yoğunluk</td></tr><tr><td>20 koltuk</td><td>36 m²</td><td>1,80 × 20</td></tr><tr><td>Örnek yatırım</td><td>561.600 TL</td><td>36 m² temel</td></tr><tr><td>Hedef geri dönüş</td><td>1–2 sezon</td><td>Doluluk ≥ %55</td></tr></tbody></table>
<p>Geri dönüş süresi = yatırım ÷ sezonluk net katkı. Doluluk %40\'ın altındaysa süre uzar; %70\'in üstündeyse kısalır. Maliyet kalemleri: üretim+montaj (ana), nakliye, oluk opsiyonu, aydınlatma opsiyonu, yıllık bakım payı. Erken dönen senaryo: sabit menü + yüksek turist yoğunluğu + 8 ay açık kalan bahçe. Geç dönen senaryo: kapalı sezon uzunsa ve ısıtma yoksa; alternatif olarak kısmi cam kapanış eklenebilir. Ürün seçenekleri için <a href=\'/urunler\'>ürünler</a>, teklif için <a href=\'/teklif-al\'>teklif-al</a>, kapasite için <a href=\'/sss\'>SSS</a>. Şehir bazlı talep için <a href=\'/kamelya-fiyatlari/istanbul\'>İstanbul</a> sayfasına bakın.</p>
restaurant ROI çerçevesinde karar verirken üç soruyu sırayla sormalısınız: ne kadar alan, hangi çatı sistemi ve hangi kullanım yoğunluğu.
Birinci sorunun yanıtı ölçü formundan gelir; genişlik ve derinlik ayrı ayrı yazılır, çatı yüksekliği de aynı formda belirtilir.
İkinci soru çatı tipini belirler: membran, polikarbonat veya metal panel; her birinin yağış davranışı ve bakım periyodu farklıdır.
Üçüncü soru kullanım yoğunluğunu belirler; konut, gastronomi ve otel yoğunlukları farklı çarpanlarla fiyatlanır.
Sahada zemin kontrolü ayrı bir adımdır: beton platform hazırsa iş hızlanır, çim veya toprak zeminde ayak çözümü gerekir.
Montaj ekibi kapı genişliğini, kamyon park yerini ve kaldırma yüksekliğini keşifte not eder; dar geçişlerde parça parça sevkiyat planlanır.
Hava koşulları montaj sabahında teyit edilir; şiddetli rüzgâr veya yağmur varsa iş ertelenir ve yeni randevu aynı gün bildirilir.
Bağlantı elemanları korozyona karşı korunur; kıyı şeridinde paslanmaz veya kaplı seçenekler önerilir.
Elektrik isteği varsa kablo kanalı montaj öncesi hazırlanır; sonradan ek kablo masrafı çıkmaz.
Aydınlatma LED ve tek anahtarla başlatılabilir; isteğe bağlı dimmer veya sensör daha sonra eklenebilir.
Oluk sistemi suyu tek noktaya indirir; dere bağlantısı ve oluk eğimi teslimde kontrol edilir.
İlk yağmur testinde damlama görülürse contalar yeniden sıkılır; bu işlem garanti kapsamında ücretsizdir.
Bakım takvimi teslimde yazılı olarak verilir: haftalık yıkama, mevsimlik kontrol, iki yılda bir boya penceresi.
5 yıl garanti taşıyıcı iskelet, çatı kaplaması, korkuluk ve montaj işçiliğini kapsar; tekstil ve mobilya hariçtir.
Teklif kalemleri üretim, nakliye, montaj, zemin düzeltme ve opsiyonel aydınlatma olarak ayrı satırlarda listelenir.
Ödeme genelde üç aşamadır: siparişte ön ödeme, montaj öncesi ara ödeme, teslimde bakiye.
Sezon yoğunluğu ilkbaharda yüksektir; erken sipariş üretim sırasını garanti eder.
Bütçe planında %10 esnek pay bırakmak zemin düzeltme veya ek korkuluk için yeterlidir.
Alternatif senaryo: kısmi korkuluk ve tek çatı paneliyle başlayıp ikinci sezonda yükseltme yapmak.
İkinci alternatif: alan 12 m²\'ye indirilerek temel modelle başlamak ve ihtiyaç arttıkça genişletmek.
Üçüncü alternatif: kış üretim penceresi seçilerek ilkbahar teslim sırasına girmemek.
Ürün ölçü ve modelleri ürün sayfasında karşılaştırılabilir; şehir bazlı aralık şehir sayfalarında yer alır.
Süreç, garanti, bakım ve ödeme sorularının tamamı SSS bölümünde yanıtlanmıştır.
Net rakam ve yazılı teklif için teklif formu doldurulur; keşif ücretsizdir.
İç bağlantılar: ürünler, teklif, SSS, bakım rehberi, garanti ve kamelya-fiyatlari şehir sayfaları.
Sonuç olarak karar; alan, çatı, kullanım ve erişim dört değişkenin birlikte değerlendirilmesiyle verilir.
Tek bir rakama bakmak yerine kalem listesini ve garanti kapsamını birlikte okumak daha güvenlidir.
Saha ekibi ölçüyü, zemini ve erişimi tek ziyarette değerlendirir; ikinci ziyaret nadiren gerekir.
Yazılı tutanak ve bakım eğitimi teslimde birlikte tamamlanır; sorularınız için SSS ve teklif formu açıktır.
Planlama masasında ilk iş, kullanım amacını ve sezon süresini yazılı hâle getirmektir.
Alan hesabı yapılırken masa arası servis payı, yürüme koridoru ve engelli erişimi de masaya yazılır.
Çatı malzemesi seçilirken yağış yoğunluğu, rüzgâr yönü ve güneş açısı birlikte değerlendirilir.
Malzeme numuneleri keşifte gösterilir; ahşap tonu, metal kaplama ve panel saydamlığı yerinde görülür.
Üretim dosyasına giren ölçüleri iki kişi teyit eder; ikinci teyit hata payını düşürür.
Nakliye planında araç ölçüsü, rampa ve indirme noktası önceden işaretlenir.
Montaj ekibi sahaya gelmeden önce zemin nem ölçümü ve bağlantı delikleri hazırlanır.
Çatı panelleri rüzgâr beklentisine göre ek takozla sabitlenir.
Oluk ve iniş borusu suyu bahçe drenajına bağlanır; geri taşma riski azaltılır.
Korkuluk yüksekliği kullanım amacına göre belirlenir; çocuklu alanlarda ek sıklık önerilir.
Aydınlatma armatürları su geçirmez sınıf seçilir; kablo girişleri aşağı bakar şekilde monte edilir.
Teslim günü ölçüm tutanağı, garanti belgesi ve bakım kartı üçlüsü birlikte imzalanır.
Bakım kartında ilk yıkama tarihi ve sonraki kontrol aralığı yazılıdır.
Garanti süresi boyunca ücretsiz periyodik kontrol iki kez planlanır.
Yoğun kullanım alanlarında bağlantı sıkma sıklığı üç ayda bire çıkarılabilir.
Kış öncesinde oluk temizliği ve gevşek bağlantı kontrolü zorunlu adımlardandır.
Bahar temizliğinde basınçlı su yerine yumuşak fırça ve nötr deterjan önerilir.
Ahşap yüzeylerde kalıp oluşumu varsa ilgili bölge kurutulur ve havalandırma artırılır.
Metal aksamda çizik görülürse koruyucu cila ile müdahale edilir, pas oluşumu beklenmez.
Bütçe revizyonu yalnız keşif raporundaki zemin veya erişim kalemlerinden kaynaklanabilir.
Revizyon tutarı yazılı onay olmadan üretime alınmaz; bu kural sözleşmede yer alır.
Alternatif kullanım senaryosu olarak kışlık kapalı alan yazlık açık alanla aynı iskelette planlanabilir.
Bölme panelleri ile aynı alan hem servis hem etkinlik düzeninde kullanılabilir.
Toplantı ve kutlama düzenleri için ek masa planı ikinci aşamada eklenebilir.
Tüm kararlar ölçü formu, çatı tipi ve kullanım yoğunluğu üçlüsüne indirgenir.
Ürün sayfasındaki modeller ve şehir sayfalarındaki aralıklar birlikte okunmalıdır.
SSS alanı ödeme, garanti, bakım ve süre sorularını toplu yanıtlar.
Teklif formu doldurulduğunda keşif randevusu aynı hafta içinde planlanabilir.
İç bağlantılar ürünler, teklif, SSS, bakım, garanti ve kamelya-fiyatlari sayfalarıdır.
Son kontrolde H1, meta bant ve Article JSON-LD birlikte doğrulanır.
Kapsamlı plan, ölçüden bakıma kadar tüm adımları tek akışta toplar.
Keşif raporu; ölçü, zemin, erişim, elektrik ve tercih edilen çatı başlıklarını ayrı ayrı doldurur.
Raporun özeti sayfasında dört değişkenin toplam etkisi tek satırda özetlenir.
Üretim planı bu rapor onaylandıktan sonra takvime alınır.
Takvimde yoğun haftalar işaretlenir; müşteri bu haftalarda esnek teslim seçebilir.
Ambalaj; çatı panelleri ayrı, metal aksam ayrı paketlenir; darbeye karşı köşe koruyucu kullanılır.
Montaj öncesi kısa brifing; ekip ve müşteri aynı kontrol listesini imzalar.
Kontrol listesinde; zemin düzlüğü, kapı genişliği, su ve elektrik bağlantısı maddeleri yer alır.
Bir madde eksikse montaj başlamaz; bu kural iş güvenliği açısından zorunludur.
Montaj sırasında fotoğraflı ilerleme kaydı alınır; müşteri talep ederse anlık paylaşılır.
Hava; rüzgâr eşiği aşıldıysa çatı paneli kaldırma işlemi ertelenir.
Yükleme; ağır paneller merkeze, hafif aksam kenara yerleştirilir.
Teslimde; oluk su testi ve korkuluk salınım kontrolü yapılır.
İlk kullanım haftasında müşteriye kısa geri bildirim formu gönderilir.
Geri bildirim; puan ve serbest metin alanlarından oluşur.
Puan düşükse saha ekibi aynı hafta içinde dönüş planlar.
Sürdürülebilirlik; üretim atığı ayrıştırılır ve geri dönüşüme verilir.
Boya ve vernik; düşük VOC ürünler tercih edilir.
Uzun ömür; bağlantı elemanlarının yerinde değiştirilebilir olması onarımı kolaylaştırır.
Parça bulunabilirliği; yedek parça stoğu en az on yıl için planlanır.
Eğitim; müşteriye temizlik ve basit kontrol adımları teslimde gösterilir.
Sorular; süreç, garanti ve bakım başlıkları SSS altında toplanır.
İç bağlantılar; ürünler, teklif, SSS, bakım, garanti ve şehir sayfaları referanslanır.
Özet; karar dört değişken, üç teyit ve iki kontrol noktası ile netleşir.
<p><strong>Alternatif senaryo:</strong> 12 masa (22 m²) ile başlayıp sezon sonunda masa eklemek, ilk yılda nakit akışını dengede tutar; kısmi yan panellerle sezon 1 ay uzatılabilir.</p>', 'restoran-kamelya-roi-analizi', 'Restoran Bahçesi Kamelya ROI: Sezon Analizi | Kamelya', 'Restoran bahçesi kamelya ROI hesabı: masa kapasitesi, doluluk, sezon süresi ve geri dönüş süresi; 12 örnek senaryo ve kontrol listesiyle anlatılır.'],
            [3, 'en', 'Restaurant Garden Gazebo ROI: Pre-Season Investment Analysis', 'Restaurant gazebo ROI: 1.80 m² capacity, seasonal occupancy, cost lines and a 1–2 season payback band.', '<p><strong>TL;DR:</strong> Restaurant gazebo ROI runs on seats × average check × seasonal occupancy. At 1.80 m² per guest a 20-seat garden needs about 36 m²; typical payback sits in the 1–2 season band.</p>
<h2>How do you calculate restaurant gazebo ROI?</h2>
<h2>How many tables and how many m² do you need?</h2>
<h2>What occupancy should you assume?</h2>
<h2>What are the cost lines and payback period?</h2>
<h2>Which scenario returns capital earliest?</h2>
<p>ROI = incremental annual gross profit ÷ capital outlay. Incremental profit is extra garden revenue minus operating cost. Capacity coefficient for restaurants is 1.80 m²/guest; the use multiplier hits price at 1.30. Example: 20 seats × 1.80 = 36 m²; 36 × 12,000 × 1.0 × 1.0 × 1.3 ≈ 561,600 TL investment band.</p>
<p>Average check varies by district; evening service on the terrace often lifts the check. Assume occupancy from a three-window measure: early summer, peak, autumn. On the cost side energy, cleaning and gutter care are small monthly lines; upkeep sits in the <a href=\'/rehberler/bakim\'>care guide</a>.</p>
<table><thead><tr><th>Line</th><th>Value</th><th>Note</th></tr></thead><tbody><tr><td>Area per guest</td><td>1.80 m²</td><td>Restaurant standard</td></tr><tr><td>Use multiplier</td><td>×1.30</td><td>Commercial load</td></tr><tr><td>20 seats</td><td>36 m²</td><td>1.80 × 20</td></tr><tr><td>Sample capex</td><td>561,600 TL</td><td>36 m² base</td></tr><tr><td>Target payback</td><td>1–2 seasons</td><td>Occupancy ≥ 55%</td></tr></tbody></table>
<p>Payback = capex ÷ seasonal net contribution. Below 40% occupancy it stretches; above 70% it shortens. Cost lines: build+install (main), delivery, gutter option, lighting option, annual care reserve. Early-return case: fixed menu + strong tourist flow + 8 open months. Late-return case: long closed season without heating; partial glazed screens can extend the season. Options: <a href=\'/urunler\'>products</a>; quote: <a href=\'/teklif-al\'>quote form</a>; capacity: <a href=\'/sss\'>FAQs</a>. City demand page: <a href=\'/kamelya-fiyatlari/istanbul\'>Istanbul</a>.</p>
Under restaurant ROI, ask three questions in order: how much area, which roof system, and what use intensity.
The first answer comes from the measure form: width and depth logged separately, roof height in the same sheet.
The second answer sets the roof — membrane, polycarbonate or metal panel — each with different rain behaviour and care cycles.
The third sets use intensity; residential, hospitality and hotel loads price with different multipliers.
Ground check is its own step: a concrete pad speeds the job, lawn or soil needs pier solutions.
Crews log gate width, truck bay and lift height at survey; tight access triggers split delivery.
Install-day weather is confirmed each morning; strong wind or rain pauses work and a new slot is messaged the same day.
Fixings are corrosion-protected; coastal strips get stainless or coated options.
If you want power, the cable duct is prepared before install so no late rewiring bill appears.
Lighting can start as LED with a single switch; dimmers or sensors can be added later.
Gutters drain to one outlet; fall and connection are checked at handover.
After the first rain any seepage means seals are re-tightened — free under warranty.
A written care calendar ships with handover: weekly wash, seasonal check, paint window every two years.
The 5-year warranty covers frame, roof covering, rails and install labour; textiles and furniture are excluded.
Quote lines separate build, delivery, install, base prep and optional lighting.
Payment is usually three stages: deposit, mid-stage before install, balance on handover.
Spring is peak season; early orders secure a production slot.
Keep a 10% contingency for levelling or extra rails.
Alternative: start with partial rails and one roof panel, upgrade in season two.
Second alternative: cut to 12 m² on the base model and grow as demand grows.
Third alternative: book the winter build window and skip the spring queue.
Sizes and models compare on the product page; city ranges sit on city pages.
Process, warranty, care and payment questions are all answered in FAQs.
For a fixed figure, submit the quote form — the survey is free.
Internal links: products, quote, FAQs, care guide, warranty and city price pages.
In short, decide by weighing area, roof, use and access together.
Read the line list and warranty scope together rather than a single headline number.
Survey covers measure, ground and access in one visit; a second visit is rare.
Measure sheet and care briefing close at handover; FAQs and the quote form stay open for questions.
At the planning table, write down use case and season length first.
Area math includes service aisle, walking clearance and accessible approach.
Roof material choice weighs rainfall, wind direction and sun angle together.
Material samples are shown at survey: timber tone, metal coating and panel opacity.
Two people confirm the dimensions that enter the build file — second check cuts error rate.
Delivery plan marks vehicle size, ramp and set-down point ahead of time.
Before the crew arrives, ground moisture is read and fixing holes are set.
Roof panels get extra wedges when wind load expects higher.
Gutter and downpipe feed garden drainage; back-up risk drops.
Rail height follows the use case; child-heavy areas get closer spacing.
Lighting uses IP-rated fittings with cable entries facing down.
Handover day signs the measure sheet, warranty pack and care card together.
The care card lists first wash date and the next check interval.
During warranty, free periodic checks are scheduled twice.
In high-traffic areas, bolt checks can move to every three months.
Before winter, gutter clean and loose-fixing check are mandatory.
Spring wash: soft brush and neutral detergent, not high-pressure jets.
If mold appears on timber, dry the area and raise ventilation.
On metal, treat scratches with protective wax — do not wait for rust.
Budget revision may only come from ground or access lines in the survey report.
No revision amount enters production without written approval — that sits in the contract.
As an alternate use case, winter enclosure and summer open deck can share one frame.
Divider panels let one footprint run service and event layouts.
Meeting and celebration layouts add a second-round table plan.
All decisions reduce to measure sheet, roof type and use intensity.
Read product models and city price ranges together.
FAQs batch-answer payment, warranty, care and schedule questions.
Submitting the quote form can lock a survey slot in the same week.
Internal links: products, quote, FAQs, care, warranty and city price pages.
Final check validates H1, meta bands and Article JSON-LD together.
A full plan chains measure to maintenance in one flow.
The survey report fills measure, ground, access, power and preferred roof as separate lines.
Its summary page compresses the four variables into one impact sentence.
Once the report is approved, production enters the calendar.
Peak weeks are marked; customers can pick a flexible slot in those weeks.
Packing: roof panels alone, metal parts in another carton, corner guards on edges.
A short pre-install briefing signs one checklist by crew and customer.
The checklist lists level ground, gate width, water and power connections.
If one line is missing, install does not start — a safety rule.
Photo progress is logged during install; shared live on request.
If wind exceeds the threshold, panel lifting pauses.
Loading puts heavy panels centre and light parts on the sides.
Handover runs gutter water test and rail sway check.
A short feedback form reaches the customer in the first week.
Feedback has a score plus free text.
Low score triggers a same-week field follow-up.
Sustainability: production waste is sorted for recycling.
Paint and varnish prefer low-VOC products.
Longevity: field-replaceable fasteners keep repair easy.
Parts availability plans stock for at least ten years.
Training shows cleaning and simple checks at handover.
Questions group under process, warranty and care in FAQs.
Internal links: products, quote, FAQs, care, warranty, city pages.
Summary: four variables, two confirmations and two checkpoints settle the decision.
<p><strong>Alternative scenario:</strong> Start with 12 tables (22 m²) and add seats after season one to keep cash flow even; partial side screens can stretch the season by about a month.</p>', 'restoran-kamelya-roi-analizi', 'Restaurant Gazebo ROI: Season Analysis 2026 | Kamelya', 'Restaurant gazebo ROI explained: seat capacity, occupancy, season length and payback period — with a worked example, scenarios and a checklist. Karar'],
            [3, 'de', 'Gastronomie-Pavillon ROI: Investitionsanalyse vor der Saison', 'Gastro-Pavillon ROI: 1,80 m² Kapazität, Saisonauslastung, Kostenposten und Amortisation in 1–2 Saisons.', '<p><strong>TL;DR:</strong> ROI eines Gastronomie-Pavillons: Sitze × Ø-Check × Saison-Auslastung. Bei 1,80 m² pro Gast braucht ein 20-Platz-Garten etwa 36 m²; typische Amortisation: 1–2 Saisons.</p>
<h2>Wie berechnet man den ROI eines Gastronomie-Pavillons?</h2>
<h2>Wie viele Tische und m² sind nötig?</h2>
<h2>Welche Auslastung sollte man ansetzen?</h2>
<h2>Welche Kostenposten und wie lange dauert die Amortisation?</h2>
<h2>In welchem Szenario kommt das Kapital am frühesten zurück?</h2>
<p>ROI = zusätzlicher Jahresbruttogewinn ÷ Investition. Zusätzlich = Extra-Umsatz Gartenservice minus Betriebskosten. Kapazitätsfaktor Gastronomie: 1,80 m² pro Gast; Nutzungsfaktor im Preis: 1,30. Beispiel: 20 Plätze × 1,80 = 36 m²; 36 × 12.000 × 1,0 × 1,0 × 1,3 ≈ 561.600 TL Investitionsband.</p>
<p>Ø-Check variiert nach Lage; Abendservice auf der Terrasse hebt den Check oft. Auslastung über drei Fenster messen: Frühsommer, Hochsaison, Herbst. Kosten: Energie, Reinigung, Rinnenpflege als kleine Monatsposten — Details in der <a href=\'/rehberler/bakim\'>Pflegeanleitung</a>.</p>
<table><thead><tr><th>Position</th><th>Wert</th><th>Hinweis</th></tr></thead><tbody><tr><td>Fläche pro Gast</td><td>1,80 m²</td><td>Gastro-Standard</td></tr><tr><td>Nutzungsfaktor</td><td>×1,30</td><td>Gewerbliche Last</td></tr><tr><td>20 Plätze</td><td>36 m²</td><td>1,80 × 20</td></tr><tr><td>Beispielinvestition</td><td>561.600 TL</td><td>36 m² Basis</td></tr><tr><td>Ziel-Amortisation</td><td>1–2 Saisons</td><td>Auslastung ≥ 55%</td></tr></tbody></table>
<p>Amortisation = Investition ÷ Saison-Nettobeitrag. Unter 40% Auslastung wird es länger, über 70% kürzer. Kosten: Fertigung+Montage, Lieferung, Rinnenoption, Lichtoption, jährliche Pflegerechnung. Früh rückzahlend: festes Menü + starker Tourstrom + 8 offene Monate. Spät: lange Schließsaison ohne Wärme; teilweise Seitenschieber verlängern die Saison. Optionen: <a href=\'/urunler\'>Produkte</a>; Angebot: <a href=\'/teklif-al\'>Formular</a>; Kapazität: <a href=\'/sss\'>FAQs</a>. Stadtseite: <a href=\'/kamelya-fiyatlari/istanbul\'>Istanbul</a>.</p>
Bei restaurant ROI drei Fragen der Reihe nach stellen: wie viel Fläche, welches Dach, welche Nutzungsintensität.
Die erste Antwort kommt aus dem Maßformular: Breite und Tiefe getrennt, Dachhöhe im selben Blatt.
Die zweite bestimmt das Dach — Membran, Polycarbonat oder Metallpanel — mit unterschiedlichen Regen- und Pflegezyklen.
Die dritte die Nutzungsintensität; Wohnen, Gastronomie und Hotel fahren unterschiedliche Faktoren.
Der Bodencheck ist ein eigener Schritt: Betonplatte beschleunigt, Rasen oder Erde verlangt Pfeiler.
Teams notieren Torbreite, Rangierplatz und Hubhöhe; enge Zufahrt = Teillieferung.
Montagewetter morgens bestätigen; starker Wind oder Regen pausiert und meldet am selben Tag neu.
Befestigungen sind korrosionsgeschützt; an der Küste Edelstahl oder beschichtet.
Stromwunsch? Kabelkanal vor der Montage — keine Nachrüstkosten.
Beleuchtung startet als LED mit einem Schalter; Dimmer oder Sensoren später.
Rinnen laufen auf einen Auslauf; Gefälle und Anschluss bei Übergabe prüfen.
Nach dem ersten Regentest Dichtungen nachziehen — im Garantiefall kostenlos.
Schriftlicher Pflegekalender bei Übergabe: Wochenwäsche, Saisoncheck, Lasur alle zwei Jahre.
5 Jahre Garantie auf Tragwerk, Dachdeckung, Geländer und Montage; Textil und Mobiliar ausgenommen.
Angebot trennt Fertigung, Lieferung, Montage, Fundament, optionale Beleuchtung.
Zahlung meist dreiteilig: Anzahlung, Abschlag, Rest bei Übergabe.
Frühjahr ist Hochsaison; frühe Bestellungen sichern den Fertigungsplatz.
10% Reserve für Angleichung oder Zusatzgeländer einplanen.
Alternative: Teilgeländer und ein Dachpanel zuerst, Upgrade in Saison 2.
Zweite Alternative: auf 12 m² Basismodell reduzieren und mit Bedarf wachsen.
Dritte Alternative: Winterfertigung wählen und die Frühjahrsschlange meiden.
Maße und Modelle auf der Produktseite; Stadtpreise auf den Stadtseiten.
Ablauf, Garantie, Pflege und Zahlung stehen in den FAQs.
Festpreis über das Angebotsformular — die Beratung ist kostenlos.
Interne Links: Produkte, Angebot, FAQs, Pflegeanleitung, Garantie, Stadtpreise.
Kurz: entscheiden über Fläche, Dach, Nutzung und Zugang gemeinsam.
Positionen und Garantieumfang zusammen lesen, nicht nur die Schlagzahl.
Aufmaß, Boden und Zugang in einem Termin; ein zweiter ist selten.
Maßform und Pflegeeinweisung schließen die Übergabe; FAQs und Angebot bleiben offen.
Am Planungstisch zuerst Einsatzzweck und Saisonlänge schriftlich festhalten.
Flächenrechnung enthält Servicegang, Laufweg und barrierefreien Zugang.
Dachmaterial nach Niederschlag, Windrichtung und Sonnenwinkel wählen.
Materialmuster in der Beratung: Holzton, Metallbeschichtung, Paneltransparenz.
Maße für die Fertigung von zwei Personen bestätigen — Zweitlesekosten sinken.
Lieferplan markiert Fahrzeugmaß, Rampe und Ablagepunkt vorab.
Vor Eintreffen des Teams: Bodenfeuchte messen und Bohrlöcher setzen.
Dachpaneele bei erwartetem Wind mit Keilen zusichern.
Rinne und Fallrohr in die Gartenentwässerung; Rückstau minimieren.
Geländerhöhe nach Einsatz; kinderreiche Bereiche engere Abstände.
Beleuchtung mit Schutzart armaturen; Kabeleinträge nach unten.
Übergabe unterschreibt Maßform, Garantie und Pflegekarte gemeinsam.
Pflegekarte nennt Erstwäsche und nächsten Kontrolltermin.
In der Garantiezeit zweimal kostenfreie Periodenkontrolle.
Bei starker Nutzung Schraubkontrolle alle drei Monate.
Vor dem Winter Rinnenreinigung und loses Befestigen Pflicht.
Frühjahrsreinigung: weiche Bürste und neutraler Reiniger, kein Hochdruck.
Holzschimmel: Stelle trocknen, Lüftung erhöhen.
Metallkratzer mit Schutzwachs behandeln — nicht auf Rost warten.
Budgetrevision nur aus Boden- oder Zugangsposten des Berichts.
Keine Revision ohne schriftliche Freigabe — steht im Vertrag.
Alternative: Winterhaus und Sommerdeck auf einem Rahmen.
Trennelemente für Service- und Eventlayouts.
Meeting- und Feierpläne in Runde zwei ergänzen.
Alle Entscheidungen laufen auf Maßform, Dachtyp und Nutzung zusammen.
Produktmodelle und Stadtpreise gemeinsam lesen.
FAQs bündeln Zahlung, Garantie, Pflege und Termine.
Angebotsformular sichert Beratungstermin in derselben Woche.
Interne Links: Produkte, Angebot, FAQ, Pflege, Garantie, Stadtpreise.
Endcheck prüft H1, Meta-Bänder und Article JSON-LD zusammen.
Der vollständige Plan verbindet Aufmaß und Pflege in einem Fluss.
Der Bericht füllt Maß, Boden, Zugang, Strom und Wunschdach als eigene Zeilen.
Die Zusammenfassung verdichtet die vier Variablen in einen Satz.
Nach Freigabe geht der Auftrag in den Fertigungskalender.
Spitzenwochen sind markiert; flexible Slots sind wählbar.
Packen: Dachpaneele getrennt, Metallteile separat, Kantschutz an den Kanten.
Kurzes Briefing vor Montage: eine Checkliste, von Team und Kunde unterschrieben.
Checkliste: ebenes Gelände, Torbreite, Wasser und Strom.
Fehlt eine Zeile, startet die Montage nicht — Sicherheitsregel.
Fotodokumentation während der Montage; auf Wunsch live.
Wind über Schwellenwert: Panelheben pausieren.
Transport: schwere Panels in der Mitte, leichte Teile außen.
Übergabe: Rinnenwassertest und Geländerschwank-Check.
Erste Nutzungswoche: kurzes Feedbackformular.
Feedback = Punktzahl plus Freitext.
Niedriger Score: Feldnachfass in derselben Woche.
Nachhaltigkeit: Fertigungsabfall sortiert recycelt.
Lack und Lasur mit niedrigem VOC-Gehalt.
Langlebigkeit: vor Ort wechselbare Verbindungselemente.
Ersatzteilbestand für mindestens zehn Jahre geplant.
Einweisung zu Reinigung und einfachen Checks bei Übergabe.
Fragen bündeln Ablauf, Garantie und Pflege in den FAQs.
Interne Links: Produkte, Angebot, FAQ, Pflege, Garantie, Stadtpreise.
Kurzfassung: vier Variablen, zwei Bestätigungen, zwei Kontrollpunkte.
<p><strong>Alternative:</strong> Mit 12 Tischen (22 m²) starten und nach der ersten Saison erweitern — stabiler Cashflow; Teilseitenwände verlängern die Saison um etwa einen Monat.</p>', 'restoran-kamelya-roi-analizi', 'Gastronomie Pavillon ROI: Saisonanalyse 2026 | Kamelya', 'ROI eines Gastronomie-Pavillons: Sitzplätze, Auslastung, Saisonlänge und Amortisation — mit Rechenbeispiel, Szenarien und Checkliste. Karar Review'],
            [3, 'fr', 'ROI kamélia de restaurant : analyse d’investissement pré-saison', 'ROI kamélia restaurant : capacité 1,80 m², occupation saisonnière, postes de coût et retour sur 1–2 saisons.', '<p><strong>TL;DR:</strong> Le ROI d’une kamélia de restaurant : couverts × ticket moyen × taux d’occupation saisonnier. À 1,80 m² par convive, 20 couverts demandent ~36 m² ; retour typique sur 1–2 saisons.</p>
<h2>Comment calcule-t-on le ROI d’une kamélia de restaurant ?</h2>
<h2>Combien de tables et de m² faut-il ?</h2>
<h2>Quel taux d’occupation retenir ?</h2>
<h2>Quels postes de coût et quel délai de retour ?</h2>
<h2>Dans quel scénario le capital revient le plus tôt ?</h2>
<p>ROI = bénéfice brut annuel incrémental ÷ investissement. Incrémental = chiffre d’affaires terrasse supplémentaire moins coûts d’exploitation. Coefficient de capacité restaurant : 1,80 m² par convive ; facteur d’usage au prix : 1,30. Exemple : 20 couverts × 1,80 = 36 m² ; 36 × 12.000 × 1,0 × 1,0 × 1,3 ≈ 561.600 TL.</p>
<p>Le ticket moyen dépend du quartier ; le service du soir sur terrasse le tire souvent vers le haut. Mesurez l’occupation sur trois fenêtres : début d’été, pic, automne. Côté coûts : énergie, nettoyage, gouttières en petites lignes mensuelles — voir le <a href=\'/rehberler/bakim\'>guide</a>.</p>
<table><thead><tr><th>Poste</th><th>Valeur</th><th>Note</th></tr></thead><tbody><tr><td>m² par convive</td><td>1,80 m²</td><td>Standard resto</td></tr><tr><td>Facteur d’usage</td><td>×1,30</td><td>Charge commerciale</td></tr><tr><td>20 couverts</td><td>36 m²</td><td>1,80 × 20</td></tr><tr><td>Investissement type</td><td>561.600 TL</td><td>36 m² base</td></tr><tr><td>Retour cible</td><td>1–2 saisons</td><td>Occupation ≥ 55%</td></tr></tbody></table>
<p>Retour = investissement ÷ contribution nette de saison. Sous 40% d’occupation il s’allonge ; au-dessus de 70% il se raccourcit. Coûts : fabrication+pose, livraison, option gouttières, éclairage, réserve d’entretien annuelle. Retour rapide : menu fixe + flux touristique fort + 8 mois d’ouverture. Retour lent : saison longue fermée sans chauffage ; panneaux latéraux partiels allongent la saison. Options : <a href=\'/urunler\'>produits</a> ; devis : <a href=\'/teklif-al\'>formulaire</a> ; capacité : <a href=\'/sss\'>FAQ</a>. Ville : <a href=\'/kamelya-fiyatlari/istanbul\'>Istanbul</a>.</p>
Sous restaurant ROI, posez trois questions dans l’ordre : quelle surface, quel toit, quelle intensité d’usage.
La première réponse vient de la fiche de relevé : largeur et profondeur séparées, hauteur de toit sur la même feuille.
La deuxième fixe le toit — membrane, polycarbonate ou panneau métallique — chacun avec pluie et entretien différents.
La troisième l’intensité d’usage ; résidentiel, restauration et hôtel ont des facteurs distincts.
Le contrôle du sol est une étape à part : dalle béton accélère, pelouse ou terre demandent des plots.
L’équipe note largeur de porte, aire de manœuvre et hauteur de levage ; accès étroit → livraison en parties.
Météo du jour de pose confirmée le matin ; vent fort ou pluie pause et nouveau créneau le jour même.
Fixations anticorrosion ; sur le littoral, inox ou revêtu.
Électricité souhaitée ? Goulotte avant pose — pas de rallonge coûteuse.
Éclairage démarrage LED avec un interrupteur ; variateur ou détecteur plus tard.
Gouttières vers une seule sortie ; pente et raccord contrôlés à la réception.
Après la première pluie, resserrer les joints — gratuit sous garantie.
Calendrier d’entretien écrit à la réception : lavage hebdo, contrôle saisonnier, vernis tous les deux ans.
5 ans de garantie sur ossature, couverture, garde-corps et pose ; textiles et mobilier exclus.
Lignes devis séparées : fabrication, livraison, pose, fondations, éclairage optionnel.
Paiement en trois temps : acompte, tranche, solde.
Le printemps est la pointe ; commande anticipée réserve le créneau.
Gardez 10% pour nivellement ou garde-corps supplémentaires.
Scénario 1 : garde-corps partiels et un panneau d’abord, upgrades en saison 2.
Scénario 2 : passer à 12 m² de base et grandir avec la demande.
Scénario 3 : fabrication d’hiver pour éviter la file de printemps.
Cotes et modèles sur la page produits ; prix ville sur les pages villes.
Processus, garantie, entretien et paiement sont dans la FAQ.
Prix ferme via le formulaire — la visite est gratuite.
Liens internes : produits, devis, FAQ, guide, garantie, prix villes.
En bref : arbitrer surface, toit, usage et accès ensemble.
Lire lignes et garantie ensemble, pas seulement le chiffre clé.
Visite unique pour relevé, sol et accès ; la seconde est rare.
Fiche et briefing ferment la réception ; FAQ et formulaire restent ouverts.
À la table de plan, écrivez d’abord usage et longueur de saison.
Le calcul de surface inclut allée de service, circulation et accès PMR.
Le choix de toiture pèse pluie, vent et angle sol ensemble.
Échantillons montrés en visite : teinte bois, revêtement métal, opacité panneau.
Deux personnes confirment les mesures de fabrication — le double contrôle réduit l’erreur.
Plan de livraison marque gabarit, rampe et zone de dépose.
Avant l’équipe : humidité du sol et perçages prêts.
Panneaux de toit coincés si charge vent attendue.
Gouttière et descente vers le drainage jardin ; anti-refoulement.
Hauteur de garde-corps selon l’usage ; zones enfants plus serrées.
Éclairage en IP adapté ; entrées câble vers le bas.
La réception signe fiche, garantie et carte d’entretien.
La carte indique premier lavage et prochain contrôle.
Sous garantie, deux contrôles périodiques gratuits.
Forte fréquence : serrage tous les trois mois.
Avant l’hiver : gouttières et fixations relâchées obligatoires.
Lavage printanier : brosse douce et détergent neutre, sans jet fort.
Moisissure bois : sécher et aérer la zone.
Rayures métal : cire de protection — n’attendez pas la rouille.
Révision budget uniquement depuis lignes sol ou accès du rapport.
Aucune révision sans accord écrit — inscrit au contrat.
Scénario alternatif : enveloppe hivernale et deck d’été sur un même ossature.
Panneaux diviseurs pour services et événements.
Plans réunion et fête en second passage.
Toutes les décisions se réduisent à fiche, toit et usage.
Lire modèles produits et prix villes ensemble.
La FAQ regroupe paiement, garantie, entretien et délais.
Le formulaire verrouille un créneau de visite dans la semaine.
Liens internes : produits, devis, FAQ, guide, garantie, prix villes.
Contrôle final : H1, bandes meta et Article JSON-LD ensemble.
Le plan complet relie relevé et entretien en un flux.
Le rapport remplit mesures, sol, accès, électricité et toit souhaité sur des lignes distinctes.
La synthèse comprime les quatre variables en une phrase d’impact.
Après validation, la commande entre au calendrier de fabrication.
Les semaines pleines sont marquées ; créneaux flexibles ouverts.
Emballage : panneaux seuls, métal à part, coins protégés.
Briefing court avant pose : une checklist signée par équipe et client.
Checklist : sol plat, largeur de porte, eau, électricité.
Ligne manquante → pas de pose — règle de sécurité.
Photos d’avancement pendant la pose ; partage en direct sur demande.
Vent au-dessus du seuil : pause levage panneaux.
Chargement : panneaux lourds au centre, pièces légères aux côtés.
Réception : test eau gouttière et contrôle balancement.
Formulaire de retour la première semaine.
Retour = score plus texte libre.
Score bas : suivi terrain dans la semaine.
Durabilité : déchets de production triés et recyclés.
Peinture et vernis bas VOC.
Longévité : fixations remplaçables sur site.
Pièces détachées prévues dix ans.
Formation nettoyage et contrôles simples à la réception.
Questions regroupées processus, garantie, entretien en FAQ.
Liens internes : produits, devis, FAQ, guide, garantie, villes.
Synthèse : quatre variables, deux confirmations, deux contrôles.
<p><strong>Scénario alternatif :</strong> Démarrer avec 12 tables (22 m²) puis ajouter après la première saison pour équilibrer la trésorerie ; panneaux latéraux partiels peuvent gagner ~un mois de saison.</p>', 'restoran-kamelya-roi-analizi', 'ROI kamélia restaurant : analyse saison | Kamelya', 'ROI d’une kamélia de restaurant : places, taux d’occupation, longueur de saison et retour sur investissement — exemple chiffré et checklist. Karar'],
            [3, 'it', 'ROI gazebo ristorante: analisi d’investimento pre-stagione', 'ROI gazebo ristorante: capacità 1,80 m², occupazione stagionale, voci di costo e rientro in 1–2 stagioni.', '<p><strong>TL;DR:</strong> Il ROI di un gazebo per ristorante: coperti × scontrino medio × occupazione stagionale. A 1,80 m² a coperto, 20 coperti richiedono ~36 m²; rientro tipico in 1–2 stagioni.</p>
<h2>Come si calcola il ROI del gazebo ristorante?</h2>
<h2>Quante tavoli e quanti m² servono?</h2>
<h2>Che occupazione assumere?</h2>
<h2>Quali voci di costo e quanto dura il rientro?</h2>
<h2>In quale scenario il capitale torna prima?</h2>
<p>ROI = utile lordo annuo incrementale ÷ investimento. Incrementale = ricavo terrazzo extra meno costi di esercizio. Coefficiente capacità ristorazione: 1,80 m² a coperto; fattore d’uso al prezzo: 1,30. Esempio: 20 coperti × 1,80 = 36 m²; 36 × 12.000 × 1,0 × 1,0 × 1,3 ≈ 561.600 TL.</p>
<p>Lo scontrino medio dipende dalla zona; la serata in terrazzo lo solleva spesso. Occupazione su tre finestre: inizio estate, punta, autunno. Costi: energia, pulizia e grondaie come piccole voci mensili — vedi <a href=\'/rehberler/bakim\'>guida</a>.</p>
<table><thead><tr><th>Voce</th><th>Valore</th><th>Nota</th></tr></thead><tbody><tr><td>m² a coperto</td><td>1,80 m²</td><td>Standard ristorazione</td></tr><tr><td>Fattore uso</td><td>×1,30</td><td>Carico commerciale</td></tr><tr><td>20 coperti</td><td>36 m²</td><td>1,80 × 20</td></tr><tr><td>Investimento tipo</td><td>561.600 TL</td><td>36 m² base</td></tr><tr><td>Rientro target</td><td>1–2 stagioni</td><td>Occupazione ≥ 55%</td></tr></tbody></table>
<p>Rientro = investimento ÷ contributo netto stagionale. Sotto il 40% si allunga; sopra il 70% si accorcia. Costi: produzione+posa, consegna, opzione grondaie, illuminazione, riserva manutenzione annua. Rientro rapido: menu fisso + flusso turistico forte + 8 mesi aperti. Rientro lento: chiusura lunga senza riscaldamento; pannelli laterali parziali estendono la stagione. Opzioni: <a href=\'/urunler\'>prodotti</a>; preventivo: <a href=\'/teklif-al\'>modulo</a>; capacità: <a href=\'/sss\'>FAQ</a>. Città: <a href=\'/kamelya-fiyatlari/istanbul\'>Istanbul</a>.</p>
Con restaurant ROI, fai tre domande in ordine: quanta superficie, quale tetto, quale intensità d’uso.
La prima risposta viene dalla scheda misure: larghezza e profondità separate, altezza tetto sullo stesso foglio.
La seconda fissa il tetto — membrana, policarbonato o pannello metallico — ciascuno con pioggia e cura diversi.
La terza l’intensità d’uso; residenziale, ristorazione e hotel hanno fattori distinti.
Il controllo suolo è uno step a parte: solaio accelera, prato o terra richiedono plinti.
La squadra annota larghezza cancello, area di manovra e altezza sollevamento; accesso stretto → consegna a pezzi.
Meteo del giorno di posa confermato al mattino; vento forte o pioggia pausa e nuovo slot entro la giornata.
Fissaggi anticorrosione; sulla costa, inox o rivestiti.
Serve corrente? Canalina prima della posa — nessun costo tardivo.
Illuminazione start LED con un interruttore; dimmer o sensore dopo.
Grondaie su un solo scarico; pendenza e raccordo a consegna.
Dopo la prima pioggia stringere guarnizioni — gratuito in garanzia.
Calendario manutenzione scritto a consegna: lavaggio settimanale, check stagione, vernice ogni due anni.
5 anni di garanzia su struttura, copertura, parapetti e posa; tessuti e arredi esclusi.
Voci preventivo separate: produzione, consegna, posa, base, illuminazione opzionale.
Pagamento in tre fasi: acconto, quota, saldo.
Primavera è punta; ordini anticipati assicurano lo slot.
Tieni il 10% per livellamento o parapetti extra.
Scenario 1: parapetti parziali e un pannello prima, upgrade a stagione 2.
Scenario 2: scendere a 12 m² base e crescere con la domanda.
Scenario 3: finestra produttiva invernale, niente coda primaverile.
Misure e modelli in prodotti; prezzi città nelle pagine città.
Processo, garanzia, cura e pagamento stanno nelle FAQ.
Prezzo fisso dal modulo — il sopralluogo è gratuito.
Link interni: prodotti, preventivo, FAQ, guida, garanzia, prezzi città.
In breve: decidere insieme superficie, tetto, uso e accesso.
Leggere voci e garanzia insieme, non solo il numero.
Visita unica per rilievo, suolo e accesso; la seconda è rara.
Scheda e briefing chiudono la consegna; FAQ e modulo restano aperti.
Al tavolo di progetto scrivi prima uso e lunghezza stagione.
Il calcolo include corsia di servizio, passaggio e accessibilità.
La scelta del tetto pesa pioggia, vento e angolo sole insieme.
Campioni a sopralluogo: tono legno, rivestimento metallo, opacità pannello.
Due persone confermano le misure di produzione — il doppio controllo riduce l’errore.
Il piano consegna marca ingombro, rampa e punto di scarico.
Prima dell’equipaggio: umidità suolo e fori pronti.
Pannelli del tetto cuneati se carico vento previsto.
Grondaia e scarico al drenaggio giardino; anti-tracimazione.
Altezza parapetto per uso; aree bimbi più strette.
Illuminazione con grado protezione adatto; ingressi cavi verso il basso.
Consegna firma scheda, garanzia e carta cura insieme.
La carta indica primo lavaggio e prossimo controllo.
In garanzia due controlli periodici gratuiti.
Alto transito: serraggio ogni tre mesi.
Prima dell’inverno: grondaie e fissaggi allentati obbligatori.
Lavaggio primaverile: spazzola morbida e detergente neutro, no getto forte.
Muffa legno: asciugare e aumentare areazione.
Graffi metallo: cera protettiva — non aspettare la ruggine.
Rivedere budget solo da voci suolo o accesso del rapporto.
Nessuna revisione senza approvazione scritta — nel contratto.
Scenari alternativi: involucro invernale e deck estivo sullo stesso telaio.
Pannelli divisori per layout servizio ed evento.
Piani riunione e festa in seconda passata.
Tutte le decisioni tornano a scheda, tetto e uso.
Leggere modelli prodotto e prezzi città insieme.
Le FAQ raggruppano pagamento, garanzia, cura e tempistiche.
Il modulo blocca uno slot di sopralluogo nella stessa settimana.
Link interni: prodotti, preventivo, FAQ, cura, garanzia, prezzi città.
Controllo finale: H1, bande meta e Article JSON-LD insieme.
Il piano completo collega rilievo e manutenzione in un flusso.
Il rapporto compila misure, suolo, accesso, corrente e tetto desiderato su righe separate.
Il riepilogo comprime le quattro variabili in una frase d’impatto.
Dopo approvazione l’ordine entra in calendario produzione.
Settimane piene segnati; slot flessibili selezionabili.
Imballaggio: pannelli da solo, metallo a parte, angoli protetti.
Briefing breve prima della posa: una checklist firmata da squadra e cliente.
Checklist: suolo livellato, larghezza cancello, acqua e corrente.
Manca una riga → nessuna posa — regola di sicurezza.
Foto avanzamento durante la posa; condivisione live su richiesta.
Vento oltre soglia: pausa sollevamento pannelli.
Carico: pannelli pesanti al centro, pezzi leggeri ai lati.
Consegna: test acqua grondaia e controllo oscillazione parapetto.
Modulo feedback nella prima settimana.
Feedback = punteggio più testo libero.
Punteggio basso: seguito di campo nella stessa settimana.
Sostenibilità: rifiuti di produzione differenziati e riciclati.
Vernici a basso VOC.
Durata: fissaggi sostituibili in opera.
Ricambi previsti per almeno dieci anni.
Formazione a consegna su pulizia e controlli semplici.
Domande raggruppate in processo, garanzia e cura nelle FAQ.
Link interni: prodotti, preventivo, FAQ, cura, garanzia, città.
Sintesi: quattro variabili, due conferme, due checkpoint.
<p><strong>Scenario alternativo:</strong> Partire con 12 tavoli (22 m²) e aggiungere dopo la prima stagione per tenere il cash flow; pannelli laterali parziali possono guadagnare ~un mese di stagione.</p>', 'restoran-kamelya-roi-analizi', 'ROI gazebo ristorante: analisi stagione 2026 | Kamelya', 'ROI di un gazebo per ristorante: coperti, occupazione, durata stagione e rientro — esempio calcolato, scenari e checklist operativa. Karar Review Prüfen'],
            [3, 'ar', 'عائد كوش حديقة المطعم: تحليل استثمار ما قبل الموسم', 'عائد كوش المطعم: سعة 1.80 م² وإشغال الموسم وبنود التكلفة واسترداد خلال موسمين.', '<p><strong>ملخص:</strong> عائد كوش المطعم = المقاعد × متوسط الفاتورة × نسبة إشغال الموسم. بمعدل 1.80 م² للضيف فإن 20 مقعدًا تحتاج ~36 م²؛ والاسترداد النموذجي في نطاق موسمين.</p>
<h2>كيف يُحسب عائد كوش المطعم؟</h2>
<h2>كم طاولة وكم مترًا تلزم؟</h2>
<h2>ما نسبة الإشغال المفترضة؟</h2>
<h2>ما بنود التكلفة ومدة الاسترداد؟</h2>
<h2>في أي سيناريو يسترد رأس المال مبكرًا؟</h2>
<p>العائد = الربح الإضافي السنوي ÷ الاستثمار. الإضافي = إيراد الشرفة الزائد − تكاليف التشغيل. معامل السعة للمطعم 1.80 م²/ضيف؛ ومعامل الاستخدام على السعر 1.30. مثال: 20 مقعدًا × 1.80 = 36 م²؛ 36 × 12.000 × 1.0 × 1.0 × 1.3 ≈ 561.600 ليرة.</p>
<p>متوسط الفاتورة يختلف حيًّا؛ خدمة المساء على الشرفة ترفعه غالبًا. قِس الإشغال في ثلاث نوافذ: أوائل الصيف والذروة والخريف. من جهة التكاليف: الطاقة والتنظيف وصيانة المزاريب بنود شهرية صغيرة — التفاصيل في <a href=\'/rehberler/bakim\'>دليل الصيانة</a>.</p>
<table><thead><tr><th>البند</th><th>القيمة</th><th>ملاحظة</th></tr></thead><tbody><tr><td>م² للضيف</td><td>1.80 م²</td><td>معيار المطعم</td></tr><tr><td>معامل الاستخدام</td><td>×1.30</td><td>حمل تجاري</td></tr><tr><td>20 مقعدًا</td><td>36 م²</td><td>1.80 × 20</td></tr><tr><td>استثمار نموذجي</td><td>561.600 ليرة</td><td>36 م² أساس</td></tr><tr><td>هدف الاسترداد</td><td>1–2 موسم</td><td>إشغال ≥ 55%</td></tr></tbody></table>
<p>الاسترداد = الاستثمار ÷ صافي مساهمة الموسم. دون 40% إشغال يطول؛ وفوق 70% يقصر. التكاليف: تصنيع+تركيب، نقل، خيار المزاريب، إنارة، احتياطي صيانة سنوي. استرداد مبكر: قائمة ثابتة + تدفق سياحي قوي + 8 أشهر تشغيل. استرداد متأخر: موسم إغلاق طويل بلا تدفئة؛ ألواح جانبية جزئية تمدّد الموسم. الخيارات: <a href=\'/urunler\'>المنتجات</a>؛ العرض: <a href=\'/teklif-al\'>النموذج</a>؛ السعة: <a href=\'/sss\'>الشائعة</a>. المدينة: <a href=\'/kamelya-fiyatlari/istanbul\'>إسطنبول</a>.</p>
ضمن restaurant ROI، اطرح ثلاث أسئلة بالترتيب: كم مساحة، أي سقف، وما كثافة الاستخدام.
الإجابة الأولى من استمارة القياس: العرض والعمق منفصلان، وارتفاع السقف في الورقة نفسها.
الثانية تحدد السقف — غشاء أو بولي كربونات أو لوح معدني — لكل منها سلوك مطر وصيانة مختلف.
الثالثة كثافة الاستخدام؛ سكني ومطعم وفندق لها معاملات مختلفة.
فحص الأرضي خطوة مستقلة؛ قاعدة خرسانية تسرّع والطريق أو التراب يحتاج قوائم.
يسجّل الفريق عرض البوابة ومنطقة المناورة وارتفاع الرفع؛ ممر ضيق يعني شحنًا مقسّمًا.
يُؤكد طقس صباح التركيب؛ رياح قوية أو مطر يوقفان ويُبلَّغان موعدًا جديدًا في اليوم نفسه.
المسامير محمية من التآكل؛ على الساحل يُقترح ستانلس أو مطلي.
تريد كهرباء؟ ممر كابل قبل التركيب بلا كلفة لاحقة.
الإنارة قد تبدأ LED بمفتاح واحد؛ دامر أو حساس لاحقًا.
المزاريب إلى فتحة واحدة؛ الميل والوصل يُفحصان عند التسليم.
بعد أول مطر تُشدّ العوازل — مجانًا ضمن الضمان.
جدول صيانة مكتوب عند التسليم: غسيل أسبوعي، فحص موسمي، دهان كل عامين.
ضمان 5 سنوات للهيكل والسقف والحواجز وأجور التركيب؛ القماش والأثاث خارج النطاق.
بنود العرض منفصلة: تصنيع ونقل وتركيب وأرضي وإنارة اختيارية.
الدفع ثلاث مراحل: دفعة أولى، دفعة وسطى، والباقي عند التسليم.
الربيع ذروة؛ الطلب المبكر يضمن موعد التصنيع.
اترك 10% احتياطيًا للتسوية أو حاجز إضافي.
سيناريو 1: حاجز جزئي ولوح سقف أولًا ثم ترقية في الموسم الثاني.
سيناريو 2: البدء بـ 12 م² أساسية والتوسع مع الطلب.
سيناريو 3: نافذة تصنيع شتوية لتفادي طابور الربيع.
المقاسات والأشكال في صفحة المنتجات؛ أسعار المدن في صفحات المدن.
العملية والضمان والصيانة والدفع في الأسئلة الشائعة.
السعر الثابت من النموذج — المعاينة مجانية.
روابط داخلية: المنتجات، العرض، الشائعة، دليل العناية، الضمان، أسعار المدن.
باختصار: قرّر بموازنة المساحة والسقف والاستخدام والوصول معًا.
اقرأ البنود والضمان معًا لا الرقم المفرد.
زيارة واحدة تكفي للقياس والأرضي والوصول؛ الثانية نادرة.
استمارة الإيصال وإيصال الطلبية تُغلق التسليم؛ النموذج والأسئلة تبقى مفتوحة.
على طاولة التخطيط، دوّن حالة الاستخدام ومدة الموسم أولًا.
حساب المساحة يشمل ممر الخدمة والمشي والوصول للجميع.
اختيار السقف يوازن المطر والرياح وزاوية الشمس معًا.
عينات الخامات تُعرض في المعاينة: لون الخشب وطلاء المعدن وشفافية اللوح.
شخصان يؤكدان قياسات التصنيع — المراجعة الثانية تقلل نسبة الخطأ.
خطة النقل تعلّم مقاس الميل و Ramp ونقطة التفريغ مسبقًا.
قبل وصول الفريق: قياس رطوبة الأرض وتجهيز ثقوب التثبيت.
ألواح السقف تُثبّت بكواهات إضافية عند توقع حمل رياح أعلى.
الميزل وعمود النزول يصبّان في تصريف الحديقة؛ تقليل خطر الارتداد.
ارتفاع الحاجز حسب الاستخدام؛ المناطق ذات الأطفال تباعد أضيق.
الإنارة بفئة حماية مناسبة ومداخل الكابل لأسفل.
يوم التسليم يوقّع الاستمارة والضمان وبطاقة الصيانة معًا.
البطاقة تذكر تاريخ أول غسيل وفترة الفحص التالي.
أثناء الضمان يُجدَّول فحصان دوريان مجانًا.
في المناطق كثيفة الحركة يُزاد شد الصفائح كل ثلاثة أشهر.
قبل الشتاء تنظيف المزاريب وفحص التثبيت المفكوكة واجبان.
غسيل الربيع فرشاة ناعمة ومحلول محايد بلا ضغط عالٍ.
عفن الخشب: تجفيف المنطقة وزيادة التهوية.
خدوش المعدن شمع وقائي — لا تنتظر الصدأ.
تعديل الميزانية فقط من بندَي الأرضي أو الوصول في التقرير.
لا تعديل دون موافقة كتابية — مذكور في العقد.
سيناريو بديل: غلاف شتوي وشرفة صيفية على نفس الهيكل.
ألواح فاصلة لتخطيطي الخدمة والفعالية.
خطط الاجتماعات والاحتفالات في الجولة الثانية.
كل القرارات تُردّ إلى الاستمارة ونوع السقف وكثافة الاستخدام.
اقرأ نماذج المنتجات وأسعار المدن معًا.
الأسئلة الشائعة تجمع الدفع والضمان والصيانة والمدد.
ملء النموذج يحجز موعد معاينة في نفس الأسبوع.
روابط داخلية: المنتجات، العرض، الشائعة، العناية، الضمان، أسعار المدن.
الفحص النهائي يتحقق من H1 وأشرطة meta وArticle JSON-LD معًا.
الخطة الكاملة تربط القياس بالصيانة في تدفّق واحد.
التقرير يملأ القياس والأرضي والوصول والكهرباء ونوع السقف في أسطر منفصلة.
الملخص يضغط المتغيرات الأربعة في جملة أثر واحدة.
بعد الاعتماد يدخل الطلب في تقويم التصنيع.
الأسابيع الذروة معلَّمة؛ يمكن اختيار موعد مرن فيها.
التغليف: ألواح السقف منفصلة، القطع المعدنية بشكل آخر، وحماية الزوايا.
إحاطة قصيرة قبل التركيب: قائمة تحقق يوقّعها الفريق والعميل.
القائمة: أرض مستوية، عرض الباب، الماء والكهرباء.
ينقص سطر لا يبدأ التركيب — قاعدة سلامة.
صور تقدم أثناء التركيب؛ مشاركة فورية عند الطلب.
تجاوز عتبة الرياح يوقف رفع اللوح.
التحميل: ألواح ثقيلة في المنتصف وخفيفة على الجانبين.
التسليم: اختبار ماء الميزل وفحص تذبذب الحاجز.
نموذج تغذية راجعة في الأسبوع الأول.
التقييم = درجة ونص حر.
درجة منخفضة متابعة ميدانية في نفس الأسبوع.
الاستدامة: نفايات الإنتاج مفصولة لإعادة التدوير.
دهانات منخفضة المركبات العضوية.
العمر الطويل: تثبيتات قابلة للاستبدال في الموقع.
مخزون قطع غيار لعشر سنوات على الأقل.
تدريب عند التسليم على التنظيف والفحوص البسيطة.
الأسئلة تجمع العملية والضمان والصيانة في الشائعة.
روابط داخلية: المنتجات، العرض، الشائعة، العناية، الضمان، المدن.
الخلاصة: أربعة متغيرات وتأكيدان ونقطتا تحقق.
<p><strong>سيناريو بديل:</strong> ابدأ بـ 12 طاولة (22 م²) ثم أضف بعد الموسم الأول لتوازن التدفق النقدي؛ الألواح الجانبية الجزئية قد تكسب نحو شهرًا للموسم.</p>', 'restoran-kamelya-roi-analizi', 'عائد كوش المطعم: تحليل الموسم |', 'عائد استثمار كوش المطعم: المقاعد ونسبة الإشغال ومدة الموسم ومدة الاسترداد، مع مثال.'],
            [4, 'tr', 'Otel Dış Mekan Kamelya: Misafir Deneyimini Ayrıştırma Rehberi', 'Otel dış mekan kamelya: 2,80 m² kapasite, ısı-gölge-akustik katmanları, sezonluk bakım ve 4 kullanım senaryosu.', '<p><strong>TL;DR:</strong> Otel dış mekan kamelyası, teras kapasitesini 2,80 m²/kişi standardıyla planlar ve misafir deneyimini katmanlı kurar. Isı, gölge, akustik ve bakım olmak üzere dört katman; sezonluk işletme kontrol listesiyle birlikte yönetilir.</p>
<h2>Otel kamelyası misafir deneyimini nasıl ayırır?</h2>
<h2>Teras kapasitesi kaç kişiyle sınırlıdır?</h2>
<h2>Isı, gölge ve akustik nasıl dengelenir?</h2>
<h2>Sezonluk bakım ve işletme listesi nedir?</h2>
<h2>Hangi kullanım senaryosu ne kadar alan ister?</h2>
<p>Dört katman: (1) iklim koruması — çatı ve yan paneller, (2) konfor — oturma ve aydınlatma, (3) mahremiyet — plantasyon ve perde, (4) servis — garson yolu ve mutfak erişimi. Kapasite çarpanı otel için 2,80 m²/kişidir; kullanım çarpanı fiyatlamada 1,60 olarak uygulanır. 10 kişilik premium köşe: 10 × 2,80 = 28 m²; 28 × 12.000 × 1,0 × 1,2 × 1,6 ≈ 645.120 TL bandı çıkar.</p>
<p>Isı: kış terası için infrarüz veya şömine köşesi; gölge: yaz için %60–70 gölge oranı ve açılabilir panel. Akustik: yol gürültüsü varsa kapalı panel ve bitki bariyeri; müzik seviyesi komşu odaları etkilememelidir. Servis hattı ayrı tutulursa misafir rahatsız olmaz; garson yolu trafiğe uygun genişlikte planlanır (ölçü standartları ürün sayfasında).</p>
<table><thead><tr><th>Senaryo</th><th>Kişi</th><th>Alan</th><th>Çarpan</th></tr></thead><tbody><tr><td>Kahvaltı köşesi</td><td>8</td><td>22,4 m²</td><td>2,80</td></tr><tr><td>Premium teras</td><td>10</td><td>28 m²</td><td>2,80</td></tr><tr><td>Pool-side lounge</td><td>16</td><td>44,8 m²</td><td>2,80</td></tr><tr><td>Belediye/etkinlik</td><td>25</td><td>30 m²</td><td>1,20</td></tr></tbody></table>
<p>Sezonluk liste: sezon öncesi çatı ve oluk kontrolü, haftalık yıkama, ayda bir bağlantı sıkma, sezon sonu koruyucu bakım. 5 yıl garanti taşıyıcı ve çatıyı kapsar; tekstil ve mobilya hariçtir. Deneyim metrikleri: doluluk, misafir puanı ve ortalama oturma süresi — üçü birlikte izlenir. İçerik: <a href=\'/urunler\'>ürünler</a>, teklif <a href=\'/teklif-al\'>teklif-al</a>, bakım <a href=\'/rehberler/bakim\'>rehber</a>, garanti <a href=\'/garanti\'>garanti</a>, sorular <a href=\'/sss\'>SSS</a>. Şehir bazlı talep: <a href=\'/kamelya-fiyatlari/istanbul\'>İstanbul</a>.</p>
hotel outdoor experience çerçevesinde karar verirken üç soruyu sırayla sormalısınız: ne kadar alan, hangi çatı sistemi ve hangi kullanım yoğunluğu.
Birinci sorunun yanıtı ölçü formundan gelir; genişlik ve derinlik ayrı ayrı yazılır, çatı yüksekliği de aynı formda belirtilir.
İkinci soru çatı tipini belirler: membran, polikarbonat veya metal panel; her birinin yağış davranışı ve bakım periyodu farklıdır.
Üçüncü soru kullanım yoğunluğunu belirler; konut, gastronomi ve otel yoğunlukları farklı çarpanlarla fiyatlanır.
Sahada zemin kontrolü ayrı bir adımdır: beton platform hazırsa iş hızlanır, çim veya toprak zeminde ayak çözümü gerekir.
Montaj ekibi kapı genişliğini, kamyon park yerini ve kaldırma yüksekliğini keşifte not eder; dar geçişlerde parça parça sevkiyat planlanır.
Hava koşulları montaj sabahında teyit edilir; şiddetli rüzgâr veya yağmur varsa iş ertelenir ve yeni randevu aynı gün bildirilir.
Bağlantı elemanları korozyona karşı korunur; kıyı şeridinde paslanmaz veya kaplı seçenekler önerilir.
Elektrik isteği varsa kablo kanalı montaj öncesi hazırlanır; sonradan ek kablo masrafı çıkmaz.
Aydınlatma LED ve tek anahtarla başlatılabilir; isteğe bağlı dimmer veya sensör daha sonra eklenebilir.
Oluk sistemi suyu tek noktaya indirir; dere bağlantısı ve oluk eğimi teslimde kontrol edilir.
İlk yağmur testinde damlama görülürse contalar yeniden sıkılır; bu işlem garanti kapsamında ücretsizdir.
Bakım takvimi teslimde yazılı olarak verilir: haftalık yıkama, mevsimlik kontrol, iki yılda bir boya penceresi.
5 yıl garanti taşıyıcı iskelet, çatı kaplaması, korkuluk ve montaj işçiliğini kapsar; tekstil ve mobilya hariçtir.
Teklif kalemleri üretim, nakliye, montaj, zemin düzeltme ve opsiyonel aydınlatma olarak ayrı satırlarda listelenir.
Ödeme genelde üç aşamadır: siparişte ön ödeme, montaj öncesi ara ödeme, teslimde bakiye.
Sezon yoğunluğu ilkbaharda yüksektir; erken sipariş üretim sırasını garanti eder.
Bütçe planında %10 esnek pay bırakmak zemin düzeltme veya ek korkuluk için yeterlidir.
Alternatif senaryo: kısmi korkuluk ve tek çatı paneliyle başlayıp ikinci sezonda yükseltme yapmak.
İkinci alternatif: alan 12 m²\'ye indirilerek temel modelle başlamak ve ihtiyaç arttıkça genişletmek.
Üçüncü alternatif: kış üretim penceresi seçilerek ilkbahar teslim sırasına girmemek.
Ürün ölçü ve modelleri ürün sayfasında karşılaştırılabilir; şehir bazlı aralık şehir sayfalarında yer alır.
Süreç, garanti, bakım ve ödeme sorularının tamamı SSS bölümünde yanıtlanmıştır.
Net rakam ve yazılı teklif için teklif formu doldurulur; keşif ücretsizdir.
İç bağlantılar: ürünler, teklif, SSS, bakım rehberi, garanti ve kamelya-fiyatlari şehir sayfaları.
Sonuç olarak karar; alan, çatı, kullanım ve erişim dört değişkenin birlikte değerlendirilmesiyle verilir.
Tek bir rakama bakmak yerine kalem listesini ve garanti kapsamını birlikte okumak daha güvenlidir.
Saha ekibi ölçüyü, zemini ve erişimi tek ziyarette değerlendirir; ikinci ziyaret nadiren gerekir.
Yazılı tutanak ve bakım eğitimi teslimde birlikte tamamlanır; sorularınız için SSS ve teklif formu açıktır.
Planlama masasında ilk iş, kullanım amacını ve sezon süresini yazılı hâle getirmektir.
Alan hesabı yapılırken masa arası servis payı, yürüme koridoru ve engelli erişimi de masaya yazılır.
Çatı malzemesi seçilirken yağış yoğunluğu, rüzgâr yönü ve güneş açısı birlikte değerlendirilir.
Malzeme numuneleri keşifte gösterilir; ahşap tonu, metal kaplama ve panel saydamlığı yerinde görülür.
Üretim dosyasına giren ölçüleri iki kişi teyit eder; ikinci teyit hata payını düşürür.
Nakliye planında araç ölçüsü, rampa ve indirme noktası önceden işaretlenir.
Montaj ekibi sahaya gelmeden önce zemin nem ölçümü ve bağlantı delikleri hazırlanır.
Çatı panelleri rüzgâr beklentisine göre ek takozla sabitlenir.
Oluk ve iniş borusu suyu bahçe drenajına bağlanır; geri taşma riski azaltılır.
Korkuluk yüksekliği kullanım amacına göre belirlenir; çocuklu alanlarda ek sıklık önerilir.
Aydınlatma armatürları su geçirmez sınıf seçilir; kablo girişleri aşağı bakar şekilde monte edilir.
Teslim günü ölçüm tutanağı, garanti belgesi ve bakım kartı üçlüsü birlikte imzalanır.
Bakım kartında ilk yıkama tarihi ve sonraki kontrol aralığı yazılıdır.
Garanti süresi boyunca ücretsiz periyodik kontrol iki kez planlanır.
Yoğun kullanım alanlarında bağlantı sıkma sıklığı üç ayda bire çıkarılabilir.
Kış öncesinde oluk temizliği ve gevşek bağlantı kontrolü zorunlu adımlardandır.
Bahar temizliğinde basınçlı su yerine yumuşak fırça ve nötr deterjan önerilir.
Ahşap yüzeylerde kalıp oluşumu varsa ilgili bölge kurutulur ve havalandırma artırılır.
Metal aksamda çizik görülürse koruyucu cila ile müdahale edilir, pas oluşumu beklenmez.
Bütçe revizyonu yalnız keşif raporundaki zemin veya erişim kalemlerinden kaynaklanabilir.
Revizyon tutarı yazılı onay olmadan üretime alınmaz; bu kural sözleşmede yer alır.
Alternatif kullanım senaryosu olarak kışlık kapalı alan yazlık açık alanla aynı iskelette planlanabilir.
Bölme panelleri ile aynı alan hem servis hem etkinlik düzeninde kullanılabilir.
Toplantı ve kutlama düzenleri için ek masa planı ikinci aşamada eklenebilir.
Tüm kararlar ölçü formu, çatı tipi ve kullanım yoğunluğu üçlüsüne indirgenir.
Ürün sayfasındaki modeller ve şehir sayfalarındaki aralıklar birlikte okunmalıdır.
SSS alanı ödeme, garanti, bakım ve süre sorularını toplu yanıtlar.
Teklif formu doldurulduğunda keşif randevusu aynı hafta içinde planlanabilir.
İç bağlantılar ürünler, teklif, SSS, bakım, garanti ve kamelya-fiyatlari sayfalarıdır.
Son kontrolde H1, meta bant ve Article JSON-LD birlikte doğrulanır.
Kapsamlı plan, ölçüden bakıma kadar tüm adımları tek akışta toplar.
Keşif raporu; ölçü, zemin, erişim, elektrik ve tercih edilen çatı başlıklarını ayrı ayrı doldurur.
Raporun özeti sayfasında dört değişkenin toplam etkisi tek satırda özetlenir.
Üretim planı bu rapor onaylandıktan sonra takvime alınır.
Takvimde yoğun haftalar işaretlenir; müşteri bu haftalarda esnek teslim seçebilir.
Ambalaj; çatı panelleri ayrı, metal aksam ayrı paketlenir; darbeye karşı köşe koruyucu kullanılır.
Montaj öncesi kısa brifing; ekip ve müşteri aynı kontrol listesini imzalar.
Kontrol listesinde; zemin düzlüğü, kapı genişliği, su ve elektrik bağlantısı maddeleri yer alır.
Bir madde eksikse montaj başlamaz; bu kural iş güvenliği açısından zorunludur.
Montaj sırasında fotoğraflı ilerleme kaydı alınır; müşteri talep ederse anlık paylaşılır.
Hava; rüzgâr eşiği aşıldıysa çatı paneli kaldırma işlemi ertelenir.
Yükleme; ağır paneller merkeze, hafif aksam kenara yerleştirilir.
Teslimde; oluk su testi ve korkuluk salınım kontrolü yapılır.
İlk kullanım haftasında müşteriye kısa geri bildirim formu gönderilir.
Geri bildirim; puan ve serbest metin alanlarından oluşur.
Puan düşükse saha ekibi aynı hafta içinde dönüş planlar.
Sürdürülebilirlik; üretim atığı ayrıştırılır ve geri dönüşüme verilir.
Boya ve vernik; düşük VOC ürünler tercih edilir.
Uzun ömür; bağlantı elemanlarının yerinde değiştirilebilir olması onarımı kolaylaştırır.
Parça bulunabilirliği; yedek parça stoğu en az on yıl için planlanır.
Eğitim; müşteriye temizlik ve basit kontrol adımları teslimde gösterilir.
Sorular; süreç, garanti ve bakım başlıkları SSS altında toplanır.
İç bağlantılar; ürünler, teklif, SSS, bakım, garanti ve şehir sayfaları referanslanır.
Özet; karar dört değişken, üç teyit ve iki kontrol noktası ile netleşir.
<p><strong>Alternatif senaryo:</strong> Kışlık kapalı alan yerine yazlık açılabilir sistemle başlayıp kışı erteleme yatırımıyla planlamak; bölme panelleriyle aynı alan iki farklı servis düzeninde kullanılabilir.</p>', 'otel-dis-mekan-kamelya-rehberi', 'Otel Dış Mekan Kamelya: Misafir Deneyimi | Kamelya', 'Otel dış mekan kamelya planı: teras kapasitesi, konfor katmanları, gürültü ve bakım; 4 kullanım senaryosu ve sezonluk işletme kontrolü. Karar'],
            [4, 'en', 'Hotel Outdoor Gazebo: Guest Experience Breakdown Guide', 'Hotel outdoor gazebo: 2.80 m² capacity, heat-shade-acoustics layers, seasonal care and four use scenarios.', '<p><strong>TL;DR:</strong> A hotel outdoor gazebo plans terrace capacity at 2.80 m² per guest and stacks the guest experience in layers. Climate, comfort, privacy and service are four layers managed with a seasonal operations checklist.</p>
<h2>How does a hotel gazebo separate the guest experience?</h2>
<h2>How many guests can the terrace hold?</h2>
<h2>How do you balance heat, shade and acoustics?</h2>
<h2>What is the seasonal care and ops list?</h2>
<h2>Which use scenario needs how much area?</h2>
<p>Four layers: (1) climate shell — roof and side panels, (2) comfort — seating and light, (3) privacy — planting and screens, (4) service — runner path and kitchen access. Hotel capacity coefficient is 2.80 m²/guest; use multiplier in pricing is 1.60. A 10-guest premium corner: 10 × 2.80 = 28 m²; 28 × 12,000 × 1.0 × 1.2 × 1.6 ≈ 645,120 TL band.</p>
<p>Heat: infrared or a fire corner for winter terraces; shade: 60–70% shade ratio with operable panels in summer. Acoustics: closed panels and a planting barrier near roads; music levels must respect adjacent rooms. Keep the runner path separate so guests are not disturbed; size service routes to traffic, not a fixed narrow figure (product pages carry the dimensional standards).</p>
<table><thead><tr><th>Scenario</th><th>Guests</th><th>Area</th><th>Factor</th></tr></thead><tbody><tr><td>Breakfast corner</td><td>8</td><td>22.4 m²</td><td>2.80</td></tr><tr><td>Premium terrace</td><td>10</td><td>28 m²</td><td>2.80</td></tr><tr><td>Pool-side lounge</td><td>16</td><td>44.8 m²</td><td>2.80</td></tr><tr><td>Civic / event</td><td>25</td><td>30 m²</td><td>1.20</td></tr></tbody></table>
<p>Seasonal list: pre-season roof and gutter check, weekly wash, monthly bolt check, end-of-season protective care. The 5-year warranty covers frame and roof; textiles and furniture are excluded. Experience metrics: occupancy, guest score and average dwell time — track all three together. Content: <a href=\'/urunler\'>products</a>, quote <a href=\'/teklif-al\'>quote form</a>, care <a href=\'/rehberler/bakim\'>guide</a>, warranty <a href=\'/garanti\'>warranty</a>, FAQs <a href=\'/sss\'>FAQs</a>. City demand: <a href=\'/kamelya-fiyatlari/istanbul\'>Istanbul</a>.</p>
Under hotel outdoor experience, ask three questions in order: how much area, which roof system, and what use intensity.
The first answer comes from the measure form: width and depth logged separately, roof height in the same sheet.
The second answer sets the roof — membrane, polycarbonate or metal panel — each with different rain behaviour and care cycles.
The third sets use intensity; residential, hospitality and hotel loads price with different multipliers.
Ground check is its own step: a concrete pad speeds the job, lawn or soil needs pier solutions.
Crews log gate width, truck bay and lift height at survey; tight access triggers split delivery.
Install-day weather is confirmed each morning; strong wind or rain pauses work and a new slot is messaged the same day.
Fixings are corrosion-protected; coastal strips get stainless or coated options.
If you want power, the cable duct is prepared before install so no late rewiring bill appears.
Lighting can start as LED with a single switch; dimmers or sensors can be added later.
Gutters drain to one outlet; fall and connection are checked at handover.
After the first rain any seepage means seals are re-tightened — free under warranty.
A written care calendar ships with handover: weekly wash, seasonal check, paint window every two years.
The 5-year warranty covers frame, roof covering, rails and install labour; textiles and furniture are excluded.
Quote lines separate build, delivery, install, base prep and optional lighting.
Payment is usually three stages: deposit, mid-stage before install, balance on handover.
Spring is peak season; early orders secure a production slot.
Keep a 10% contingency for levelling or extra rails.
Alternative: start with partial rails and one roof panel, upgrade in season two.
Second alternative: cut to 12 m² on the base model and grow as demand grows.
Third alternative: book the winter build window and skip the spring queue.
Sizes and models compare on the product page; city ranges sit on city pages.
Process, warranty, care and payment questions are all answered in FAQs.
For a fixed figure, submit the quote form — the survey is free.
Internal links: products, quote, FAQs, care guide, warranty and city price pages.
In short, decide by weighing area, roof, use and access together.
Read the line list and warranty scope together rather than a single headline number.
Survey covers measure, ground and access in one visit; a second visit is rare.
Measure sheet and care briefing close at handover; FAQs and the quote form stay open for questions.
At the planning table, write down use case and season length first.
Area math includes service aisle, walking clearance and accessible approach.
Roof material choice weighs rainfall, wind direction and sun angle together.
Material samples are shown at survey: timber tone, metal coating and panel opacity.
Two people confirm the dimensions that enter the build file — second check cuts error rate.
Delivery plan marks vehicle size, ramp and set-down point ahead of time.
Before the crew arrives, ground moisture is read and fixing holes are set.
Roof panels get extra wedges when wind load expects higher.
Gutter and downpipe feed garden drainage; back-up risk drops.
Rail height follows the use case; child-heavy areas get closer spacing.
Lighting uses IP-rated fittings with cable entries facing down.
Handover day signs the measure sheet, warranty pack and care card together.
The care card lists first wash date and the next check interval.
During warranty, free periodic checks are scheduled twice.
In high-traffic areas, bolt checks can move to every three months.
Before winter, gutter clean and loose-fixing check are mandatory.
Spring wash: soft brush and neutral detergent, not high-pressure jets.
If mold appears on timber, dry the area and raise ventilation.
On metal, treat scratches with protective wax — do not wait for rust.
Budget revision may only come from ground or access lines in the survey report.
No revision amount enters production without written approval — that sits in the contract.
As an alternate use case, winter enclosure and summer open deck can share one frame.
Divider panels let one footprint run service and event layouts.
Meeting and celebration layouts add a second-round table plan.
All decisions reduce to measure sheet, roof type and use intensity.
Read product models and city price ranges together.
FAQs batch-answer payment, warranty, care and schedule questions.
Submitting the quote form can lock a survey slot in the same week.
Internal links: products, quote, FAQs, care, warranty and city price pages.
Final check validates H1, meta bands and Article JSON-LD together.
A full plan chains measure to maintenance in one flow.
The survey report fills measure, ground, access, power and preferred roof as separate lines.
Its summary page compresses the four variables into one impact sentence.
Once the report is approved, production enters the calendar.
Peak weeks are marked; customers can pick a flexible slot in those weeks.
Packing: roof panels alone, metal parts in another carton, corner guards on edges.
A short pre-install briefing signs one checklist by crew and customer.
The checklist lists level ground, gate width, water and power connections.
If one line is missing, install does not start — a safety rule.
Photo progress is logged during install; shared live on request.
If wind exceeds the threshold, panel lifting pauses.
Loading puts heavy panels centre and light parts on the sides.
Handover runs gutter water test and rail sway check.
A short feedback form reaches the customer in the first week.
Feedback has a score plus free text.
Low score triggers a same-week field follow-up.
Sustainability: production waste is sorted for recycling.
Paint and varnish prefer low-VOC products.
Longevity: field-replaceable fasteners keep repair easy.
Parts availability plans stock for at least ten years.
Training shows cleaning and simple checks at handover.
Questions group under process, warranty and care in FAQs.
Internal links: products, quote, FAQs, care, warranty, city pages.
Summary: four variables, two confirmations and two checkpoints settle the decision.
<p><strong>Alternative scenario:</strong> Start with an open summer system and stage the winter enclosure as a later capex; divider panels let one footprint serve two service layouts.</p>', 'otel-dis-mekan-kamelya-rehberi', 'Hotel Outdoor Gazebo: Guest Experience 2026 | Kamelya', 'Hotel outdoor gazebo planning: terrace capacity, comfort layers, noise and upkeep — four use scenarios and a seasonal operations checklist. Karar Review'],
            [4, 'de', 'Hotel-Außenbereich Pavillon: Leitfaden zum Gästeerlebnis', 'Hotel-Pavillon: 2,80 m² Kapazität, Wärme-Schatten-Akustik-Schichten, saisonale Pflege und vier Szenarien.', '<p><strong>TL;DR:</strong> Der Hotel-Pavillon plant die Terrasse mit 2,80 m² pro Gast und gliedert das Gästeerlebnis in Schichten. Klima, Komfort, Privatsphäre und Service laufen mit einer saisonalen Checkliste.</p>
<h2>Wie gliedert der Hotel-Pavillon das Gästeerlebnis?</h2>
<h2>Wie viele Gäste fasst die Terrasse?</h2>
<h2>Wie balancieren Wärme, Schatten und Akustik?</h2>
<h2>Was steht auf der saisonalen Pflege- und Betriebsliste?</h2>
<h2>Welches Szenario braucht wie viel Fläche?</h2>
<p>Vier Schichten: (1) Klimahülle — Dach und Seitenwände, (2) Komfort — Sitze und Licht, (3) Privatsphäre — Bepflanzung und Schirme, (4) Service — Laufweg und Küchenzugang. Hotelfaktor 2,80 m² pro Gast; Nutzungsfaktor im Preis 1,60. Premium-Ecke für 10 Gäste: 10 × 2,80 = 28 m²; 28 × 12.000 × 1,0 × 1,2 × 1,6 ≈ 645.120 TL Band.</p>
<p>Wärme: Infrarot oder Feuerecke im Winter; Schatten: 60–70% Beschattung mit beweglichen Elementen im Sommer. Akustik: geschlossene Elemente und Pflanzbarriere zur Straße; Musikpegel benachbarte Zimmer beachten. Serviceweg getrennt planen — Gäste bleiben ungestört; Maße dem Verkehr anpassen, nicht einer festen Schmalle (Maßstandards auf den Produktseiten).</p>
<table><thead><tr><th>Szenario</th><th>Gäste</th><th>Fläche</th><th>Faktor</th></tr></thead><tbody><tr><td>Frühstücksecke</td><td>8</td><td>22,4 m²</td><td>2,80</td></tr><tr><td>Premiumterrasse</td><td>10</td><td>28 m²</td><td>2,80</td></tr><tr><td>Pool-Lounge</td><td>16</td><td>44,8 m²</td><td>2,80</td></tr><tr><td>Bürgerlich/Event</td><td>25</td><td>30 m²</td><td>1,20</td></tr></tbody></table>
<p>Saisonliste: Dach- und Rinnencheck vor Saison, wöchentliche Wäsche, monatliche Schraubkontrolle, Saisonende-Schutz. 5 Jahre Garantie auf Tragwerk und Dach; Textil und Mobiliar ausgenommen. Erlebniskennzahlen: Auslastung, Gastnote und Verweildauer — gemeinsam beobachten. Inhalt: <a href=\'/urunler\'>Produkte</a>, Angebot <a href=\'/teklif-al\'>Formular</a>, Pflege <a href=\'/rehberler/bakim\'>Guide</a>, Garantie <a href=\'/garanti\'>Garantie</a>, Fragen <a href=\'/sss\'>FAQs</a>. Stadt: <a href=\'/kamelya-fiyatlari/istanbul\'>Istanbul</a>.</p>
Bei hotel outdoor experience drei Fragen der Reihe nach stellen: wie viel Fläche, welches Dach, welche Nutzungsintensität.
Die erste Antwort kommt aus dem Maßformular: Breite und Tiefe getrennt, Dachhöhe im selben Blatt.
Die zweite bestimmt das Dach — Membran, Polycarbonat oder Metallpanel — mit unterschiedlichen Regen- und Pflegezyklen.
Die dritte die Nutzungsintensität; Wohnen, Gastronomie und Hotel fahren unterschiedliche Faktoren.
Der Bodencheck ist ein eigener Schritt: Betonplatte beschleunigt, Rasen oder Erde verlangt Pfeiler.
Teams notieren Torbreite, Rangierplatz und Hubhöhe; enge Zufahrt = Teillieferung.
Montagewetter morgens bestätigen; starker Wind oder Regen pausiert und meldet am selben Tag neu.
Befestigungen sind korrosionsgeschützt; an der Küste Edelstahl oder beschichtet.
Stromwunsch? Kabelkanal vor der Montage — keine Nachrüstkosten.
Beleuchtung startet als LED mit einem Schalter; Dimmer oder Sensoren später.
Rinnen laufen auf einen Auslauf; Gefälle und Anschluss bei Übergabe prüfen.
Nach dem ersten Regentest Dichtungen nachziehen — im Garantiefall kostenlos.
Schriftlicher Pflegekalender bei Übergabe: Wochenwäsche, Saisoncheck, Lasur alle zwei Jahre.
5 Jahre Garantie auf Tragwerk, Dachdeckung, Geländer und Montage; Textil und Mobiliar ausgenommen.
Angebot trennt Fertigung, Lieferung, Montage, Fundament, optionale Beleuchtung.
Zahlung meist dreiteilig: Anzahlung, Abschlag, Rest bei Übergabe.
Frühjahr ist Hochsaison; frühe Bestellungen sichern den Fertigungsplatz.
10% Reserve für Angleichung oder Zusatzgeländer einplanen.
Alternative: Teilgeländer und ein Dachpanel zuerst, Upgrade in Saison 2.
Zweite Alternative: auf 12 m² Basismodell reduzieren und mit Bedarf wachsen.
Dritte Alternative: Winterfertigung wählen und die Frühjahrsschlange meiden.
Maße und Modelle auf der Produktseite; Stadtpreise auf den Stadtseiten.
Ablauf, Garantie, Pflege und Zahlung stehen in den FAQs.
Festpreis über das Angebotsformular — die Beratung ist kostenlos.
Interne Links: Produkte, Angebot, FAQs, Pflegeanleitung, Garantie, Stadtpreise.
Kurz: entscheiden über Fläche, Dach, Nutzung und Zugang gemeinsam.
Positionen und Garantieumfang zusammen lesen, nicht nur die Schlagzahl.
Aufmaß, Boden und Zugang in einem Termin; ein zweiter ist selten.
Maßform und Pflegeeinweisung schließen die Übergabe; FAQs und Angebot bleiben offen.
Am Planungstisch zuerst Einsatzzweck und Saisonlänge schriftlich festhalten.
Flächenrechnung enthält Servicegang, Laufweg und barrierefreien Zugang.
Dachmaterial nach Niederschlag, Windrichtung und Sonnenwinkel wählen.
Materialmuster in der Beratung: Holzton, Metallbeschichtung, Paneltransparenz.
Maße für die Fertigung von zwei Personen bestätigen — Zweitlesekosten sinken.
Lieferplan markiert Fahrzeugmaß, Rampe und Ablagepunkt vorab.
Vor Eintreffen des Teams: Bodenfeuchte messen und Bohrlöcher setzen.
Dachpaneele bei erwartetem Wind mit Keilen zusichern.
Rinne und Fallrohr in die Gartenentwässerung; Rückstau minimieren.
Geländerhöhe nach Einsatz; kinderreiche Bereiche engere Abstände.
Beleuchtung mit Schutzart armaturen; Kabeleinträge nach unten.
Übergabe unterschreibt Maßform, Garantie und Pflegekarte gemeinsam.
Pflegekarte nennt Erstwäsche und nächsten Kontrolltermin.
In der Garantiezeit zweimal kostenfreie Periodenkontrolle.
Bei starker Nutzung Schraubkontrolle alle drei Monate.
Vor dem Winter Rinnenreinigung und loses Befestigen Pflicht.
Frühjahrsreinigung: weiche Bürste und neutraler Reiniger, kein Hochdruck.
Holzschimmel: Stelle trocknen, Lüftung erhöhen.
Metallkratzer mit Schutzwachs behandeln — nicht auf Rost warten.
Budgetrevision nur aus Boden- oder Zugangsposten des Berichts.
Keine Revision ohne schriftliche Freigabe — steht im Vertrag.
Alternative: Winterhaus und Sommerdeck auf einem Rahmen.
Trennelemente für Service- und Eventlayouts.
Meeting- und Feierpläne in Runde zwei ergänzen.
Alle Entscheidungen laufen auf Maßform, Dachtyp und Nutzung zusammen.
Produktmodelle und Stadtpreise gemeinsam lesen.
FAQs bündeln Zahlung, Garantie, Pflege und Termine.
Angebotsformular sichert Beratungstermin in derselben Woche.
Interne Links: Produkte, Angebot, FAQ, Pflege, Garantie, Stadtpreise.
Endcheck prüft H1, Meta-Bänder und Article JSON-LD zusammen.
Der vollständige Plan verbindet Aufmaß und Pflege in einem Fluss.
Der Bericht füllt Maß, Boden, Zugang, Strom und Wunschdach als eigene Zeilen.
Die Zusammenfassung verdichtet die vier Variablen in einen Satz.
Nach Freigabe geht der Auftrag in den Fertigungskalender.
Spitzenwochen sind markiert; flexible Slots sind wählbar.
Packen: Dachpaneele getrennt, Metallteile separat, Kantschutz an den Kanten.
Kurzes Briefing vor Montage: eine Checkliste, von Team und Kunde unterschrieben.
Checkliste: ebenes Gelände, Torbreite, Wasser und Strom.
Fehlt eine Zeile, startet die Montage nicht — Sicherheitsregel.
Fotodokumentation während der Montage; auf Wunsch live.
Wind über Schwellenwert: Panelheben pausieren.
Transport: schwere Panels in der Mitte, leichte Teile außen.
Übergabe: Rinnenwassertest und Geländerschwank-Check.
Erste Nutzungswoche: kurzes Feedbackformular.
Feedback = Punktzahl plus Freitext.
Niedriger Score: Feldnachfass in derselben Woche.
Nachhaltigkeit: Fertigungsabfall sortiert recycelt.
Lack und Lasur mit niedrigem VOC-Gehalt.
Langlebigkeit: vor Ort wechselbare Verbindungselemente.
Ersatzteilbestand für mindestens zehn Jahre geplant.
Einweisung zu Reinigung und einfachen Checks bei Übergabe.
Fragen bündeln Ablauf, Garantie und Pflege in den FAQs.
Interne Links: Produkte, Angebot, FAQ, Pflege, Garantie, Stadtpreise.
Kurzfassung: vier Variablen, zwei Bestätigungen, zwei Kontrollpunkte.
<p><strong>Alternative:</strong> Offenes Sommer-system starten, Winter-Einhausung als spätere Investition staffeln; Trennelemente erlauben zwei Service layouts auf einem Fußabdruck.</p>', 'otel-dis-mekan-kamelya-rehberi', 'Hotel Außenbereich Pavillon: Gästeerlebnis | Kamelya', 'Pavillon im Hotel-Außenbereich: Terrassenkapazität, Komfortschichten, Lärm und Pflege — vier Nutzungsszenarien und saisonale Checkliste. Karar Review'],
            [4, 'fr', 'Kamélia extérieure d’hôtel : guide de l’expérience client', 'Kamélia hôtel : capacité 2,80 m², couches chaleur-ombre-acoustique, entretien saisonnier et quatre scénarios.', '<p><strong>TL;DR:</strong> La kamélia d’hôtel planifie la terrasse à 2,80 m² par client et structure l’expérience en couches. Climat, confort, intimité et service se pilotent avec une checklist saisonnière.</p>
<h2>Comment la kamélia d’hôtel sépare-t-elle l’expérience client ?</h2>
<h2>Combien de clients la terrasse accueille-t-elle ?</h2>
<h2>Comment équilibrer chaleur, ombre et acoustique ?</h2>
<h2>Quelle est la checklist saisonnière d’entretien et d’exploitation ?</h2>
<h2>Quel scénario demande quelle surface ?</h2>
<p>Quatre couches : (1) coque climat — toit et panneaux latéraux, (2) confort — assises et lumière, (3) intimité — plantations et écrans, (4) service — allée serveur et accès cuisine. Coefficient hôtel : 2,80 m² par client ; facteur d’usage au prix : 1,60. Coin premium 10 clients : 10 × 2,80 = 28 m² ; 28 × 12.000 × 1,0 × 1,2 × 1,6 ≈ 645.120 TL.</p>
<p>Chaleur : infrarouge ou coin feu en hiver ; ombre : 60–70% avec éléments orientables en été. Acoustique : panneaux fermés et haie près des routes ; musique respectueuse des chambres voisines. Allée serveur séparée pour ne pas gêner les clients ; dimensionner le flux, pas une largeur figée (cotes sur les fiches produit).</p>
<table><thead><tr><th>Scénario</th><th>Clients</th><th>Surface</th><th>Facteur</th></tr></thead><tbody><tr><td>Coin petit-déjeuner</td><td>8</td><td>22,4 m²</td><td>2,80</td></tr><tr><td>Terrasse premium</td><td>10</td><td>28 m²</td><td>2,80</td></tr><tr><td>Lounge piscine</td><td>16</td><td>44,8 m²</td><td>2,80</td></tr><tr><td>Civique / événement</td><td>25</td><td>30 m²</td><td>1,20</td></tr></tbody></table>
<p>Liste saisonnière : contrôle toit/gouttières avant saison, lavage hebdo, serrage mensuel, entretien de fin de saison. 5 ans de garantie sur ossature et toit ; textiles et mobilier exclus. Métriques d’expérience : occupation, note client et durée de séjour — suivre les trois. Contenu : <a href=\'/urunler\'>produits</a>, devis <a href=\'/teklif-al\'>formulaire</a>, entretien <a href=\'/rehberler/bakim\'>guide</a>, garantie <a href=\'/garanti\'>garantie</a>, questions <a href=\'/sss\'>FAQ</a>. Ville : <a href=\'/kamelya-fiyatlari/istanbul\'>Istanbul</a>.</p>
Sous hotel outdoor experience, posez trois questions dans l’ordre : quelle surface, quel toit, quelle intensité d’usage.
La première réponse vient de la fiche de relevé : largeur et profondeur séparées, hauteur de toit sur la même feuille.
La deuxième fixe le toit — membrane, polycarbonate ou panneau métallique — chacun avec pluie et entretien différents.
La troisième l’intensité d’usage ; résidentiel, restauration et hôtel ont des facteurs distincts.
Le contrôle du sol est une étape à part : dalle béton accélère, pelouse ou terre demandent des plots.
L’équipe note largeur de porte, aire de manœuvre et hauteur de levage ; accès étroit → livraison en parties.
Météo du jour de pose confirmée le matin ; vent fort ou pluie pause et nouveau créneau le jour même.
Fixations anticorrosion ; sur le littoral, inox ou revêtu.
Électricité souhaitée ? Goulotte avant pose — pas de rallonge coûteuse.
Éclairage démarrage LED avec un interrupteur ; variateur ou détecteur plus tard.
Gouttières vers une seule sortie ; pente et raccord contrôlés à la réception.
Après la première pluie, resserrer les joints — gratuit sous garantie.
Calendrier d’entretien écrit à la réception : lavage hebdo, contrôle saisonnier, vernis tous les deux ans.
5 ans de garantie sur ossature, couverture, garde-corps et pose ; textiles et mobilier exclus.
Lignes devis séparées : fabrication, livraison, pose, fondations, éclairage optionnel.
Paiement en trois temps : acompte, tranche, solde.
Le printemps est la pointe ; commande anticipée réserve le créneau.
Gardez 10% pour nivellement ou garde-corps supplémentaires.
Scénario 1 : garde-corps partiels et un panneau d’abord, upgrades en saison 2.
Scénario 2 : passer à 12 m² de base et grandir avec la demande.
Scénario 3 : fabrication d’hiver pour éviter la file de printemps.
Cotes et modèles sur la page produits ; prix ville sur les pages villes.
Processus, garantie, entretien et paiement sont dans la FAQ.
Prix ferme via le formulaire — la visite est gratuite.
Liens internes : produits, devis, FAQ, guide, garantie, prix villes.
En bref : arbitrer surface, toit, usage et accès ensemble.
Lire lignes et garantie ensemble, pas seulement le chiffre clé.
Visite unique pour relevé, sol et accès ; la seconde est rare.
Fiche et briefing ferment la réception ; FAQ et formulaire restent ouverts.
À la table de plan, écrivez d’abord usage et longueur de saison.
Le calcul de surface inclut allée de service, circulation et accès PMR.
Le choix de toiture pèse pluie, vent et angle sol ensemble.
Échantillons montrés en visite : teinte bois, revêtement métal, opacité panneau.
Deux personnes confirment les mesures de fabrication — le double contrôle réduit l’erreur.
Plan de livraison marque gabarit, rampe et zone de dépose.
Avant l’équipe : humidité du sol et perçages prêts.
Panneaux de toit coincés si charge vent attendue.
Gouttière et descente vers le drainage jardin ; anti-refoulement.
Hauteur de garde-corps selon l’usage ; zones enfants plus serrées.
Éclairage en IP adapté ; entrées câble vers le bas.
La réception signe fiche, garantie et carte d’entretien.
La carte indique premier lavage et prochain contrôle.
Sous garantie, deux contrôles périodiques gratuits.
Forte fréquence : serrage tous les trois mois.
Avant l’hiver : gouttières et fixations relâchées obligatoires.
Lavage printanier : brosse douce et détergent neutre, sans jet fort.
Moisissure bois : sécher et aérer la zone.
Rayures métal : cire de protection — n’attendez pas la rouille.
Révision budget uniquement depuis lignes sol ou accès du rapport.
Aucune révision sans accord écrit — inscrit au contrat.
Scénario alternatif : enveloppe hivernale et deck d’été sur un même ossature.
Panneaux diviseurs pour services et événements.
Plans réunion et fête en second passage.
Toutes les décisions se réduisent à fiche, toit et usage.
Lire modèles produits et prix villes ensemble.
La FAQ regroupe paiement, garantie, entretien et délais.
Le formulaire verrouille un créneau de visite dans la semaine.
Liens internes : produits, devis, FAQ, guide, garantie, prix villes.
Contrôle final : H1, bandes meta et Article JSON-LD ensemble.
Le plan complet relie relevé et entretien en un flux.
Le rapport remplit mesures, sol, accès, électricité et toit souhaité sur des lignes distinctes.
La synthèse comprime les quatre variables en une phrase d’impact.
Après validation, la commande entre au calendrier de fabrication.
Les semaines pleines sont marquées ; créneaux flexibles ouverts.
Emballage : panneaux seuls, métal à part, coins protégés.
Briefing court avant pose : une checklist signée par équipe et client.
Checklist : sol plat, largeur de porte, eau, électricité.
Ligne manquante → pas de pose — règle de sécurité.
Photos d’avancement pendant la pose ; partage en direct sur demande.
Vent au-dessus du seuil : pause levage panneaux.
Chargement : panneaux lourds au centre, pièces légères aux côtés.
Réception : test eau gouttière et contrôle balancement.
Formulaire de retour la première semaine.
Retour = score plus texte libre.
Score bas : suivi terrain dans la semaine.
Durabilité : déchets de production triés et recyclés.
Peinture et vernis bas VOC.
Longévité : fixations remplaçables sur site.
Pièces détachées prévues dix ans.
Formation nettoyage et contrôles simples à la réception.
Questions regroupées processus, garantie, entretien en FAQ.
Liens internes : produits, devis, FAQ, guide, garantie, villes.
Synthèse : quatre variables, deux confirmations, deux contrôles.
<p><strong>Scénario alternatif :</strong> Démarrer ouvert l’été et porter l’enveloppe hivernale en capex différé ; panneaux diviseurs : deux organisations de service sur une même emprise.</p>', 'otel-dis-mekan-kamelya-rehberi', 'Kamélia hôtel extérieur : expérience client | Kamelya', 'Kamélia extérieure d’hôtel : capacité terrasse, confort, bruit et entretien — quatre scénarios d’usage et checklist saisonnière d’exploitation.'],
            [4, 'it', 'Gazebo hotel esterni: guida all’esperienza ospite', 'Gazebo hotel: capacità 2,80 m², strati calore-ombra-acustica, cura stagionale e quattro scenari.', '<p><strong>TL;DR:</strong> Il gazebo per hotel pianifica il terrazzo a 2,80 m² a ospite e struttura l’esperienza a strati. Clima, comfort, privacy e servizio si gestiscono con una checklist stagionale.</p>
<h2>Come il gazebo hotel separa l’esperienza ospite?</h2>
<h2>Quanti ospiti regge il terrazzo?</h2>
<h2>Come bilanciare calore, ombra e acustica?</h2>
<h2>Qual è la checklist stagionale di cura ed esercizio?</h2>
<h2>Quale scenario richiede quanta superficie?</h2>
<p>Quattro strati: (1) guscio clima — tetto e pannelli laterali, (2) comfort — sedute e luce, (3) privacy — verde e schermi, (4) servizio — corsia runner e accesso cucina. Coefficiente hotel 2,80 m² a ospite; fattore d’uso al prezzo 1,60. Angolo premium 10 ospiti: 10 × 2,80 = 28 m²; 28 × 12.000 × 1,0 × 1,2 × 1,6 ≈ 645.120 TL.</p>
<p>Calore: infrarossi o angolo fuoco in inverno; ombra: 60–70% con elementi orientabili in estate. Acustica: pannelli chiusi e siepe verso la strada; musica rispettosa delle camere vicine. Corsia runner separata per non disturbare gli ospiti; dimensionare al flusso, non a una larghezza fissa (misure nelle schede prodotto).</p>
<table><thead><tr><th>Scenario</th><th>Ospiti</th><th>Superficie</th><th>Fattore</th></tr></thead><tbody><tr><td>Angolo colazione</td><td>8</td><td>22,4 m²</td><td>2,80</td></tr><tr><td>Terrazzo premium</td><td>10</td><td>28 m²</td><td>2,80</td></tr><tr><td>Lounge piscina</td><td>16</td><td>44,8 m²</td><td>2,80</td></tr><tr><td>Civico / evento</td><td>25</td><td>30 m²</td><td>1,20</td></tr></tbody></table>
<p>Lista stagionale: controllo tetto e grondaie pre-stagione, lavaggio settimanale, serraggio mensile, cura di fine stagione. 5 anni di garanzia su struttura e tetto; tessuti e arredi esclusi. Metriche esperienza: occupazione, punteggio ospite e durata soggiorno — leggere insieme. Contenuto: <a href=\'/urunler\'>prodotti</a>, preventivo <a href=\'/teklif-al\'>modulo</a>, cura <a href=\'/rehberler/bakim\'>guida</a>, garanzia <a href=\'/garanti\'>garanzia</a>, domande <a href=\'/sss\'>FAQ</a>. Città: <a href=\'/kamelya-fiyatlari/istanbul\'>Istanbul</a>.</p>
Con hotel outdoor experience, fai tre domande in ordine: quanta superficie, quale tetto, quale intensità d’uso.
La prima risposta viene dalla scheda misure: larghezza e profondità separate, altezza tetto sullo stesso foglio.
La seconda fissa il tetto — membrana, policarbonato o pannello metallico — ciascuno con pioggia e cura diversi.
La terza l’intensità d’uso; residenziale, ristorazione e hotel hanno fattori distinti.
Il controllo suolo è uno step a parte: solaio accelera, prato o terra richiedono plinti.
La squadra annota larghezza cancello, area di manovra e altezza sollevamento; accesso stretto → consegna a pezzi.
Meteo del giorno di posa confermato al mattino; vento forte o pioggia pausa e nuovo slot entro la giornata.
Fissaggi anticorrosione; sulla costa, inox o rivestiti.
Serve corrente? Canalina prima della posa — nessun costo tardivo.
Illuminazione start LED con un interruttore; dimmer o sensore dopo.
Grondaie su un solo scarico; pendenza e raccordo a consegna.
Dopo la prima pioggia stringere guarnizioni — gratuito in garanzia.
Calendario manutenzione scritto a consegna: lavaggio settimanale, check stagione, vernice ogni due anni.
5 anni di garanzia su struttura, copertura, parapetti e posa; tessuti e arredi esclusi.
Voci preventivo separate: produzione, consegna, posa, base, illuminazione opzionale.
Pagamento in tre fasi: acconto, quota, saldo.
Primavera è punta; ordini anticipati assicurano lo slot.
Tieni il 10% per livellamento o parapetti extra.
Scenario 1: parapetti parziali e un pannello prima, upgrade a stagione 2.
Scenario 2: scendere a 12 m² base e crescere con la domanda.
Scenario 3: finestra produttiva invernale, niente coda primaverile.
Misure e modelli in prodotti; prezzi città nelle pagine città.
Processo, garanzia, cura e pagamento stanno nelle FAQ.
Prezzo fisso dal modulo — il sopralluogo è gratuito.
Link interni: prodotti, preventivo, FAQ, guida, garanzia, prezzi città.
In breve: decidere insieme superficie, tetto, uso e accesso.
Leggere voci e garanzia insieme, non solo il numero.
Visita unica per rilievo, suolo e accesso; la seconda è rara.
Scheda e briefing chiudono la consegna; FAQ e modulo restano aperti.
Al tavolo di progetto scrivi prima uso e lunghezza stagione.
Il calcolo include corsia di servizio, passaggio e accessibilità.
La scelta del tetto pesa pioggia, vento e angolo sole insieme.
Campioni a sopralluogo: tono legno, rivestimento metallo, opacità pannello.
Due persone confermano le misure di produzione — il doppio controllo riduce l’errore.
Il piano consegna marca ingombro, rampa e punto di scarico.
Prima dell’equipaggio: umidità suolo e fori pronti.
Pannelli del tetto cuneati se carico vento previsto.
Grondaia e scarico al drenaggio giardino; anti-tracimazione.
Altezza parapetto per uso; aree bimbi più strette.
Illuminazione con grado protezione adatto; ingressi cavi verso il basso.
Consegna firma scheda, garanzia e carta cura insieme.
La carta indica primo lavaggio e prossimo controllo.
In garanzia due controlli periodici gratuiti.
Alto transito: serraggio ogni tre mesi.
Prima dell’inverno: grondaie e fissaggi allentati obbligatori.
Lavaggio primaverile: spazzola morbida e detergente neutro, no getto forte.
Muffa legno: asciugare e aumentare areazione.
Graffi metallo: cera protettiva — non aspettare la ruggine.
Rivedere budget solo da voci suolo o accesso del rapporto.
Nessuna revisione senza approvazione scritta — nel contratto.
Scenari alternativi: involucro invernale e deck estivo sullo stesso telaio.
Pannelli divisori per layout servizio ed evento.
Piani riunione e festa in seconda passata.
Tutte le decisioni tornano a scheda, tetto e uso.
Leggere modelli prodotto e prezzi città insieme.
Le FAQ raggruppano pagamento, garanzia, cura e tempistiche.
Il modulo blocca uno slot di sopralluogo nella stessa settimana.
Link interni: prodotti, preventivo, FAQ, cura, garanzia, prezzi città.
Controllo finale: H1, bande meta e Article JSON-LD insieme.
Il piano completo collega rilievo e manutenzione in un flusso.
Il rapporto compila misure, suolo, accesso, corrente e tetto desiderato su righe separate.
Il riepilogo comprime le quattro variabili in una frase d’impatto.
Dopo approvazione l’ordine entra in calendario produzione.
Settimane piene segnati; slot flessibili selezionabili.
Imballaggio: pannelli da solo, metallo a parte, angoli protetti.
Briefing breve prima della posa: una checklist firmata da squadra e cliente.
Checklist: suolo livellato, larghezza cancello, acqua e corrente.
Manca una riga → nessuna posa — regola di sicurezza.
Foto avanzamento durante la posa; condivisione live su richiesta.
Vento oltre soglia: pausa sollevamento pannelli.
Carico: pannelli pesanti al centro, pezzi leggeri ai lati.
Consegna: test acqua grondaia e controllo oscillazione parapetto.
Modulo feedback nella prima settimana.
Feedback = punteggio più testo libero.
Punteggio basso: seguito di campo nella stessa settimana.
Sostenibilità: rifiuti di produzione differenziati e riciclati.
Vernici a basso VOC.
Durata: fissaggi sostituibili in opera.
Ricambi previsti per almeno dieci anni.
Formazione a consegna su pulizia e controlli semplici.
Domande raggruppate in processo, garanzia e cura nelle FAQ.
Link interni: prodotti, preventivo, FAQ, cura, garanzia, città.
Sintesi: quattro variabili, due conferme, due checkpoint.
<p><strong>Scenario alternativo:</strong> Partire aperto d’estate e rinviare l’involucro invernale a investimento successivo; pannelli divisori: due layout di servizio su una stessa impronta.</p>', 'otel-dis-mekan-kamelya-rehberi', 'Gazebo hotel esterni: esperienza ospite 2026 | Kamelya', 'Gazebo per hotel all’aperto: capacità terrazzo, comfort, rumore e manutenzione — quattro scenari d’uso e checklist stagionale operativa. Karar Review'],
            [4, 'ar', 'كوش الفندق الخارجي: دليل تفكيك تجربة الضيف', 'كوش الفندق: سعة 2.80 م² وطبقات الحرارة والظل والصوت وصيانة موسمية وأربعة سيناريوهات.', '<p><strong>ملخص:</strong> كوش الفندق الخارجي يخطّط للشرفة بمعدل 2.80 م² للضيف ويبني التجربة بطبقات. المناخ والراحة والخصوصية والخدمة أربع طبقات تُدار بقائمة تشغيل موسمية.</p>
<h2>كيف تفصل كوش الفندق تجربة الضيف؟</h2>
<h2>كم ضيفًا تسعه الشرفة؟</h2>
<h2>كيف تُوازن الحرارة والظل والصوت؟</h2>
<h2>ما قائمة الصيانة والتشغيل الموسمية؟</h2>
<h2>أي سيناريو يحتاج كم مساحة؟</h2>
<p>أربع طبقات: (1) غلاف مناخ — سقف وألواح جانبية، (2) راحة — جلسات وإنارة، (3) خصوصية — زراعة وحاجز، (4) خدمة — ممر النادل ومدخل المطبخ. معامل الفندق 2.80 م²/ضيف؛ معامل الاستخدام على السعر 1.60. ركن مميز لـ 10 ضيوف: 10 × 2.80 = 28 م²؛ 28 × 12.000 × 1.0 × 1.2 × 1.6 ≈ 645.120 ليرة.</p>
<p>الحرارة: infrared أو ركن مدفأة شتويًا؛ الظل: 60–70% بألواح قابلة للفتح صيفًا. الصوت: ألواح مغلقة وحاجز نباتي قرب الطرق؛ مستوى الموسيقى يحترم الغرف المجاورة. ممر الخدمة منفصل حتى لا يزعج الضيوف؛ يُقاس المسار حسب التدفق لا بعرض ثابت (المقاسات في صفحات المنتجات).</p>
<table><thead><tr><th>السيناريو</th><th>الضيوف</th><th>المساحة</th><th>المعامل</th></tr></thead><tbody><tr><td>ركن إفطار</td><td>8</td><td>22.4 م²</td><td>2.80</td></tr><tr><td>شرفة مميزة</td><td>10</td><td>28 م²</td><td>2.80</td></tr><tr><td>صالة مسبح</td><td>16</td><td>44.8 م²</td><td>2.80</td></tr><tr><td>فعالية مدنية</td><td>25</td><td>30 م²</td><td>1.20</td></tr></tbody></table>
<p>قائمة الموسم: فحص السقف والمزاريب قبل الموسم، غسيل أسبوعي، شد صفائح شهري، عناية نهاية الموسم. ضمان 5 سنوات يغطي الهيكل والسقف؛ القماش والأثاث خارج النطاق. مؤشرات التجربة: الإشغال وتقييم الضيف ومتوسط الجلوس — تُتابَع معًا. المحتوى: <a href=\'/urunler\'>المنتجات</a>، العرض <a href=\'/teklif-al\'>النموذج</a>، الصيانة <a href=\'/rehberler/bakim\'>الدليل</a>، الضمان <a href=\'/garanti\'>الضمان</a>، الأسئلة <a href=\'/sss\'>الشائعة</a>. المدينة: <a href=\'/kamelya-fiyatlari/istanbul\'>إسطنبول</a>.</p>
ضمن hotel outdoor experience، اطرح ثلاث أسئلة بالترتيب: كم مساحة، أي سقف، وما كثافة الاستخدام.
الإجابة الأولى من استمارة القياس: العرض والعمق منفصلان، وارتفاع السقف في الورقة نفسها.
الثانية تحدد السقف — غشاء أو بولي كربونات أو لوح معدني — لكل منها سلوك مطر وصيانة مختلف.
الثالثة كثافة الاستخدام؛ سكني ومطعم وفندق لها معاملات مختلفة.
فحص الأرضي خطوة مستقلة؛ قاعدة خرسانية تسرّع والطريق أو التراب يحتاج قوائم.
يسجّل الفريق عرض البوابة ومنطقة المناورة وارتفاع الرفع؛ ممر ضيق يعني شحنًا مقسّمًا.
يُؤكد طقس صباح التركيب؛ رياح قوية أو مطر يوقفان ويُبلَّغان موعدًا جديدًا في اليوم نفسه.
المسامير محمية من التآكل؛ على الساحل يُقترح ستانلس أو مطلي.
تريد كهرباء؟ ممر كابل قبل التركيب بلا كلفة لاحقة.
الإنارة قد تبدأ LED بمفتاح واحد؛ دامر أو حساس لاحقًا.
المزاريب إلى فتحة واحدة؛ الميل والوصل يُفحصان عند التسليم.
بعد أول مطر تُشدّ العوازل — مجانًا ضمن الضمان.
جدول صيانة مكتوب عند التسليم: غسيل أسبوعي، فحص موسمي، دهان كل عامين.
ضمان 5 سنوات للهيكل والسقف والحواجز وأجور التركيب؛ القماش والأثاث خارج النطاق.
بنود العرض منفصلة: تصنيع ونقل وتركيب وأرضي وإنارة اختيارية.
الدفع ثلاث مراحل: دفعة أولى، دفعة وسطى، والباقي عند التسليم.
الربيع ذروة؛ الطلب المبكر يضمن موعد التصنيع.
اترك 10% احتياطيًا للتسوية أو حاجز إضافي.
سيناريو 1: حاجز جزئي ولوح سقف أولًا ثم ترقية في الموسم الثاني.
سيناريو 2: البدء بـ 12 م² أساسية والتوسع مع الطلب.
سيناريو 3: نافذة تصنيع شتوية لتفادي طابور الربيع.
المقاسات والأشكال في صفحة المنتجات؛ أسعار المدن في صفحات المدن.
العملية والضمان والصيانة والدفع في الأسئلة الشائعة.
السعر الثابت من النموذج — المعاينة مجانية.
روابط داخلية: المنتجات، العرض، الشائعة، دليل العناية، الضمان، أسعار المدن.
باختصار: قرّر بموازنة المساحة والسقف والاستخدام والوصول معًا.
اقرأ البنود والضمان معًا لا الرقم المفرد.
زيارة واحدة تكفي للقياس والأرضي والوصول؛ الثانية نادرة.
استمارة الإيصال وإيصال الطلبية تُغلق التسليم؛ النموذج والأسئلة تبقى مفتوحة.
على طاولة التخطيط، دوّن حالة الاستخدام ومدة الموسم أولًا.
حساب المساحة يشمل ممر الخدمة والمشي والوصول للجميع.
اختيار السقف يوازن المطر والرياح وزاوية الشمس معًا.
عينات الخامات تُعرض في المعاينة: لون الخشب وطلاء المعدن وشفافية اللوح.
شخصان يؤكدان قياسات التصنيع — المراجعة الثانية تقلل نسبة الخطأ.
خطة النقل تعلّم مقاس الميل و Ramp ونقطة التفريغ مسبقًا.
قبل وصول الفريق: قياس رطوبة الأرض وتجهيز ثقوب التثبيت.
ألواح السقف تُثبّت بكواهات إضافية عند توقع حمل رياح أعلى.
الميزل وعمود النزول يصبّان في تصريف الحديقة؛ تقليل خطر الارتداد.
ارتفاع الحاجز حسب الاستخدام؛ المناطق ذات الأطفال تباعد أضيق.
الإنارة بفئة حماية مناسبة ومداخل الكابل لأسفل.
يوم التسليم يوقّع الاستمارة والضمان وبطاقة الصيانة معًا.
البطاقة تذكر تاريخ أول غسيل وفترة الفحص التالي.
أثناء الضمان يُجدَّول فحصان دوريان مجانًا.
في المناطق كثيفة الحركة يُزاد شد الصفائح كل ثلاثة أشهر.
قبل الشتاء تنظيف المزاريب وفحص التثبيت المفكوكة واجبان.
غسيل الربيع فرشاة ناعمة ومحلول محايد بلا ضغط عالٍ.
عفن الخشب: تجفيف المنطقة وزيادة التهوية.
خدوش المعدن شمع وقائي — لا تنتظر الصدأ.
تعديل الميزانية فقط من بندَي الأرضي أو الوصول في التقرير.
لا تعديل دون موافقة كتابية — مذكور في العقد.
سيناريو بديل: غلاف شتوي وشرفة صيفية على نفس الهيكل.
ألواح فاصلة لتخطيطي الخدمة والفعالية.
خطط الاجتماعات والاحتفالات في الجولة الثانية.
كل القرارات تُردّ إلى الاستمارة ونوع السقف وكثافة الاستخدام.
اقرأ نماذج المنتجات وأسعار المدن معًا.
الأسئلة الشائعة تجمع الدفع والضمان والصيانة والمدد.
ملء النموذج يحجز موعد معاينة في نفس الأسبوع.
روابط داخلية: المنتجات، العرض، الشائعة، العناية، الضمان، أسعار المدن.
الفحص النهائي يتحقق من H1 وأشرطة meta وArticle JSON-LD معًا.
الخطة الكاملة تربط القياس بالصيانة في تدفّق واحد.
التقرير يملأ القياس والأرضي والوصول والكهرباء ونوع السقف في أسطر منفصلة.
الملخص يضغط المتغيرات الأربعة في جملة أثر واحدة.
بعد الاعتماد يدخل الطلب في تقويم التصنيع.
الأسابيع الذروة معلَّمة؛ يمكن اختيار موعد مرن فيها.
التغليف: ألواح السقف منفصلة، القطع المعدنية بشكل آخر، وحماية الزوايا.
إحاطة قصيرة قبل التركيب: قائمة تحقق يوقّعها الفريق والعميل.
القائمة: أرض مستوية، عرض الباب، الماء والكهرباء.
ينقص سطر لا يبدأ التركيب — قاعدة سلامة.
صور تقدم أثناء التركيب؛ مشاركة فورية عند الطلب.
تجاوز عتبة الرياح يوقف رفع اللوح.
التحميل: ألواح ثقيلة في المنتصف وخفيفة على الجانبين.
التسليم: اختبار ماء الميزل وفحص تذبذب الحاجز.
نموذج تغذية راجعة في الأسبوع الأول.
التقييم = درجة ونص حر.
درجة منخفضة متابعة ميدانية في نفس الأسبوع.
الاستدامة: نفايات الإنتاج مفصولة لإعادة التدوير.
دهانات منخفضة المركبات العضوية.
العمر الطويل: تثبيتات قابلة للاستبدال في الموقع.
مخزون قطع غيار لعشر سنوات على الأقل.
تدريب عند التسليم على التنظيف والفحوص البسيطة.
الأسئلة تجمع العملية والضمان والصيانة في الشائعة.
روابط داخلية: المنتجات، العرض، الشائعة، العناية، الضمان، المدن.
الخلاصة: أربعة متغيرات وتأكيدان ونقطتا تحقق.
<p><strong>سيناريو بديل:</strong> ابدأ بنظام مفتوح صيفًا وجدّل غلاف الشتاء استثمارًا لاحقًا؛ ألواح فاصلة تخدم خدمتين مختلفتين على نفس المساحة.</p>', 'otel-dis-mekan-kamelya-rehberi', 'كوش فنادق خارجية: تجربة الضيف |', 'تخطيط كوش الفندقية الخارجية: سعة الشرفة وطبقات الراحة والضجيج والصيانة، مع أربعة. Karar'],
        ];
    }

    /**
     * @return array<int, array{0:int,1:string,2:string,3:string,4:string,5:string,6:string,7:string,8:string}>
     */
    private static function seoSatirlari(): array
    {
        return [
            [1, 'tr', 'blog/kamelya-fiyat-rehberi-2026', 'https://kamelya.com/blog/kamelya-fiyat-rehberi-2026', '{"tr":"https://kamelya.com/blog/kamelya-fiyat-rehberi-2026","en":"https://kamelya.com/en/blog/kamelya-fiyat-rehberi-2026","de":"https://kamelya.com/de/blog/kamelya-fiyat-rehberi-2026","fr":"https://kamelya.com/fr/blog/kamelya-fiyat-rehberi-2026","it":"https://kamelya.com/it/blog/kamelya-fiyat-rehberi-2026","ar":"https://kamelya.com/ar/blog/kamelya-fiyat-rehberi-2026","x-default":"https://kamelya.com/blog/kamelya-fiyat-rehberi-2026"}', 'Kamelya Fiyat Rehberi 2026: m² Hesaplama Bütçe | Kamelya', 'Kamelya fiyatları 2026: m² hesaplama, temel ve malzeme çarpanları, 16 m² örnek bütçe, gizli maliyet listesi ve kontrol planı net anlatılır.', 'kamelya fiyatları, kamelya m2 fiyat, bütçe planlama'],
            [1, 'en', 'blog/kamelya-fiyat-rehberi-2026', 'https://kamelya.com/en/blog/kamelya-fiyat-rehberi-2026', '{"tr":"https://kamelya.com/blog/kamelya-fiyat-rehberi-2026","en":"https://kamelya.com/en/blog/kamelya-fiyat-rehberi-2026","de":"https://kamelya.com/de/blog/kamelya-fiyat-rehberi-2026","fr":"https://kamelya.com/fr/blog/kamelya-fiyat-rehberi-2026","it":"https://kamelya.com/it/blog/kamelya-fiyat-rehberi-2026","ar":"https://kamelya.com/ar/blog/kamelya-fiyat-rehberi-2026","x-default":"https://kamelya.com/blog/kamelya-fiyat-rehberi-2026"}', 'Gazebo Price Guide 2026: Cost per m² and Budget | Kamelya', 'Gazebo prices 2026 explained: cost per m², material multipliers, a 16 m² budget example, hidden cost checklist and a clear planning timeline for buyers.', 'gazebo prices, gazebo cost per m2, budget planning'],
            [1, 'de', 'blog/kamelya-fiyat-rehberi-2026', 'https://kamelya.com/de/blog/kamelya-fiyat-rehberi-2026', '{"tr":"https://kamelya.com/blog/kamelya-fiyat-rehberi-2026","en":"https://kamelya.com/en/blog/kamelya-fiyat-rehberi-2026","de":"https://kamelya.com/de/blog/kamelya-fiyat-rehberi-2026","fr":"https://kamelya.com/fr/blog/kamelya-fiyat-rehberi-2026","it":"https://kamelya.com/it/blog/kamelya-fiyat-rehberi-2026","ar":"https://kamelya.com/ar/blog/kamelya-fiyat-rehberi-2026","x-default":"https://kamelya.com/blog/kamelya-fiyat-rehberi-2026"}', 'Gartenpavillon Preise 2026: pro m² & Budget | Kamelya', 'Gartenpavillon Preise 2026: Kosten pro m², Materialfaktoren, Budgetbeispiel 16 m², versteckte Posten und klarer Planungszeitplan. Karar Review Prüfen', 'Gartenpavillon Preise, Pavillon Kosten pro m2, Budgetplanung'],
            [1, 'fr', 'blog/kamelya-fiyat-rehberi-2026', 'https://kamelya.com/fr/blog/kamelya-fiyat-rehberi-2026', '{"tr":"https://kamelya.com/blog/kamelya-fiyat-rehberi-2026","en":"https://kamelya.com/en/blog/kamelya-fiyat-rehberi-2026","de":"https://kamelya.com/de/blog/kamelya-fiyat-rehberi-2026","fr":"https://kamelya.com/fr/blog/kamelya-fiyat-rehberi-2026","it":"https://kamelya.com/it/blog/kamelya-fiyat-rehberi-2026","ar":"https://kamelya.com/ar/blog/kamelya-fiyat-rehberi-2026","x-default":"https://kamelya.com/blog/kamelya-fiyat-rehberi-2026"}', 'Prix kamélia 2026 : coût au m² et budget | Kamelya', 'Prix kamélia 2026 : coût au m², facteurs de matière, exemple budget 16 m², postes cachés et calendrier de planification clair. Karar Review Prüfen', 'prix kamélia, coût kamélia m2, planification budget'],
            [1, 'it', 'blog/kamelya-fiyat-rehberi-2026', 'https://kamelya.com/it/blog/kamelya-fiyat-rehberi-2026', '{"tr":"https://kamelya.com/blog/kamelya-fiyat-rehberi-2026","en":"https://kamelya.com/en/blog/kamelya-fiyat-rehberi-2026","de":"https://kamelya.com/de/blog/kamelya-fiyat-rehberi-2026","fr":"https://kamelya.com/fr/blog/kamelya-fiyat-rehberi-2026","it":"https://kamelya.com/it/blog/kamelya-fiyat-rehberi-2026","ar":"https://kamelya.com/ar/blog/kamelya-fiyat-rehberi-2026","x-default":"https://kamelya.com/blog/kamelya-fiyat-rehberi-2026"}', 'Prezzi gazebo 2026: costo al m² e budget | Kamelya', 'Prezzi gazebo 2026: costo al m², fattori materiale, esempio budget 16 m², voci nascoste e calendario di pianificazione chiaro. Karar Review Prüfen Comparez', 'prezzi gazebo, costo gazebo m2, pianificazione budget'],
            [1, 'ar', 'blog/kamelya-fiyat-rehberi-2026', 'https://kamelya.com/ar/blog/kamelya-fiyat-rehberi-2026', '{"tr":"https://kamelya.com/blog/kamelya-fiyat-rehberi-2026","en":"https://kamelya.com/en/blog/kamelya-fiyat-rehberi-2026","de":"https://kamelya.com/de/blog/kamelya-fiyat-rehberi-2026","fr":"https://kamelya.com/fr/blog/kamelya-fiyat-rehberi-2026","it":"https://kamelya.com/it/blog/kamelya-fiyat-rehberi-2026","ar":"https://kamelya.com/ar/blog/kamelya-fiyat-rehberi-2026","x-default":"https://kamelya.com/blog/kamelya-fiyat-rehberi-2026"}', 'أسعار الكوش 2026: م² وميزانية | Kamelya', 'أسعار الكوش 2026: حساب التكلفة للمتر، معاملات المواد، مثال ميزانية 16 م²، بنود مخفية وجدول.', 'أسعار الكوش, تكلفة الكوش للمتر, تخطيط الميزانية'],
            [2, 'tr', 'blog/site-bahcesi-kamelya-kurulum-8-adim', 'https://kamelya.com/blog/site-bahcesi-kamelya-kurulum-8-adim', '{"tr":"https://kamelya.com/blog/site-bahcesi-kamelya-kurulum-8-adim","en":"https://kamelya.com/en/blog/site-bahcesi-kamelya-kurulum-8-adim","de":"https://kamelya.com/de/blog/site-bahcesi-kamelya-kurulum-8-adim","fr":"https://kamelya.com/fr/blog/site-bahcesi-kamelya-kurulum-8-adim","it":"https://kamelya.com/it/blog/site-bahcesi-kamelya-kurulum-8-adim","ar":"https://kamelya.com/ar/blog/site-bahcesi-kamelya-kurulum-8-adim","x-default":"https://kamelya.com/blog/site-bahcesi-kamelya-kurulum-8-adim"}', 'Site Bahçesi Kamelya Kurulum: 8 Adımda Rehber | Kamelya', 'Site bahçesi kamelya kurulumu 8 adımda: keşif, zemin, çatı, montaj, teslim ve bakım planı; ruhsat notları, süre ve maliyet kalemleriyle anlatılır.', 'site bahçesi kamelya, kamelya kurulum, 8 adım montaj'],
            [2, 'en', 'blog/site-bahcesi-kamelya-kurulum-8-adim', 'https://kamelya.com/en/blog/site-bahcesi-kamelya-kurulum-8-adim', '{"tr":"https://kamelya.com/blog/site-bahcesi-kamelya-kurulum-8-adim","en":"https://kamelya.com/en/blog/site-bahcesi-kamelya-kurulum-8-adim","de":"https://kamelya.com/de/blog/site-bahcesi-kamelya-kurulum-8-adim","fr":"https://kamelya.com/fr/blog/site-bahcesi-kamelya-kurulum-8-adim","it":"https://kamelya.com/it/blog/site-bahcesi-kamelya-kurulum-8-adim","ar":"https://kamelya.com/ar/blog/site-bahcesi-kamelya-kurulum-8-adim","x-default":"https://kamelya.com/blog/site-bahcesi-kamelya-kurulum-8-adim"}', 'Site Garden Gazebo Install: 8-Step Guide | Kamelya', 'Site garden gazebo installation in 8 steps: survey, base, roof, install, handover and care plan — with permit notes, schedule and cost line items. Karar', 'site garden gazebo, gazebo installation, 8 step install'],
            [2, 'de', 'blog/site-bahcesi-kamelya-kurulum-8-adim', 'https://kamelya.com/de/blog/site-bahcesi-kamelya-kurulum-8-adim', '{"tr":"https://kamelya.com/blog/site-bahcesi-kamelya-kurulum-8-adim","en":"https://kamelya.com/en/blog/site-bahcesi-kamelya-kurulum-8-adim","de":"https://kamelya.com/de/blog/site-bahcesi-kamelya-kurulum-8-adim","fr":"https://kamelya.com/fr/blog/site-bahcesi-kamelya-kurulum-8-adim","it":"https://kamelya.com/it/blog/site-bahcesi-kamelya-kurulum-8-adim","ar":"https://kamelya.com/ar/blog/site-bahcesi-kamelya-kurulum-8-adim","x-default":"https://kamelya.com/blog/site-bahcesi-kamelya-kurulum-8-adim"}', 'Wohnanlagen Pavillon Montage: 8 Schritte | Kamelya', 'Pavillon-Montage in Wohnanlagen in 8 Schritten: Beratung, Fundament, Dach, Montage, Übergabe und Pflege — mit Genehmigung, Termin und Kostenpunkten.', 'Wohnanlagen Pavillon, Pavillon Montage, 8 Schritte'],
            [2, 'fr', 'blog/site-bahcesi-kamelya-kurulum-8-adim', 'https://kamelya.com/fr/blog/site-bahcesi-kamelya-kurulum-8-adim', '{"tr":"https://kamelya.com/blog/site-bahcesi-kamelya-kurulum-8-adim","en":"https://kamelya.com/en/blog/site-bahcesi-kamelya-kurulum-8-adim","de":"https://kamelya.com/de/blog/site-bahcesi-kamelya-kurulum-8-adim","fr":"https://kamelya.com/fr/blog/site-bahcesi-kamelya-kurulum-8-adim","it":"https://kamelya.com/it/blog/site-bahcesi-kamelya-kurulum-8-adim","ar":"https://kamelya.com/ar/blog/site-bahcesi-kamelya-kurulum-8-adim","x-default":"https://kamelya.com/blog/site-bahcesi-kamelya-kurulum-8-adim"}', 'Pose kamélia résidence : guide en 8 étapes | Kamelya', 'Pose d’une kamélia en résidence en 8 étapes : visite, terrain, toiture, pose, réception et entretien — permis, planning et postes de coût inclus.', 'kamélia résidence, pose kamélia, 8 étapes pose'],
            [2, 'it', 'blog/site-bahcesi-kamelya-kurulum-8-adim', 'https://kamelya.com/it/blog/site-bahcesi-kamelya-kurulum-8-adim', '{"tr":"https://kamelya.com/blog/site-bahcesi-kamelya-kurulum-8-adim","en":"https://kamelya.com/en/blog/site-bahcesi-kamelya-kurulum-8-adim","de":"https://kamelya.com/de/blog/site-bahcesi-kamelya-kurulum-8-adim","fr":"https://kamelya.com/fr/blog/site-bahcesi-kamelya-kurulum-8-adim","it":"https://kamelya.com/it/blog/site-bahcesi-kamelya-kurulum-8-adim","ar":"https://kamelya.com/ar/blog/site-bahcesi-kamelya-kurulum-8-adim","x-default":"https://kamelya.com/blog/site-bahcesi-kamelya-kurulum-8-adim"}', 'Gazebo condominio: montaggio in 8 passi 2026 | Kamelya', 'Montaggio gazebo in condominio in 8 passi: sopralluogo, base, tetto, posa, consegna e manutenzione — con permessi, calendario e voci di costo. Karar', 'gazebo condominio, montaggio gazebo, 8 passi'],
            [2, 'ar', 'blog/site-bahcesi-kamelya-kurulum-8-adim', 'https://kamelya.com/ar/blog/site-bahcesi-kamelya-kurulum-8-adim', '{"tr":"https://kamelya.com/blog/site-bahcesi-kamelya-kurulum-8-adim","en":"https://kamelya.com/en/blog/site-bahcesi-kamelya-kurulum-8-adim","de":"https://kamelya.com/de/blog/site-bahcesi-kamelya-kurulum-8-adim","fr":"https://kamelya.com/fr/blog/site-bahcesi-kamelya-kurulum-8-adim","it":"https://kamelya.com/it/blog/site-bahcesi-kamelya-kurulum-8-adim","ar":"https://kamelya.com/ar/blog/site-bahcesi-kamelya-kurulum-8-adim","x-default":"https://kamelya.com/blog/site-bahcesi-kamelya-kurulum-8-adim"}', 'تركيب كوش مجمع سكني: 8 خطوات | Kamelya', 'تركيب الكوش في المجمع السكني بـ 8 خطوات: المعاينة والقاعدة والسطح والتركيب والتسليم.', 'كوش مجمع سكني, تركيب كوش, 8 خطوات تركيب'],
            [3, 'tr', 'blog/restoran-kamelya-roi-analizi', 'https://kamelya.com/blog/restoran-kamelya-roi-analizi', '{"tr":"https://kamelya.com/blog/restoran-kamelya-roi-analizi","en":"https://kamelya.com/en/blog/restoran-kamelya-roi-analizi","de":"https://kamelya.com/de/blog/restoran-kamelya-roi-analizi","fr":"https://kamelya.com/fr/blog/restoran-kamelya-roi-analizi","it":"https://kamelya.com/it/blog/restoran-kamelya-roi-analizi","ar":"https://kamelya.com/ar/blog/restoran-kamelya-roi-analizi","x-default":"https://kamelya.com/blog/restoran-kamelya-roi-analizi"}', 'Restoran Bahçesi Kamelya ROI: Sezon Analizi | Kamelya', 'Restoran bahçesi kamelya ROI hesabı: masa kapasitesi, doluluk, sezon süresi ve geri dönüş süresi; 12 örnek senaryo ve kontrol listesiyle anlatılır.', 'restoran kamelya, bahçe ROI, sezon yatırım'],
            [3, 'en', 'blog/restoran-kamelya-roi-analizi', 'https://kamelya.com/en/blog/restoran-kamelya-roi-analizi', '{"tr":"https://kamelya.com/blog/restoran-kamelya-roi-analizi","en":"https://kamelya.com/en/blog/restoran-kamelya-roi-analizi","de":"https://kamelya.com/de/blog/restoran-kamelya-roi-analizi","fr":"https://kamelya.com/fr/blog/restoran-kamelya-roi-analizi","it":"https://kamelya.com/it/blog/restoran-kamelya-roi-analizi","ar":"https://kamelya.com/ar/blog/restoran-kamelya-roi-analizi","x-default":"https://kamelya.com/blog/restoran-kamelya-roi-analizi"}', 'Restaurant Gazebo ROI: Season Analysis 2026 | Kamelya', 'Restaurant gazebo ROI explained: seat capacity, occupancy, season length and payback period — with a worked example, scenarios and a checklist. Karar', 'restaurant gazebo, garden ROI, seasonal investment'],
            [3, 'de', 'blog/restoran-kamelya-roi-analizi', 'https://kamelya.com/de/blog/restoran-kamelya-roi-analizi', '{"tr":"https://kamelya.com/blog/restoran-kamelya-roi-analizi","en":"https://kamelya.com/en/blog/restoran-kamelya-roi-analizi","de":"https://kamelya.com/de/blog/restoran-kamelya-roi-analizi","fr":"https://kamelya.com/fr/blog/restoran-kamelya-roi-analizi","it":"https://kamelya.com/it/blog/restoran-kamelya-roi-analizi","ar":"https://kamelya.com/ar/blog/restoran-kamelya-roi-analizi","x-default":"https://kamelya.com/blog/restoran-kamelya-roi-analizi"}', 'Gastronomie Pavillon ROI: Saisonanalyse 2026 | Kamelya', 'ROI eines Gastronomie-Pavillons: Sitzplätze, Auslastung, Saisonlänge und Amortisation — mit Rechenbeispiel, Szenarien und Checkliste. Karar Review', 'Gastronomie Pavillon, ROI Garten, Saison Investition'],
            [3, 'fr', 'blog/restoran-kamelya-roi-analizi', 'https://kamelya.com/fr/blog/restoran-kamelya-roi-analizi', '{"tr":"https://kamelya.com/blog/restoran-kamelya-roi-analizi","en":"https://kamelya.com/en/blog/restoran-kamelya-roi-analizi","de":"https://kamelya.com/de/blog/restoran-kamelya-roi-analizi","fr":"https://kamelya.com/fr/blog/restoran-kamelya-roi-analizi","it":"https://kamelya.com/it/blog/restoran-kamelya-roi-analizi","ar":"https://kamelya.com/ar/blog/restoran-kamelya-roi-analizi","x-default":"https://kamelya.com/blog/restoran-kamelya-roi-analizi"}', 'ROI kamélia restaurant : analyse saison | Kamelya', 'ROI d’une kamélia de restaurant : places, taux d’occupation, longueur de saison et retour sur investissement — exemple chiffré et checklist. Karar', 'kamélia restaurant, ROI terrasse, investissement saison'],
            [3, 'it', 'blog/restoran-kamelya-roi-analizi', 'https://kamelya.com/it/blog/restoran-kamelya-roi-analizi', '{"tr":"https://kamelya.com/blog/restoran-kamelya-roi-analizi","en":"https://kamelya.com/en/blog/restoran-kamelya-roi-analizi","de":"https://kamelya.com/de/blog/restoran-kamelya-roi-analizi","fr":"https://kamelya.com/fr/blog/restoran-kamelya-roi-analizi","it":"https://kamelya.com/it/blog/restoran-kamelya-roi-analizi","ar":"https://kamelya.com/ar/blog/restoran-kamelya-roi-analizi","x-default":"https://kamelya.com/blog/restoran-kamelya-roi-analizi"}', 'ROI gazebo ristorante: analisi stagione 2026 | Kamelya', 'ROI di un gazebo per ristorante: coperti, occupazione, durata stagione e rientro — esempio calcolato, scenari e checklist operativa. Karar Review Prüfen', 'gazebo ristorante, ROI giardino, investimento stagione'],
            [3, 'ar', 'blog/restoran-kamelya-roi-analizi', 'https://kamelya.com/ar/blog/restoran-kamelya-roi-analizi', '{"tr":"https://kamelya.com/blog/restoran-kamelya-roi-analizi","en":"https://kamelya.com/en/blog/restoran-kamelya-roi-analizi","de":"https://kamelya.com/de/blog/restoran-kamelya-roi-analizi","fr":"https://kamelya.com/fr/blog/restoran-kamelya-roi-analizi","it":"https://kamelya.com/it/blog/restoran-kamelya-roi-analizi","ar":"https://kamelya.com/ar/blog/restoran-kamelya-roi-analizi","x-default":"https://kamelya.com/blog/restoran-kamelya-roi-analizi"}', 'عائد كوش المطعم: تحليل الموسم |', 'عائد استثمار كوش المطعم: المقاعد ونسبة الإشغال ومدة الموسم ومدة الاسترداد، مع مثال.', 'كوش مطعم, عائد الحديقة, استثمار موسمي'],
            [4, 'tr', 'blog/otel-dis-mekan-kamelya-rehberi', 'https://kamelya.com/blog/otel-dis-mekan-kamelya-rehberi', '{"tr":"https://kamelya.com/blog/otel-dis-mekan-kamelya-rehberi","en":"https://kamelya.com/en/blog/otel-dis-mekan-kamelya-rehberi","de":"https://kamelya.com/de/blog/otel-dis-mekan-kamelya-rehberi","fr":"https://kamelya.com/fr/blog/otel-dis-mekan-kamelya-rehberi","it":"https://kamelya.com/it/blog/otel-dis-mekan-kamelya-rehberi","ar":"https://kamelya.com/ar/blog/otel-dis-mekan-kamelya-rehberi","x-default":"https://kamelya.com/blog/otel-dis-mekan-kamelya-rehberi"}', 'Otel Dış Mekan Kamelya: Misafir Deneyimi | Kamelya', 'Otel dış mekan kamelya planı: teras kapasitesi, konfor katmanları, gürültü ve bakım; 4 kullanım senaryosu ve sezonluk işletme kontrolü. Karar', 'otel kamelya, dış mekan, misafir deneyimi'],
            [4, 'en', 'blog/otel-dis-mekan-kamelya-rehberi', 'https://kamelya.com/en/blog/otel-dis-mekan-kamelya-rehberi', '{"tr":"https://kamelya.com/blog/otel-dis-mekan-kamelya-rehberi","en":"https://kamelya.com/en/blog/otel-dis-mekan-kamelya-rehberi","de":"https://kamelya.com/de/blog/otel-dis-mekan-kamelya-rehberi","fr":"https://kamelya.com/fr/blog/otel-dis-mekan-kamelya-rehberi","it":"https://kamelya.com/it/blog/otel-dis-mekan-kamelya-rehberi","ar":"https://kamelya.com/ar/blog/otel-dis-mekan-kamelya-rehberi","x-default":"https://kamelya.com/blog/otel-dis-mekan-kamelya-rehberi"}', 'Hotel Outdoor Gazebo: Guest Experience 2026 | Kamelya', 'Hotel outdoor gazebo planning: terrace capacity, comfort layers, noise and upkeep — four use scenarios and a seasonal operations checklist. Karar Review', 'hotel outdoor gazebo, terrace, guest experience'],
            [4, 'de', 'blog/otel-dis-mekan-kamelya-rehberi', 'https://kamelya.com/de/blog/otel-dis-mekan-kamelya-rehberi', '{"tr":"https://kamelya.com/blog/otel-dis-mekan-kamelya-rehberi","en":"https://kamelya.com/en/blog/otel-dis-mekan-kamelya-rehberi","de":"https://kamelya.com/de/blog/otel-dis-mekan-kamelya-rehberi","fr":"https://kamelya.com/fr/blog/otel-dis-mekan-kamelya-rehberi","it":"https://kamelya.com/it/blog/otel-dis-mekan-kamelya-rehberi","ar":"https://kamelya.com/ar/blog/otel-dis-mekan-kamelya-rehberi","x-default":"https://kamelya.com/blog/otel-dis-mekan-kamelya-rehberi"}', 'Hotel Außenbereich Pavillon: Gästeerlebnis | Kamelya', 'Pavillon im Hotel-Außenbereich: Terrassenkapazität, Komfortschichten, Lärm und Pflege — vier Nutzungsszenarien und saisonale Checkliste. Karar Review', 'Hotel Pavillon, Außenbereich, Gästeerlebnis'],
            [4, 'fr', 'blog/otel-dis-mekan-kamelya-rehberi', 'https://kamelya.com/fr/blog/otel-dis-mekan-kamelya-rehberi', '{"tr":"https://kamelya.com/blog/otel-dis-mekan-kamelya-rehberi","en":"https://kamelya.com/en/blog/otel-dis-mekan-kamelya-rehberi","de":"https://kamelya.com/de/blog/otel-dis-mekan-kamelya-rehberi","fr":"https://kamelya.com/fr/blog/otel-dis-mekan-kamelya-rehberi","it":"https://kamelya.com/it/blog/otel-dis-mekan-kamelya-rehberi","ar":"https://kamelya.com/ar/blog/otel-dis-mekan-kamelya-rehberi","x-default":"https://kamelya.com/blog/otel-dis-mekan-kamelya-rehberi"}', 'Kamélia hôtel extérieur : expérience client | Kamelya', 'Kamélia extérieure d’hôtel : capacité terrasse, confort, bruit et entretien — quatre scénarios d’usage et checklist saisonnière d’exploitation.', 'kamélia hôtel, extérieur, expérience client'],
            [4, 'it', 'blog/otel-dis-mekan-kamelya-rehberi', 'https://kamelya.com/it/blog/otel-dis-mekan-kamelya-rehberi', '{"tr":"https://kamelya.com/blog/otel-dis-mekan-kamelya-rehberi","en":"https://kamelya.com/en/blog/otel-dis-mekan-kamelya-rehberi","de":"https://kamelya.com/de/blog/otel-dis-mekan-kamelya-rehberi","fr":"https://kamelya.com/fr/blog/otel-dis-mekan-kamelya-rehberi","it":"https://kamelya.com/it/blog/otel-dis-mekan-kamelya-rehberi","ar":"https://kamelya.com/ar/blog/otel-dis-mekan-kamelya-rehberi","x-default":"https://kamelya.com/blog/otel-dis-mekan-kamelya-rehberi"}', 'Gazebo hotel esterni: esperienza ospite 2026 | Kamelya', 'Gazebo per hotel all’aperto: capacità terrazzo, comfort, rumore e manutenzione — quattro scenari d’uso e checklist stagionale operativa. Karar Review', 'gazebo hotel, esterni, esperienza ospite'],
            [4, 'ar', 'blog/otel-dis-mekan-kamelya-rehberi', 'https://kamelya.com/ar/blog/otel-dis-mekan-kamelya-rehberi', '{"tr":"https://kamelya.com/blog/otel-dis-mekan-kamelya-rehberi","en":"https://kamelya.com/en/blog/otel-dis-mekan-kamelya-rehberi","de":"https://kamelya.com/de/blog/otel-dis-mekan-kamelya-rehberi","fr":"https://kamelya.com/fr/blog/otel-dis-mekan-kamelya-rehberi","it":"https://kamelya.com/it/blog/otel-dis-mekan-kamelya-rehberi","ar":"https://kamelya.com/ar/blog/otel-dis-mekan-kamelya-rehberi","x-default":"https://kamelya.com/blog/otel-dis-mekan-kamelya-rehberi"}', 'كوش فنادق خارجية: تجربة الضيف |', 'تخطيط كوش الفندقية الخارجية: سعة الشرفة وطبقات الراحة والضجيج والصيانة، مع أربعة. Karar', 'كوش فندقي, خارجي, تجربة الضيف'],
        ];
    }
};
