<?php

declare(strict_types=1);

namespace Kamelya\Services;

use Kamelya\Core\Hata;
use Kamelya\Repositories\BultenRepository;
use PDO;

/** Bülten çift-onay akışı + toplu gönderim (kuyruk üzerinden). */
final class BultenService
{
    public function __construct(
        private PDO $baglanti,
        private BultenRepository $bulten,
        private BildirimService $bildirim,
        private AuditLogService $denetim
    ) {
    }

    /** @param array<string, mixed> $veri */
    public function kaydol(array $veri, string $ip, string $siteTabani): array
    {
        if (($veri['kvkk_onayi'] ?? false) !== true) {
            throw new Hata('VALIDATION_ERROR', 'KVKK onayı zorunludur.', [['field' => 'kvkk_onayi', 'issue' => 'required']], 422);
        }

        $eposta = strtolower(trim((string) ($veri['eposta'] ?? '')));
        if (filter_var($eposta, FILTER_VALIDATE_EMAIL) === false) {
            throw new Hata('VALIDATION_ERROR', 'E-posta geçersiz.', [['field' => 'eposta', 'issue' => 'invalid_format']], 422);
        }

        $mevcut = $this->bulten->epostaIleGetir($eposta);
        if ($mevcut !== null && $mevcut['durum'] === 'onaylandi') {
            throw new Hata('CONFLICT', 'Bu e-posta zaten abone.', [['field' => 'eposta', 'issue' => 'duplicate']], 409);
        }

        if ($mevcut !== null) {
            // Bekleyen/iptal kaydı varsa jetonu yenile (tekrar gönderim).
            $token = bin2hex(random_bytes(32));
            $this->baglanti->prepare('UPDATE bulten_aboneleri SET onay_token = ?, kvkk_onayi = 1, durum = \'bekliyor\' WHERE id = ?')
                ->execute([$token, $mevcut['id']]);
            $this->dogrulamaGonder($eposta, $token, $veri['dil_kodu'] ?? 'tr', $siteTabani);

            return ['mesaj' => 'Doğrulama e-postası yeniden gönderildi.'];
        }

        $token = bin2hex(random_bytes(32));
        $this->bulten->olustur([
            'eposta' => $eposta,
            'ad_soyad' => $veri['ad_soyad'] ?? null,
            'dil_kodu' => $veri['dil_kodu'] ?? 'tr',
            'onay_token' => $token,
            'kvkk_onayi' => true,
            'ip_adresi' => $ip,
        ]);
        $this->dogrulamaGonder($eposta, $token, $veri['dil_kodu'] ?? 'tr', $siteTabani);

        return ['mesaj' => 'Kaydınız alındı. E-postadaki doğrulama bağlantısına tıklayın.'];
    }

    public function onayla(string $token): array
    {
        $satir = $this->bulten->tokenIleGetir($token);
        if ($satir === null) {
            throw new Hata('NOT_FOUND', 'Doğrulama bağlantısı geçersiz.', [['field' => 'token', 'issue' => 'not_found']], 404);
        }

        if ($satir['durum'] !== 'onaylandi') {
            $this->bulten->onayla((int) $satir['id']);
        }

        return ['mesaj' => 'Aboneliğiniz onaylandı.'];
    }

    public function iptalEt(string $token): array
    {
        $satir = $this->bulten->tokenIleGetir($token);
        if ($satir === null) {
            throw new Hata('NOT_FOUND', 'Bağlantı geçersiz.', [['field' => 'token', 'issue' => 'not_found']], 404);
        }

        $this->bulten->iptal((int) $satir['id']);

        return ['mesaj' => 'Aboneliğiniz iptal edildi.'];
    }

    /** @return array{satirlar: array, toplam: int} */
    public function liste(int $sayfa, int $adet): array
    {
        return $this->bulten->adminListe($sayfa, $adet);
    }

    public function topluGonder(array $kullanici, string $konu, string $govde, string $ip, string $ajan): array
    {
        if (trim($konu) === '' || trim($govde) === '') {
            throw new Hata('VALIDATION_ERROR', 'Konu ve gövde zorunludur.', [['field' => 'konu', 'issue' => 'required']], 422);
        }

        $sayac = 0;
        foreach ($this->bulten->onaylilar() as $abone) {
            $this->bildirim->dogrudan('bulten', (string) $abone['eposta'], $konu, $govde);
            $sayac++;
        }

        $this->denetim->kaydet((int) $kullanici['id'], 'bulten_toplu', 'bulten', null, null, ['alici_sayisi' => $sayac], $ip, $ajan);

        return ['kuyruga_eklenen' => $sayac];
    }

    private function dogrulamaGonder(string $eposta, string $token, string $dil, string $siteTabani): void
    {
        $baglanti = rtrim($siteTabani, '/') . '/bulten/onay?token=' . $token;
        $this->bildirim->dogrudan(
            'bulten_dogrulama',
            $eposta,
            'Bülten aboneliğinizi doğrulayın — Kamelya',
            "Aboneliğinizi tamamlamak için bağlantıya tıklayın:\n{$baglanti}\n\nDil: {$dil}"
        );
    }
}
