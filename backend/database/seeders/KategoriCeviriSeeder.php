<?php

declare(strict_types=1);

namespace Kamelya\Seeders;

use PDO;

/**
 * F16.2.1 — Kategori İçerikleri (12 kategori × 6 dil = 72 çeviri + SEO).
 *
 * Her kategori için:
 *   - kategori_cevirileri: isim, aciklama (200-250 kelime, 3 paragraf), slug
 *   - seo_verileri: meta_baslik (50-60 krk), meta_aciklama (150-160 krk)
 *
 * Idempotent: mevcut kayıtları UPDATE, yokları INSERT (ON DUPLICATE KEY).
 * Transaction içinde çalışır.
 */
final class KategoriCeviriSeeder
{
    /** @return array<int, array{kod: string, ceviriler: array<string, array{isim: string, aciklama: string, slug: string, seo_baslik: string, seo_aciklama: string}>}> */
    public static function veri(): array
    {
        return [
            // =========================================================
            // MODEL KATEGORİLERİ
            // =========================================================
            [
                'kod' => 'altigen',
                'ceviriler' => [
                    'tr' => [
                        'isim' => 'Altıgen Kamelya',
                        'aciklama' => "Altıgen kamelya modelleri, altı kenarlı simetrik yapısıyla bahçenize mimari bir zarafet katar. Geniş iç hacmi ve dengeli taşıyıcı sistemi sayesinde hem görsel hem de yapısal avantaj sunar. Ahşap, alüminyum ve kompozit malzeme seçenekleriyle üretilen altıgen modeller, oturma grupları ve yemek masaları için ideal iç mekân genişliği sağlar. Köşe açılarının eşit dağılımı, mobilya yerleşimine serbestlik verir ve dört köşeli formlardaki ölü alanları ortadan kaldırır.\n\nSite bahçelerinden restoran teraslarına, butik otel peyzajlarından kamu parklarına kadar geniş bir kullanım alanına hitap eder. Altıgen formun her cepheden estetik görünmesi, onu farklı yönlerden algılanan mekânlarda öne çıkarır. Panel arası açıklıklar, doğal havalandırma sağlarken yağmur ve rüzgâra karşı koruma sunar. Altıgen kamelyalar, orta bahçe ve ada konumlandırması için sıklıkla tercih edilir; çevresindeki peyzajla 360 derecelik görsel ilişki kurar.\n\nDiğer modellere kıyasla daha geniş iç hacim ve dengeli gölgeleme alanı sunan altıgen kamelyalar, m² başına düşen kullanılabilir alanı da artırır. Taşıyıcı kolonlar eşit aralıklarla dağıldığı için uzun açıklıklarda titreşim ve eğilme riski düşer; bu da açık havada güvenli bir oturma deneyimi demektir. Aileniz veya misafirleriniz için farklı bir oturma deneyimi arıyorsanız, altıgen kamelya modellerimizi inceleyebilir ve ücretsiz keşif talebinde bulunabilirsiniz.",
                        'slug' => 'altigen-kamelya',
                        'seo_baslik' => 'Altıgen Kamelya Modelleri ve Fiyatları 2026 | Kamelya',
                        'seo_aciklama' => 'Altıgen kamelya modelleri: simetrik altı kenarlı tasarım, geniş iç hacim, ahşap ve alüminyum seçenekleri. m² şeffaf fiyat, ücretsiz keşif, 5 yıl garanti.',
                    ],
                    'en' => [
                        'isim' => 'Hexagonal Gazebo',
                        'aciklama' => "Hexagonal gazebo models bring architectural elegance to your garden through their six-sided symmetrical structure. The spacious interior and balanced support system offer both visual and structural advantages. Available in timber, aluminium, and composite materials, hexagonal designs provide ample interior width for dining sets and lounge furniture. Equal corner angles free up furniture layout and remove the dead zones typical of four-sided forms.\n\nThese structures suit residential gardens, restaurant terraces, boutique hotel landscapes, and municipal parks with equal grace. Because the hexagonal form presents an attractive profile from every angle, it works especially well in open areas viewed from multiple directions. Ventilation gaps between panels maintain natural airflow while shielding occupants from rain and wind. Landscape designers often select hexagonal gazebos for central garden placements and island configurations where the structure is seen from all sides.\n\nCompared with square or rectangular alternatives, a hexagonal gazebo delivers more usable floor area per square metre and balanced shading across the footprint. Support columns spread at even intervals reduce vibration and deflection across long spans, reinforcing safe outdoor seating. If you want a distinctive seating experience for your family or guests, explore our hexagonal gazebo collection and request a free site survey today.",
                        'slug' => 'hexagonal-gazebo',
                        'seo_baslik' => 'Hexagonal Gazebo Models & Prices 2026 | Kamelya',
                        'seo_aciklama' => 'Hexagonal gazebo models: six-sided symmetrical design, spacious interior, timber and aluminium options. Transparent m² pricing, free survey, 5-year warranty.',
                    ],
                    'de' => [
                        'isim' => 'Sechseckiger Pavillon',
                        'aciklama' => "Sechseckige Pavillons zeichnen sich durch ihre symmetrische Sechs-Eck-Geometrie aus und verleihen Gärten eine architektonische Präsenz. Der großzügige Innenraum und das ausgewogene Tragwerk bieten gleichzeitig optische und statische Vorteile. Verfügbar in Holz-, Aluminium- und Kompositbauweise, eignen sich Sechseckmodelle besonders für Esstische und Loungegruppen. Gleich große Winkel ermöglichen freie Möblierung und eliminieren tote Zonen, wie sie bei Vier-Eck-Grundrissen auftreten. Die symmetrische Lastverteilung reduziert Punktbelastungen und erhöht die Standfestigkeit.\n\nEinsatzgebiete reichen von Wohn-Gärten über Restaurantterrassen bis hin zu Hotelparkanlagen und öffentlichen Grünflächen. Die Sechseckform wirkt aus jeder Blickrichtung harmonisch und ist daher ideal für freistehende Platzierungen im Gartenmittelpunkt. Belüftungsspalten zwischen den Füllungen sorgen für natürliche Luftzirkulation, während Wetterschutz gewährleistet bleibt (EN 13561 konform). Planer setzen sechseckige Pavillons bevorzugt als Inselobjekte ein, wenn die Konstruktion von allen Seiten wahrgenommen wird. Die sechs Seiten erlauben flexible Öffnungs- und Schließzonen je nach Himmelsrichtung und Windlage.\n\nIm Vergleich zu rechteckigen Varianten bietet ein sechseckiger Pavillon mehr nutzbare Fläche pro Quadratmeter und eine gleichmäßige Verschattung über die gesamte Grundfläche. Gleichmäßig verteilte Stützen reduzieren Schwingungen und Durchbiegungen bei großen Spannweiten und erhöhen die Sicherheit im Außenbereich. Für eine besondere Sitzatmosphäre empfehlen wir, unsere Sechseck-Modelle zu prüfen und einen kostenlosen Aufmaß-Termin zu vereinbaren.",
                        'slug' => 'sechseckiger-pavillon',
                        'seo_baslik' => 'Sechseckiger Pavillon: Modelle & Preise 2026 | Kamelya',
                        'seo_aciklama' => 'Sechseckige Pavillons: symmetrische Geometrie, großzügiger Innenraum, Holz- und Aluminiumvarianten. Transparente m²-Preise, kostenloser Aufmaß, 5 Jahre Garantie.',
                    ],
                    'fr' => [
                        'isim' => 'Gazebo Hexagonal',
                        'aciklama' => "Le gazebo hexagonal, avec sa structure à six côtés parfaitement symétrique, confère à votre jardin une élégance architecturale immédiate. Son volume intérieur généreux et son système de soutien équilibré offrent autant d'avantages visuels que structurels. Disponible en bois, en aluminium et en composite, il accueille aussi bien une table de repas qu'un salon d'extérieur. Les angles réguliers libèrent l'agencement du mobilier et suppriment les angles morts propres aux formes rectangulaires.\n\nDes jardins résidentiels aux terrasses de restaurant, en passant par les paysages d'hôtels de charme et les parcs publics, le gazebo hexagonal s'adapte à toutes les scénographies. Sa forme présente un profil harmonieux depuis chaque angle, ce qui en fait un choix idéal pour les espaces ouverts observés sous plusieurs perspectives. Les interstices entre les panneaux favorisent une ventilation naturelle tout en protégeant de la pluie et du vent. Les paysagistes le privilégient souvent comme pièce maîtresse au centre du jardin, lorsqu'il est contemplé sous tous les azimuts.\n\nPar rapport aux modèles rectangulaires, le gazebo hexagonal offre une surface utile au mètre carré supérieure et un ombrage équilibré sur l'ensemble de l'emprise. Des poteaux répartis à intervalles égaux réduisent vibrations et fléchisseons sur grandes portées et renforcent la sécurité d'usage. Pour une expérience de séjour singulière, découvrez notre collection et demandez une étude gratuite sur site.",
                        'slug' => 'gazebo-hexagonal',
                        'seo_baslik' => 'Gazebo Hexagonal: Modèles & Prix 2026 | Kamelya',
                        'seo_aciklama' => 'Gazebo hexagonal : design à six côtés symétriques, volume intérieur généreux, bois et aluminium. Prix au m² transparents, étude gratuite, garantie 5 ans.',
                    ],
                    'it' => [
                        'isim' => 'Gazebo Esagonale',
                        'aciklama' => "Il gazebo esagonale, con la sua struttura a sei lati perfettamente simmetrica, regala al giardino un'eleganza architettonica immediata. L'ampio spazio interno e il sistema di sostegno equilibrato offrono vantaggi sia visivi che strutturali. Disponibile in legno, alluminio e composito, il modello esagonale ospita con disinvoltura tavoli da pranzo e lounge all'aperto. Gli angoli regolari liberano l'arredamento ed eliminano gli spazi morti tipici delle forme rettangolari.\n\nDai giardini residenziali alle terrazze dei ristoranti, dai paesaggi dei boutique hotel ai parchi comunali, il gazebo esagonale si adatta a ogni contesto. La forma esagonale si presenta armoniosa da ogni prospettiva, ideale per le collocazioni centrali e a isola nel verde. Le fessure di ventilazione tra i pannelli garantiscono aria naturale mentre proteggono da pioggia e vento. I landscape designer lo scelgono spesso come punto focale del giardino, quando l'opera è osservata da tutti i lati.\n\nRispetto ai modelli rettangolari, il gazebo esagonale offre una superficie utilizzabile maggiore per metro quadro e un'ombreggiatura equilibrata su tutta l'impronta. Pali distribuiti a intervalli regolari riducono vibrazioni e flessioni sulle luci maggiori e rafforzano la sicurezza d'uso all'aperto. Per un'esperienza di seduta distintiva, esplora la nostra collezione e richiedi un sopralluogo gratuito.",
                        'slug' => 'gazebo-esagonale',
                        'seo_baslik' => 'Gazebo Esagonale: Modelli e Prezzi 2026 | Kamelya',
                        'seo_aciklama' => 'Gazebo esagonale: design a sei lati simmetrici, spazio interno ampio, legno e alluminio. Prezzi al m² trasparenti, sopralluogo gratuito, garanzia 5 anni.',
                    ],
                    'ar' => [
                        'isim' => 'كوش سداسي',
                        'aciklama' => "تتميز موديلات الكوش السداسي بتصميمه المتماثل المكوّن من ستة أضلاع، مما يضيف إلى حديقتكم أناقة معمارية فريدة. يوفّر الفراغ الداخلي الواسع والنظام الحامل المتوازن مزايا بصرية وهيكلية في آنٍ واحد. تُصنع النماذج السداسية من الأخشاب والألمنيوم والمواد المركّبة، وهي مثالية لاستيعاب طاولات الطعام وأطقم الجلوس. توزّع الزوايا المتقاربة ترتيب الأثاث وتحيّد الزوايا الميتة الشائعة في الأشكال المستطيلة. كما يقلّل التوزيع المتماثل للحمولات من التركيز على نقاط بعينها ويرفع ثبات الهيكل.\n\nتخدم هذه الموديلات حدائق السكن والمسابح وتراسات المطاعم ومناظر الفنادق والحدائق العامة على حدٍّ سواء. إذ يُقدّم الشكل السداسي مظهراً جميلاً من كل اتجاه، مما يجعله خياراً مثالياً للتوسط في الحديقة أو كنقطة جزيرة. تسمح الفجوات بين الألواح بتهوية طبيعية أثناء توفير الحماية من المطر والرياح. كثيراً ما يفضّل مصممو المناظر الطبيعية الكوش السداسي كعنصر مركزي حين يُشاهد من جميع الجهات. وتتيح الأوجه الستة مناطق فتح وإغلاق مرنة بحسب اتجاه الرياح ومسار الشمس.\n\nمقارنة بالموديلات المستطيلة، يوفّر الكوش السداسي مساحة قابلة للاستخدام أكبر لكل متر مربع وتظليلاً متوازناً على كامل المساحة. الأعمدة الموزّعة بفواصل متساوية تقلّل الاهتزاز والانثناء على الفتحات الواسعة وتعزّز سلامة الاستخدام في الخارج. إن كنتم تبحثون عن تجربة جلسة مميزة لعائلتكم أو ضيوفكم، تصفّحوا موديلاتنا واطلبوا استشارة مجانية في الموقع اليوم.",
                        'slug' => 'hexagonal-kush',
                        'seo_baslik' => 'كوش سداسي: موديلات وأسعار وأحجام 2026 | Kamelya',
                        'seo_aciklama' => 'كوش سداسي: تصميم ستة أضلاع متماثل، فراغ داخلي واسع، خشب وألمنيوم للمتر المربع. أسعار شفافة للمتر مربع، استشارة مجانية، ضمان 5 سنوات معتمدة.',
                    ],
                ],
            ],
            [
                'kod' => 'kare',
                'ceviriler' => [
                    'tr' => [
                        'isim' => 'Kare Kamelya',
                        'aciklama' => "Kare kamelya modelleri, dört eşit kenarlı sade geometrisiyle her bahçeye uyum sağlayan en çok tercih edilen formdur. Kompakt yapısı sayesinde dar alanlarda bile ferah bir oturma alanı sunar. Standart ölçülere uygunluğu, mobilya yerleşimini kolaylaştırır ve maliyet tahminini şeffaf kılar. Eşit kenarlar, masa ve sandalye düzenini simetrik kurmaya izin verir; köşelerde israf edilen alan minimuma iner. Böylece aynı taban alanında daha çok kişi rahatça oturabilir.\n\nÖzellikle site bahçeleri, apartman avluları ve dar arsalar için ideal bir çözümdür. Kare form, köşe veya duvar kenarı konumlandırmalarına uyum sağlarken üç açık cephesiyle doğal ışık ve hava alışverişini korur. Dayanıklı malzeme seçenekleri sayesinde dört mevsim kullanılabilir; yağmur, kar ve yoğun güneş karşısında yapısını korur. Bakım gereksinimi düşüktür, yıllık yüzey kontrolü yeterlidir; yüzey kaplaması UV stabilitesi sayesinde yıllarca rengini korur. Kararlı taban planı, zemin hazırlığını ve montajı da kolaylaştırır.\n\nAltıgen ve dikdörtgen modellere kıyasla daha ekonomik bir başlangıç noktası sunan kare kamelya, m² başına düşen maliyeti düşürür. Dar bütçeli projelerde genişletilebilir yapısıyla öne çıkar; ileride yan kanat veya gölge sail eklentileriyle büyütülebilir. Taşıyıcı sistemi dört köşede toplandığı için rüzgâr yükü dengeli dağıtılır. Bahçeniz için ölçüsünü belirlemek ve ücretsiz keşif talep etmek isterseniz hemen iletişime geçebilirsiniz.",
                        'slug' => 'kare-kamelya',
                        'seo_baslik' => 'Kare Kamelya Modelleri ve Fiyatları 2026 | Kamelya',
                        'seo_aciklama' => 'Kare kamelya modelleri: dört eşit kenarlı kompakt tasarım, dar alanlara uygun, ahşap ve alüminyum. Şeffaf m² fiyat, ücretsiz keşif, 5 yıl garanti.',
                    ],
                    'en' => [
                        'isim' => 'Square Gazebo',
                        'aciklama' => "Square gazebo models are the most widely chosen form for gardens thanks to their clean four-sided geometry. The compact footprint delivers a generous seating area even in tight spaces. Standardised dimensions simplify furniture layout and make cost estimation straightforward. Equal sides let you arrange tables and chairs symmetrically while minimising wasted corner space.\n\nSquare designs suit residential gardens, apartment courtyards, and narrow plots especially well. Placed in a corner or against a wall, the structure keeps three open façades that preserve natural light and cross-ventilation. Durable material options ensure year-round use through rain, snow, and intense sun while requiring only an annual surface inspection for maintenance. UV-stable coatings keep colour intact for years without repainting.\n\nCompared with hexagonal or rectangular alternatives, a square gazebo offers a more economical entry point and lowers cost per square metre. Its expandable layout makes it a practical choice for budget-conscious projects; side panels or sail shades can be added later as needs grow. Support concentrated at four corners distributes wind load evenly. To size your garden and request a free site survey, contact our team today.",
                        'slug' => 'square-gazebo',
                        'seo_baslik' => 'Square Gazebo Models & Prices UK 2026 | Kamelya',
                        'seo_aciklama' => 'Square gazebo models: clean four-sided compact design, ideal for tight spaces, timber and aluminium options. Transparent m² pricing, free survey, 5-year warranty.',
                    ],
                    'de' => [
                        'isim' => 'Quadratischer Pavillon',
                        'aciklama' => "Quadratische Pavillons überzeugen durch ihre klare Vier-Eck-Geometrie und sind die meistgewählte Form für Gärten. Das kompakte Grundrissmaß bietet auch auf beengten Flächen eine großzügige Sitzfläche. Normierte Maße erleichtern die Möblierung und machen die Kostenkalkulation transparent. Gleich lange Seiten erlauben eine symmetrische Tisch- und Stuhl-Anordnung und minimieren ungenutzte Eckflächen. So finden auf derselben Grundfläche mehr Personen komfortabel Platz. Die klare Geometrie vereinfacht zusätzlich die statische Bemessung.\n\nBesonders geeignet sind quadratische Pavillons für Wohn-Gärten, Hofanlagen und schmale Bauplätze. Als Eck- oder Wandstellung behält die Konstruktion drei offene Fassaden für Tageslicht und Querlüftung. Wetterfeste Materialien ermöglichen ganzjährige Nutzung bei Regen, Schnee und hoher Sonneneinstrahlung; ein jährlicher Oberflächencheck genügt zur Pflege. UV-stabile Beschichtungen halten die Farbe über Jahre ohne Neuanstrich. Der stabile Grundriss vereinfacht zusätzlich Bodenvorbereitung und Montage am Einsatzort.\n\nGegenüber sechs- oder rechteckigen Varianten bietet ein quadratischer Pavillon einen wirtschaftlicheren Einstieg und senkt die Kosten pro Quadratmeter. Die erweiterbare Grundrissgestaltung eignet sich für budgetbewusste Projekte; Seitenpaneele oder Schattsegel lassen sich später ergänzen. Die Traglast verteilt sich gleichmäßig über die vier Ecken und optimiert das Windverhalten. Für die Aufmaßplanung und einen kostenlosen Vor-Ort-Termin kontaktieren Sie uns bitte.",
                        'slug' => 'quadratischer-pavillon',
                        'seo_baslik' => 'Quadratischer Pavillon: Modelle & Preise 2026 | Kamelya',
                        'seo_aciklama' => 'Quadratische Pavillons: klare Vier-Eck-Geometrie, kompaktes Grundrissmaß, Holz- und Aluminiumvarianten. Transparente m²-Preise, kostenloser Aufmaß, 5 Jahre Garantie.',
                    ],
                    'fr' => [
                        'isim' => 'Gazebo Carré',
                        'aciklama' => "Le gazebo carré, avec sa géométrie à quatre côtés épurée, s'intègre harmonieusement dans tout type de jardin. Son emprise compacte offre une surface de séjour généreuse même dans les espaces réduits. Les dimensions normalisées simplifient l'installation du mobilier et rendent l'estimation des coûts transparente. Des côtés égaux permettent un agencement symétrique des tables et chaises en minimisant les angles morts.\n\nIl convient tout particulièrement aux jardins résidentiels, cours d'immeubles et terrains étroits. Placé en angle ou contre un mur, il conserve trois façades ouvertes qui préservent la lumière naturelle et la ventilation transversale. Les matériaux durables assurent une utilisation toute l'année face à la pluie, la neige et le soleil intense, avec un simple contrôle annuel de surface. Les finitions stables aux UV conservent la teinte pendant des années sans repeinture.\n\nPar rapport aux modèles hexagonaux ou rectangulaires, le gazebo carré constitue un point d'entrée plus économique et réduit le coût au mètre carré. Sa structure extensible séduit les projets au budget maîtrisé ; des panneaux latéraux ou voiles d'ombrage peuvent s'ajouter ultérieurement. La charge structurale se répartit sur les quatre angles pour un comportement au vent optimal. Pour dimensionner votre espace et demander une étude gratuite, contactez notre équipe.",
                        'slug' => 'gazebo-carre',
                        'seo_baslik' => 'Gazebo Carré: Modèles & Prix Professionnels 2026 | Kamelya',
                        'seo_aciklama' => 'Gazebo carré : géométrie à quatre côtés épurée, emprise compacte pour petits espaces, bois et aluminium. Prix au m² transparents, étude gratuite, garantie 5 ans.',
                    ],
                    'it' => [
                        'isim' => 'Gazebo Quadrato',
                        'aciklama' => "Il gazebo quadrato, con la sua geometria a quattro lati essenziale, si adatta con disinvoltura a ogni tipo di giardino. L'impronta compatta garantisce una zona soggiorno ampia anche negli spazi ridotti. Le dimensioni standardizzate semplificano l'arredamento e rendono la stima dei costi trasparente. I lati uguali permettono di disporre tavoli e sedie in modo simmetrico riducendo al minimo gli spazi angolari morti.\n\nÈ la scelta ideale per giardini residenziali, cortili condominiali e lotti stretti. Collocato in angolo o contro una parete, mantiene tre facciate aperte che preservano la luce naturale e la ventilazione incrociata. I materiali duraturi assicurano utilizzo tutto l'anno sotto pioggia, neve e sole intenso, con un semplice controllo annuale della superficie. Le finiture stabili ai raggi UV conservano il colore per anni senza verniciature.\n\nRispetto ai modelli esagonali o rettangolari, il gazebo quadrato offre un punto d'ingresso più economico e riduce il costo al metro quadro. La sua struttura estendibile è perfetta per progetti con budget definito; pannelli laterali o vele d'ombra si possono aggiungere in seguito. Il carico strutturale si distribuisce sui quattro angoli per un comportamento ottimale al vento. Per dimensionare il tuo spazio e richiedere un sopralluogo gratuito, contatta il nostro team.",
                        'slug' => 'gazebo-quadrato',
                        'seo_baslik' => 'Gazebo Quadrato: Modelli e Prezzi 2026 | Kamelya',
                        'seo_aciklama' => 'Gazebo quadrato: geometria a quattro lati essenziale, impronta compatta, legno e alluminio. Prezzi al m² trasparenti, sopralluogo gratuito, garanzia 5 anni.',
                    ],
                    'ar' => [
                        'isim' => 'كوش مربع',
                        'aciklama' => "تتميز موديلات الكوش المربع بتصميمه الهندسي النظيف المكوّن من أربعة أضلاع متساوية، وهو الأكثر شيوعاً في الحدائق. يوفّر الحجم المدمج مساحة جلسة واسعة حتى في الأماكن الضيقة. تسهّل الأبعاد القياسية ترتيب الأثاث وتجعل تقدير التكاليف واضحاً. تتيح الأضلاع المتساوية ترتيب الطاولات والكراسي بشكل متماثل مع تقليل الزوايا الميتة، ليجد المزيد من الأشخاص راحة على المساحة نفسها.\n\nإنها الحل الأمثل لحدائق السكن وصحون العمارات والأراضي الضيقة. عند وضعه في زاوية أو بجانب جدار، يحافظ على ثلاثة واجهات مفتوحة تضمن الضوء الطبيعي والتهوية المتقاطعة. توفّر المواد المتينة استخداماً على مدار العام أمام المطر والثلج والشمس القوية، مع حاجة لفحص سطحي سنوي فقط. تحافظ طبقات الألوان المستقرة أمام الأشعة فوق البنفسجية على اللون لسن سنوات دون إعادة طلاء. كما يبسّط الثبات في الأرضية تحضير الموقع والتركيب.\n\nمقارنة بالنماذج السداسية والمستطيلة، يوفّر الكوش المربع نقطة انطلاقة اقتصادية أكثر ويقلّل التكلفة لكل متر مربع. تجعله بنيته القابلة للتوسعة خياراً عملياً للمشاريع ذات الميزانية المحدودة؛ ويمكن إضافة ألواح جانبية أو مظلات لاحقاً. توزّع الحمولات الإنشائية على الزوايا الأربع لتوزيع أمثل لأحمال الرياح. لتحديد مساحتكم وطلب استشارة مجانية، تواصلوا مع فريقنا اليوم.",
                        'slug' => 'square-kush',
                        'seo_baslik' => 'كوش مربع: موديلات وأسعار وأحجام 2026 | Kamelya',
                        'seo_aciklama' => 'كوش مربع: تصميم أربعة أضلاع نظيف، حجم مدمج للمساحات الضيقة، خشب وألمنيوم عالي الجودة. أسعار شفافة للمتر مربع، استشارة مجانية، ضمان 5 سنوات معتمد.',
                    ],
                ],
            ],
            [
                'kod' => 'dikdortgen',
                'ceviriler' => [
                    'tr' => [
                        'isim' => 'Dikdörtgen Kamelya',
                        'aciklama' => "Dikdörtgen kamelya modelleri, uzun kenarlı dikdörtgen taban planıyla geniş oturma ve yemek alanları için tasarlanmıştır. Oranlı genişliği, büyük grupları tek çatı altında toplar. Endüstriyel ve ticari kullanımlarda kapasite avantajı sağlar; restoran terasları ve etkinlik alanları için idealdir. Uzun eksen boyunca dizilen masalar, servis akışını hızlandırır ve misafir yoğunluğunu dengeler. Geniş taban, engelli erişimi ve tekerlekli sandalye manevrası için de uygundur.\n\nUzunlamasına konumlandırma, bahçe yolu veya teras kenarına paralel yerleşimi kolaylaştırır. Çift taraflı açıklık, iç mekân ile bahçe arasında sürekli görsel bağlantı kurar. Alüminyum ve kompozit seçenekleri, ticari kullanımın yoğun bakım gereksinimini karşılayacak dayanıklılık sunar. Yapısal çelik takviyeli taşıyıcılar, uzun açıklıklarda bile güvenli destek sağlar; çökme ve titreşim toleransları hesaplanmıştır. Yağmur oluğu ve kenar perdesi seçenekleri dört mevsim kullanımı tamamlar.\n\nKare modellere kıyasla daha fazla oturma kapasitesi sunan dikdörtgen kamelya, m² başına düşen kişi sayısını artırır. Aynı taban alanında daha çok sandalye sığdırması, işletmenin gelirini doğrudan etkiler. Kalabalık aileler ve işletme sahipleri için güçlü bir tercihtir. Ölçü ve fiyat bilgisi için ücretsiz keşif talebinde bulunabilirsiniz.",
                        'slug' => 'dikdortgen-kamelya',
                        'seo_baslik' => 'Dikdörtgen Kamelya Modelleri ve Fiyatları 2026 | Kamelya',
                        'seo_aciklama' => 'Dikdörtgen kamelya modelleri: geniş taban planı, yüksek oturma kapasitesi, alüminyum ve kompozit seçenekleri. m² şeffaf fiyat, ücretsiz keşif, 5 yıl garanti.',
                    ],
                    'en' => [
                        'isim' => 'Rectangular Gazebo',
                        'aciklama' => "Rectangular gazebo models are engineered around an elongated floor plan that maximises seating and dining capacity. Their proportional width accommodates large groups under a single roof, making them a strong choice for commercial applications. Restaurant terraces and event areas benefit most from the extra capacity. Tables aligned along the long axis speed service flow and balance peak occupancy. The wide footprint also suits wheelchair access and turning circles.\n\nLengthwise placement allows the structure to run parallel to garden paths or terrace edges. Openings on both long sides maintain a visual connection between interior and landscape. Aluminium and composite options deliver the durability required for high-traffic commercial use. Steel-reinforced support posts ensure safe loads even across longer spans, with calculated tolerances for deflection and vibration. Rain gutters and side curtain options complete four-season usability.\n\nCompared with square models, a rectangular gazebo increases the number of people accommodated per square metre. Fitting more chairs within the same footprint directly improves revenue per square metre. It is a compelling option for large families and business owners. To obtain dimensions and pricing, request a free site survey from our team.",
                        'slug' => 'rectangular-gazebo',
                        'seo_baslik' => 'Rectangular Gazebo Models & Prices 2026 | Kamelya',
                        'seo_aciklama' => 'Rectangular gazebo models: elongated floor plan, high seating capacity, aluminium and composite options. Transparent m² pricing, free survey, 5-year warranty.',
                    ],
                    'de' => [
                        'isim' => 'Rechteckiger Pavillon',
                        'aciklama' => "Rechteckige Pavillons basieren auf einem langgestreckten Grundriss, der Sitz- und Essflächen maximiert. Das proportionale Maß fasst große Gruppen unter einem Dach und eignet sich daher besonders für gewerbliche Einsätze. Restaurantterrassen und Eventflächen profitieren am stärksten von der erhöhten Kapazität. Entlang der Längsachse aufgestellte Tische beschleunigen den Serviceweg und stabilisieren die Spitzenauslastung. Der breite Grundriss eignet sich zudem für Rollstuhlzugang und Wendekreise.\n\nDie längsseitige Anordnung erlaubt eine parallele Platzierung zu Gartenwegen oder Terrassenrändern. Öffnungen an beiden Längsseiten halten die visuelle Verbindung zwischen Innenraum und Garten aufrecht. Aluminium- und Kompositvarianten bieten die nötige Beständigkeit für den Dauerbetrieb. Stahlbewehrte Stützen garantieren sichere Lastabtragung auch bei größeren Spannweiten; Durchbiegung und Schwingung sind berechnet. Dachrinnen und Seitenvorhänge ergänzen die Ganzjahresnutzung.\n\nGegenüber quadratischen Modellen erhöht ein rechteckiger Pavillon die Personenzahl pro Quadratmeter. Mehr Stühle auf gleicher Grundfläche steigern den Umsatz je Quadratmeter direkt. Er ist eine überzeugende Wahl für Großfamilien und Betreiber. Für Maße und Preisangaben fordern Sie bitte einen kostenlosen Aufmaß-Termin an.",
                        'slug' => 'rechteckiger-pavillon',
                        'seo_baslik' => 'Rechteckiger Pavillon: Modelle & Preise 2026 | Kamelya',
                        'seo_aciklama' => 'Rechteckige Pavillons: langgestreckter Grundriss, hohe Sitzkapazität, Aluminium- und Kompositvarianten. Transparente m²-Preise, kostenloser Aufmaß, 5 Jahre Garantie.',
                    ],
                    'fr' => [
                        'isim' => 'Gazebo Rectangulaire',
                        'aciklama' => "Le gazebo rectangulaire repose sur un plan au sol allongé qui maximise l'espace de séjour et de repas. Ses proportions accueillent de grands groupes sous un même toit, ce qui en fait un choix pertinent pour les usages commerciaux. Les terrasses de restaurant et les espaces événementiels en tirent le meilleur bénéfice. Les tables alignées le long du grand axe fluidifient le service et équilibrent les pics de fréquentation.\n\nL'orientation longitudinale permet de le placer en parallèle des allées de jardin ou des lisières de terrasse. Les ouvertures sur les deux grands côtés préservent la continuité visuelle entre l'intérieur et le paysage. Les options aluminium et composite offrent la durabilité requise par un usage intensif. Des poteaux renforcés en acier assurent la stabilité sur de grandes portées, avec des tolérances de flèche et de vibration calculées.\n\nPar rapport aux modèles carrés, le gazebo rectangulaire augmente le nombre de personnes accueillies au mètre carré. Davantage de chaises sur une même emprise améliore directement le chiffre d'affaires au mètre carré. Gouttières et rideaux latéraux prolongent l'usage sur quatre saisons. Il s'impose pour les familles nombreuses et les professionnels. Pour obtenir cotes et tarifs, demandez une étude gratuite sur site.",
                        'slug' => 'gazebo-rectangulaire',
                        'seo_baslik' => 'Gazebo Rectangulaire: Modèles & Prix 2026 | Kamelya',
                        'seo_aciklama' => 'Gazebo rectangulaire : plan allongé, grande capacité d\'accueil, aluminium et composite. Prix au m² transparents, étude gratuite sur site, garantie 5 ans.',
                    ],
                    'it' => [
                        'isim' => 'Gazebo Rettangolare',
                        'aciklama' => "Il gazebo rettangolare nasce da una planimetria allungata che massimizza lo spazio di seduta e pranzo. Le proporzioni accolgono gruppi numerosi sotto un unico tetto, rendendolo una scelta solida per gli usi commerciali. Le terrazze dei ristoranti e gli spazi per eventi ne beneficiano al massimo. I tavoli allineati sull'asse lungo velocizzano il servizio e bilanciano i picchi di affollamento. L'impronta ampia è adatta anche all'accesso in sedia a rotelle.\n\nIl posizionamento longitudinale permette di allinearlo ai viali del giardino o ai bordi della terrazza. Le aperture sui due lati lunghi mantengono il legame visivo tra interno e paesaggio. Le opzioni in alluminio e composito garantiscono la resistenza richiesta dall'uso intensivo. Pali portanti rinforzati in acciaio assicurano sicurezza anche su luci maggiori, con tolleranze di flessione e vibrazione calcolate. Docce e tende laterali completano l'uso in quattro stagioni.\n\nRispetto ai modelli quadrati, il gazebo rettangolare aumenta il numero di persone per metro quadro. Più sedie sulla stessa impronta migliorano direttamente il fatturato al metro quadro. È una scelta forte per famiglie numerose e gestori. Per dimensioni e prezzi, richiedi un sopralluogo gratuito.",
                        'slug' => 'gazebo-rettangolare',
                        'seo_baslik' => 'Gazebo Rettangolare: Modelli e Prezzi 2026 | Kamelya',
                        'seo_aciklama' => 'Gazebo rettangolare: planimetria allungata, alta capienza, alluminio e composito. Prezzi al m² trasparenti, sopralluogo gratuito, garanzia 5 anni.',
                    ],
                    'ar' => [
                        'isim' => 'كوش مستطيل',
                        'aciklama' => "تُصمَّم موديلات الكوش المستطيل وفق خطة أرضية ممتدة تعظّم مساحات الجلوس وتناول الطعام. تستوعب نسبتها العريضة مجموعات كبيرة تحت سقف واحد، مما يجعلها خياراً قوياً للاستخدام التجاري. تستفيد منها تراسات المطاعم ومناسبات الفعاليات بشكل خاص. الطاولات المصفوفة على المحور الطويل تسرّع حركة الخدمة وتوازن ذروة الإشغال.\n\nيسمح التوجيه الطولي بتركيبه موازياً لممرات الحديقة أو حواف التراس. تحافظ الفتحات على الجانبين الطويلين على الاتصال البصري بين الداخل والمنظر الطبيعي. توفّر خيارات الألمنيوم والمواد المركّبة التحمّل المطلوب للاستخدام المكثّف. تضمن الأعمدة المدعّمة بالصلب أماناً حتى على مدى أوسع، مع احتمالات انثناء واهتزاز محسوبة.\n\nمقارنة بالنماذج المربعة، يزيد الكوش المستطيل من عدد الأشخاص لكل متر مربع. زيادة عدد الكراسي على المساحة نفسها ترفع الإيراد للمتر المربع مباشرة. المزاريب والستائر الجانبية تمدّد الاستخدام طوال الفصول الأربعة. إنه خيار قوي للعائلات الكبيرة وملاك الأعمال. للحصول على الأبعاد والأسعار، اطلبوا استشارة مجانية في الموقع.",
                        'slug' => 'rectangular-kush',
                        'seo_baslik' => 'كوش مستطيل: موديلات وأسعار وأحجام 2026 | Kamelya',
                        'seo_aciklama' => 'كوش مستطيل: خطة أرضية ممتدة، سعة جلوس عالية، ألمنيوم ومواد مركّبة للمتر المربع. أسعار شفافة للمتر مربع، استشارة مجانية، ضمان 5 سنوات معتمد.',
                    ],
                ],
            ],
            [
                'kod' => 'modern',
                'ceviriler' => [
                    'tr' => [
                        'isim' => 'Modern Kamelya',
                        'aciklama' => "Modern kamelya modelleri, minimalist çizgiler ve çağdaş malzeme kombinasyonlarıyla öne çıkan çağdaş bir tasarım anlayışını temsil eder. Düz çatı hatları, ince profil taşıyıcılar ve geniş cam yüzeyler, geleneksel kamelya kalıplarını aşar. Alüminyum çerçeve ve temperli cam paneller, ferah bir iç mekân algısı yaratır. Keskin köşe detayları ve gömme aydınlatma, mekâna sakin bir ritim kazandırır.\n\nGenellikle butik oteller, tasarım ofisleri ve çağdaş konut projelerinde tercih edilir. Yüksek m² fiyatına rağmen uzun ömürlü malzeme ve düşük bakım gereksinimi, toplam sahip olma maliyetini düşürür. Akıllı entegrasyon seçenekleri — aydınlatma, ısıtma, gölgeleme — yaşam kalitesini artırır. Cam çatı seçeneği, yıldız gözlemine olanak tanır; gölgeli kanatlar ise mevsimsel güneşe karşı koruma sağlar.\n\nKlasik modellere kıyasla daha çarpıcı bir mimari duruş sergileyen modern kamelya, bahçenizin odak noktası haline gelir. Premium segmentte değer artışı sağlayan bir yatırım aracıdır; dış cephe diliyle uyumu, konut projelerinde satış katkısı yaratır. Modern kamelya modellerimizi inceleyip ücretsiz keşif talebinde bulunabilirsiniz.",
                        'slug' => 'modern-kamelya',
                        'seo_baslik' => 'Modern Kamelya Modelleri ve Fiyatları 2026 | Kamelya',
                        'seo_aciklama' => 'Modern kamelya modelleri: minimalist çizgi, alüminyum çerçeve, temperli cam. Çağdaş tasarım, düşük bakım, 5 yıl garanti. m² şeffaf fiyat, ücretsiz keşif.',
                    ],
                    'en' => [
                        'isim' => 'Modern Gazebo',
                        'aciklama' => "Modern gazebo models represent a contemporary design approach defined by minimalist lines and current material combinations. Flat roof profiles, slender support frames, and extensive glazing depart from traditional gazebo archetypes. Aluminium frames and tempered glass panels create an airy interior presence. Sharp corner detailing and recessed lighting give the space a calm rhythm.\n\nThese structures are frequently specified for boutique hotels, design studios, and contemporary residential projects. Despite a higher price per square metre, long-lasting materials and low maintenance requirements reduce total cost of ownership. Smart integrations — lighting, heating, shading — elevate everyday usability. A glass roof option even supports stargazing, while shaded side wings protect against seasonal sun.\n\nCompared with classic models, a modern gazebo makes a bolder architectural statement and becomes the focal point of your garden. It also represents an investment that adds value in the premium segment; its façade language supports sales in residential developments. Explore our modern gazebo collection and request a free site survey.",
                        'slug' => 'modern-gazebo',
                        'seo_baslik' => 'Modern Gazebo Models & Prices 2026 | Kamelya',
                        'seo_aciklama' => 'Modern gazebo models: minimalist lines, aluminium frames, tempered glass. Contemporary design, low maintenance, 5-year warranty. Transparent m² pricing, free survey.',
                    ],
                    'de' => [
                        'isim' => 'Moderner Pavillon',
                        'aciklama' => "Moderne Pavillons verkörpern einen zeitgenössischen Designansatz mit minimalistischen Linien und aktuellen Materialkombinationen. Flache Dachprofile, schlanke Tragrahmen und großzügige Verglasungen lösen sich vom traditionellen Pavillon-Schema. Aluminiumrahmen und Sicherheitsglaspaneele erzeugen einen leichten Innenraumcharakter. Scharfe Eckdetails und eingelassene Beleuchtung verleihen dem Raum eine ruhige Rhythmik.\n\nDer Einsatz erfolgt häufig in Boutique-Hotels, Designbüros und modernen Wohnprojekten. Trotz höherem Quadratmeterpreis senken langlebige Materialien und geringer Pflegeaufwand die Gesamtbetriebskosten. Smarte Integrationen — Beleuchtung, Heizung, Beschattung — erhöhen den Komfort. Eine Glasdach-Option ermöglicht sogar Sternenbeobachtung; beschattete Seitenflügel schützen vor saisonaler Sonne.\n\nIm Vergleich zu klassischen Modellen setzt ein moderner Pavillon einen markanteren architektonischen Akzent und wird zum Blickfang Ihres Gartens. Er stellt zugleich eine wertsteigernde Investition im Premium-Segment dar; die Fassadensprache unterstützt Verkäufe im Wohnungsbauprojekt. Prüfen Sie unsere Modern-Modelle und fordern Sie einen kostenlosen Aufmaß-Termin an.",
                        'slug' => 'moderner-pavillon',
                        'seo_baslik' => 'Moderner Pavillon: Modelle & Preise 2026 | Kamelya',
                        'seo_aciklama' => 'Moderne Pavillons: minimalistische Linien, Aluminiumrahmen, Sicherheitsglas. Zeitgenössisches Design, geringer Pflegeaufwand, 5 Jahre Garantie.',
                    ],
                    'fr' => [
                        'isim' => 'Gazebo Moderne',
                        'aciklama' => "Le gazebo moderne incarne une approche contemporaine définie par des lignes minimalistes et des combinaisons matériaux actuelles. Toitures planes, structures élancées et larges vitrages dépassent l'archétype traditionnel. Les cadres en aluminium et les panneaux en verre trempé créent une présence intérieure aérée. Des angles nets et un éclairage encastré confèrent au volume un rythme apaisé.\n\nCes structures séduisent hôtels de charme, agences de design et projets résidentiels contemporains. Malgré un prix au mètre carré plus élevé, la durabilité des matériaux et l'entretien réduit maîtrisent le coût global de possession. Les intégrations intelligentes — éclairage, chauffage, ombrage — améliorent le confort d'usage. Une option de toit en verre permet même d'observer les étoiles, tandis que des ailes ombragées protègent du soleil saisonnier.\n\nPar rapport aux modèles classiques, le gazebo moderne affirme une présence architecturale plus marquée et devient le point focal du jardin. Il constitue aussi un investissement valorisant dans le segment premium ; son langage de façade soutient la commercialisation des programmes résidentiels. Découvrez notre collection et demandez une étude gratuite.",
                        'slug' => 'gazebo-moderne',
                        'seo_baslik' => 'Gazebo Moderne: Modèles & Prix 2026 | Kamelya',
                        'seo_aciklama' => 'Gazebo moderne : lignes minimalistes, cadres aluminium, verre trempé. Design contemporain, entretien réduit, garantie 5 ans. Prix au m², étude gratuite.',
                    ],
                    'it' => [
                        'isim' => 'Gazebo Moderno',
                        'aciklama' => "Il gazebo moderno incarna un approccio contemporaneo caratterizzato da linee minimaliste e combinazioni materiali attuali. Tetti piatti, strutture snelle e ampie vetrate superano l'archetipo tradizionale. I telai in alluminio e i pannelli in vetro temperato creano un'atmosfera interna ariosa. Angoli netti e illuminazione integrata conferiscono allo spazio un ritmo quieto.\n\nQueste strutture si scelgono spesso per boutique hotel, studi di design e progetti residenziali contemporanei. Nonostante un prezzo al metro quadro più alto, la durata dei materiali e la manutenzione ridotta abbassano il costo totale di proprietà. Le integrazioni intelligenti — illuminazione, riscaldamento, oscuramento — migliorano il comfort d'uso. Un'opzione di tetto in vetro consente persino l'osservazione delle stelle, mentre ali ombreggiate proteggono dal sole stagionale.\n\nRispetto ai modelli classici, il gazebo moderno esprime una presenza architettonica più decisa e diventa il punto focale del giardino. Rappresenta anche un investimento che valorizza il segmento premium; il linguaggio di facciata sostiene le vendite nei progetti residenziali. Esplora la nostra collezione e richiedi un sopralluogo gratuito.",
                        'slug' => 'gazebo-moderno',
                        'seo_baslik' => 'Gazebo Moderno: Modelli e Prezzi 2026 | Kamelya',
                        'seo_aciklama' => 'Gazebo moderno: linee minimaliste, telai in alluminio, vetro temperato. Design contemporaneo, manutenzione ridotta, garanzia 5 anni. Prezzi al m².',
                    ],
                    'ar' => [
                        'isim' => 'كوش عصري',
                        'aciklama' => "تجسّد موديلات الكوش العصرية نهجاً تصميمياً معاً يمتاز بخطوط بسيطة وتركيبات مادّية حديثة. تتجاوز مسطّحات السقف والأعمدة النحيلة والواجهات الزجاجية الواسعة النماذج التقليدية. تخلق الهياكل الألمنيومية ولوحات الزجاج المقسّى إحساساً داخلياً متنفساً. تمنح الزوايا الحادة والإضاءة المدمجة الفراغ إيقاعاً هادئاً.\n\nغالباً ما تُختار هذه الهياكل للفنادق الصغيرة ومكاتب التصميم والمشاريع السكنية المعاصرة. ورغم سعر المرجع الأعلى، فإن موادها طويلة العمر وصيانتها المنخفضة تخفض تكلفة التملّك الإجمالية. تعزّز التكاملات الذكية — الإضاءة والتدفئة والتظليل — راحة الاستخدام. كما يتيح خيار السقف الزجاجي مراقبة النجوم، بينما توفر الأجنحة المظلّلة حماية من شمس الموسم.\n\nمقارنة بالنماذج الكلاسيكية، يمنح الكوش العصري حضوراً معمارياً أبرز ويصبح نقطة الجذب في حديقتكم. كما يمثّل استثماراً يرفع القيمة في الشريحة الراقية؛ ولغة الواجهة تدعم المبيعات في المشاريع السكنية. تصفّحوا موديلاتنا واطلبوا استشارة مجانية في الموقع.",
                        'slug' => 'modern-kush',
                        'seo_baslik' => 'كوش عصري: موديلات وأسعار 2026 | Kamelya',
                        'seo_aciklama' => 'كوش عصري: خطوط بسيطة، هيكل ألمنيوم، زجاج مقسّى. تصميم معاصر، صيانة منخفضة، ضمان 5 سنوات. أسعار شفافة، استشارة مجانية.',
                    ],
                ],
            ],
            [
                'kod' => 'klasik',
                'ceviriler' => [
                    'tr' => [
                        'isim' => 'Klasik Kamelya',
                        'aciklama' => "Klasik kamelya modelleri, geleneksel kır evi estetiğini çağdaş dayanıklılık standartlarıyla birleştirir. Kırma çatı detayları, oyma korkuluklar ve ahşap işçiliği, zamansız bir görsel dil oluşturur. Doğal ahşap kaplama, sıcak ve davetkâr bir atmosfer yaratır; her mevsim görsel çekiciliğini korur. Simetrik cephe düzeni ve ince profil tranşlar, mekâna ölçülü bir zarafet katar.\n\nÖzellikle tarihi dokuya sahip konutlarda, kırsal turizm tesislerinde ve klasik peyzajlı bahçelerde tercih edilir. Geleneksel Türk ahşap mimarisinin izlerini taşır; el işçiliği detaylar, seri üretim ürünlerden ayrışır. Yüksek kaliteli ahşap koruyucu uygulaması, nem ve güneş hasarına karşı uzun ömür sağlar. Yıllık bakım rutini, ahşabın doğal patinasını korur; menteşe ve bağlantı elemanları korozyona dayanıklı kaplanmıştır.\n\nModern ve minimalist modellere kıyasla daha sıcak, daha kişisel bir karakter sunan klasik kamelya, bahçenize hikâye katar. Miras değeri taşıyan bir yatırım olarak öne çıkar; restorasyon projelerinde ve sit alanlarında uyumlu bir seçenektir. Klasik kamelya modellerimizi inceleyip ücretsiz keşif talebinde bulunabilirsiniz.",
                        'slug' => 'klasik-kamelya',
                        'seo_baslik' => 'Klasik Kamelya Modelleri ve Fiyatları 2026 | Kamelya',
                        'seo_aciklama' => 'Klasik kamelya modelleri: geleneksel kır evi estetiği, doğal ahşap, el işçiliği detaylar. Zamansız tasarım, uzun ömür, 5 yıl garanti. m² şeffaf fiyat, ücretsiz keşif.',
                    ],
                    'en' => [
                        'isim' => 'Classic Gazebo',
                        'aciklama' => "Classic gazebo models combine traditional country-house aesthetics with contemporary durability standards. Pitched roof details, decorative balustrades, and fine carpentry create a timeless visual language. Natural timber cladding produces a warm, inviting atmosphere that retains its appeal across every season. Symmetrical façade layouts and slender profile trims add measured elegance to the setting.\n\nThese designs are frequently selected for heritage properties, rural tourism venues, and formally landscaped gardens. They echo the craft traditions of Turkish timber architecture; hand-finished details distinguish them from mass-produced alternatives. High-quality wood preservatives protect against moisture and UV damage for long service life. An annual maintenance routine preserves the timber's natural patina; hardware is coated for corrosion resistance.\n\nCompared with modern or minimalist models, a classic gazebo offers a warmer, more personal character and adds narrative depth to your garden. It also stands out as an investment with heritage value, fitting restoration schemes and conservation areas. Explore our classic gazebo collection and request a free site survey.",
                        'slug' => 'classic-gazebo',
                        'seo_baslik' => 'Classic Gazebo Models & Prices 2026 | Kamelya',
                        'seo_aciklama' => 'Classic gazebo models: traditional country-house aesthetics, natural timber, handcrafted details. Timeless design, long service life, 5-year warranty. Transparent m² pricing.',
                    ],
                    'de' => [
                        'isim' => 'Klassischer Pavillon',
                        'aciklama' => "Klassische Pavillons verbinden traditionelle Landhaus-Ästhetik mit zeitgemäßen Dauerhaftigkeitsstandards. Satteldach-Details, dekorative Brüstungen und feine Schreinerarbeiten schaffen eine zeitlose Bildsprache. Natürliche Holzverkleidung erzeugt eine warme, einladende Atmosphäre, die in jeder Jahreszeit Bestand hat. Symmetrische Fassadenanordnungen und schlanke Profilleisten verleihen dem Auftritt Maß und Eleganz.\n\nDiese Ausführung wird häufig für denkmalgeschützte Anlagen, ländliche Tourismusbetriebe und formell angelegte Gärten gewählt. Sie zitieren das Handwerksverständnis der türkischen Holzarchitektur; handveredelte Details trennen sie von Massenprodukten. Hochwertige Holzschutzmittel schützen vor Feuchte und UV-Strahlung und sichern lange Nutzungsdauer. Eine jährliche Pflegeroutine bewahrt die natürliche Patina; Beschläge sind korrosionsgeschützt.\n\nGegenüber modernen oder minimalistischen Modellen bietet ein klassischer Pavillon einen wärmeren, persönlicheren Charakter und erzählt eine Geschichte im Garten. Er stellt zugleich eine investitionswürdige Ertragsobjekt-Ästhetik dar und passt zu Sanierungsvorhaben und Denkmalschutzgebieten. Prüfen Sie unsere Klassik-Modelle und fordern Sie einen kostenlosen Aufmaß-Termin an.",
                        'slug' => 'klassischer-pavillon',
                        'seo_baslik' => 'Klassischer Pavillon: Modelle & Preise 2026 | Kamelya',
                        'seo_aciklama' => 'Klassische Pavillons: Landhaus-Ästhetik, natürliches Holz, handveredelte Details. Zeitloses Design, lange Nutzungsdauer, 5 Jahre Garantie. Transparente m²-Preise.',
                    ],
                    'fr' => [
                        'isim' => 'Gazebo Classique',
                        'aciklama' => "Le gazebo classique marie l'esthétique traditionnelle de la maison de campagne aux standards de durabilité contemporains. Les déclinaisons de toit à deux pans, les balustres décoratives et l'ébénisterie fine composent un langage visuel intemporel. Le bardage en bois naturel installe une atmosphère chaleureuse qui traverse les saisons. La symétrie des façades et les profils élancés apportent une élégance mesurée.\n\nCes modèles séduisent les propriétés patrimoniales, les établissements de tourisme rural et les jardins au dessin formel. Ils perpétuent la tradition de l'architecture bois turque ; les finitions à la main les distinguent des produits industriels. Les traitements de haute qualité protègent l'humidité et les UV pour une longévité accrue. Un entretien annuel préserve la patine naturelle ; les ferrures sont protégées contre la corrosion.\n\nPar rapport aux modèles modernes ou minimalistes, le gazebo classique offre un caractère plus chaleureux et personnel, enrichissant le récit du jardin. Il s'affirme aussi comme un investissement doté d'une valeur patrimoniale, adapté aux restaurations et zones protégées. Découvrez notre collection et demandez une étude gratuite.",
                        'slug' => 'gazebo-classique',
                        'seo_baslik' => 'Gazebo Classique: Modèles & Prix 2026 | Kamelya',
                        'seo_aciklama' => 'Gazebo classique : esthétique de maison de campagne, bois naturel, finitions à la main. Design intemporel, longévité, garantie 5 ans. Prix au m² transparents.',
                    ],
                    'it' => [
                        'isim' => 'Gazebo Classico',
                        'aciklama' => "Il gazebo classico unisce l'estetica tradizionale della casa di campagna agli standard di durabilità contemporanei. I dettagli del tetto a due falde, le balaustrate decorative e la falegnameria fine creano un linguaggio visivo senza tempo. Il rivestimento in legno naturale genera un'atmosfera calda e accogliente che attraversa le stagioni. La simmetria delle facciate e i profili snelli donano eleganza misurata.\n\nQuesti modelli si scelgono spesso per proprietà storiche, strutture di turismo rurale e giardini dal disegno formale. Rievocano la tradizione dell'architettura in legno turca; le finiture a mano li distinguono dai prodotti industriali. Trattamenti di alta qualità proteggono da umidità e raggi UV per una lunga durata. Una manutenzione annuale preserva la naturale patina; gli elementi metallici sono trattati contro la corrosione.\n\nRispetto ai modelli moderni o minimalisti, il gazebo classico offre un carattere più caldo e personale e arricchisce la narrazione del giardino. Si impone anche come investimento con valore patrimoniale, adatto a restauri e aree tutelate. Esplora la nostra collezione e richiedi un sopralluogo gratuito.",
                        'slug' => 'gazebo-classico',
                        'seo_baslik' => 'Gazebo Classico: Modelli e Prezzi 2026 | Kamelya',
                        'seo_aciklama' => 'Gazebo classico: estetica di casa di campagna, legno naturale, finiture a mano. Design senza tempo, lunga durata, garanzia 5 anni. Prezzi al m² trasparenti.',
                    ],
                    'ar' => [
                        'isim' => 'كوش كلاسيكي',
                        'aciklama' => "تجمع موديلات الكوش الكلاسيكية بين جماليات بيت الريف التقليدي ومعايير المتانة المعاصرة. تخلق تفاصيل السقف المائل والدرابزين الزخرفي ونحارة الأخشاب لغة بصرية خالدة. يوفّر التكسية الخشبية الطبيعية أجواءً دافئة وجذابة تدوم طوال المواسم. تمنح التماثلات في الواجهات وال profiles النحيلة أناقة محسوبة.\n\nغالباً ما تُختار هذه الموديلات للعقارات التراثية ومقار السياحة الريفية والحدائق ذات التصميم الرسمي. تحمل أثر تقليد النجارة في العمارة الخشبية التركية؛ إذ تميّز التفاصيل اليدوية هذه المنتجات عن السلع الصناعية. توفّر معالجات الأخشاب عالية الجودة حماية من الرطوبة والأشعة فوق البنفسجية لعمر تشغيلي طويل. يحافظ الروتين السنوي على بَنية الأخشاب الطبيعية؛ أما عناصر التثبيت فمطليّة ضد التآكل.\n\nمقارنة بالنماذج العصرية والبسيطة، يوفّر الكوش الكلاسيكي طابعاً أكثر دفئاً وشخصية ويضيف عمقاً سردياً لحديقتكم. كما يبرز كاستثمار ذو قيمة تراثية، مناسب لمشاريع الترميم والمناطق المحمية. تصفّحوا موديلاتنا واطلبوا استشارة مجانية في الموقع.",
                        'slug' => 'classic-kush',
                        'seo_baslik' => 'كوش كلاسيكي: موديلات وأسعار 2026 | Kamelya',
                        'seo_aciklama' => 'كوش كلاسيكي: جماليات بيت الريف، خشب طبيعي، تفاصيل يدوية. تصميم خالد، عمر طويل، ضمان 5 سنوات. أسعار شفافة، استشارة مجانية.',
                    ],
                ],
            ],

            // =========================================================
            // MALZEME KATEGORİLERİ
            // =========================================================
            [
                'kod' => 'ahsap',
                'ceviriler' => [
                    'tr' => [
                        'isim' => 'Ahşap Kamelya',
                        'aciklama' => "Ahşap kamelya modelleri, doğal malzemenin sıcak dokusunu ve nefes alan yapısını bahçenize taşır. Kereste, ahşabın doğal damarlarını ve ton farklarını koruyarak her projeye özgün bir karakter kazandırır. Yüksek kaliteli çam, meşe veya sedir seçenekleri, iklim koşullarına göre seçilebilir. Yağmur akışını yöneten eğimli çatı ve ahşap oluk detayı, uzun ömürlü koruma sağlar.\n\nDoğal yalıtım özelliği sayesinde yazın serin, kışın korunaklı bir alan sunar. Ahşabın termal konforu, sentetik malzemelerin ötesinde bir yaşam deneyimi sağlar. Düzenli bakım — yılda bir kez koruyucu uygulama — ile onlarca yıl dayanır. Çürüme ve böcek hasarına karşı emprenye işlemi, uzun ömür garantisi verir; bağlantı elemanları paslanmaz çelik tercih edilir.\n\nAlüminyum ve kompozit alternatiflere kıyasla daha doğal bir görünüm sunan ahşap kamelya, peyzajla kusursuz uyum sağlar. Çevre dostu ve sürdürülebilir bir seçimdir; karbon ayak izi düşüktür, sertifikalı kereste kaynaklıdır. Ahşap kamelya modellerimizi inceleyip ücretsiz keşif talebinde bulunabilirsiniz.",
                        'slug' => 'ahsap-kamelya',
                        'seo_baslik' => 'Ahşap Kamelya Modelleri ve Fiyatları 2026 | Kamelya',
                        'seo_aciklama' => 'Ahşap kamelya modelleri: doğal kereste, çam meşe sedir, termal konfor. Emprenyeli, uzun ömürlü, 5 yıl garanti. m² şeffaf fiyat, ücretsiz keşif.',
                    ],
                    'en' => [
                        'isim' => 'Wooden Gazebo',
                        'aciklama' => "Wooden gazebo models bring the warm texture and breathable character of natural timber into your garden. Solid wood preserves grain patterns and tonal variations, giving every project a distinctive identity. High-quality pine, oak, or cedar options can be matched to local climate conditions. Pitched roofs and timber gutters manage rainwater and extend protective life.\n\nNatural insulation keeps the interior cool in summer and protected in winter, delivering a level of thermal comfort that synthetic materials cannot replicate. With annual treatment — one preservative application per year — the structure lasts for decades. Anti-rot and insect-resistant impregnation extends service life further; stainless-steel fixings resist corrosion.\n\nCompared with aluminium or composite alternatives, a wooden gazebo offers a more organic appearance that blends seamlessly with landscaping. It is also an environmentally responsible choice with a low carbon footprint, using responsibly sourced timber. Explore our wooden gazebo collection and request a free site survey.",
                        'slug' => 'wooden-gazebo',
                        'seo_baslik' => 'Wooden Gazebo Models & Prices 2026 | Kamelya',
                        'seo_aciklama' => 'Wooden gazebo models: natural timber, pine, oak, cedar, thermal comfort. Pressure-treated, long-lasting, 5-year warranty. Transparent m² pricing, free survey.',
                    ],
                    'de' => [
                        'isim' => 'Holzpavillon',
                        'aciklama' => "Holzpavillons übertragen die warme Textur und atmungsaktive Beschaffenheit von Naturholz in Ihren Garten. Massivholz bewahrt Faserzeichnung und Farbnuancen und verleiht jedem Projekt eine individuelle Identität. Hochwertige Kiefer-, Eichen- oder Zedernvarianten lassen sich an das lokale Klima anpassen. Geneigte Dächer und Holzrinnen leiten Regenwasser ab und verlängern den Schutz.\n\nNatürliche Dämmung hält den Innenraum im Sommer kühl und im Winter geschützt — ein thermischer Komfort, den synthetische Materialien nicht bieten. Mit jährlicher Imprägnierung hält die Konstruktion jahrzehntelang. Fäulnis- und insektensichere Behandlung verlängert die Nutzungsdauer zusätzlich; Edelstahlbeschläge sind korrosionsbeständig.\n\nGegenüber Aluminium- oder Kompositalternativen bietet ein Holzpavillon einen organischeren Anschluss an die Gartenlandschaft. Er ist zudem eine umweltbewusste Wahl mit geringem CO₂-Fußabdruck und zertifizierter Holzherkunft. Prüfen Sie unsere Holz-Modelle und fordern Sie einen kostenlosen Aufmaß-Termin an.",
                        'slug' => 'holzpavillon',
                        'seo_baslik' => 'Holzpavillon: Modelle & Preise 2026 | Kamelya',
                        'seo_aciklama' => 'Holzpavillons: Naturholz, Kiefer, Eiche, Zeder, thermischer Komfort. Imprägniert, langlebig, 5 Jahre Garantie. Transparente m²-Preise, kostenloser Aufmaß.',
                    ],
                    'fr' => [
                        'isim' => 'Gazebo en Bois',
                        'aciklama' => "Les gazebo en bois introduisent dans votre jardin la texture chaleureuse et la respiration du matériau naturel. Le bois massif conserve les veines et les nuances tonales, conférant à chaque projet une identité distinctive. Les essences de qualité — pin, chêne, cèdre — s'adaptent aux conditions climatiques locales. Les toitures inclinées et gouttières en bois évacuent la pluie et prolongent la protection.\n\nL'isolation naturelle maintient un intérieur frais en été et abrité en hiver, offrant un confort thermique qu'aucun matériau synthétique ne saurait égaler. Avec un traitement annuel — une application de préservatif par an — la structure dure des décennies. L'imprégnation anti-rot et anti-insectes prolonge encore la durée de vie ; les fixations en inox résistent à la corrosion.\n\nPar rapport aux alternatives en aluminium ou composite, le gazebo en bois propose une apparence plus organique qui se fond dans le paysage. C'est aussi un choix écologique à faible empreinte carbone, issu de bois sourcé de manière responsable. Découvrez notre collection et demandez une étude gratuite.",
                        'slug' => 'gazebo-en-bois',
                        'seo_baslik' => 'Gazebo en Bois: Modèles & Prix 2026 | Kamelya',
                        'seo_aciklama' => 'Gazebo en bois : bois naturel, pin, chêne, cèdre, confort thermique. Imprégné, durable, garantie 5 ans. Prix au m² transparents, étude gratuite.',
                    ],
                    'it' => [
                        'isim' => 'Gazebo in Legno',
                        'aciklama' => "I gazebo in legno portano nel giardino la texture calda e traspirante del materiale naturale. Il legno massello conserva le venature e le sfumature cromatiche, donando a ogni progetto un'identità distintiva. Le essenze di qualità — pino, rovere, cedro — si adattano alle condizioni climatiche locali. Tetti inclinati e gronda in legno gestiscono l'acqua piovana e prolungano la protezione.\n\nL'isolamento naturale mantiene l'interno fresco in estate e protetto in inverno, offrendo un comfort termico che i materiali sintetici non possono eguagliare. Con un trattamento annuale — una sola applicazione di preservativo all'anno — la struttura dura decenni. L'imbevimento anti-putrefazione e anti-insetti prolunga ulteriormente la vita utile; le ferramenta in acciaio inox resistono alla corrosione.\n\nRispetto alle alternative in alluminio o composito, il gazebo in legno offre un aspetto più organico che si fonde con il paesaggio. È anche una scelta ecologica a bassa impronta di carbonio, con legno di provenienza responsabile. Esplora la nostra collezione e richiedi un sopralluogo gratuito.",
                        'slug' => 'gazebo-in-legno',
                        'seo_baslik' => 'Gazebo in Legno: Modelli e Prezzi 2026 | Kamelya',
                        'seo_aciklama' => 'Gazebo in legno: legno naturale, pino, rovere, cedro, comfort termico. Impregnato, durevole, garanzia 5 anni. Prezzi al m² trasparenti, sopralluogo gratuito.',
                    ],
                    'ar' => [
                        'isim' => 'كوش خشبي',
                        'aciklama' => "تُضيف موديلات الكوش الخشبية ملمس الأخشاب الطبيعي وطبيعتها المتنفسة إلى حديقتكم. يحفظ الأخشاب الصلبة التعرّجات والألوان المختلفة، مما يمنح كل مشروع هوية مميزة. تتوفر خيارات الصنوبر والبلوط والأرز الجبلي وفق ظروف المناخ المحلية. توجّه الأسقف المائلة ومزاريب الخشب مياه الأمطار لتمديد الحماية.\n\nيوفّر العزل الطبيعي برودة في الصيف وحماية في الشتاء، مقدّماً راحة حرارية لا تضاهيها المواد الاصطناعية. مع معالجة سنوية واحدة فقط، تدوم الهياكل عقوداً. يزيد المعالج ضد التحلل والحشرات من عمر التشغيل؛ وعناصر التثبيت من الفولاذ المقاوم للصدأ تقاوم التآكل.\n\nمقارنة ببدائل الألمنيوم والمواد المركّبة، يوفّر الكوش الخشبي مظهراً أكثر عضوية يندمج مع المنظر الطبيعي. وهو أيضاً خيار صديق للبيئة بصمة كربونية منخفضة بخشب من مصادر مسؤولة. تصفّحوا موديلاتنا واطلبوا استشارة مجانية.",
                        'slug' => 'wooden-kush',
                        'seo_baslik' => 'كوش خشبي: موديلات وأسعار 2026 | Kamelya',
                        'seo_aciklama' => 'كوش خشبي: خشب طبيعي، صنوبر، بلوط، أرز، راحة حرارية. معالَج، طويل العمر، ضمان 5 سنوات. أسعار شفافة، استشارة مجانية.',
                    ],
                ],
            ],
            [
                'kod' => 'aluminyum',
                'ceviriler' => [
                    'tr' => [
                        'isim' => 'Alüminyum Kamelya',
                        'aciklama' => "Alüminyum kamelya modelleri, hafif dayanıklı alaşım yapısıyla modern dış mekân yaşamının standartlarını belirler. Korozyona dirençli yüzey, boya gerektirmez ve yıllarca ilk günkü görünümünü korur. Toz boya kaplama, geniş renk seçenekleri sunar ve UV ışınlarına karşı solmaya dayanıklıdır. Kayar paneller ve gizli menteşe detayları, temiz bir siluet sağlar.\n\nYoğun kullanım gerektiren ticari alanlar — restoran terasları, otel bahçeleri, plaza avluları — için idealdir. Bakım gereksinimi neredeyse sıfırdır; yıkanması yeterlidir. Prefabrik panel sistemi, hızlı montaj ve sökme imkânı sağlar; mevsimsel kullanım için uygundur. Statik hesapları EN 13561 standardına uygundur; rüzgâr yükü testleri belgelidir.\n\nAhşap ve kompozit alternatiflere kıyasla daha uzun ömürlü ve daha az bakım gerektiren alüminyum kamelya, toplam sahip olma maliyetini düşürür. Korozyon direnci, sahil ve nemli bölgelerde ek avantaj sağlar; tuzlu suya dayanıklı alaşım seçenekleri mevcuttur. Alüminyum kamelya modellerimizi inceleyip ücretsiz keşif talebinde bulunabilirsiniz.",
                        'slug' => 'aluminyum-kamelya',
                        'seo_baslik' => 'Alüminyum Kamelya Modelleri ve Fiyatları 2026 | Kamelya',
                        'seo_aciklama' => 'Alüminyum kamelya modelleri: hafif alaşım, korozyon dirençli, boyasız, toz boya renk seçenekleri. Sıfır bakım, EN 13561 uyumlu, 5 yıl garanti. m² şeffaf fiyat.',
                    ],
                    'en' => [
                        'isim' => 'Aluminium Gazebo',
                        'aciklama' => "Aluminium gazebo models set the standard for modern outdoor living with their lightweight, durable alloy construction. The corrosion-resistant surface requires no painting and retains its original finish for years. Powder-coating provides a wide colour palette and resists UV fading. Sliding panels and concealed hinge details deliver a clean silhouette.\n\nThey are especially suited to high-traffic commercial settings — restaurant terraces, hotel gardens, plaza courtyards. Maintenance is essentially zero; a simple wash is enough. A prefabricated panel system enables fast installation and seasonal dismantling. Structural calculations comply with EN 13561, with certified wind-load testing.\n\nCompared with timber or composite alternatives, an aluminium gazebo lasts longer and demands less upkeep, lowering total cost of ownership. Corrosion resistance is a further advantage in coastal and humid regions; marine-grade alloy options are available. Explore our aluminium gazebo collection and request a free site survey.",
                        'slug' => 'aluminium-gazebo',
                        'seo_baslik' => 'Aluminium Gazebo Models & Prices 2026 | Kamelya',
                        'seo_aciklama' => 'Aluminium gazebo models: lightweight alloy, corrosion-resistant, maintenance-free, powder-coated colours. EN 13561 compliant, 5-year warranty. Transparent m² pricing, free survey.',
                    ],
                    'de' => [
                        'isim' => 'Aluminium-Pavillon',
                        'aciklama' => "Aluminium-Pavillons definieren mit ihrer leichten, dauerhaften Legierungskonstruktion den Maßstab für modernes Outdoor-Living. Die korrosionsbeständige Oberfläche benötigt keine Lackierung und behält über Jahre den Erstzustand. Pulverbeschichtung bietet ein breites Farbspektrum und ist UV-beständig. Schiebetürpaneele und verdeckte Bänderdetails sorgen für eine klare Silhouette.\n\nBesonders geeignet für frequentierte Gewerbebereiche — Restaurantterrassen, Hotelparkanlagen, Platzhöfe. Der Pflegeaufwand liegt nahe bei null; ein einfaches Abwischen genügt. Ein vorgefertigtes Panelsystem ermöglicht schnelle Montage und saisonalen Abbau. Statische Nachweise entsprechen EN 13561; Windlastprüfungen sind zertifiziert.\n\nGegenüber Holz- oder Kompositalternativen hält ein Aluminium-Pavillon länger, erfordert weniger Pflege und senkt die Gesamtbetriebskosten. Korrosionsbeständigkeit bietet zusätzlichen Nutzen an Küsten und in feuchten Regionen; salzbeständige Legierungen sind verfügbar. Prüfen Sie unsere Aluminium-Modelle und fordern Sie einen kostenlosen Aufmaß-Termin an.",
                        'slug' => 'aluminium-pavillon',
                        'seo_baslik' => 'Aluminium-Pavillon: Modelle & Preise 2026 | Kamelya',
                        'seo_aciklama' => 'Aluminium-Pavillons: leichte Legierung, korrosionsbeständig, pflegefrei, Pulverbeschichtung. EN 13561-konform, 5 Jahre Garantie. Transparente m²-Preise.',
                    ],
                    'fr' => [
                        'isim' => 'Gazebo en Aluminium',
                        'aciklama' => "Les gazebo en aluminium fixent le standard du vieillissement extérieur contemporain grâce à leur structure alliage légère et durable. La surface résistante à la corrosion ne nécessite aucune peinture et conserve son aspect d'origine pendant des années. La thermolaquage offre une large palette de couleurs résistante aux UV. Panneaux coulissants et ferrures dissimulées offrent une silhouette épurée.\n\nIls conviennent tout particulièrement aux espaces commerciaux à fort passage — terrasses de restaurant, jardins d'hôtel, cours de plaza. L'entretien est quasi nul ; un simple rinçage suffit. Un système de panneaux préfabriqués permet une installation rapide et un démontage saisonnier. Les calculs structurels respectent la norme EN 13561, avec essais de charge au vent certifiés.\n\nPar rapport aux alternatives en bois ou composite, le gazebo en aluminium offre une durée de vie supérieure et un entretien réduit, maîtrisant le coût total de possession. Sa résistance à la corrosion constitue un atout supplémentaire en zones côtières et humides ; des alliages marins sont disponibles. Découvrez notre collection et demandez une étude gratuite.",
                        'slug' => 'gazebo-en-aluminium',
                        'seo_baslik' => 'Gazebo en Aluminium: Modèles & Prix 2026 | Kamelya',
                        'seo_aciklama' => 'Gazebo en aluminium : alliage léger, résistant à la corrosion, sans entretien, thermolaquage. Conforme EN 13561, garantie 5 ans. Prix au m² transparents.',
                    ],
                    'it' => [
                        'isim' => 'Gazebo in Alluminio',
                        'aciklama' => "I gazebo in alluminio fissano lo standard del vivere outdoor contemporaneo grazie alla loro leggera e duratura struttura in lega. La superficie resistente alla corrosione non richiede verniciatura e conserva l'aspetto originale per anni. La verniciatura a polvere offre un'ampia palette di colori resistente ai raggi UV. Pannelli scorrevoli e cerniere nascoste garantiscono una silhouette pulita.\n\nSi adattano in modo ideale ai contesti commerciali ad alto traffico — terrazze dei ristoranti, giardini degli hotel, cortili delle piazze. La manutenzione è praticamente nulla; è sufficiente un semplice risciacquo. Un sistema di pannelli prefabbricati consente installazione rapida e smontaggio stagionale. I calcoli strutturali rispettano la norma EN 13561, con prove di carico al vento certificate.\n\nRispetto alle alternative in legno o composito, il gazebo in alluminio dura di più e richiede meno manutenzione, riducendo il costo totale di proprietà. La resistenza alla corrosione rappresenta un vantaggio aggiuntivo in zone costiere e umide; leghe marine sono disponibili. Esplora la nostra collezione e richiedi un sopralluogo gratuito.",
                        'slug' => 'gazebo-in-alluminio',
                        'seo_baslik' => 'Gazebo in Alluminio: Modelli e Prezzi 2026 | Kamelya',
                        'seo_aciklama' => 'Gazebo in alluminio: lega leggera, resistente alla corrosione, senza manutenzione, verniciatura a polvere. Conforme EN 13561, garanzia 5 anni. Prezzi al m².',
                    ],
                    'ar' => [
                        'isim' => 'كوش ألمنيوم',
                        'aciklama' => "تحدد موديلات الكوش الألمنيوم معايير الحياة الخارجية العصرية بفضل بنيتها الخفيفة وطويلة العمر. لا تحتاج السطح المقاوم للتآكل إلى طلاء ويحافظ على مظهره الأصلي لسنوات. توفر الطلاء البودري لوحة ألوان واسعة مقاومة لأشعة فوق البنفسجية. الألواح المنزلقة وتفاصيل المفصلات المخفية تمنح شكلاً نظيفاً.\n\nإنها مثالية للاستخدام التجاري المكثف — تراسات المطاعم وحدائق الفنادق وصحون المباني. الصيانة شبه معدومة؛ يكفي غسلها. يتيح نظام الألواح المسبقة الصنع تركيباً سريعاً وفكاً موسمياً. تتوافق الحسابات الإنشائية مع المعيار EN 13561، مع اختبارات حمل رياح معتمدة.\n\nمقارنة ببدائل الأخشاب والمواد المركّبة، يدوم الكوش الألمنيوم أطول ويقلل الصيانة، مؤدياً لخفض تكلفة التملّك الإجمالية. يضيف مقاومة التآكل قيمة إضافية في المناطق الساحلية والرطبة؛ وتتوفر سبائك مقاومة للملوحة. تصفّحوا موديلاتنا واطلبوا استشارة مجانية.",
                        'slug' => 'aluminium-kush',
                        'seo_baslik' => 'كوش ألمنيوم: موديلات وأسعار 2026 | Kamelya',
                        'seo_aciklama' => 'كوش ألمنيوم: سبيكة خفيفة، مقاومة للتآكل، بلا صيانة، طلاء بودري. متوافق مع EN 13561، ضمان 5 سنوات. أسعار شفافة للمتر مربع.',
                    ],
                ],
            ],
            [
                'kod' => 'kompozit',
                'ceviriler' => [
                    'tr' => [
                        'isim' => 'Kompozit Kamelya',
                        'aciklama' => "Kompozit kamelya modelleri, ahşabın doğal görünümünü polimer dayanıklılığıyla birleştiren yenilikçi bir malzeme çözümü sunar. Ahşap-plastik kompozit (WPC) yüzey, çürüme, böcek ve nem hasarına karşı tam direnç sağlar. Ahşap dokusunu taklit ederken bakım gerektirmez; rengi zamanla solmaz. Pürüzlü zemin dokusu, ıslak hava koşullarında kaymaz tutuş sağlar.\n\nYoğun nemli bölgeler, sahil şeritleri ve havuz kenarları için idealdir; suyla doğrudan temas sorun oluşturmaz. UV stabilitesi, renk solmasını minimize eder. Kaymaz yüzey dokusu, ıslak zeminlerde güvenlik sağlar. Montajı vidalı sistem sayesinde hızlıdır; sökme ve yeniden kurulum kolaydır. Kenar profilleri, kesit görünümünü gizleyerek temiz bir bitiş sağlar.\n\nAhşaba kıyasla daha az bakım, alüminyuma kıyasla daha doğal görünüm sunan kompozit kamelya, arada bir konumlanır. Uzun vadede bakım maliyeti sıfıra yakındır; deterjanlı su ile temizlenir. Kompozit kamelya modellerimizi inceleyip ücretsiz keşif talebinde bulunabilirsiniz.",
                        'slug' => 'kompozit-kamelya',
                        'seo_baslik' => 'Kompozit Kamelya Modelleri ve Fiyatları 2026 | Kamelya',
                        'seo_aciklama' => 'Kompozit kamelya modelleri: ahşap-plastik kompozit WPC, çürüme dirençli, kaymaz yüzey, UV stabil. Bakım gerektirmez, 5 yıl garanti. m² şeffaf fiyat, ücretsiz keşif.',
                    ],
                    'en' => [
                        'isim' => 'Composite Gazebo',
                        'aciklama' => "Composite gazebo models offer an innovative material solution that merges the natural look of timber with polymer durability. Wood-plastic composite (WPC) surfaces resist rot, insects, and moisture damage entirely. They mimic wood grain while eliminating the need for maintenance; colour holds without fading. A textured floor provides slip-resistant grip in wet weather.\n\nThese structures suit high-humidity regions, coastal strips, and poolside settings; direct water contact poses no problem. UV stabilisation minimises colour fading. A slip-resistant texture adds safety on wet surfaces. Screw-fix installation is fast, and dismantling for seasonal storage is straightforward. Edge profiles hide cut sections for a clean finish.\n\nCompared with timber, composite requires far less upkeep; compared with aluminium, it looks more natural. A composite gazebo occupies the practical middle ground with near-zero long-term maintenance cost, cleaning easily with soapy water. Explore our composite gazebo collection and request a free site survey.",
                        'slug' => 'composite-gazebo',
                        'seo_baslik' => 'Composite Gazebo Models & Prices 2026 | Kamelya',
                        'seo_aciklama' => 'Composite gazebo models: wood-plastic composite WPC, rot-resistant, slip-resistant, UV-stable. Maintenance-free, 5-year warranty. Transparent m² pricing, free survey.',
                    ],
                    'de' => [
                        'isim' => 'Komposit-Pavillon',
                        'aciklama' => "Komposit-Pavillons bieten eine innovative Materiallösung, die den natürlichen Holzlook mit Polymer-Dauerhaftigkeit verbindet. Holz-Kunststoff-Verbund (WPC)-Oberflächen widerstehen Fäulnis, Insekten und Feuchtigkeit vollständig. Sie imitieren die Holzmaserung und entfallen auf Pflege; die Farbe bleicht nicht aus. Eine strukturierte Bodenfläche bietet rutschfeste Sicherheit bei Nässe.\n\nBesonders geeignet für feuchte Regionen, Küstenstreifen und Poolbereiche; direkter Wasserkontakt stellt kein Problem dar. UV-Stabilisierung minimiert das Ausbleiben der Farbe. Eine rutschfeste Oberflächentextur erhöht die Sicherheit auf nassen Böden. Die Schraubmontage ist schnell; Demontage für die Zwischenlagerung unkompliziert. Kantenprofile verdecken Schnittkanten für einen sauberen Abschluss.\n\nGegenüber Holz benötigt Komposit deutlich weniger Pflege; gegenüber Aluminium wirkt es natürlicher. Ein Komposit-Pavillon besetzt die pragmatische Mittelposition mit nahezu null Wartungskosten über die Lebensdauer und lässt sich mit Seifenwasser reinigen. Prüfen Sie unsere Komposit-Modelle und fordern Sie einen kostenlosen Aufmaß-Termin an.",
                        'slug' => 'komposit-pavillon',
                        'seo_baslik' => 'Komposit-Pavillon: Modelle & Preise 2026 | Kamelya',
                        'seo_aciklama' => 'Komposit-Pavillons: Holz-Kunststoff-Verbund WPC, faulnisbeständig, rutschfest, UV-stabil. Pflegefrei, 5 Jahre Garantie. Transparente m²-Preise.',
                    ],
                    'fr' => [
                        'isim' => 'Gazebo Composite',
                        'aciklama' => "Les gazebo composite proposent une solution matérielle innovante qui marie l'apparence naturelle du bois à la durabilité des polymères. Les surfaces bois-plastique (WPC) résistent intégralement à la pourriture, aux insectes et à l'humidité. Elles imitent le grain du bois sans exiger d'entretien ; la teinte ne se dégrade pas. Un sol texturé offre une adhérence antidérapante par temps humide.\n\nIls conviennent aux régions humides, aux lisières côtières et aux abords de piscine ; le contact direct avec l'eau ne pose aucun problème. La stabilisation UV minimise la décoloration. Une texture antidérapante renforce la sécurité sur les sols mouillés. Le montage par vis est rapide, le démontage pour stockage saisonnier aisé. Des profils de bordure masquent les coupes pour une finition nette.\n\nPar rapport au bois, le composite requiert beaucoup moins d'entretien ; par rapport à l'aluminium, il offre un aspect plus naturel. Le gazebo composite occupe une position intermédiaire pragmatique avec un coût d'entretien quasi nul, nettoyable à l'eau savonneuse. Découvrez notre collection et demandez une étude gratuite.",
                        'slug' => 'gazebo-composite',
                        'seo_baslik' => 'Gazebo Composite: Modèles & Prix 2026 | Kamelya',
                        'seo_aciklama' => 'Gazebo composite : bois-plastique WPC, résistant à la pourriture, antidérapant, stabilisé UV. Sans entretien, garantie 5 ans. Prix au m² transparents.',
                    ],
                    'it' => [
                        'isim' => 'Gazebo Composito',
                        'aciklama' => "I gazebo compositi offrono una soluzione materiale innovativa che fonde l'aspetto naturale del legno alla durabilità dei polimeri. Le superfici in legno-plastica (WPC) resistono a marcescenza, insetti e umidità. Imitano la venatura del legno eliminando ogni necessità di manutenzione; il colore non sbiadisce. Un pavimento testurizzato garantisce presa antiscivolo al bagnato.\n\nSi adattano alle regioni umide, alle fasce costiere e alle aree piscina; il contatto diretto con l'acqua non crea problemi. La stabilizzazione UV minimizza lo sbiadimento del colore. Una texture antiscivolo aumenta la sicurezza sui pavimenti bagnati. Il montaggio a vite è rapido e lo smontaggio per stoccaggio stagionale è semplice. Profili di bordo nascondono i tagli per una finitura pulita.\n\nRispetto al legno, il composito richiede molta meno manutenzione; rispetto all'alluminio, ha un aspetto più naturale. Il gazebo composito occupa una posizione intermedia pragmatica con costi di manutenzione prossimi allo zero, lavabile con acqua saponata. Esplora la nostra collezione e richiedi un sopralluogo gratuito.",
                        'slug' => 'gazebo-composito',
                        'seo_baslik' => 'Gazebo Composito: Modelli e Prezzi 2026 | Kamelya',
                        'seo_aciklama' => 'Gazebo composito: legno-plastica WPC, resistente a marcescenza, antiscivolo, stabilizzato UV. Senza manutenzione, garanzia 5 anni. Prezzi al m².',
                    ],
                    'ar' => [
                        'isim' => 'كوش مركّب',
                        'aciklama' => "توفّر موديلات الكوش المركّبة حلاً مادياً مبتكراً يجمع بين مظهر الأخشاب الطبيعي ومتانة بوليمر. تقاوم أسطح الخشب والبلاستيك المركّب (WPC) التحلل والحشرات والرطوبة تماماً. تحاكي عروق الأخشاب دون الحاجة إلى صيانة؛ ولا يبهت لونها مع الوقت. يوفّر الأرضيات الملمسة تمسكاً مانعاً للانزلاق في الطقس الرطب.\n\nإنها مثالية للمناطق الرطبة والأشرطة الساحلية ومحيط المسابح؛ إذ لا يشكّل التلامس المباشر مع الماء أي مشكلة. تقلّل الاستقرار فوق البنفسجي من بهتان الألوان. تضمن الملمس المانع للانزلاق الأمان على الأسطح المبللة. التركيب بالمسامير سريع، والفك لتخزين موسمي ميسّر. تخفي الملامح الجانبية قطع الأطراف لإنهاء نظيف.\n\nمقارنة بالأخشاب، تتطلب الصيانة أقل بكثير؛ ومقارنة بالألمنيوم، تبدو أكثر طبيعية. يشغل الكوش المركّب موضعاً عملياً وسيطاً بصيانة شبه معدومة على المدى الطويل، ويُنظَّف بماء وصابون. تصفّحوا موديلاتنا واطلبوا استشارة مجانية.",
                        'slug' => 'composite-kush',
                        'seo_baslik' => 'كوش مركّب: موديلات وأسعار 2026 | Kamelya',
                        'seo_aciklama' => 'كوش مركّب: خشب وبلاستيك WPC، مقاوم للتحلل، مانع للانزلاق، مستقر فوق البنفسجي. بلا صيانة، ضمان 5 سنوات. أسعار شفافة، استشارة مجانية.',
                    ],
                ],
            ],

            // =========================================================
            // KULLANIM AMACI KATEGORİLERİ
            // =========================================================
            [
                'kod' => 'site_bahcesi',
                'ceviriler' => [
                    'tr' => [
                        'isim' => 'Site Bahçesi Kamelyaları',
                        'aciklama' => "Site bahçesi kamelyaları, apartman ve site sakinlerinin ortak kullanım alanları için özel olarak tasarlanmış dayanıklı çözümler sunar. Geniş oturma düzeni, çocuklu ailelerin ve yaşlı sakinlerin aynı anda rahatça kullanabileceği ergonomiye sahiptir. Güvenlik standartlarına uygun korkuluk yüksekliği ve kaymaz zemin, günlük kullanım için kritik öneme sahiptir. Giriş rampası ve tekerlekli sandalye açıklığı, erişilebilirliği tamamlar.\n\nOrtak alan yönetimi, düşük bakım maliyeti ve uzun ömür talep eder. Alüminyum ve kompozit seçenekleri, yoğun kullanımda yıpranmaya dayanır; ahşap seçenekleri ise doğal görünümü korurken emprenye ile güçlendirilmiştir. Peyzajla uyumlu renk seçenekleri, sitenin genel mimarisine entegre olur. Sessiz tasarım, komşuları rahatsız etmeden gölge sağlar; aydınlatma entegrasyonları akşam kullanımını güvenli kılar.\n\nÖzel bahçe kamelyalarına kıyasla daha geniş oturma kapasitesi ve toplu kullanım için optimize edilmiş yerleşim sunan site bahçesi kamelyaları, yönetim kurulunun bütçe planlamasına da uygundur. m² bazlı şeffaf fiyatlandırma sayesinde teklif süreci hızlanır. Dayanıklı malzeme seçimi, işletme giderlerini yıllar boyunca düşürür. Ücretsiz keşif için hemen başvurun.",
                        'slug' => 'site-bahcesi-kamelyalari',
                        'seo_baslik' => 'Site Bahçesi Kamelyaları ve Fiyatları 2026 | Kamelya',
                        'seo_aciklama' => 'Site bahçesi kamelyaları: ortak alan için dayanıklı, güvenli korkuluk, kaymaz zemin, düşük bakım. Toplu kullanım, m² şeffaf fiyat, ücretsiz keşif, 5 yıl garanti.',
                    ],
                    'en' => [
                        'isim' => 'Residential Complex Gazebos',
                        'aciklama' => "Residential complex gazebos are purpose-built for shared outdoor areas in apartment communities and gated compounds. The generous seating layout accommodates families with children and elderly residents comfortably at the same time. Balcony-height safety railings and slip-resistant flooring are critical for everyday communal use. Entrance ramps and wheelchair clearances complete accessibility.\n\nShared-area management demands low maintenance cost and long service life. Aluminium and composite options withstand heavy daily traffic, while timber variants are impregnated for strength without sacrificing natural appearance. Colour palettes integrate with the site's overall architecture. Quiet design provides shade without disturbing neighbours; lighting integrations make evening use safe.\n\nCompared with private garden gazebos, residential complex models offer greater seating capacity and layouts optimised for communal use — a practical fit for board budgeting. Transparent per-square-metre pricing accelerates the quotation process. Durable material selection keeps operating costs low for years. Request a free site survey today.",
                        'slug' => 'residential-complex-gazebos',
                        'seo_baslik' => 'Residential Complex Gazebos & Prices 2026 | Kamelya',
                        'seo_aciklama' => 'Residential complex gazebos: durable communal areas, safety railings, slip-resistant floors, low maintenance. High capacity, transparent m² pricing, free survey, 5-year warranty.',
                    ],
                    'de' => [
                        'isim' => 'Wohnanlagen-Pavillons',
                        'aciklama' => "Wohnanlagen-Pavillons sind speziell für gemeinschaftliche Außenflächen in Mehrfamilienhäusern und Wohnparks konzipiert. Die großzügige Sitzordnung beherbergt Familien mit Kindern und ältere Bewohner gleichermaßen ergonomisch. Geländerhöhe nach Sicherheitsnorm und rutschfester Boden sind für den täglichen Gemeinschaftsbetrieb unverzichtbar. Zugangsrampen und Rollstuhlfreiräume vervollständigen die Barrierefreiheit.\n\nGemeinschaftsverwaltung verlangt geringe Betriebskosten und lange Lebensdauer. Aluminium- und Kompositvarianten widerstehen intensiver Nutzung; Holzvarianten sind imprägniert und behalten ihren natürlichen Look. Farbpaletten fügen sich in die Gesamtarchitektur ein. Die leise Konstruktion spendet Schatten, ohne die Nachbarschaft zu stören; Beleuchtungsintegrationen sichern den Abendbetrieb.\n\nGegenüber privaten Gartenpavillons bieten Wohnanlagen-Modelle mehr Sitzplatzkapazität und für den Gemeinschaftsbetrieb optimierte Grundrisse — passend zur Budgetplanung der Hausverwaltung. Transparente Quadratmeterpreise beschleunigen das Angebotswesen. Robuste Materialwahl hält die Betriebskosten über Jahre niedrig. Fordern Sie jetzt einen kostenlosen Aufmaß-Termin an.",
                        'slug' => 'wohnanlagen-pavillon',
                        'seo_baslik' => 'Wohnanlagen-Pavillons: Modelle & Preise 2026 | Kamelya',
                        'seo_aciklama' => 'Wohnanlagen-Pavillons: robuste Gemeinschaftsflächen, Sicherheitsgeländer, rutschfester Boden, geringe Pflege. Hohe Kapazität, transparente m²-Preise, kostenloser Aufmaß.',
                    ],
                    'fr' => [
                        'isim' => 'Gazebos pour Copropriétés',
                        'aciklama' => "Les gazebo pour copropriétés sont conçus pour les espaces extérieurs partagés d'immeubles résidentiels et de résidences fermées. La disposition généreuse accueille familles avec enfants et résidents âgés en toute ergonomie. Garde-corps à hauteur réglementaire et sols antidérapants sont essentiels à l'usage quotidien collectif. Rampes d'accès et passages pour fauteuils complètent l'accessibilité.\n\nLa gestion commune exige des coûts d'entretien faibles et une longévité élevée. Les options aluminium et composite résistent à l'intensité d'usage, tandis que les versions bois sont imprégnées sans perdre leur aspect naturel. Les palettes de couleurs s'intègrent à l'architecture globale. Un design discret procure de l'ombre sans déranger les voisins ; les intégrations d'éclairage sécurisent l'usage nocturne.\n\nPar rapport aux gazebo de jardin privés, les modèles pour copropriétés offrent une capacité supérieure et des implantations optimisées pour l'usage collectif — adaptés au budget du conseil syndical. La tarification au mètre carré accélère le processus de devis. Le choix de matériaux durables maintient bas les coûts d'exploitation. Demandez une étude gratuite dès maintenant.",
                        'slug' => 'gazebo-copropriete',
                        'seo_baslik' => 'Gazebos pour Copropriétés: Prix 2026 | Kamelya',
                        'seo_aciklama' => 'Gazebos pour copropriétés : espaces partagés durables, garde-corps sécuritaires, sols antidérapants, entretien réduit. Grande capacité, prix au m², étude gratuite.',
                    ],
                    'it' => [
                        'isim' => 'Gazebo per Condomini',
                        'aciklama' => "I gazebo per condomini sono progettati per gli spazi esterni condivisi in condomini e residenze recintate. La disposizione generosa accoglie famiglie con bambini e residenti anziani in ergonomicità. Parapetti a norma e pavimenti antiscivolo sono essenziali per l'uso quotidiano collettivo. Rampe di accesso e passaggi per sedie a rotelle completano l'accessibilità.\n\nLa gestione condominiale richiede costi di manutenzione ridotti e lunga durata. Le opzioni in alluminio e composito resistono all'intensità d'uso, mentre le versioni in legno sono impregnate senza perdere l'aspetto naturale. Le palette cromatiche si integrano nell'architettura complessiva. Un design discreto fornisce ombra senza disturbare i vicini; le integrazioni illuminanti rendono sicuro l'uso serale.\n\nRispetto ai gazebo da giardino privato, i modelli per condomini offrono capienza superiore e disposizioni ottimizzate per l'uso collettivo — adatte al budget del consiglio di amministrazione. La tariffazione al metro quadro accelera il processo di preventivo. La scelta di materiali durevoli mantiene bassi i costi operativi per anni. Richiedi un sopralluogo gratuito.",
                        'slug' => 'gazebo-condomini',
                        'seo_baslik' => 'Gazebo per Condomini: Modelli e Prezzi 2026 | Kamelya',
                        'seo_aciklama' => 'Gazebo per condomini: spazi condivisi durevoli, parapetti sicuri, pavimenti antiscivolo, manutenzione ridotta. Alta capienza, prezzi al m², sopralluogo gratuito.',
                    ],
                    'ar' => [
                        'isim' => 'كوش حدائق المجمعات السكنية',
                        'aciklama' => "تُصمَّم كوش حدائق المجمعات السكنية خصيصاً للأماكن المشتركة في المجمعات السكنية والأبراج السكنية. تستوعب تخطيطاتها الواسعة العائلات مع الأطفال وكبار السن في آنٍ واحد بمرونة. يُعدّ الدرابزين بارتفاع الأمان والأرضيات المانعة للانزلاق ضروريين للاستخدام اليومي الجماعي. منحدرات الدخول ومساحات الكراسي المتحركة تكمل سهولة الوصول.\n\nتتطلب الإدارة المشتركة تكاليف صيانة منخفضة وعمر تشغيلي طويلاً. تقاوم خيارات الألمنيوم والمواد المركّبة الاستخدام المكثّف، بينما تُعزَّز نسخ الأخشاب بالمعالجة دون فقدان مظهرها الطبيعي. تندمج لوحات الألوان مع العمارة العامة للمنشأة. يوفّر التصميم الهادئ ظلاً دون إزعاج الجيران؛ وتكاملات الإضاءة تجعل الاستخدام الليلي آمناً.\n\nمقارنة بكوش الحدائق الخاصة، تمنح موديلات المجمعات السكنية سعة جلوس أكبر وتخطيطات محسّنة للاستخدام الجماعي — مناسبة لميزانية لجنة الإدارة. تسرّع الأسعار الشفافة للمتر مربع عملية عرض التسعير. اختيار المواد المتينة يبقي تكاليف التشغيل منخفضة لسنوات. اطلبوا استشارة مجانية الآن.",
                        'slug' => 'residential-kush',
                        'seo_baslik' => 'كوش حدائق المجمعات السكنية: أسعار 2026 | Kamelya',
                        'seo_aciklama' => 'كوش المجمعات السكنية: أماكن مشتركة متينة، درابزين آمن، أرضيات مانعة للانزلاق، صيانة منخفضة. سعة عالية، أسعار شفافة للمتر مربع، استشارة مجانية.',
                    ],
                ],
            ],
            [
                'kod' => 'restoran',
                'ceviriler' => [
                    'tr' => [
                        'isim' => 'Restoran Kamelyaları',
                        'aciklama' => "Restoran kamelyaları, dış mekân oturma kapasitesini dört mevsim kullanılabilir hale getirmek için tasarlanmış ticari sınıf çözümlerdir. Geniş açıklıklar, servis personelinin hareket kolaylığını sağlarken misafir mahremiyetini korur. Yağmur ve rüzgâr koruması, sezon dışında da gelir kaybını önler. Yan paneller ve perde seçenekleri, hava koşullarına göre hızlı uyum sağlar.\n\nRestoran sahipleri için kritik faktör, yatırımın geri dönüş süresidir. Ek oturma alanı, masa devir hızını artırır ve mevsimsel gelir dalgalanmalarını dengeler. Alüminyum ve kompozit seçenekleri, yoğun temizlik ve kimyasal maruziyete dayanıklıdır. Aydınlatma ve ısıtma entegrasyon seçenekleri, kullanım saatlerini uzatır. Hızlı montaj, sezon öncesi hazır olma garantisi verir; modüler panel yapısı, yeniden düzenlemeye izin verir.\n\nÖzel kullanım kamelyalarına kıyasla daha yüksek kapasite ve ticari dayanıklılık sunan restoran kamelyaları, işletme bütçesine uygun m² fiyatlandırmasıyla da avantaj sağlar. Su yalıtımı ve drenaj detayları, sürekli dış mekân kullanımını destekler. Menü genişletme ve dış mekân deneyimi için ideal bir yatırımdır. Ücretsiz keşif ve teklif için hemen iletişime geçin.",
                        'slug' => 'restoran-kamelyalari',
                        'seo_baslik' => 'Restoran Kamelyaları ve Fiyatları 2026 | Kamelya',
                        'seo_aciklama' => 'Restoran kamelyaları: ticari sınıf, dört mevsim kullanım, yüksek kapasite, yağmur rüzgâr koruması. Hızlı montaj, m² şeffaf fiyat, ücretsiz keşif, 5 yıl garanti.',
                    ],
                    'en' => [
                        'isim' => 'Restaurant Gazebos',
                        'aciklama' => "Restaurant gazebos are commercial-grade structures engineered to make outdoor seating usable across all four seasons. Wide openings preserve staff movement while maintaining guest privacy. Rain and wind protection prevents revenue loss during shoulder seasons. Side panels and curtain options allow quick adaptation to weather.\n\nFor restaurateurs, the critical factor is payback period. Additional seating increases table turnover and smooths seasonal revenue fluctuations. Aluminium and composite options withstand frequent cleaning and chemical exposure. Lighting and heating integrations extend operating hours. Fast installation guarantees readiness before peak season; modular panel layouts allow reconfiguration.\n\nCompared with residential models, restaurant gazebos deliver higher capacity and commercial durability — advantages reinforced by transparent per-square-metre pricing that fits operating budgets. Waterproofing and drainage details support continuous outdoor use. They represent a sound investment for expanding menus and enhancing the outdoor dining experience. Contact us for a free site survey and quotation.",
                        'slug' => 'restaurant-gazebos',
                        'seo_baslik' => 'Restaurant Gazebos & Prices 2026 | Kamelya',
                        'seo_aciklama' => 'Restaurant gazebos: commercial grade, four-season use, high capacity, rain and wind protection. Fast installation, transparent m² pricing, free survey, 5-year warranty.',
                    ],
                    'de' => [
                        'isim' => 'Restaurant-Pavillons',
                        'aciklama' => "Restaurant-Pavillons sind gewerbliche Konstruktionen, die Sitzplätze im Freien ganzjährig nutzbar machen. Weite Öffnungen erleichtern das Servieren und wahren zugleich die Privatsphäre der Gäste. Wetterschutz verhindert Umsatzeinbußen in der Nebensaison. Seitenpaneele und Vorhänge ermöglichen schnelle Wetteranpassungen.\n\nFür Gastronomen ist die Amortisationszeit entscheidend. Zusätzliche Sitzplätze erhöhen die Tischumschlagrate und glätten saisonale Umsatzschwankungen. Aluminium- und Kompositvarianten widerstehen häufiger Reinigung und chemischer Belastung. Beleuchtungs- und Heizintegrationen verlängern die Betriebszeiten. Schnelle Montage sichert die Saisonvorbereitung; modulare Panelstrukturen erlauben Umplanungen.\n\nGegenüber Wohnmodellen bieten Restaurant-Pavillons höhere Kapazität und gewerbliche Robustheit — Vorteile, die durch transparente Quadratmeterpreise im Betriebsbudget untermauert werden. Abdichtung und Entwässerungsdetails stützen den Dauerbetrieb im Freien. Sie sind eine solide Investition zur Menüerweiterung und Aufwertung des Outdoor-Erlebnisses. Kontaktieren Sie uns für einen kostenlosen Aufmaß-Termin und ein Angebot.",
                        'slug' => 'restaurant-pavillon',
                        'seo_baslik' => 'Restaurant-Pavillons: Modelle & Preise 2026 | Kamelya',
                        'seo_aciklama' => 'Restaurant-Pavillons: gewerblicher Standard, ganzjährige Nutzung, hohe Kapazität, Wetterschutz. Schnelle Montage, transparente m²-Preise, kostenloser Aufmaß.',
                    ],
                    'fr' => [
                        'isim' => 'Gazebos pour Restaurants',
                        'aciklama' => "Les gazebo pour restaurants sont des structures de qualité commerciale conçues pour rendre les terrasses exploitables toute l'année. Les larges ouvertures facilitent le service tout en préservant l'intimité des clients. La protection contre la pluie et le vent évite les pertes de chiffre d'affaires hors saison. Panneaux latéraux et rideaux permettent une adaptation météo rapide.\n\nPour les restaurateurs, la période de retour sur investissement est déterminante. Des places supplémentaires augmentent la rotation des tables et lissent les variations saisonnières. Les options aluminium et composite résistent aux nettoyages fréquents et aux agressions chimiques. Les intégrations d'éclairage et de chauffage prolongent les horaires. Une installation rapide garantit la prête avant la haute saison ; la modularité des panneaux permet la reconfiguration.\n\nPar rapport aux modèles résidentiels, les gazebo pour restaurants offrent une capacité supérieure et une robustesse commerciale — atouts consolidés par une tarification au mètre carré compatible avec les budgets d'exploitation. L'étanchéité et l'évacuation des eaux soutiennent l'usage continu en extérieur. Ils constituent un investissement solide pour élargir la carte et valoriser l'expérience en extérieur. Contactez-nous pour une étude gratuite et un devis.",
                        'slug' => 'gazebo-restaurant',
                        'seo_baslik' => 'Gazebos pour Restaurants: Prix 2026 | Kamelya',
                        'seo_aciklama' => 'Gazebos pour restaurants : qualité commerciale, usage toute l\'année, grande capacité, protection pluie-vent. Installation rapide, prix au m², étude gratuite.',
                    ],
                    'it' => [
                        'isim' => 'Gazebo per Ristoranti',
                        'aciklama' => "I gazebo per ristoranti sono strutture di grado commerciale pensate per rendere utilizzabile la seduta esterna per tutte e quattro le stagioni. Le ampie aperture facilitano il servizio mantenendo la privacy degli ospiti. La protezione da pioggia e vento previene perdite di ricavi fuori stagione. Pannelli laterali e tende permettono adattamenti rapidi al meteo.\n\nPer i ristoratori, il fattore critico è il periodo di rientro. I posti aggiuntivi aumentano il turnover dei tavoli e ammorbidiscono le oscillazioni stagionali. Le opzioni in alluminio e composito resistono a lavaggi frequenti e sostanze chimiche. Le integrazioni di illuminazione e riscaldamento estendono gli orari. L'installazione rapida garantisce la prontezza prima dell'alta stagione; la modularità dei pannelli consente riorganizzazioni.\n\nRispetto ai modelli residenziali, i gazebo per ristoranti offrono capienza superiore e robustezza commerciale — vantaggi rafforzati da una tariffazione al metro quadro compatibile con i budget operativi. Impermeabilizzazione e scarichi sostengono l'uso continuativo all'aperto. Rappresentano un investimento solido per ampliare il menu e valorizzare l'esperienza esterna. Contattaci per un sopralluogo gratuito e un preventivo.",
                        'slug' => 'gazebo-ristoranti',
                        'seo_baslik' => 'Gazebo per Ristoranti: Modelli e Prezzi 2026 | Kamelya',
                        'seo_aciklama' => 'Gazebo per ristoranti: grado commerciale, uso quattro stagioni, alta capienza, protezione pioggia-vento. Installazione rapida, prezzi al m², sopralluogo gratuito.',
                    ],
                    'ar' => [
                        'isim' => 'كوش المطاعم',
                        'aciklama' => "تُصمَّم كوش المطاعم كهياكل بمواصفات تجارية تجعل الجلوس الخارجي متاحاً على مدار الأربع فصول. تسمح الفتحات الواسعة بحركة خدمة الموظفين مع الحفاظ على خصوصية الضيوف. يمنع الحماية من المطر والرياح فقدان الإيرادات خارج الموسم. الألواح الجانبية والستائر تتيح تكيّفاً سريعاً مع الطقس.\n\nيُعدّ فترة استرداد الاستثمار العامل الحاسم لأصحاب المطاعم. تزيد المقاعد الإضافية من معدل دوران الطاولات وتنعّم تقلبات الإيرادات الموسمية. تقاوم خيارات الألمنيوم والمواد المركّبة التنظيف المتكرر والتعرض للكيماويات. تمدّ خيارات الإضاءة والتدفئة ساعات التشغيل. يضمن التركيب السريع الجاهزية قبل الموسم الذروة؛ وبنية الألواح المعيارية تسمح بإعادة التنظيم.\n\nمقارنة بموديلات السكن، تمنح كوش المطاعم سعّة أكبر ومتانة تجارية — مزايا يعزّزها التسعير الشفاف للمتر مربع بما يتوافق مع ميزانيات التشغيل. تدعم العزل والمصارف الاستخدام المستمر في الخارج. تمثّل استثماراً راسخاً لتوسيع القائمة وتعزيز تجربة تناول الطعام في الخارج. تواصلوا معنا لاستشارة مجانية وعرض سعر.",
                        'slug' => 'restaurant-kush',
                        'seo_baslik' => 'كوش المطاعم: أسعار 2026 | Kamelya',
                        'seo_aciklama' => 'كوش المطاعم: مواصفات تجارية، استخدام طوال العام، سعة عالية، حماية من المطر والرياح. تركيب سريع، أسعار شفافة للمتر مربع، استشارة مجانية.',
                    ],
                ],
            ],
            [
                'kod' => 'otel',
                'ceviriler' => [
                    'tr' => [
                        'isim' => 'Otel Kamelyaları',
                        'aciklama' => "Otel kamelyaları, konuk deneyimini dış mekân alanlarıyla premium seviyeye taşıyan lüks sınıf çözümlerdir. Yüksek tavanlı açıklıklar, geniş manzara çerçeveleri ve sessiz tasarım, otel peyzajıyla kusursuz bütünleşir. Özel ölçülendirme, proje spesifikasyonlarına tam uyum sağlar; çatı akustiği ve yalıtım seçenekleri konforu destekler.\n\nButik oteller ve zincir tesisler için ayrı ayrı optimize edilmiştir: butiklerde karakter ve özgünlük, zincirlerde tekrarlanabilir kalite ve standartlaşma öne çıkar. Akıllı entegrasyon seçenekleri — gizlilik camı, uzaktan kumandalı gölgeleme, ambiyans aydınlatması — konuk memnuniyetini artırır. Premium malzeme seçimi, otelin marka algısını güçlendirir; sakin renk paletleri lobi diliyle uyum sağlar.\n\nStandart kamelyalara kıyasla daha yüksek dayanıklılık, daha zarif tasarım ve otel operasyonuna uygun modülerlik sunan otel kamelyaları, yatırım geri dönüşünü hızlandırır. Gürültü ve mahremiyet dengesi, yüksek puanlı konuk deneyimi için kritiktir. Referans projelerimizi inceleyip proje bazlı ücretsiz keşif talebinde bulunabilirsiniz.",
                        'slug' => 'otel-kamelyalari',
                        'seo_baslik' => 'Otel Kamelyaları ve Fiyatları 2026 | Kamelya',
                        'seo_aciklama' => 'Otel kamelyaları: premium lüks sınıf, özel ölçülendirme, akıllı entegrasyon, manzara çerçevesi. Butik ve zincir otel, m² şeffaf fiyat, ücretsiz keşif, 5 yıl garanti.',
                    ],
                    'en' => [
                        'isim' => 'Hotel Gazebos',
                        'aciklama' => "Hotel gazebos are luxury-tier solutions that elevate guest experience through premium outdoor spaces. High-clearance openings, expansive view frames, and quiet design integrate seamlessly with hotel landscaping. Custom sizing ensures full compliance with project specifications; roof acoustics and insulation options support comfort.\n\nBoutique properties and chain resorts are optimised separately: boutique projects prioritise character and originality, while chain rollouts emphasise repeatable quality and standardisation. Smart integrations — privacy glass, remote-controlled shading, ambient lighting — improve guest satisfaction. Premium material selection reinforces brand perception; muted colour palettes align with lobby design language.\n\nCompared with standard models, hotel gazebos offer greater durability, more refined design, and modularity suited to hotel operations — accelerating investment payback. Balancing noise and privacy is critical for high guest-satisfaction scores. Review our reference projects and request a project-specific free site survey.",
                        'slug' => 'hotel-gazebos',
                        'seo_baslik' => 'Hotel Gazebos & Prices 2026 | Kamelya',
                        'seo_aciklama' => 'Hotel gazebos: luxury tier, custom sizing, smart integrations, panoramic frames. Boutique and chain hotels, transparent m² pricing, free survey, 5-year warranty.',
                    ],
                    'de' => [
                        'isim' => 'Hotel-Pavillons',
                        'aciklama' => "Hotel-Pavillons sind Luxuslösungen, die das Gästeerlebnis durch Premium-Außenflächen aufwerten. Hohe Durchgangshöhen, großzügige Aussichtsrahmen und leises Design fügen sich nahtlos in die Hotellandschaft ein. Individuelle Maßfertigung gewährleistet die vollständige Projektkonformität; Dachakustik und Dämmoptionen stützen den Komfort.\n\nBoutique-Häuserdata und Kettenstandorte werden getrennt optimiert: Boutique-Projekte priorisieren Charakter und Originalität, Kettenprojekte reproduzierbare Qualität und Standardisierung. Smarte Integrationen — Privatsphärenglas, ferngesteuerte Beschattung, Ambientelicht — erhöhen die Gästezufriedenheit. Premium-Materialien stärken die Markenwahrnehmung; zurückhaltende Farbpaletten harmonieren mit der Lobby-Sprache.\n\nGegenüber Standardmodellen bieten Hotel-Pavillons höhere Beständigkeit, edleres Design und für den Hotelbetrieb geeignete Modularität — was die Amortisation beschleunigt. Lärm- und Privatsphäre-Balance ist für hohe Guest-Scores entscheidend. Prüfen Sie unsere Referenzprojekte und fordern Sie einen projektspezifischen kostenlosen Aufmaß-Termin an.",
                        'slug' => 'hotel-pavillon',
                        'seo_baslik' => 'Hotel-Pavillons: Modelle & Preise 2026 | Kamelya',
                        'seo_aciklama' => 'Hotel-Pavillons: Luxusklasse, Maßanfertigung, Smart-Integrationen, Panoramarahmen. Boutique- und Kettenhotels, transparente m²-Preise, kostenloser Aufmaß.',
                    ],
                    'fr' => [
                        'isim' => 'Gazebos pour Hôtels',
                        'aciklama' => "Les gazebo pour hôtels sont des solutions de luxe qui élèvent l'expérience client grâce à des espaces extérieurs premium. Ouvertures généreuses, cadres panoramiques et design discret s'intègrent harmonieusement au paysage hôtelier. Le dimensionnement sur mesure assure la conformité totale aux spécifications de projet ; acoustique de toiture et isolation soutiennent le confort.\n\nLes établissements de charme et les chaînes sont optimisés séparément : le boutique mise sur le caractère et l'originalité, la chaîne sur la qualité reproductible et la standardisation. Les intégrations intelligentes — verre intimate, volet télécommandé, éclairage d'ambiance — améliorent la satisfaction. Les matériaux premium renforcent la perception de marque ; des palettes sobres dialoguent avec le langage du lobby.\n\nPar rapport aux modèles standards, les gazebo pour hôtels offrent une durabilité supérieure, un design plus raffiné et une modularité adaptée aux opérations hôtelières — accélérant le retour sur investissement. L'équilibre entre bruit et intimité est déterminant pour les scores de satisfaction. Découvrez nos projets de référence et demandez une étude gratuite spécifique.",
                        'slug' => 'gazebo-hotel',
                        'seo_baslik' => 'Gazebos pour Hôtels: Prix 2026 | Kamelya',
                        'seo_aciklama' => 'Gazebos pour hôtels : gamme luxe, dimensionnement sur mesure, intégrations intelligentes, cadres panoramiques. Boutique et chaînes, prix au m², étude gratuite.',
                    ],
                    'it' => [
                        'isim' => 'Gazebo per Hotel',
                        'aciklama' => "I gazebo per hotel sono soluzioni di lusso che elevano l'esperienza ospite attraverso spazi esterni premium. Aperture generose, cornici panoramiche e design discreto si integrano armoniosamente nel paesaggio alberghiero. Il dimensionamento su misura garantisce piena conformità alle specifiche di progetto; acustica del tetto e isolamento sostengono il comfort.\n\nLe strutture boutique e le catene vengono ottimizzate separatamente: il boutique privilegia carattere e originalità, la catena qualità riproducibile e standardizzazione. Le integrazioni intelligenti — vetro riservato, oscuramento telecomandato, illuminazione d'atmosfera — migliorano la soddisfazione. I materiali premium rafforzano la percezione del brand; palette sobrie dialogano con il linguaggio della hall.\n\nRispetto ai modelli standard, i gazebo per hotel offrono maggiore durabilità, design più raffinato e modularità adatta alle operazioni alberghiere — accelerando il ritorno dell'investimento. L'equilibrio tra rumore e riservatezza è cruciale per gli alti punteggi di soddisfazione. Scopri i nostri progetti di riferimento e richiedi un sopralluogo gratuito specifico.",
                        'slug' => 'gazebo-hotel',
                        'seo_baslik' => 'Gazebo per Hotel: Modelli e Prezzi 2026 | Kamelya',
                        'seo_aciklama' => 'Gazebo per hotel: gamma lusso, dimensionamento su misura, integrazioni intelligenti, cornici panoramiche. Boutique e catene, prezzi al m², sopralluogo gratuito.',
                    ],
                    'ar' => [
                        'isim' => 'كوش الفنادق',
                        'aciklama' => "تُعدّ كوش الفنادق حلولاً فاخرة ترفع تجربة الضيوف عبر أماكن خارجية راقية. الفتحات العالية وإطارات الإطلالة الواسعة والتصميم الهادئ تندمج بسلاسة مع مناظر الفندق. يوفّر القياس حسب الطلب امتثالاً كاملاً لمواصفات المشروع؛ والعزل والأكوستيك للسقف يدعمان الراحة.\n\nتُحسَّن الملكيات الصغيرة وسلاسل المنتجعات بشكل منفصل: تُفضّل الصغيرة الطابع والأصالة، بينما تُركّز السلاسل على الجودة القابلة للتكرار والتوحيد القياسي. تعزّز التكاملات الذكية — زجاج الخصوصية والتظليل عن بعد والإضاءة المحيطية — رضا الضيوف. تقوّي المواد الراقية إدراك العلامة التجارية؛ ولوحات الألوان الهادئة تنسجم مع لغة اللوبي.\n\nمقارنة بالنماذج القياسية، تمنح كوش الفنادق متانة أعلى وتصميماً أرقى ومرونة تناسب عمليات الفنادق — مؤدية لتسريع استرداد الاستثمار. موازنة الضوضاء والخصوصية حاسمة لارتفاع درجات الرضا. تصفّحوا مشاريعنا المرجعية واطلبوا استشارة مجانية خاصة بالمشروع.",
                        'slug' => 'hotel-kush',
                        'seo_baslik' => 'كوش الفنادق: أسعار 2026 | Kamelya',
                        'seo_aciklama' => 'كوش الفنادق: شريحة فاخرة، قياس حسب الطلب، تكاملات ذكية، إطارات بانورامية. فنادق صغيرة وسلاسل، أسعار شفافة للمتر مربع، استشارة مجانية.',
                    ],
                ],
            ],
            [
                'kod' => 'belediye',
                'ceviriler' => [
                    'tr' => [
                        'isim' => 'Belediye Kamelyaları',
                        'aciklama' => "Belediye kamelyaları, kamusal alanlar — parklar, meydanlar, yürüyüş yolları — için tasarlanmış dayanıklı ve güvenli yapılardır. Yüksek güvenlik standartlarına uygun korkuluklar, kaymaz zemin ve yangına dayanıklı malzeme seçimi, kamu kullanımının zorunlu gereksinimlerini karşılar. Geniş açıklıklar, tekerlekli sandalye erişimine uygundur; engelsiz giriş rampaları standarttır.\n\nBelediye projeleri ihale ve şartname sürecinden geçer; bu nedenle net teknik belgeler, norm referansları (EN 13561) ve test raporları kritik öneme sahiptir. Düşük bakım maliyeti ve uzun ömür, kamu bütçesinin sürdürülebilirliğini destekler. Anti-graffiti kaplama ve vandalizm direnci, yoğun kullanım alanlarında uzun ömür sağlar; bağlantı elemanları sökülebilir tasarlanmıştır.\n\nÖzel alan kamelyalarına kıyasla daha yüksek dayanıklılık, erişilebilirlik ve standart uyumu sunan belediye kamelyaları, kamu ihalelerinde güçlü bir adaydır. Yeşil alan peyzajıyla uyumlu renk seçenekleri, şehir kimliğine katkı sağlar. Şartname uyumu ve teklif süreci için ücretsiz keşif talebinde bulunabilirsiniz.",
                        'slug' => 'belediye-kamelyalari',
                        'seo_baslik' => 'Belediye Kamelyaları ve Fiyatları 2026 | Kamelya',
                        'seo_aciklama' => 'Belediye kamelyaları: kamusal alan, park meydan, yüksek güvenlik, erişilebilir, vandalizm dirençli. EN 13561 uyumlu, düşük bakım, 5 yıl garanti. m² şeffaf fiyat, ücretsiz keşif.',
                    ],
                    'en' => [
                        'isim' => 'Municipal Gazebos',
                        'aciklama' => "Municipal gazebos are durable, safety-compliant structures designed for public spaces — parks, plazas, and promenades. High-security railings, slip-resistant flooring, and flame-retardant materials meet the mandatory requirements of communal use. Wide openings accommodate wheelchair access; step-free entrance ramps are standard.\n\nMunicipal projects pass through tender and specification processes, so clear technical documentation, norm references (EN 13561), and test reports are essential. Low maintenance cost and long service life support sustainable public budgets. Anti-graffiti coating and vandal resistance ensure longevity in high-traffic areas; fixings are designed to be tamper-resistant.\n\nCompared with private models, municipal gazebos offer superior durability, accessibility, and standards compliance — making them a strong candidate in public tenders. Colour options integrate with green-area landscaping and civic identity. Request a free site survey for specification alignment and quotation support.",
                        'slug' => 'municipal-gazebos',
                        'seo_baslik' => 'Municipal Gazebos & Prices 2026 | Kamelya',
                        'seo_aciklama' => 'Municipal gazebos: public spaces, parks and plazas, high safety, accessible, vandal-resistant. EN 13561 compliant, low maintenance, 5-year warranty. Transparent m² pricing, free survey.',
                    ],
                    'de' => [
                        'isim' => 'Kommunal-Pavillons',
                        'aciklama' => "Kommunal-Pavillons sind robuste, sicherheitskonforme Konstruktionen für öffentliche Flächen — Parks, Plätze und Promenaden. Sicherheitsgeländer in hoher Ausführung, rutschfester Boden und flammhemmende Materialien erfüllen die zwingenden Anforderungen des Gemeinschaftsbetriebs. Weite Öffnungen ermöglichen den Rollstuhlzugang; schwellenfreie Rampen sind Standard.\n\nKommunalprojekte durchlaufen Ausschreibungs- und Spezifikationsverfahren; klare technische Dokumentation, Normreferenzen (EN 13561) und Prüfberichte sind daher unverzichtbar. Geringe Pflegekosten und lange Lebensdauer stützen nachhaltige kommunale Haushalte. Anti-Graffiti-Beschichtung und Vandalismusresistenz sichern die Dauerhaftigkeit in frequentierten Bereichen; Befestigungen sind manipulationssicher ausgeführt.\n\nGegenüber privaten Modellen bieten Kommunal-Pavillons höhere Robustheit, Barrierefreiheit und Normkonformität — was sie zu einem starken Kandidaten in öffentlichen Vergaben macht. Farbvarianten fügen sich in Grünflächen und kommunale Identität ein. Fordern Sie einen kostenlosen Aufmaß-Termin für Spezifikationsabgleich und Angebot an.",
                        'slug' => 'kommunal-pavillon',
                        'seo_baslik' => 'Kommunal-Pavillons: Modelle & Preise 2026 | Kamelya',
                        'seo_aciklama' => 'Kommunal-Pavillons: öffentliche Flächen, Parks und Plätze, hohe Sicherheit, barrierefrei, vandal resistant. EN 13561-konform, geringe Pflege, 5 Jahre Garantie.',
                    ],
                    'fr' => [
                        'isim' => 'Gazebos Municipaux',
                        'aciklama' => "Les gazebo municipaux sont des structures durables et conformes aux normes de sécurité, conçus pour les espaces publics — parcs, places et promenades. Garde-corps haute sécurité, sols antidérapants et matériaux ignifuges répondent aux exigences obligatoires de l'usage collectif. Les larges ouvertures permettent l'accès en fauteuil roulant ; les rampes sans marche sont standard.\n\nLes projets municipaux suivent des procédures d'appel d'offres et de spécification ; une documentation technique claire, des références normatives (EN 13561) et des rapports d'essai sont donc essentiels. Des coûts d'entretien faibles et une longue durée de vie soutiennent les budgets publics durables. Le revêtement anti-graffiti et la résistance au vandalisme assurent la pérennité ; les fixations sont conçues résistantes à l'effraction.\n\nPar rapport aux modèles privés, les gazebo municipaux offrent une robustesse, une accessibilité et une conformité supérieures — les rendant des candidats solides dans les marchés publics. Les coloris s'intègrent au paysage vert et à l'identité civique. Demandez une étude gratuite pour l'alignement des spécifications et le devis.",
                        'slug' => 'gazebo-municipal',
                        'seo_baslik' => 'Gazebos Municipaux: Prix 2026 | Kamelya',
                        'seo_aciklama' => 'Gazebos municipaux : espaces publics, parcs et places, sécurité élevée, accessibles, anti-vandalisme. Conforme EN 13561, entretien réduit, garantie 5 ans.',
                    ],
                    'it' => [
                        'isim' => 'Gazebo Comunali',
                        'aciklama' => "I gazebo comunali sono strutture durevoli e conformi alle norme di sicurezza, progettate per gli spazi pubblici — parchi, piazze e lungomare. Parapetti ad alta sicurezza, pavimenti antiscivolo e materiali ritardanti di fiamma rispondono ai requisiti obbligatori dell'uso collettivo. Le ampie aperture consentono l'accesso in sedia a rotelle; rampe senza gradini sono standard.\n\nI progetti comunali seguono procedure di gara e specifica; documentazione tecnica chiara, riferimenti normativi (EN 13561) e rapporti di prova sono quindi essenziali. Costi di manutenzione ridotti e lunga durata sostengono bilanci pubblici sostenibili. La finitura anti-graffiti e la resistenza al vandalismo assicurano longevità nelle aree ad alto transito; gli elementi di fissaggio sono a prova di manomissione.\n\nRispetto ai modelli privati, i gazebo comunali offrono maggiore robustezza, accessibilità e conformità — li rendendo candidati solidi nelle gare pubbliche. Le colorazioni si integrano nel paesaggio verde e nell'identità civica. Richiedi un sopralluogo gratuito per l'allineamento delle specifiche e il preventivo.",
                        'slug' => 'gazebo-comunali',
                        'seo_baslik' => 'Gazebo Comunali: Modelli e Prezzi 2026 | Kamelya',
                        'seo_aciklama' => 'Gazebo comunali: spazi pubblici, parchi e piazze, alta sicurezza, accessibili, anti vandalismo. Conforme EN 13561, manutenzione ridotta, garanzia 5 anni.',
                    ],
                    'ar' => [
                        'isim' => 'كوش البلدية',
                        'aciklama' => "تُصمَّم كوش البلدية كهياكل متينة ومتوافقة مع معايير الأمان للأماكن العامة — الحدائق والساحات وممرات المشي. يلبّي الدرابزين عالي الأمان والأرضيات المانعة للانزلاق والمواد المقاومة للاشتعال المتطلبات الإلزامية للاستخدام الجماعي. تسمح الفتحات الواسعة بالوصول لمستخدمي الكراسي المتحركة؛ ومنحدرات خالية من الدرج قياسية.\n\nتمرّ مشاريع البلدية بإجراءات المنافسات والمواصفات؛ لذا فإن التوثيق التقني الواضح والمرجعيات المعيارية (EN 13561) وتقارير الاختبار بالغة الأهمية. تدعم تكاليف الصيانة المنخفضة والعمر الطويل استدامة الميزانيات العامة. يضمن الطلاء المضاد للرسم والمقاومة للتخرّب البقاء في المناطق ذات الحركة الكثيفة؛ وعناصر التثبيت مصمّمة لمقاومة التخريب.\n\nمقارنة بالموديلات الخاصة، تمنح كوش البلدية متانة وسهولة وصول وامتثالاً أعلى — مما يجعلها مرشحاً قوياً في المنافسات العامة. تنسجم خيارات الألوان مع مناظر المساحات الخضراء والهوية البلدية. اطلبوا استشارة مجانية لمواءمة المواصفات وعرض السعر.",
                        'slug' => 'municipal-kush',
                        'seo_baslik' => 'كوش البلدية: موديلات وأسعار 2026 | Kamelya',
                        'seo_aciklama' => 'كوش البلدية: أماكن عامة، حدائق وساحات، أمان مرتفع، سهولة وصول، مقاومة للتخرّب. متوافق مع EN 13561، صيانة منخفضة، ضمان 5 سنوات.',
                    ],
                ],
            ],
        ];
    }

