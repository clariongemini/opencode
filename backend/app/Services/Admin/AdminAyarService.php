<?php

declare(strict_types=1);

namespace Kamelya\Services\Admin;

use Kamelya\Repositories\Admin\AyarYonetimRepository;
use Kamelya\Services\AuditLogService;
use PDO;

/** Site ayarları — yalnızca allowlist anahtarlar yazılabilir. */
final class AdminAyarService
{
    public function __construct(
        private PDO $baglanti,
        private AyarYonetimRepository $ayarlar,
        private AuditLogService $denetim
    ) {
    }

    /** @return array<int, array<string, mixed>> */
    public function liste(): array
    {
        return $this->ayarlar->tumunuGetir();
    }

    /** @param array<string, string> $degerler */
    public function guncelle(array $kullanici, array $degerler, string $ip, string $ajan): array
    {
        $sayac = $this->ayarlar->topluGuncelle($degerler);
        $this->denetim->kaydet((int) $kullanici['id'], 'update', 'ayar', null, null, ['guncellenen' => $sayac], $ip, $ajan);

        return ['guncellenen' => $sayac];
    }
}
