<?php

declare(strict_types=1);

namespace Kamelya\Services;

use Kamelya\Core\Hata;
use PDO;

/**
 * Özellik toggle — bağımlılık ağacı kuralları:
 * etkin = kendi aktif VE tüm parent'lar aktif. Parent kapanınca çocuklar
 * DB'de kapatılır; parent açılınca çocuklar kapalı kalır. Zorunlu kapatılamaz.
 */
final class OzellikToggleService
{
    public function __construct(
        private PDO $baglanti,
        private AuditLogService $denetim
    ) {
    }

    /** @return array<int, array<string, mixed>> ağaç (cocuklar iç içe) */
    public function agacGetir(): array
    {
        $ifade = $this->baglanti->query('SELECT * FROM ozellik_toggle ORDER BY sira ASC, id ASC');
        $satirlar = $ifade->fetchAll();

        $harita = [];
        foreach ($satirlar as $satir) {
            $etkin = ((int) $satir['aktif'] === 1) && $this->parentAktif($satir, $satirlar);
            $harita[$satir['anahtar']] = [
                'anahtar' => $satir['anahtar'],
                'baslik' => $satir['baslik'],
                'aciklama' => $satir['aciklama'],
                'aktif' => (int) $satir['aktif'],
                'zorunlu' => (int) $satir['zorunlu'],
                'etkin_aktif' => $etkin ? 1 : 0,
                'cocuklar' => [],
            ];
        }

        $kok = [];
        foreach ($satirlar as $satir) {
            $anahtar = $satir['anahtar'];
            $parent = $satir['parent_anahtar'];
            if ($parent === null || !isset($harita[$parent])) {
                $kok[] = &$harita[$anahtar];
            } else {
                $harita[$parent]['cocuklar'][] = &$harita[$anahtar];
            }
        }

        unset($harita);

        return $kok;
    }

    public function aktifMi(string $anahtar): bool
    {
        return $this->etkinDurum($anahtar)['etkin_aktif'] === 1;
    }

    /** @return array{kendi_aktif: int, parent_aktif: int, etkin_aktif: int} */
    public function etkinDurum(string $anahtar): array
    {
        $onbellek = $this->onbellekOku();
        if (!isset($onbellek[$anahtar])) {
            return ['kendi_aktif' => 0, 'parent_aktif' => 0, 'etkin_aktif' => 0];
        }

        $kendi = (int) $onbellek[$anahtar]['aktif'];
        $ebeveyn = $this->parentAktifDurum($anahtar, $onbellek) ? 1 : 0;

        return ['kendi_aktif' => $kendi, 'parent_aktif' => $ebeveyn, 'etkin_aktif' => ($kendi === 1 && $ebeveyn === 1) ? 1 : 0];
    }

    /** @return array{anahtar: string, aktif: int, etkilenen_cocuklar: array} */
    public function toggle(array $kullanici, string $anahtar, bool $aktif, string $ip, string $ajan): array
    {
        $satir = $this->satirGetir($anahtar);
        if ($satir === null) {
            throw new Hata('NOT_FOUND', 'Özellik bulunamadı.', [['field' => 'anahtar', 'issue' => 'not_found']], 404);
        }

        if ((int) $satir['zorunlu'] === 1 && !$aktif) {
            throw new Hata('ZORUNLU_OZELLIK', 'Zorunlu özellik kapatılamaz.', [['field' => 'anahtar', 'issue' => 'mandatory']], 422);
        }

        $etkilenen = [];
        $this->baglanti->beginTransaction();
        try {
            $guncelle = $this->baglanti->prepare('UPDATE ozellik_toggle SET aktif = ? WHERE anahtar = ?');
            $guncelle->execute([$aktif ? 1 : 0, $anahtar]);

            if (!$aktif) {
                foreach ($this->cocuklariTopla($anahtar) as $cocuk) {
                    $guncelle->execute([0, $cocuk]);
                    $etkilenen[] = $cocuk;
                }
            }

            if ($this->baglanti->inTransaction()) {
                $this->baglanti->commit();
            }
        } catch (\Throwable $hata) {
            if ($this->baglanti->inTransaction()) {
                $this->baglanti->rollBack();
            }

            throw $hata;
        }

        $this->denetim->kaydet((int) $kullanici['id'], 'ozellik_toggle', 'ozellik', null, ['anahtar' => $anahtar, 'aktif' => (int) $satir['aktif']], ['anahtar' => $anahtar, 'aktif' => $aktif ? 1 : 0], $ip, $ajan);
        $this->onbellekTemizle();

        return ['anahtar' => $anahtar, 'aktif' => $aktif ? 1 : 0, 'etkilenen_cocuklar' => $etkilenen];
    }

    public function zorunluMu(string $anahtar): bool
    {
        $satir = $this->satirGetir($anahtar);

        return $satir !== null && (int) $satir['zorunlu'] === 1;
    }

