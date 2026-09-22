<?php

declare(strict_types=1);

namespace Kamelya\Services\Admin;

use Kamelya\Core\Config;
use Kamelya\Core\Hata;
use Kamelya\Repositories\Admin\BlogYonetimRepository;
use Kamelya\Repositories\Admin\GaleriYonetimRepository;
use Kamelya\Repositories\Admin\ResimYonetimRepository;
use Kamelya\Repositories\Admin\UrunYonetimRepository;
use Kamelya\Services\AuditLogService;
use PDO;

/**
 * Görsel yükleme — kurallar config/guvenlik.php'den (MIME, boyut, public-dışı).
 * Hedefe göre: urun → urun_resimleri (ilk görsel otomatik kapak),
 * blog → kapak_resmi, galeri → galeri satırı.
 */
final class YuklemeService
{
    /** @var array<string, string> */
    private const UZANTI_HARITASI = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public function __construct(
        private PDO $baglanti,
        private ResimYonetimRepository $resimler,
        private UrunYonetimRepository $urunler,
        private BlogYonetimRepository $yazilar,
        private GaleriYonetimRepository $galeri,
        private AuditLogService $denetim
    ) {
    }

    /** @param array<string, mixed> $dosya */
    public function yukle(array $kullanici, array $dosya, string $hedef, int $hedefId, array $ekstra, string $ip, string $ajan): array
    {
        if (($dosya['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new Hata('VALIDATION_ERROR', 'Dosya yüklenemedi.', [['field' => 'file', 'issue' => 'upload_error']], 422);
        }

        $kural = Config::al('guvenlik.yukleme', []);
        $maks = (int) ($kural['maks_boyut'] ?? 5 * 1024 * 1024);
        if ((int) $dosya['size'] > $maks) {
            throw new Hata('VALIDATION_ERROR', 'Dosya boyutu aşıldı (maks 5MB).', [['field' => 'file', 'issue' => 'too_large']], 422);
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file((string) $dosya['tmp_name']);
        $izinliler = $kural['izinli_mime'] ?? array_keys(self::UZANTI_HARITASI);
        if (!in_array($mime, $izinliler, true) || !isset(self::UZANTI_HARITASI[$mime])) {
            throw new Hata('VALIDATION_ERROR', 'Desteklenmeyen dosya türü.', [['field' => 'file', 'issue' => 'invalid_mime']], 422);
        }

        $altDizin = match ($hedef) {
            'urun' => 'urunler',
            'blog' => 'blog',
            'galeri' => 'galeri',
            'atolye' => 'atolye',
            'donusum' => 'donusum',
            default => throw new Hata('VALIDATION_ERROR', 'Geçersiz hedef.', [['field' => 'hedef', 'issue' => 'invalid']], 422),
        };

        $goreceli = $this->dosyaKaydet($dosya, $altDizin);
        $tamYol = dirname(__DIR__, 3) . '/' . $goreceli;

        if ($hedef === 'urun') {
            if ($this->urunler->hamGetir($hedefId) === null) {
                unlink($tamYol);

                throw new Hata('PRODUCT_NOT_FOUND', 'Ürün bulunamadı.', [['field' => 'hedef_id', 'issue' => 'not_found']], 404);
            }

            $kapak = !$this->resimler->kapakVarMi($hedefId);
            $tur = in_array($ekstra['tur'] ?? 'normal', ['normal', '360', 'video'], true) ? $ekstra['tur'] : 'normal';
            $id = $this->resimler->ekle($hedefId, $goreceli, null, $tur, $kapak);
            $this->denetim->kaydet((int) $kullanici['id'], 'create', 'urun_resmi', $id, null, ['urun_id' => $hedefId], $ip, $ajan);

            return ['id' => $id, 'dosya_yolu' => $goreceli, 'kapak_mi' => $kapak];
        }

        if ($hedef === 'blog') {
            if ($this->yazilar->hamGetir($hedefId) === null) {
                unlink($tamYol);

                throw new Hata('NOT_FOUND', 'Yazı bulunamadı.', [['field' => 'hedef_id', 'issue' => 'not_found']], 404);
            }

            $this->yazilar->guncelle($hedefId, ['kapak_resmi' => $goreceli]);
            $this->denetim->kaydet((int) $kullanici['id'], 'update', 'blog', $hedefId, null, ['kapak_resmi' => $goreceli], $ip, $ajan);

            return ['id' => $hedefId, 'dosya_yolu' => $goreceli, 'kapak_mi' => true];
        }

        if ($hedef === 'donusum') {
            return ['id' => 0, 'dosya_yolu' => $goreceli, 'kapak_mi' => false];
        }

        $baslik = trim((string) ($ekstra['baslik'] ?? ''));
        if ($baslik === '') {
            unlink($tamYol);

            throw new Hata('VALIDATION_ERROR', 'Galeri başlığı zorunludur.', [['field' => 'baslik', 'issue' => 'required']], 422);
        }

        $id = $this->galeri->olustur([
            'dil_kodu' => $ekstra['dil_kodu'] ?? 'tr',
            'baslik' => $baslik,
            'aciklama' => $ekstra['aciklama'] ?? null,
            'dosya_yolu' => $goreceli,
            'proje_hikayesi' => $ekstra['proje_hikayesi'] ?? null,
        ]);
        $this->denetim->kaydet((int) $kullanici['id'], 'create', 'galeri', $id, null, ['baslik' => $baslik], $ip, $ajan);

        return ['id' => $id, 'dosya_yolu' => $goreceli, 'kapak_mi' => false];
    }

    public function resimSil(array $kullanici, int $id, string $ip, string $ajan): void
    {
        $satir = $this->resimler->idIleGetir($id);
        if ($satir === null) {
            throw new Hata('NOT_FOUND', 'Görsel bulunamadı.', [['field' => 'id', 'issue' => 'not_found']], 404);
        }

        $this->dosyaSil((string) $satir['dosya_yolu']);
        $this->resimler->sil($id);
        $this->denetim->kaydet((int) $kullanici['id'], 'delete', 'urun_resmi', $id, ['urun_id' => $satir['urun_id']], null, $ip, $ajan);
    }

    /** @param array<string, mixed> $dosya */
    public function dosyaKaydet(array $dosya, string $altDizin): string
    {
        if (($dosya['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new Hata('VALIDATION_ERROR', 'Dosya yüklenemedi.', [['field' => 'file', 'issue' => 'upload_error']], 422);
        }

        $kural = Config::al('guvenlik.yukleme', []);
        $maks = (int) ($kural['maks_boyut'] ?? 5 * 1024 * 1024);
        if ((int) $dosya['size'] > $maks) {
            throw new Hata('VALIDATION_ERROR', 'Dosya boyutu aşıldı (maks 5MB).', [['field' => 'file', 'issue' => 'too_large']], 422);
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file((string) $dosya['tmp_name']);
        $izinliler = $kural['izinli_mime'] ?? array_keys(self::UZANTI_HARITASI);
        if (!in_array($mime, $izinliler, true) || !isset(self::UZANTI_HARITASI[$mime])) {
            throw new Hata('VALIDATION_ERROR', 'Desteklenmeyen dosya türü.', [['field' => 'file', 'issue' => 'invalid_mime']], 422);
        }

        if (preg_match('/^[a-z0-9_]+$/', $altDizin) !== 1) {
            throw new Hata('VALIDATION_ERROR', 'Geçersiz hedef.', [['field' => 'hedef', 'issue' => 'invalid']], 422);
        }

        $kok = dirname(__DIR__, 3) . '/storage/yuklemeler/' . $altDizin;
        if (!is_dir($kok)) {
            mkdir($kok, 0750, true);
        }

        $ad = 'kml_' . uniqid('', true) . '.' . self::UZANTI_HARITASI[$mime];
        $tamYol = $kok . '/' . $ad;
        if (dirname($tamYol) !== $kok || !move_uploaded_file((string) $dosya['tmp_name'], $tamYol)) {
            throw new Hata('INTERNAL_ERROR', 'Dosya kaydedilemedi.', [], 500);
        }

        return 'storage/yuklemeler/' . $altDizin . '/' . $ad;
    }

    public function dosyaSil(string $goreceli): void
    {
        $kok = realpath(dirname(__DIR__, 3) . '/storage/yuklemeler');
        $tam = realpath(dirname(__DIR__, 3) . '/' . $goreceli);
        if ($kok !== false && $tam !== false && str_starts_with($tam, $kok) && is_file($tam)) {
            unlink($tam);
        }
    }
}
