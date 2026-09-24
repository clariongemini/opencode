<?php

declare(strict_types=1);

/** İstanbul kamelya bakım takvimi/sayaç — kapsam satır 75. */

$AYLAR = [
    'tr' => ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'],
    'en' => ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
    'de' => ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'],
    'fr' => ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'],
    'it' => ['Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno', 'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre'],
    'ar' => ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'],
];

$ISLER = [
    'tr' => [
        1  => 'Kış bakımı: ağır kar/yük altına girmemesi için çatıyı kontrol edin.',
        2  => 'Kış sonu: dondurma-erime döngüsünden kaynaklı çatlakları inceleyin.',
        3  => 'İlkbahar başlangıcı: genel yıkama, küf/kürt kontrolü, vida/bagli bağlantıları sıkın.',
        4  => 'İlkbahar: ahşap yüzeylerini hafifçe örgün, vernik/yağ için hazırlayın.',
        5  => 'Vernik/yağ uygulaması (hava 15°C üzeri, kuru): 2-3 tabak dış mekan verniği.',
        6  => 'Yaz hazırlığı: gölgelik/pano temizliği, su kaçağı testi, böcek koruması.',
        7  => 'Yaz bakımı: periyodik temizlik, nem ölçümü, şişirme kontrolü.',
        8  => 'Yaz sonu: ağır yağmur/olu öncesi su yalıtım kontrolü, marangoz bağlantıları.',
        9  => 'Sonbahar başlangıcı: yaprak/dalgıç temizliği, çatı/selen temizliği.',
        10 => 'Sonbahar: ikinci vernik/yağ bakımı (gerekirse), winterize hazırlığı.',
        11 => 'Kış öncesi: su borularını boşaltın, cam/panel kontrolleri, rüzgar bağlamaları.',
        12 => 'Kış: kar temizliği (yumuşak metinle), ağır yük altına girmemesi için destek.',
    ],
    'en' => [
        1  => 'Winter care: check roof under heavy snow load.',
        2  => 'Late winter: inspect cracks from freeze-thaw cycles.',
        3  => 'Early spring: general wash, mold/fungus check, tighten bolts/connections.',
        4  => 'Spring: lightly sand wood surfaces, prepare for varnish/oil.',
        5  => 'Apply varnish/oil (above 15°C, dry): 2-3 coats of outdoor varnish.',
        6  => 'Summer prep: clean shade/panels, leak test, insect protection.',
        7  => 'Summer maintenance: periodic cleaning, moisture check, swelling control.',
        8  => 'Late summer: waterproofing check before heavy rain, carpentry joints.',
        9  => 'Early autumn: leaf/debris cleaning, roof/gutter cleaning.',
        10 => 'Autumn: second varnish/oil coat if needed, winterizing prep.',
        11 => 'Pre-winter: drain water pipes, glass/panel checks, wind bracing.',
        12 => 'Winter: snow removal (soft tool), support to prevent overload.',
    ],
    'de' => [
        1  => 'Winterpflege: Dach unter Schneelast prüfen.',
        2  => 'Spätwinter: Risse durch Frost-Tau-Zyklen prüfen.',
        3  => 'Frühjahr: Allgemeinreinigung, Schimmel/Pilz-Check, Schrauben/Verbindungen anziehen.',
        4  => 'Frühling: Holzoberflächen leicht schleifen, für Lasur/Öl vorbereiten.',
        5  => 'Lasur/Öl auftragen (über 15°C, trocken): 2-3 Schichten Außenlasur.',
        6  => 'Sommerprep: Schatten/Paneele reinigen, Dichtheitstest, Insektenschutz.',
        7  => 'Sommerpflege: Regelmäßige Reinigung, Feuchtemessung, Quellkontrolle.',
        8  => 'Spätsommer: Abdichtung vor Starkregen prüfen, Holzverbindungen.',
        9  => 'Herbstbeginn: Laub/Schmutz entfernen, Dach/Rinne reinigen.',
        10 => 'Herbst: Zweite Lasur/Öl-Schicht falls nötig, Wintervorbereitung.',
        11 => 'Wintervorbereitung: Wasserleitungen entleeren, Glas/Platten prüfen, Windverspannung.',
        12 => 'Winter: Schneeräumung (weiches Werkzeug), Stützen gegen Überlast.',
    ],
    'fr' => [
        1  => 'Entretien hiver : vérifier le toit sous charge de neige.',
        2  => 'Fin hiver : inspecter fissures cycles gel-dégel.',
        3  => 'Début printemps : lavage général, contrôle moisissures/champignons, serrer boulons/liaisons.',
        4  => 'Printemps : poncer légèrement le bois, préparer pour vernis/huile.',
        5  => 'Appliquer vernis/huile (au-dessus de 15°C, sec) : 2-3 couches vernis extérieur.',
        6  => 'Préparation été : nettoyage store/panneaux, test étanchéité, protection insectes.',
        7  => 'Entretien été : nettoyage périodique, contrôle humidité, gonflement.',
        8  => 'Fin été : vérifier étanchéité avant fortes pluies, assemblages menuiserie.',
        9  => 'Début automne : nettoyage feuilles/débris, toit/gouttières.',
        10 => 'Automne : 2e couche vernis/huile si besoin, préparation hivernage.',
        11 => 'Pré-hiver : purger canalisations, vérifier vitres/panneaux, haubanage vent.',
        12 => 'Hiver : déneigement (outil doux), supports contre surcharge.',
    ],
    'it' => [
        1  => 'Manutenzione inverno: controllare tetto sotto carico neve.',
        2  => 'Fine inverno: ispezionare crepe da cicli gelo-disgelo.',
        3  => 'Inizio primavera: lavaggio generale, controllo muffa/funghi, stringere bulloni/connessioni.',
        4  => 'Primavera: levigare leggermente legno, preparare per vernice/olio.',
        5  => 'Applicare vernice/olio (sopra 15°C, asciutto): 2-3 mani vernice esterno.',
        6  => 'Prep estate: pulizia tenda/pannelli, test tenuta, protezione insetti.',
        7  => 'Manutenzione estate: pulizia periodica, controllo umidità, rigonfiamento.',
        8  => 'Fine estate: verificare impermeabilizzazione prima piogge, giunzioni falegnameria.',
        9  => 'Inizio autunno: pulizia foglie/detriti, tetto/grondaie.',
        10 => 'Autunno: 2° mano vernice/olio se serve, preparazione invernaggio.',
        11 => 'Pre-inverno: svuotare tubi, controllare vetri/pannelli, controventatura vento.',
        12 => 'Inverno: rimozione neve (attrezzo morbido), supporti contro sovraccarico.',
    ],
    'ar' => [
        1  => 'صيانة الشتاء: تحقق من السقف تحت حمل الثلج.',
        2  => 'نهاية الشتاء: تفقد الشقوق من دورات التجمد والذوبان.',
        3  => 'بداية الربيع: غسيل عام، تفقد العفن/الفطريات، شد البراغي/التوصيلات.',
        4  => 'الربيع: صنفرة خفيفة للخشب، تحضير للورنيش/الزيت.',
        5  => 'تطبيق الورنيش/الزيت (أعلى 15°C، جاف): 2-3 طبقة ورنيش خارجي.',
        6  => 'استعداد الصيف: تنظيف الظل/الألواح، اختبار التسرب، حماية من الحشرات.',
        7  => 'صيانة الصيف: تنظيف دوري، قياس الرطوبة، مراقبة الانتفاخ.',
        8  => 'نهاية الصيف: التحقق من العزل المائي قبل الأمطار، وصلات النجارة.',
        9  => 'بداية الخريف: تنظيف الأوراق/الحطام، تنظيف السقف/المزاريب.',
        10 => 'الخريف: طبقة ثانية ورنيش/زيت إذا لزم، استعداد للشتاء.',
        11 => 'قبل الشتاء: تصريف الأنابيب، فحص الزجاج/الألواح، شد الريح.',
        12 => 'الشتاء: إزالة الثلج (أداة ناعمة)، دعامات ضد الحمل الزائد.',
    ],
];

