<?php

declare(strict_types=1);

/** Ödeme bilgileri — ayarlar.banka_hesaplari JSON'undan tablo. */

$ayarlar = apiGet('/ayarlar', $dil);
$veri = $ayarlar['data'] ?? [];
$bankalar = json_decode((string) ($veri['banka_hesaplari'] ?? '[]'), true);
if (!is_array($bankalar)) {
    $bankalar = [];
}

$notHam = json_decode((string) ($veri['odeme_notu'] ?? ''), true);
$not = is_array($notHam) ? (string) ($notHam[$dil] ?? $notHam['tr'] ?? '') : '';

$SEO = [
    'baslik' => 'Ödeme Bilgileri — Kamelya',
    'aciklama' => 'Kamelya ödeme bilgileri: banka hesapları, IBAN listesi ve havale/EFT talimatları. Sipariş ödemelerinizi buradaki hesaplara yapabilirsiniz.',
    'yol' => $MEVCUT_YOL,
];
?>
<?= kirinti([['etiket' => t('anasayfa'), 'yol' => siteUrl('/')], ['etiket' => 'Ödeme Bilgileri', 'yol' => null]]) ?>
<h1>Ödeme Bilgileri</h1>
<?php if ($not !== ''): ?>
<p><?= htmlspecialchars($not, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>
<?php if ($bankalar === []): ?>
<p><?= htmlspecialchars(t('urun_bos'), ENT_QUOTES, 'UTF-8') ?></p>
<?php else: ?>
<table class="card"><tbody>
  <?php foreach ($bankalar as $b): ?>
  <tr>
    <th scope="row"><?= htmlspecialchars($b['banka'] ?? '', ENT_QUOTES, 'UTF-8') ?></th>
    <td><span class="iban-metin"><?= htmlspecialchars($b['iban'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
    <button type="button" class="btn iban-kopyala">Kopyala</button><br>
    <small><?= htmlspecialchars($b['hesap_sahibi'] ?? '', ENT_QUOTES, 'UTF-8') ?> — <?= htmlspecialchars($b['sube'] ?? '', ENT_QUOTES, 'UTF-8') ?></small></td>
  </tr>
  <?php endforeach; ?>
</tbody></table>
<?php endif; ?>
<script>
(function () {
  document.querySelectorAll('.iban-kopyala').forEach(function (b) {
    b.addEventListener('click', function () {
      var metin = b.parentElement.querySelector('.iban-metin').textContent;
      if (navigator.clipboard) navigator.clipboard.writeText(metin).then(function () { b.textContent = '✓'; });
    });
  });
})();
