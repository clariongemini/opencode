<?php

declare(strict_types=1);

namespace Kamelya\Services;

use Kamelya\Core\Gizlilik;
use PDO;

/** Kritik işlem günlüğü — IP maskeli saklanır, ham IP yazılmaz. */
final class AuditLogService
{
    public function __construct(private PDO $baglanti)
    {
    }

    /** @param array<string, mixed>|null $eski @param array<string, mixed>|null $yeni */
    public function kaydet(
        ?int $kullaniciId,
        string $islem,
        string $varlikTipi,
        ?int $varlikId,
        ?array $eski,
        ?array $yeni,
        string $ip,
        string $ajan
    ): void {
        $ifade = $this->baglanti->prepare(
            'INSERT INTO audit_loglari
             (kullanici_id, islem, varlik_tipi, varlik_id, eski_deger, yeni_deger, ip_adresi, user_agent)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $ifade->execute([
            $kullaniciId,
            $islem,
            $varlikTipi,
            $varlikId,
            $eski === null ? null : json_encode($eski, JSON_UNESCAPED_UNICODE),
            $yeni === null ? null : json_encode($yeni, JSON_UNESCAPED_UNICODE),
            Gizlilik::ipMaskele($ip),
            mb_substr($ajan, 0, 500),
        ]);
    }

    /** @return array{satirlar: array, toplam: int} */
    public function liste(int $sayfa, int $adet): array
    {
        $sayac = $this->baglanti->query('SELECT COUNT(*) FROM audit_loglari');
        $toplam = (int) $sayac->fetchColumn();

        $ifade = $this->baglanti->prepare(
            'SELECT * FROM audit_loglari ORDER BY id DESC LIMIT :lim OFFSET :off'
        );
        $ifade->bindValue(':lim', $adet, PDO::PARAM_INT);
        $ifade->bindValue(':off', ($sayfa - 1) * $adet, PDO::PARAM_INT);
        $ifade->execute();

        return ['satirlar' => $ifade->fetchAll(), 'toplam' => $toplam];
    }
}