    public static function calistir(PDO $baglanti): int
    {
        $sayac = 0;
        $baglanti->beginTransaction();

        try {
            $kategoriBul = $baglanti->prepare('SELECT id FROM kategoriler WHERE kod = ?');
            $ceviriYaz = $baglanti->prepare(
                'INSERT INTO kategori_cevirileri (kategori_id, dil_kodu, isim, aciklama, slug)
                 VALUES (?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE isim = VALUES(isim), aciklama = VALUES(aciklama), slug = VALUES(slug)'
            );
            $seoYaz = $baglanti->prepare(
                'INSERT INTO seo_verileri (sayfa_tipi, referans_id, dil_kodu, meta_baslik, meta_aciklama, robots)
                 VALUES (?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE meta_baslik = VALUES(meta_baslik), meta_aciklama = VALUES(meta_aciklama)'
            );

            foreach (self::veri() as $kategori) {
                $kategoriBul->execute([$kategori['kod']]);
                $kategoriId = $kategoriBul->fetchColumn();
                if ($kategoriId === false) {
                    throw new \RuntimeException("Kategori bulunamadı: {$kategori['kod']} — önce KategoriSeeder çalışmalı.");
                }
                $kategoriId = (int) $kategoriId;

                foreach ($kategori['ceviriler'] as $dil => $ceviri) {
                    $ceviriYaz->execute([
                        $kategoriId,
                        $dil,
                        $ceviri['isim'],
                        $ceviri['aciklama'],
                        $ceviri['slug'],
                    ]);

                    $seoYaz->execute([
                        'kategori',
                        $kategoriId,
                        $dil,
                        $ceviri['seo_baslik'],
                        $ceviri['seo_aciklama'],
                        'index, follow',
                    ]);

                    $sayac++;
                }
            }

            $baglanti->commit();

            return $sayac;
        } catch (\Throwable $hata) {
            $baglanti->rollBack();
            throw $hata;
        }
    }
}