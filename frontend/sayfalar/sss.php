<?php

declare(strict_types=1);

/** SSS — DB-driven 40 soru (public API) + 8 kapsam filtresi + FAQPage JSON-LD. */

$yanit = apiGet('/sss-sorulari', $dil);
$olarak = is_array($yanit) ? ($yanit['data'] ?? []) : [];
$sayi = is_array($yanit) ? (int) ($yanit['meta']['total'] ?? count($olarak)) : 0;

/** @var array<string, string> $kapsamAdlari 8 kapsam — AdminSssService::KAPSAMLAR */
$kapsamAdlari = [
    'fiyatlama' => [
        'tr' => 'Fiyatlama', 'en' => 'Pricing', 'de' => 'Preise', 'fr' => 'Prix', 'it' => 'Prezzi', 'ar' => 'الأسعار',
    ],
    'malzeme' => [
        'tr' => 'Malzeme', 'en' => 'Materials', 'de' => 'Material', 'fr' => 'Matériaux', 'it' => 'Materiali', 'ar' => 'الخامات',
    ],
    'bakim' => [
        'tr' => 'Bakım', 'en' => 'Care', 'de' => 'Pflege', 'fr' => 'Entretien', 'it' => 'Manutenzione', 'ar' => 'العناية',
    ],
    'montaj' => [
        'tr' => 'Montaj', 'en' => 'Installation', 'de' => 'Montage', 'fr' => 'Pose', 'it' => 'Posa', 'ar' => 'التركيب',
    ],
    'garanti' => [
        'tr' => 'Garanti', 'en' => 'Warranty', 'de' => 'Garantie', 'fr' => 'Garantie', 'it' => 'Garanzia', 'ar' => 'الضمان',
    ],
    'teknik' => [
        'tr' => 'Teknik', 'en' => 'Technical', 'de' => 'Technik', 'fr' => 'Technique', 'it' => 'Tecnico', 'ar' => 'تقني',
    ],
    'kullanim' => [
        'tr' => 'Kullanım', 'en' => 'Usage', 'de' => 'Verwendung', 'fr' => 'Usage', 'it' => 'Uso', 'ar' => 'الاستخدام',
    ],
    'karsilastirma' => [
        'tr' => 'Karşılaştırma', 'en' => 'Comparison', 'de' => 'Vergleich', 'fr' => 'Comparaison', 'it' => 'Confronto', 'ar' => 'المقارنة',
    ],
];
$kapsamlar = array_keys($kapsamAdlari);

$bosMetin = [
    'tr' => 'SSS hazırlanıyor — en kısa sürede burada olacak.',
    'en' => 'FAQ is being prepared — it will appear here soon.',
    'de' => 'FAQ wird vorbereitet — erscheint hier in Kürze.',
    'fr' => 'La FAQ est en préparation — bientôt disponible ici.',
    'it' => 'Le FAQ sono in preparazione — arriveranno qui a breve.',
    'ar' => 'يجري تحضير الأسئلة الشائعة — ستظهر هنا قريباً.',
];

/** seo_verileri (statik/sss) — 6 dil, byte bantlı (title 50–60 / desc 150–160). */
$SEO_HAM = [
    'tr' => [
        'Kamelya SSS: Fiyat, Malzeme, Bakım Soruları | Kamelya',
        'Kamelya sık sorulan 40 soru: 2026 fiyat aralığı, malzeme karşılaştırma, bakım sıklığı, montaj süresi ve garanti hakkında net cevaplar. 6 dilde.',
    ],
    'en' => [
        'Kamelya FAQ: Price, Material, Care Answers | Kamelya',
        'Frequently asked Kamelya questions: 2026 price range, material comparison, maintenance frequency, installation duration and warranty — clear answers.',
    ],
    'de' => [
        'Kamelya FAQ 2026: Preis, Material, Pflege | Kamelya',
        'Häufige Fragen zu Kamelya: Preisspanne 2026, Materialvergleich, Pflegeintervall, Montagedauer und Garantie — klare Antworten in 6 Sprachen zusammen.',
    ],
    'fr' => [
        'Kamelya FAQ 2026 : Prix, Matériau, Entretien | Kamelya',
        'Questions fréquentes sur Kamelya : prix 2026, comparaison des matières, entretien, durée de pose et garantie — réponses claires en 6 langues ici.',
    ],
    'it' => [
        'Kamelya FAQ 2026: Prezzo, Materiale, Manutenzione | Kamelya',
        'Domande frequenti su Kamelya: prezzi 2026, confronto materiali, manutenzione, posa e garanzia — risposte chiare in 6 lingue qui disponibili Qui aggiornate.',
    ],
    'ar' => [
        'كاميليا: أسئلة شائعة | كاميليا',
        'أسئلة كاميليا الشائعة عن الأسعار والمواد والعناية والتركيب والضمان بست لغات مختلفة.',
    ],
];
$seoHam = $SEO_HAM[$dil] ?? $SEO_HAM['tr'];

