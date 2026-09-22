<?php

declare(strict_types=1);

namespace Kamelya\Services;

/**
 * El yapımı SMTP istemcisi (harici paket yok): EHLO, STARTTLS (tls),
 * AUTH LOGIN (kullanıcı varsa), MAIL/RCPT/DATA/QUIT. Hata → RuntimeException.
 */
final class SmtpTasiyici
{
    public function __construct(
        private string $sunucu,
        private int $port,
        private ?string $kullanici,
        private ?string $sifre,
        private string $sifreleme,
        private string $gonderen
    ) {
    }

    public function gonder(string $alici, string $konu, string $govde): void
    {
        if (filter_var($alici, FILTER_VALIDATE_EMAIL) === false) {
            throw new \RuntimeException('Geçersiz alıcı: ' . $alici);
        }

        $protokol = $this->sifreleme === 'ssl' ? 'ssl://' : 'tcp://';
        $soket = @stream_socket_client($protokol . $this->sunucu . ':' . $this->port, $hataNo, $hata, 15);
        if ($soket === false) {
            throw new \RuntimeException('SMTP bağlantısı kurulamadı: ' . $hata);
        }

        stream_set_timeout($soket, 15);
        $this->bekle($soket, [220]);

        $this->komut($soket, 'EHLO kamelya', [250]);
        if ($this->sifreleme === 'tls') {
            $this->komut($soket, 'STARTTLS', [220]);
            $kripto = @stream_socket_enable_crypto($soket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            if ($kripto !== true) {
                fclose($soket);

                throw new \RuntimeException('STARTTLS kurulamadı.');
            }

            $this->komut($soket, 'EHLO kamelya', [250]);
        }

        if ($this->kullanici !== null && $this->kullanici !== '') {
            $this->komut($soket, 'AUTH LOGIN', [334]);
            $this->komut($soket, base64_encode($this->kullanici), [334]);
            $this->komut($soket, base64_encode((string) $this->sifre), [235]);
        }

        $this->komut($soket, 'MAIL FROM:<' . $this->gonderen . '>', [250]);
        $this->komut($soket, 'RCPT TO:<' . $alici . '>', [250, 251]);

        $this->komut($soket, 'DATA', [354]);
        $konuKodlu = mb_encode_mimeheader($konu, 'UTF-8', 'B', "\r\n");
        $mesaj = "From: {$this->gonderen}\r\nTo: {$alici}\r\nSubject: {$konuKodlu}\r\n"
            . "MIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: 8bit\r\n\r\n{$govde}\r\n.";
        $this->komut($soket, $mesaj, [250]);
        $this->komut($soket, 'QUIT', [221]);
        fclose($soket);
    }

    /** @param array<int, int> $beklenen */
    private function bekle($soket, array $beklenen): string
    {
        $yanit = '';
        while (($satir = fgets($soket, 512)) !== false) {
            $yanit .= $satir;
            if (strlen($satir) >= 4 && $satir[3] === ' ') {
                break;
            }
        }

        $kod = (int) substr($yanit, 0, 3);
        if (!in_array($kod, $beklenen, true)) {
            fclose($soket);

            throw new \RuntimeException('SMTP beklenmeyen yanıt: ' . trim($yanit));
        }

        return $yanit;
    }

    /** @param array<int, int> $beklenen */
    private function komut($soket, string $komut, array $beklenen): string
    {
        fwrite($soket, $komut . "\r\n");

        return $this->bekle($soket, $beklenen);
    }
}