    /** @return array{anahtar: string, aktif: int, etkilenen_cocuklar: array, atlanan_zorunlu: array} */
    public function topluCocuk(array $kullanici, string $anahtar, bool $aktif, string $ip, string $ajan): array
    {
        if ($this->satirGetir($anahtar) === null) {
            throw new Hata('NOT_FOUND', 'Özellik bulunamadı.', [['field' => 'anahtar', 'issue' => 'not_found']], 404);
        }

        $cocuklar = $this->cocuklariTopla($anahtar);
        $etkilenen = [];
        $atlanan = [];

        $this->baglanti->beginTransaction();
        try {
            $guncelle = $this->baglanti->prepare('UPDATE ozellik_toggle SET aktif = ? WHERE anahtar = ?');
            foreach ($cocuklar as $cocuk) {
                $satir = $this->satirGetir($cocuk);
                if ($satir !== null && (int) $satir['zorunlu'] === 1 && !$aktif) {
                    $atlanan[] = $cocuk;
                    continue;
                }

                $guncelle->execute([$aktif ? 1 : 0, $cocuk]);
                $etkilenen[] = $cocuk;
            }

            if ($this->baglanti->inTransaction()) {
                $this->baglanti->commit();
            }
        } catch (\Throwable $hata) {
            if ($this->baglanti->inTransaction()) {
                $this->baglanti->rollBack();
            }

            throw $hata;
        }

        $this->denetim->kaydet((int) $kullanici['id'], 'ozellik_toplu', 'ozellik', null, ['anahtar' => $anahtar], ['aktif' => $aktif ? 1 : 0, 'etkilenen' => count($etkilenen)], $ip, $ajan);
        $this->onbellekTemizle();

        return ['anahtar' => $anahtar, 'aktif' => $aktif ? 1 : 0, 'etkilenen_cocuklar' => $etkilenen, 'atlanan_zorunlu' => $atlanan];
    }

    /** @return array<string, mixed>|null */
    private function satirGetir(string $anahtar): ?array
    {
        $ifade = $this->baglanti->prepare('SELECT * FROM ozellik_toggle WHERE anahtar = ?');
        $ifade->execute([$anahtar]);
        $satir = $ifade->fetch();

        return $satir === false ? null : $satir;
    }

    /** @return array<int, string> */
    private function cocuklariTopla(string $anahtar): array
    {
        $ifade = $this->baglanti->prepare('SELECT anahtar FROM ozellik_toggle WHERE parent_anahtar = ?');
        $ifade->execute([$anahtar]);

        $cikti = [];
        foreach ($ifade->fetchAll(PDO::FETCH_COLUMN) as $cocuk) {
            $cikti[] = $cocuk;
            foreach ($this->cocuklariTopla($cocuk) as $torun) {
                $cikti[] = $torun;
            }
        }

        return $cikti;
    }

    /** @param array<int, array<string, mixed>> $tum */
    private function parentAktif(array $satir, array $tum): bool
    {
        $parent = $satir['parent_anahtar'];
        if ($parent === null) {
            return true;
        }

        foreach ($tum as $aday) {
            if ($aday['anahtar'] === $parent) {
                return (int) $aday['aktif'] === 1 && $this->parentAktif($aday, $tum);
            }
        }

        return true;
    }

    /** @param array<string, array<string, mixed>> $harita */
    private function parentAktifDurum(string $anahtar, array $harita): bool
    {
        $parent = $harita[$anahtar]['parent_anahtar'] ?? null;
        if ($parent === null || !isset($harita[$parent])) {
            return true;
        }

        return (int) $harita[$parent]['aktif'] === 1 && $this->parentAktifDurum($parent, $harita);
    }

    /** @return array<string, array<string, mixed>> */
    private function onbellekOku(): array
    {
        $dosya = $this->onbellekDosya();
        if (is_file($dosya)) {
            $ham = json_decode((string) file_get_contents($dosya), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("JSON parse hatasi ozellik: " . json_last_error_msg() . " ($dosya)");
                return [];
            }
            if (is_array($ham) && ($ham['zaman'] ?? 0) + 3600 > time() && isset($ham['veri'])) {
                return $ham['veri'];
            }
        }

        $ifade = $this->baglanti->query('SELECT anahtar, parent_anahtar, aktif FROM ozellik_toggle');
        $harita = [];
        foreach ($ifade->fetchAll() as $satir) {
            $harita[$satir['anahtar']] = $satir;
        }

        $dizin = dirname($dosya);
        if (!is_dir($dizin)) {
            mkdir($dizin, 0750, true);
        }

        file_put_contents($dosya, json_encode(['zaman' => time(), 'veri' => $harita]), LOCK_EX);

        return $harita;
    }

    private function onbellekTemizle(): void
    {
        $dosya = $this->onbellekDosya();
        if (is_file($dosya)) {
            unlink($dosya);
        }
    }

    private function onbellekDosya(): string
    {
        return dirname(__DIR__, 3) . '/cache/ozellik_toggle.json';
    }
}
