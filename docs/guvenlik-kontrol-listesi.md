# Güvenlik Kontrol Listesi — Kamelya (production)

- [ ] `APP_ENV=production`, `APP_DEBUG=false` (hata detayı sızmaz).
- [ ] `FORCE_HTTPS=true` + geçerli TLS sertifikası (HSTS yalnızca HTTPS'te gönderilir).
- [ ] `JWT_GIZLI_ANAHTAR` ≥ 32 bayt rastgele; `.env` izinleri `600`.
- [ ] `CORS_ORIGINS` yalnızca prod domain(ler)i (yıldız yasak).
- [ ] Admin şifreleri ≥ 12 karakter; ilk kurulum sonrası seed şifresi değiştirilir.
- [ ] `storage/yuklemeler/` web kökü dışı + yazma izinleri kısıtlı (0750 dizin).
- [ ] Rate limitler devrede (login 5/15dk, leads 3/saat) — `Retry-After` izlenir.
- [ ] Audit log 90 günden eskileri arşivlenip silinir (cron — F8 sonrası iş).
- [ ] `seo_analitik_verileri` + MySQL dump günlük yedekli (şifreli, 30 gün).
- [ ] Hata logları (`error_log`) ve slow-query log izlenir; uptime monitörü (`/api/v1/health`) 1 dk aralıklı.
- [ ] Sunucu: dizin listeleme kapalı, dotfile erişimi engelli (`.htaccess`/Nginx), PHP sürümü güncel.
