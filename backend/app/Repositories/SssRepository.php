<?php

declare(strict_types=1);

namespace Kamelya\Repositories;

use PDO;

/** Public SSS okuma — dil + opsuyonel kapsam filtresi. */
final class SssRepository
{
    public function __construct(private PDO $baglanti)
    {
    }

    /**
     * @return array<int, array{id: int, sira: int, kapsam: string|null, soru: string, cevap: string}>
     */
    public function liste(string $dil, ?string $kapsam = null): array
    {
        $sql = 'SELECT s.id, s.sira, s.sayfa_kapsami AS kapsam, c.soru, c.cevap
                FROM sss_sorulari s
                JOIN sss_cevirileri c ON c.soru_id = s.id AND c.dil_kodu = :dil
                WHERE s.aktif = 1';
        if ($kapsam !== null && $kapsam !== '') {
            $sql .= ' AND s.sayfa_kapsami = :kapsam';
        }
        $sql .= ' ORDER BY s.sira ASC, s.id ASC';

        $ifade = $this->baglanti->prepare($sql);
        $ifade->bindValue(':dil', $dil);
        if ($kapsam !== null && $kapsam !== '') {
            $ifade->bindValue(':kapsam', $kapsam);
        }
        $ifade->execute();

        $satirlar = [];
        foreach ($ifade->fetchAll() as $satir) {
            $satirlar[] = [
                'id' => (int) $satir['id'],
                'sira' => (int) $satir['sira'],
                'kapsam' => $satir['kapsam'] !== null ? (string) $satir['kapsam'] : null,
                'soru' => (string) $satir['soru'],
                'cevap' => (string) $satir['cevap'],
            ];
        }

        return $satirlar;
    }
}
