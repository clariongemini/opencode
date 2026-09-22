# KAMELYA WEB SİTESİ — V1 / V2 Kapsam ve Özellik Mimarisi

## Profesyonel Proje Kapsam Dokümanı
Bu doküman, kamelya ve dış mekân yaşam alanları odaklı web projesinin ilk sürüm ve ileri aşama kapsamını; sayfa mimarisi, ürün yapısı, hesaplama araçları, içerik, SEO, dönüşüm, teknik altyapı ve gelecek vizyonu başlıkları altında sistematik biçimde tanımlar.

* **V1** — Üretime alınacak temel platform ve ticari altyapı
* **V2** — Platform olgunlaştığında eklenecek gelişmiş dijital deneyimler

## 1. Proje Sürüm Mimarisi

| Sürüm | Kapsam | Temel Amaç |
| :--- | :--- | :--- |
| **V1** | Kurumsal site + ürün kataloğu + teklif/fiyat araçları + içerik/SEO + dönüşüm + yönetilebilir altyapı | Satış ve müşteri kazanımını destekleyen üretime hazır dijital platform |
| **V2** | İleri görsel deneyimler + mobil uygulama + Photo-to-Quote + akıllı/AI özellikler | Platformu ileri dijital ürün ve müşteri deneyimi seviyesine taşımak |

## 2. VERSİYON 1 — Temel Platform ve Ticari Altyapı

### 2.1 Temel Sayfalar ve Site Mimarisi
* Ana Sayfa
* Hakkımızda
* Ürünler / Kamelya Modelleri
* Galeri
* İletişim
* Teklif Al / Ücretsiz Keşif Formu
* Sık Sorulan Sorular (SSS)
* Blog
* Kamelya Bakımı Rehberi
* Malzeme Karşılaştırma Rehberi
* Referanslar / Müşteri Hikâyeleri
* Gizlilik Politikası ve KVKK
* XML Site Haritası

### 2.2 Ürün, Kategori ve Katalog Yapısı
* Model bazlı kategoriler: Altıgen, Kare, Dikdörtgen, Modern, Klasik
* Malzeme bazlı kategoriler: Ahşap, Alüminyum, Kompozit
* Kullanım amacına göre kategoriler: Site Bahçesi, Restoran, Otel, Belediye
* Ürün detay sayfaları: ölçü, malzeme, çatı tipi ve korkuluk detayları
* Ürün karşılaştırma özelliği
* Fiyat aralığı etiketleri
* Stok durumu gösterimi kapsam dışıdır; ürünler sipariş üzerine üretim modeliyle ele alınır

### 2.3 Fiyatlandırma ve Teklif Hesaplama
* m2 bazlı interaktif fiyat hesaplama aracı
* Yönetim panelinden m2 birim fiyatlarının yönetilebilmesi
* Kullanıcının doğrudan m2 girmesiyle anlık fiyat hesaplama
* Kullanıcının genişlik ve derinlik değerlerini girerek otomatik m2 hesaplaması
* Hesaplanan alana göre anlık fiyat/teklif değeri oluşturulması

### 2.4 Görsel, Video ve Proje Sunumu
* 360° ürün görüntüleme
* Sanal showroom / sanal tur
* Gerçek uygulamalardan proje galerisi
* Video müşteri referansları
* Öncesi / sonrası proje görselleri
* Sinematik tanıtım videosu site altyapısının temel kapsamına dahil değildir; gerektiğinde frontend içerik çalışması olarak ayrıca ele alınabilir
* Drone çekimleri V2 kapsamına alınmıştır

### 2.5 Teknolojik ve Etkileşimli Özellikler
* Sıcaklık / gölge etkisi simülasyonu
* Bilimsel verilere dayalı gölgeleme ve sıcaklık etkisi anlatımı
* Yüksek sıcaklık koşullarının dış mekân kullanımına etkisinin görselleştirilmesi
* Online keşif randevu sistemi
* Canlı sohbet / WhatsApp entegrasyonu
* PWA (Progressive Web App) altyapısı
* 3D konfigüratör V1 kapsamından çıkarılmıştır
* AR önizleme V1 kapsamından çıkarılmıştır
* Photo-to-Quote özelliği V2 kapsamına alınmıştır

### 2.6 İçerik, SEO ve Çok Dilli Yapı
* Kamelya rehberleri, ürün karşılaştırmaları ve bilgilendirici içeriklerden oluşan blog yapısı
* Yapılandırılmış veri destekli SSS bölümü
* Kamelya bakım rehberi: mevsimsel bakım, temizlik, boya, küf ve zararlılar
* Ahşap / alüminyum / kompozit karşılaştırma rehberi
* Kamelya, çardak ve pergola farklarını açıklayan rehber içerik
* İstanbul'a özel mevsimsel bakım takvimi ve sayaç sistemi
* Instagram üzerinden video içeriklerinin gömülmesi; sunucuya ayrıca video yüklenmemesi
* Anahtar kelime odaklı SEO: kamelya fiyatları, çardak imalatı, şehir bazlı kamelya aramaları
* Yerel SEO: Google Business Profile ve şehir bazlı açılış sayfaları
* Çok dilli yapı: Türkçe, İngilizce, Almanca, Fransızca, İtalyanca ve Arapça
* **Dil bazlı para birimi ve fiyat sunumu:** Avrupa dillerinde Euro, Arapça içerikte bölgesel fiyatlandırma yaklaşımı. (Not: Fiyatlar canlı kur yerine, yönetim panelinden dil bazlı sabit olarak belirlenecektir.)