$ogeler = [];
foreach ($olarak as $satir) {
    $ogeler[] = [
        'soru' => (string) ($satir['soru'] ?? ''),
        'cevap' => (string) ($satir['cevap'] ?? ''),
        'kapsam' => (string) ($satir['kapsam'] ?? ''),
    ];
}

$SEO = [
    'baslik' => $seoHam[0],
    'aciklama' => $seoHam[1],
    'yol' => $MEVCUT_YOL,
    'jsonld' => $ogeler !== [] ? jsonldSss($ogeler) : null,
];
if ($SEO['jsonld'] === null) {
    unset($SEO['jsonld']);
}

$tumuEtiket = t('urun_filtre_tumu');
$sekmeHtml = '<button type="button" class="btn btn-ikincil sss-sekme aktif" data-kapsam="" aria-pressed="true">'
    . htmlspecialchars($tumuEtiket, ENT_QUOTES, 'UTF-8') . '</button>';
foreach ($kapsamlar as $kod) {
    $ad = $kapsamAdlari[$kod][$dil] ?? $kapsamAdlari[$kod]['tr'];
    $sekmeHtml .= '<button type="button" class="btn btn-ikincil sss-sekme" data-kapsam="'
        . htmlspecialchars($kod, ENT_QUOTES, 'UTF-8') . '" aria-pressed="false">'
        . htmlspecialchars($ad, ENT_QUOTES, 'UTF-8') . '</button>';
}

$akordeonHtml = '';
if ($ogeler === []) {
    $metin = $bosMetin[$dil] ?? $bosMetin['tr'];
    $akordeonHtml = '<p id="sss-bos">' . htmlspecialchars($metin, ENT_QUOTES, 'UTF-8') . '</p>';
} else {
    $akordeonHtml = sssAkordeon($ogeler);
    // data-kapsam her akordeon satırına (client-side filtre)
    $akordeonHtml = preg_replace_callback(
        '/data-akordeon="(\d+)"/',
        static function (array $m) use ($ogeler): string {
            $i = (int) $m[1];
            $k = htmlspecialchars($ogeler[$i]['kapsam'] ?? '', ENT_QUOTES, 'UTF-8');

            return 'data-akordeon="' . $m[1] . '" data-kapsam="' . $k . '"';
        },
        $akordeonHtml
    );
}
?>
<?= kirinti([['etiket' => t('anasayfa'), 'yol' => siteUrl('/')], ['etiket' => t('sss_baslik'), 'yol' => null]]) ?>
<h1><?= htmlspecialchars(t('sss_baslik'), ENT_QUOTES, 'UTF-8') ?></h1>
<p><small>(<?= (int) $sayi ?>)</small></p>
<nav class="sss-sekmeler" aria-label="<?= htmlspecialchars(t('sss_baslik'), ENT_QUOTES, 'UTF-8') ?>">
  <?= $sekmeHtml ?>
</nav>
<div id="sss-liste">
  <?= $akordeonHtml ?>
</div>
<p id="sss-filtre-bos" hidden><?= htmlspecialchars($bosMetin[$dil] ?? $bosMetin['tr'], ENT_QUOTES, 'UTF-8') ?></p>
<script>
(function () {
  var dugmeler = document.querySelectorAll('.sss-sekme');
  var satirlar = document.querySelectorAll('#sss-liste .akordeon');
  var bos = document.getElementById('sss-filtre-bos');
  var hepsi = document.getElementById('sss-bos');
  if (!dugmeler.length) return;
  dugmeler.forEach(function (d) {
    d.addEventListener('click', function () {
      var k = d.getAttribute('data-kapsam') || '';
      dugmeler.forEach(function (x) {
        x.classList.toggle('aktif', x === d);
        x.setAttribute('aria-pressed', x === d ? 'true' : 'false');
      });
      var gorunen = 0;
      satirlar.forEach(function (s) {
        var sk = s.getAttribute('data-kapsam') || '';
        var uygun = k === '' || sk === k;
        s.hidden = !uygun;
        if (uygun) gorunen++;
      });
      if (bos) bos.hidden = !(hepsi === null && gorunen === 0);
    });
  });
})();
</script>
