<?php

declare(strict_types=1);

/** Admin panel sabitleri — sır yok (token tarayıcıda tutulur). */

const API_TABAN = 'http://127.0.0.1:8000/api/v1';
const DILLER = ['tr', 'en', 'de', 'fr', 'it', 'ar'];
const RTL_DILLER = ['ar'];

function dilListesi(): string
{
    return implode(',', DILLER);
}