### 2.7 Güven, Referans ve Kurumsal Kanıtlar
* Müşteri yorumları ve puanlama yapısı
* Gerçek proje referansları: fotoğraf ve proje hikâyesi
* Sertifikasyon vitrini: CE, TÜV, ISO ve ilgili yapısal garanti bilgileri
* Garanti koşulları ve kapsamının açık biçimde sunulması
* Ekip tanıtımı: usta, marangoz ve montaj ekibi
* Atölye / üretim tesisi fotoğrafları
* Basında Biz / medya yansımaları (V1 kapsamı dışında)
* Ödüller ve başarılar (V1 kapsamı dışında)

### 2.8 Dönüşüm ve Müşteri İletişimi
* Tüm ana sayfa ve içeriklerde ücretsiz keşif / teklif CTA'ları
* WhatsApp hızlı iletişim butonu
* Telefonla doğrudan arama butonu
* Form doldurma ve geri arama talebi
* Bülten aboneliği
* Kampanya ve indirim duyuruları
* Sınırlı süreli tekliflerin yönetimi

### 2.9 Kullanıcı Deneyimi, Performans ve Teknik Altyapı
* Responsive ve mobil öncelikli tasarım
* Core Web Vitals ve sayfa performansı optimizasyonu
* Basit ve ölçeklenebilir navigasyon / menü mimarisi
* Site içi arama
* Çerez bildirimi ve KVKK uyumluluğu
* Temel erişilebilirlik standartları
* Çoklu para birimi desteği
* Google Maps entegrasyonu
* Gelişmiş sosyal medya entegrasyonu
* Sosyal medya içeriklerinin uygun alanlarda kullanıcı arayüzünde gösterilmesi
* Yeni proje ve müşteri portföylerinin sosyal medya üzerinden paylaşılabilmesi
* Uygun içerik şablonlarıyla sosyal paylaşım akışının desteklenmesi

## 3. VERSİYON 2 — Gelişmiş Dijital Deneyim ve Gelecek Vizyonu
*(V2 özellikleri, V1'in üretim ve satış altyapısı üzerinde çalışacak ileri seviye özellikler olarak konumlandırılmıştır.)*

### 3.1 Gelişmiş Görsel İçerik
* Profesyonel drone çekimleri ve havadan proje sunumları
* Proje lokasyonlarını ve uygulama alanlarını daha güçlü görsel içerikle sergileme

### 3.2 Gelişmiş Teklif ve Ürün Deneyimi
* Photo-to-Quote: Kullanıcının fotoğraf yükleyerek proje/ölçü/teklif sürecini başlatabilmesi
* Fotoğraf üzerinden ön değerlendirme ve teklif sürecini destekleyen dijital akış

### 3.3 Mobil Uygulama
* PWA deneyiminin ardından iOS ve Android mobil uygulama geliştirme
* Web platformundaki temel müşteri işlemlerinin mobil uygulamaya taşınması
* Bildirim ve tekrar etkileşim senaryoları için mobil altyapı

### 3.4 Akıllı Dış Mekân ve Sürdürülebilirlik Özellikleri
* Akıllı sulama / bitki entegrasyonu
* Güneş paneli entegrasyonunun görselleştirilmesi
* CO2 tasarruf hesaplayıcısı
* Dış mekân çözümlerinin çevresel ve enerji etkilerinin hesaplanması

### 3.5 Yapay Zekâ Destekli Kullanıcı Deneyimi
* Yapay zekâ destekli bahçe ve dış mekân danışmanı
* Kullanıcının ihtiyaçlarına göre ürün ve kullanım senaryosu yönlendirmesi
* İleri aşamada görsel ve proje verilerinden yararlanan öneri mekanizmaları

### 3.6 Gelişmiş Bakım ve Kullanıcı Etkileşimi
* Genişletilmiş kamelya bakım takvimi
* Kullanıcıya özel bakım hatırlatıcıları
* Mevsimsel bakım bildirimleri
* Proje bazlı bakım geçmişi ve takip senaryoları

### 3.7 Topluluk ve Proje Keşif Özellikleri
* Komşu proje haritası
* Kullanıcı galerisi
* Kullanıcı içeriklerinin değerlendirilmesi / oylanması
* AR destekli montaj rehberi

### 3.8 İş Modeli Dışında Tutulan Özellikler
* Bayilik portalı (B2B) — iş modeli kapsamına alınmamıştır
* Taksit / finansman hesaplayıcı — mevcut ödeme modeliyle uyumlu olmadığı için kapsam dışıdır
* Müşteri montaj kılavuzu — montaj operasyonunun doğrudan yürütülmesi nedeniyle kapsam dışıdır

## 4. V1 → V2 Geçiş Mantığı

| Aşama | Odak | Örnek Yetenekler |
| :--- | :--- | :--- |
| **V1** | Kurumsal + Ticari Platform | Ürün kataloğu, fiyat hesaplama, teklif, keşif, referanslar, SEO, çok dil, PWA, iletişim |
| **V2** | İleri Dijital Ürün | Photo-to-Quote, mobil uygulama, AI danışman, AR, sürdürülebilirlik ve akıllı dış mekân özellikleri |