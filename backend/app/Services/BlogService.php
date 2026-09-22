<?php

declare(strict_types=1);

namespace Kamelya\Services;

use Kamelya\Core\Hata;
use Kamelya\Repositories\BlogRepository;
use PDO;

/** Public blog okuma orkestrasyonu. */
final class BlogService
{
    public function __construct(
        private PDO $baglanti,
        private BlogRepository $blog
    ) {
    }

    /** @return array{satirlar: array, toplam: int} */
    public function liste(string $dil, int $sayfa, int $adet): array
    {
        return $this->blog->liste($dil, $sayfa, $adet);
    }

    /** @return array<string, mixed> */
    public function detay(string $dil, string $slug): array
    {
        $yazi = $this->blog->slugIleGetir($dil, $slug);
        if ($yazi === null) {
            throw new Hata('BLOG_NOT_FOUND', 'Yazı bulunamadı.', [['field' => 'slug', 'issue' => 'not_found']], 404);
        }

        $yazi['ilgili'] = $this->blog->ilgiliGetir($dil, (int) $yazi['id']);

        return $yazi;
    }
}