$aylar = $AYLAR[$dil] ?? $AYLAR['tr'];
$isler = $ISLER[$dil] ?? $ISLER['tr'];

$bugun = new DateTime();
$ay = (int) $bugun->format('n');
$yil = (int) $bugun->format('Y');
$gun = (int) $bugun->format('j');

/** seo meta — 6 dil, byte bantlı (title 50–60 / desc 150–160). F16.2.9. */
$SEO_HAM = [
    'tr' => [
        'İstanbul Kamelya Bakım Takvimi: 12 Aylık Plan | Kamelya',
        'İstanbul iklimine özel 12 aylık kamelya bakım takvimi: kar kontrolü, ilkbahar yıkama, boya penceresi, sonbahar oluk temizliği ve bakım sayacı.',
    ],
    'en' => [
        'Istanbul Gazebo Care Calendar: 12-Month Plan | Kamelya',
        'A 12-month Istanbul gazebo care calendar shaped by local weather: snow checks, spring wash, paint window, autumn gutter clean and a next-service countdown.',
    ],
    'de' => [
        'Istanbul Kamelie Pflegekalender: 12 Monate | Kamelya',
        '12-Monats-Pflegekalender für Istanbul nach lokalem Klima: Schneekontrolle, Frühjahrswäsche, Lasurfenster, Herbst-Dachrinnenreinigung und Terminanzeige.',
    ],
    'fr' => [
        'Calendrier entretien kamélia Istanbul : 12 mois | Kamelya',
        'Calendrier d’entretien sur 12 mois pour Istanbul selon le climat : contrôle neige, lavage printanier, vernis, gouttières d’automne et compte à rebours.',
    ],
    'it' => [
        'Calendario manutenzione kamelya Istanbul: 12 mesi | Kamelya',
        'Calendario di manutenzione di 12 mesi per Istanbul sul clima locale: controllo neve, lavaggio primaverile, finestra vernice, grondaie e conto alla rovescia.',
    ],
    'ar' => [
        'تقويم صيانة الكاميليا | Kamelya',
        'تقويم إسطنبول للكاميليا: فحص الثلج والغسيل والورنيش وتنظيف المزاريب شهرياً لعام 2026.',
    ],
];
$seoHam = $SEO_HAM[$dil] ?? $SEO_HAM['tr'];

