<?php

declare(strict_types=1);

namespace Kamelya\Services;

use Kamelya\Core\Hata;
use Kamelya\Repositories\KampanyaRepository;
use PDO;
use PDOException;

/** Kampanya vitrini + admin CRUD (audit'li). Fiziksel silme (çeviriler CASCADE). */
final class KampanyaService
{
    public const TIPlER = ['yuzde', 'sabit'];

    public function __construct(
        private PDO $baglanti,
        private KampanyaRepository $kampanyalar,
        private AuditLogService $denetim
    ) {
    }

    /** @return array<int, array<string, mixed>> */
    public function aktifListe(string $dil): array
    {
        $cikti = [];
        foreach ($this->kampanyalar->aktifListe($dil) as $satir) {
            $cikti[] = [
                'id' => (int) $satir['id'],
                'kod' => $satir['kod'],
                'baslik' => $satir['ceviri_baslik'] ?? $satir['kod'],
                'aciklama' => $satir['ceviri_aciklama'],
                'cta_metni' => $satir['cta_metni'],
                'indirim_orani' => $satir['indirim_orani'] === null ? null : (float) $satir['indirim_orani'],
                'indirim_tipi' => $satir['indirim_tipi'],
                'banner_gorsel' => $satir['banner_gorsel'],
                'link_url' => $satir['link_url'],
                'bitis' => $satir['bitis'],
            ];
        }

        return $cikti;
    }

    /** @return array<int, array<string, mixed>> */
    public function adminListe(): array
    {
        return $this->kampanyalar->adminListe();
    }

    /** @param array<string, mixed> $veri */
    public function olustur(array $kullanici, array $veri, string $ip, string $ajan): array
    {
        $this->dogrula($veri, false);

        try {
            $id = $this->kampanyalar->olustur($veri);
        } catch (PDOException $hata) {
            if ($hata->getCode() === '23000') {
                throw new Hata('CONFLICT', 'Bu kod zaten kayıtlı.', [['field' => 'kod', 'issue' => 'duplicate']], 409);
            }

            throw $hata;
        }

        $this->cevirileriKaydet($id, $veri['ceviriler'] ?? []);
        $this->denetim->kaydet((int) $kullanici['id'], 'create', 'kampanya', $id, null, ['kod' => $veri['kod']], $ip, $ajan);

        return ['id' => $id];
    }

    /** @param array<string, mixed> $veri */
    public function guncelle(array $kullanici, int $id, array $veri, string $ip, string $ajan): array
    {
        $eski = $this->kampanyalar->idIleGetir($id);
        if ($eski === null) {
            throw new Hata('NOT_FOUND', 'Kampanya bulunamadı.', [['field' => 'id', 'issue' => 'not_found']], 404);
        }

        $this->dogrula($veri, true);
        $this->kampanyalar->guncelle($id, $veri);
        $this->cevirileriKaydet($id, $veri['ceviriler'] ?? []);
        $this->denetim->kaydet((int) $kullanici['id'], 'update', 'kampanya', $id, ['kod' => $eski['kod']], ['kod' => $veri['kod'] ?? $eski['kod']], $ip, $ajan);

        return ['id' => $id];
    }

    public function sil(array $kullanici, int $id, string $ip, string $ajan): void
    {
        $eski = $this->kampanyalar->idIleGetir($id);
        if ($eski === null) {
            throw new Hata('NOT_FOUND', 'Kampanya bulunamadı.', [['field' => 'id', 'issue' => 'not_found']], 404);
        }

        $this->kampanyalar->sil($id);
        $this->denetim->kaydet((int) $kullanici['id'], 'delete', 'kampanya', $id, ['kod' => $eski['kod']], null, $ip, $ajan);
    }

    /** @param array<string, mixed> $veri */
    private function dogrula(array $veri, bool $guncelleme): void
    {
        $hatalar = [];
        if ((!$guncelleme || array_key_exists('kod', $veri)) && trim((string) ($veri['kod'] ?? '')) === '') {
            $hatalar[] = ['field' => 'kod', 'issue' => 'required'];
        }

        if (isset($veri['indirim_tipi']) && !in_array($veri['indirim_tipi'], self::TIPlER, true)) {
            $hatalar[] = ['field' => 'indirim_tipi', 'issue' => 'invalid'];
        }

        foreach (['baslangic', 'bitis'] as $alan) {
            if ((!$guncelleme || array_key_exists($alan, $veri))
                && \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', (string) ($veri[$alan] ?? '')) === false
                && \DateTimeImmutable::createFromFormat('Y-m-d', (string) ($veri[$alan] ?? '')) === false) {
                $hatalar[] = ['field' => $alan, 'issue' => 'invalid_format'];
            }
        }

        if ($hatalar !== []) {
            throw new Hata('VALIDATION_ERROR', 'Doğrulama hatası.', $hatalar, 422);
        }
    }

    /** @param array<string, array<string, mixed>> $ceviriler */
    private function cevirileriKaydet(int $id, array $ceviriler): void
    {
        foreach ($ceviriler as $dil => $ceviri) {
            if (!is_array($ceviri) || trim((string) ($ceviri['baslik'] ?? '')) === '') {
                continue;
            }

            $this->kampanyalar->ceviriKaydet($id, $dil, (string) $ceviri['baslik'], $ceviri['aciklama'] ?? null, $ceviri['cta_metni'] ?? null);
        }
    }
}
