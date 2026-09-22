<?php

declare(strict_types=1);

namespace Kamelya\Core;

use PDO;

/** Geri alınabilir migration sözleşmesi — her dosya bu interface'i döner. */
interface Migration
{
    public function up(PDO $baglanti): void;

    public function down(PDO $baglanti): void;
}