$SEO = [
    'baslik' => $seoHam[0],
    'aciklama' => $seoHam[1],
    'yol' => $MEVCUT_YOL,
];
?>
<?= kirinti([['etiket' => t('anasayfa'), 'yol' => siteUrl('/')], ['etiket' => t('blog_baslik'), 'yol' => siteUrl('/blog')], ['etiket' => t('rehberler_baslik'), 'yol' => siteUrl('/rehberler/bakim')], ['etiket' => t('istanbul_bakim_baslik'), 'yol' => null]]) ?>
<h1><?= htmlspecialchars(t('istanbul_bakim_baslik'), ENT_QUOTES, 'UTF-8') ?></h1>
<p class="giris"><?= htmlspecialchars(t('istanbul_bakim_giris'), ENT_QUOTES, 'UTF-8') ?></p>

<div class="kart" style="margin-bottom:1.5rem;">
  <h2><?= htmlspecialchars(t('sonraki_bakim'), ENT_QUOTES, 'UTF-8') ?></h2>
  <div id="sayac" data-hedef="<?= $yil . '-' . str_pad((string) $ay, 2, '0', STR_PAD_LEFT) . '-' . str_pad((string) min($gun + 7, 28), 2, '0', STR_PAD_LEFT) ?>T09:00:00"></div>
  <p class="yardim"><?= htmlspecialchars(t('sayac_aciklama'), ENT_QUOTES, 'UTF-8') ?></p>
</div>

<h2><?= htmlspecialchars(t('aylik_takvim'), ENT_QUOTES, 'UTF-8') ?></h2>
<table class="card">
  <thead>
    <tr><th scope="col"><?= htmlspecialchars(t('ay'), ENT_QUOTES, 'UTF-8') ?></th><th scope="col"><?= htmlspecialchars(t('islem'), ENT_QUOTES, 'UTF-8') ?></th></tr>
  </thead>
  <tbody>
    <?php for ($i = 1; $i <= 12; $i++): ?>
    <tr class="<?= $i === $ay ? 'aktif' : '' ?>">
      <th scope="row"><?= htmlspecialchars($aylar[$i - 1], ENT_QUOTES, 'UTF-8') ?></th>
      <td><?= htmlspecialchars($isler[$i] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
    </tr>
    <?php endfor; ?>
  </tbody>
</table>

<div class="notlar">
  <h3><?= htmlspecialchars(t('notlar_baslik'), ENT_QUOTES, 'UTF-8') ?></h3>
  <ul>
    <li><?= htmlspecialchars(t('not_1'), ENT_QUOTES, 'UTF-8') ?></li>
    <li><?= htmlspecialchars(t('not_2'), ENT_QUOTES, 'UTF-8') ?></li>
    <li><?= htmlspecialchars(t('not_3'), ENT_QUOTES, 'UTF-8') ?></li>
    <li><?= htmlspecialchars(t('not_4'), ENT_QUOTES, 'UTF-8') ?></li>
  </ul>
</div>

<script>
(function () {
  var el = document.getElementById('sayac');
  if (!el) return;
  var hedef = new Date(el.getAttribute('data-hedef')).getTime();
  function guncelle() {
    var simdi = Date.now();
    var fark = hedef - simdi;
    if (fark <= 0) {
      el.innerHTML = '<span class="bitmis">Süre doldu</span>';
      return;
    }
    var gun = Math.floor(fark / 86400000);
    var saat = Math.floor((fark % 86400000) / 3600000);
    var dk = Math.floor((fark % 3600000) / 60000);
    el.innerHTML = gun + ' gün ' + saat + ' sa ' + dk + ' dk';
  }
  guncelle();
  setInterval(guncelle, 60000);
})();
</script>