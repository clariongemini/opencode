# Bildirim Altyapı Planı — Kamelya (F8'de uygulanacak)

**Kural:** Bu fazda gönderim yok — yalnızca plan. Tüm zamanlar Europe/Istanbul.

## Tetikleyiciler

| Olay | Alıcı | Kanal (öneri) | Zaman |
|---|---|---|---|
| Keşif/montaj −24 saat | Ekip üyesi + müşteri | SMS (müşteri), e-posta (ekip) | `randevu_tarihi - 24h` |
| Keşif/montaj −2 saat | Ekip üyesi | SMS | `randevu_tarihi - 2h` |
| Durum → `tamamlandi` | Satış ekibi | Panel içi (audit log var) | anlık |
| Aşırı yük (>40 sa/hafta) | Yönetici | e-posta özeti (haftalık) | Pazartesi 08:00 |

## Teknik notlar

- Zamanlayıcı: cron (dakikada bir çalışan `scripts/bildirim-gonder.php` önerisi) + `randevular` taraması (`durum != iptal`, bildirim bayrağı sütunu F8 migration'ında eklenecek).
- Sağlayıcı seçimi geliştiricide (SMS: Netgsm/Twilio; e-posta: SMTP ayarları `.env`, commit dışı).
- KVKK: müşteri SMS'i için `talepler.kvkk_onayi` yeniden kullanılır; ret kaydı ayrıca tutulur.
- Tekrar-gönderim koruması: gönderilen hatırlatma `randevu_id + tetikleyici` bileşiğiyle işaretlenir (idempotent).
