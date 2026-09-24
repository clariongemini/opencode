<?php

declare(strict_types=1);

namespace Kamelya\Services;

use PDO;

/**
 * Site içi arama — LIKE tabanlı (F11 kabulü; FULLTEXT iyileştirme notu).
 * Sıralama: başlık eşleşmesi > içerik eşleşmesi. 5 dk dosya-önbellekli.
 * Rehber havuzu: statik 2 rehber sayfası (DB tablosu yok — şeffaf sabit).
 */
final class AramaService
{
    public const TURLER = ['hepsi', 'urun', 'blog', 'sss'];

    public function __construct(private PDO $baglanti)
    {
    }

    /** @return array{sonuclar: array, toplam: int} */
    public function ara(string $q, string $dil, string $tur, int $sayfa, int $adet): array
    {
        $anahtar = md5($q . '|' . $dil . '|' . $tur . '|' . $sayfa . '|' . $adet);
        $onbellek = $this->onbellekOku($anahtar);
        if ($onbellek !== null) {
            return $onbellek;
        }

        $sonuc = ['sonuclar' => [], 'toplam' => 0];
        $hedefler = $tur === 'hepsi' ? ['urun', 'blog', 'sss'] : [$tur];
        foreach ($hedefler as $hedef) {
            $parca = match ($hedef) {
                'urun' => $this->urunAra($q, $dil, $adet),
                'blog' => $this->blogAra($q, $dil, $adet),
                default => $this->sssAra($q, $dil, $adet),
            };
            foreach ($parca as $satir) {
                $sonuc['sonuclar'][] = $satir;
            }
        }

        $sonuc['toplam'] = count($sonuc['sonuclar']);
        $sonuc['sonuclar'] = array_slice($sonuc['sonuclar'], ($sayfa - 1) * $adet, $adet);
        $this->onbellekYaz($anahtar, $sonuc);

        return $sonuc;
    }

    /** @return array<int, array<string, mixed>> */
    private function urunAra(string $q, string $dil, int $adet): array
    {
        $ifade = $this->baglanti->prepare(
            "SELECT u.id, c.baslik, c.kisa_aciklama, c.slug,
                    CASE WHEN c.baslik LIKE :b THEN 0 ELSE 1 END AS ilgi
             FROM urunler u
             JOIN urun_cevirileri c ON c.urun_id = u.id AND c.dil_kodu = :dil
             WHERE u.aktif = 1 AND u.deleted_at IS NULL
             AND (c.baslik LIKE :q1 OR c.kisa_aciklama LIKE :q2 OR c.detayli_aciklama LIKE :q3)
             ORDER BY ilgi ASC, c.baslik ASC LIMIT :lim"
        );
        $ifade->bindValue(':dil', $dil);
        $ifade->bindValue(':b', '%' . $q . '%');
        $ifade->bindValue(':q1', '%' . $q . '%');
        $ifade->bindValue(':q2', '%' . $q . '%');
        $ifade->bindValue(':q3', '%' . $q . '%');
        $ifade->bindValue(':lim', $adet, PDO::PARAM_INT);
        $ifade->execute();

        $cikti = [];
        foreach ($ifade->fetchAll() as $s) {
            $cikti[] = ['tur' => 'urun', 'id' => (int) $s['id'], 'baslik' => $s['baslik'], 'ozet' => $s['kisa_aciklama'], 'yol' => '/urun/' . $s['slug']];
        }

        return $cikti;
    }

    /** @return array<int, array<string, mixed>> */
    private function blogAra(string $q, string $dil, int $adet): array
    {
        $ifade = $this->baglanti->prepare(
            "SELECT y.id, c.baslik, c.ozet, c.slug,
                    CASE WHEN c.baslik LIKE :b THEN 0 ELSE 1 END AS ilgi
             FROM blog_yazilari y
             JOIN blog_yazisi_cevirileri c ON c.yazi_id = y.id AND c.dil_kodu = :dil
             WHERE y.yayin_durumu = 'yayinda' AND y.deleted_at IS NULL
             AND (c.baslik LIKE :q1 OR c.ozet LIKE :q2 OR c.icerik LIKE :q3)
             ORDER BY ilgi ASC, c.baslik ASC LIMIT :lim"
        );
        $ifade->bindValue(':dil', $dil);
        $ifade->bindValue(':b', '%' . $q . '%');
        $ifade->bindValue(':q1', '%' . $q . '%');
        $ifade->bindValue(':q2', '%' . $q . '%');
        $ifade->bindValue(':q3', '%' . $q . '%');
        $ifade->bindValue(':lim', $adet, PDO::PARAM_INT);
        $ifade->execute();

        $cikti = [];
        foreach ($ifade->fetchAll() as $s) {
            $cikti[] = ['tur' => 'blog', 'id' => (int) $s['id'], 'baslik' => $s['baslik'], 'ozet' => $s['ozet'], 'yol' => '/blog/' . $s['slug']];
        }

        return $cikti;
    }

    /** @return array<int, array<string, mixed>> */
    private function sssAra(string $q, string $dil, int $adet): array
    {
        $ifade = $this->baglanti->prepare(
            "SELECT s.id, c.soru, c.cevap,
                    CASE WHEN c.soru LIKE :b THEN 0 ELSE 1 END AS ilgi
             FROM sss_sorulari s
             JOIN sss_cevirileri c ON c.soru_id = s.id AND c.dil_kodu = :dil
             WHERE s.aktif = 1 AND (c.soru LIKE :q1 OR c.cevap LIKE :q2)
             ORDER BY ilgi ASC, s.sira ASC LIMIT :lim"
        );
        $ifade->bindValue(':dil', $dil);
        $ifade->bindValue(':b', '%' . $q . '%');
        $ifade->bindValue(':q1', '%' . $q . '%');
        $ifade->bindValue(':q2', '%' . $q . '%');
        $ifade->bindValue(':lim', $adet, PDO::PARAM_INT);
        $ifade->execute();

        $cikti = [];
        foreach ($ifade->fetchAll() as $s) {
            $cikti[] = ['tur' => 'sss', 'id' => (int) $s['id'], 'baslik' => $s['soru'], 'ozet' => mb_substr((string) $s['cevap'], 0, 160), 'yol' => '/sss'];
        }

        return $cikti;
    }

    /** @return array{sonuclar: array, toplam: int}|null */
    private function onbellekOku(string $anahtar): ?array
    {
        $dosya = sys_get_temp_dir() . '/kamelya_arama/' . $anahtar . '.json';
        if (!is_file($dosya)) {
            return null;
        }

        $ham = json_decode((string) file_get_contents($dosya), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON parse hatasi onbellek: " . json_last_error_msg() . " ($dosya)");
            return null;
        }
        if (!is_array($ham) || ($ham['zaman'] ?? 0) + 300 < time()) {
            return null;
        }

        return $ham['veri'] ?? null;
    }

    /** @param array{sonuclar: array, toplam: int} $veri */
    private function onbellekYaz(string $anahtar, array $veri): void
    {
        $dizin = sys_get_temp_dir() . '/kamelya_arama';
        if (!is_dir($dizin)) {
            mkdir($dizin, 0700, true);
        }

        file_put_contents($dizin . '/' . $anahtar . '.json', json_encode(['zaman' => time(), 'veri' => $veri]), LOCK_EX);
    }
}
