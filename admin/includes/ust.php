<?php

declare(strict_types=1);

/** @var string $sayfaBaslik */
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title><?= htmlspecialchars($sayfaBaslik ?? 'Kamelya Admin', ENT_QUOTES, 'UTF-8') ?> — Kamelya Admin</title>
  <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>
