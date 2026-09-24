# Deployment Kılavuzu — Kamelya (Production)

**Önemli:** Bu kılavuz F16.1 altyapı dosyaları hazırlandıktan sonra güncellendi. İlk gerçek deploy geliştirici sunucusunda bu adımlarla yapılır.

---

## 1. Sunucu — Hetzner Cloud CX22

| Özellik | Değer |
|---------|-------|
| Sağlayıcı | Hetzner Cloud |
| Lokasyon | Falkenstein / Nuremberg (AB) |
| Plan | CX22 (2 vCPU, 4 GB RAM, 40 GB SSD) |
| İşletim Sistemi | Ubuntu 24.04 LTS |
| SSH Key | Ed25519 (deploy kullanıcısı için) |

### 1.1 Sunucu Kurulumu

```bash
# 1. Hetzner Console > Projects > Add Server > CX22 > Ubuntu 24.04 > SSH Key ekle
# 2. IPv4 not et (örn: 116.203.xxx.xxx)

# 3. Sunucuda ilk giriş (root)
ssh root@116.203.xxx.xxx

# 4. Deploy kullanıcısı oluştur
adduser deploy --gecos "" --disabled-password
usermod -aG sudo deploy
mkdir -p /home/deploy/.ssh
cp /root/.ssh/authorized_keys /home/deploy/.ssh/
chown -R deploy:deploy /home/deploy/.ssh
chmod 700 /home/deploy/.ssh
chmod 600 /home/deploy/.ssh/authorized_keys

# 5. Paketler
apt update && apt upgrade -y
apt install -y nginx php8.3-fpm php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl php8.3-gd php8.3-zip php8.3-bcmath php8.3-intl mysql-client certbot python3-certbot-nginx git unzip

# 6. PHP-FPM ayarları
sed -i 's/;cgi.fix_pathinfo=1/cgi.fix_pathinfo=0/' /etc/php/8.3/fpm/php.ini
systemctl restart php8.3-fpm

# 7. Nginx site config (nginx/kamelya.conf → /etc/nginx/sites-available/kamelya.conf)
#    Bkz. Section 6
```

---

## 2. Domain & DNS — Cloudflare

| Kayıp | Tür | İçerik | Proxy |
|-------|-----|--------|-------|
| @ | A | 116.203.xxx.xxx (Hetzner IP) | Proxied (Turuncu bulut) |
| www | CNAME | @ | Proxied |

### 2.1 Cloudflare SSL/TLS

- **SSL/TLS Mode**: Full (Strict)
- **Always Use HTTPS**: On
- **Automatic HTTPS Rewrites**: On
- **Minimum TLS Version**: TLS 1.2

### 2.2 Cloudflare WAF (Opsiyonel)

- Bot Fight Mode: On
- Rate Limiting: `/api/v1/calculate` → 30 req/min

---

## 3. Deploy — Deployer.php (Atomic Symlink + Rollback)

### 3.1 Yerel Hazırlık

```bash
# 1. Deployer kurulum
composer require deployer/deployer --dev
# Veya phar: curl -o deployer.phar https://deployer.org/deployer.phar

# 2. deploy.php düzenle (TODO değerleri doldur)
#    - hostname: Hetzner IP
#    - user: deploy
#    - identity_file: ~/.ssh/id_ed25519
#    - repository: git@github.com:clariongemini/opencode.git (veya özel repo)

# 3. SSH config (~/.ssh/config)
Host kamelya-prod
    HostName 116.203.xxx.xxx
    User deploy
    IdentityFile ~/.ssh/id_ed25519
    ForwardAgent yes
```

### 3.2 İlk Deploy

```bash
# Sunucuda .env.production oluştur (Section 4)
ssh kamelya-prod "mkdir -p /var/www/kamelya/shared"
scp backend/.env.production kamelya-prod:/var/www/kamelya/shared/.env
ssh kamelya-prod "chmod 600 /var/www/kamelya/shared/.env"

# Deploy
vendor/bin/dep deploy production
# Veya: php deployer.phar deploy production
```

### 3.3 Rollback

```bash
vendor/bin/dep rollback production
```

---

## 4. Ortam — `.env.production`

Dosya: `/var/www/kamelya/shared/.env` (Deployer `shared_files` ile symlink)

```bash
# Sunucuda oluştur:
cat > /var/www/kamelya/shared/.env << 'EOF'
APP_ENV=production
APP_DEBUG=false
APP_URL=https://kamelya.com
FORCE_HTTPS=true

DB_HOST=127.0.0.1
DB_PORT=3306
DB_ADI=kamelya
DB_KULLANICI=kamelya
DB_SIFRE=GÜÇLÜ_ŞİFRE_BURAYA

JWT_GIZLI_ANAHTAR=$(openssl rand -base64 32)

SMTP_HOST=smtp-relay.brevo.com
SMTP_PORT=587
SMTP_KULLANICI=BREVO_SMTP_LOGIN
SMTP_SIFRE=BREVO_SMTP_PASSWORD
SMTP_FROM_EMAIL=noreply@kamelya.com
SMTP_FROM_NAME=Kamelya
SMTP_TLS=true
BILDIRIM_EPOSTALARI=admin@kamelya.com,satis@kamelya.com

GA4_ID=G-XXXXXXXXXX
GSC_SERVICE_ACCOUNT_JSON=BASE64_ENCODED_JSON
GSC_SITE_URL=https://kamelya.com
CLARITY_ID=XXXXXXXX

ADMIN_SIFRE=GÜÇLÜ_ŞİFRE_BURAYA
EOF
chmod 600 /var/www/kamelya/shared/.env
```

**Tüm "GÜÇLÜ_ŞİFRE_BURAYA" ve placeholder değerler gerçeklerle değiştirilmeli.**

---

## 5. Veritabanı — MySQL 8+

### 5.1 Kurulum

```bash
# MySQL secure installation
mysql_secure_installation

# Veritabanı ve kullanıcı
mysql -u root -p << 'SQL'
CREATE DATABASE kamelya CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'kamelya'@'localhost' IDENTIFIED BY 'GÜÇLÜ_DB_ŞİFRESİ';
GRANT ALL PRIVILEGES ON kamelya.* TO 'kamelya'@'localhost';
FLUSH PRIVILEGES;
SQL
```

### 5.2 Migration

```bash
# Deploy sonrası otomatik çalışır (deploy.php 'migrate' task)
# Manuel:
cd /var/www/kamelya/current && php scripts/migrate.php up
```

> **Not:** Seed production'da **çalıştırılmaz** (kodda `APP_ENV=production` guard var). İlk admin SQL ile eklenir:
```sql
INSERT INTO kullanicilar (ad_soyad, eposta, sifre_hash, rol, aktif)
VALUES ('Site Yöneticisi', 'admin@kamelya.com', '$2y$10$...', 'yonetici', 1);
```

---

## 6. Nginx — Production Config

Dosya: `/etc/nginx/sites-available/kamelya.conf` (repo: `nginx/kamelya.conf`)

```bash
# 1. Config kopyala
cp /var/www/kamelya/current/nginx/kamelya.conf /etc/nginx/sites-available/kamelya.conf

# 2. Enable
ln -sf /etc/nginx/sites-available/kamelya.conf /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default

# 3. SSL (Let's Encrypt) - İlk defa
certbot --nginx -d kamelya.com -d www.kamelya.com --email admin@kamelya.com --agree-tos --non-interactive --redirect

# 4. Test & Reload
nginx -t && systemctl reload nginx

# 5. Otomatik yenileme (certbot timer zaten kurulu)
systemctl status certbot.timer
```

### 6.2 Sitemap & Robots Routing

Nginx config `nginx/kamelya.conf` içinde `/sitemap.xml` ve `/robots.txt` routing eklensin:
```nginx
location = /sitemap.xml {
    try_files $uri /router.php?sitemap=1;
}
location = /robots.txt {
    try_files $uri /router.php?robots=1;
}
```
Bu sayede frontend router (`frontend/router.php`) `/sitemap.xml` ve `/robots.txt` URL'lerini doğru şekilde yönlendirir.

**Özellikler:**
- HTTP → HTTPS 301
- HSTS (1 yıl, preload)
- CSP Report-Only (başlangıç), sonra enforce
- Security Headers (X-Frame-Options, CSP, Referrer-Policy, Permissions-Policy)
- Gzip + Static Cache (1 yıl)
- PHP-FPM socket
- Front Controller (`public/index.php`)

---

## 7. SMTP — Brevo (Sendinblue)

1. Brevo.com > SMTP & API > SMTP
2. SMTP Server: `smtp-relay.brevo.com` Port: `587` TLS
3. Login/Password → `.env.production` → `SMTP_KULLANICI`, `SMTP_SIFRE`
300 e-posta/gün ücretsiz.

---

## 8. Monitoring

| Araç | Kullanım | Kurulum |
|------|----------|---------|
| **UptimeRobot** | Uptime (5 dk aralık) | uptimerobot.com > Add Monitor > HTTPS kamelya.com/api/v1/health |
| **Better Stack** | Log aggregation | betterstack.com > Source > Nginx / PHP-FPM / App logs |

### 8.1 Better Stack Agent

```bash
# Better Stack > Sources > Connect Source > Linux
bash -c "$(curl -sSL https://betterstack.com/install.sh)" -- -t TOKEN
```

---

## 9. Zamanlanmış İşler (Cron)

```bash
# deploy kullanıcısı crontab -e
# 1. GSC Senkron (her gün 03:00)
0 3 * * * /usr/bin/php /var/www/kamelya/current/scripts/gsc-senkronize.php --gun=30 >> /var/log/kamelya-gsc.log 2>&1

# 2. Bildirim Gönder (her 5 dk)
*/5 * * * * /usr/bin/php /var/www/kamelya/current/scripts/bildirim-gonder.php --limit=20 >> /var/log/kamelya-bildirim.log 2>&1

# 3. DB Backup (her gün 02:00)
0 2 * * * /var/www/kamelya/current/scripts/backup-db.sh >> /var/log/kamelya-backup.log 2>&1

# 4. Audit Log Temizle (her gün 04:00, 90 günlük tut)
0 4 * * * /usr/bin/php /var/www/kamelya/current/scripts/audit-temizle.php --gun=90 >> /var/log/kamelya-audit.log 2>&1
# NOT: scripts/audit-temizle.php henüz oluşturilmemiş — F16.4 deploy sonrası.
# Gecici: DELETE FROM audit_loglari WHERE olusturulma_zamani < NOW() - INTERVAL 90 DAY
```

---

## 10. Yedekleme & Geri Yükleme

### 10.1 Günlük DB Backup (Local + Hetzner Storage Box)

Script: `scripts/backup-db.sh` (Deployer `shared_dirs`'a kopyalar)

```bash
# Hetzner Storage Box (opsiyonel ama önerilir)
# Console > Storage Box > oluştur > SSH key ekle
# ~/.ssh/config:
# Host storagebox
#     HostName uXXXXXX.your-storagebox.de
#     User uXXXXXX
#     Port 23
#     IdentityFile ~/.ssh/id_ed25519

# backup-db.sh içindeki STORAGE_BOX_* değişkenlerini doldur
```

### 10.2 Geri Yükleme Testi (Çeyrekte bir)

```bash
# Staging/test veritabanında
ssh kamelya-prod
cd /var/www/kamelya/current
./scripts/restore-db.sh /var/www/kamelya/backups/db_backup_2026-09-22_02-00-00.sql.gz
```

### 10.3 Dosya Yedeği (storage/uploads)

```bash
# rsync to Storage Box
rsync -avz -e "ssh -p 23" /var/www/kamelya/shared/storage/ uXXXXXX@uXXXXXX.your-storagebox.de:/home/backups/kamelya/storage/
```

---

## 11. Go-Live Checklist (F16.4 → F16.5)

| Adım | Komut / Doğrulama |
|------|-------------------|
| DNS | `dig kamelya.com +short` → Hetzner IP (Cloudflare proxy) |
| SSL | `curl -I https://kamelya.com` → 200, HSTS header |
| Health | `curl https://kamelya.com/api/v1/health` → `{"success":true,"data":{"status":"ok","db":"up"}}` |
| Hesaplama | POST `/api/v1/calculate` → fiyat + capacity |
| Lead → Randevu | Form submit → 201 + email (Brevo) |
| GA4/Clarity | Realtime raporunda event'ler görünüyor mu? |
| PWA | `https://kamelya.com/manifest.json` → 200, SW `aktif` |
| Sitemap | `https://kamelya.com/sitemap.xml` → 200, URL'ler |
| Robots | `https://kamelya.com/robots.txt` → Sitemap ref |

---

## 12. GSC Kurulumu (F16.3)

Bkz. `docs/gsc-kurulum.md` — Service Account + `scripts/gsc-senkronize.php` cron.

---

## 13. Rollback Prosedürü

```bash
# 1. Kod rollback
vendor/bin/dep rollback production

# 2. DB rollback (eğer migration bozduysa)
./scripts/restore-db.sh /var/www/kamelya/backups/db_backup_YYYY-MM-DD_HH-MM-SS.sql.gz

# 3. Health check
curl https://kamelya.com/api/v1/health
```

---

## 14. Güvenlik Notları

- `.env` dosyası **asla** git'e commit edilmez (`shared_files` ile yönetilir)
- `JWT_GIZLI_ANAHTAR` her deploy'da **değil**, sadece rotasyon gerektiğinde değişir
- `FORCE_HTTPS=true` production'da zorunlu
- Admin panel IP allowlist (opsiyonel): Nginx config'te `allow 1.2.3.4; deny all;` bloğu
- `csp` başlangıçta `Report-Only`, 1 hafta sonra `enforce` et
- `storage/` ve `backups/` dizinleri web'den erişilemez (Nginx `internal`)

---

## 15. Dosya Konumları Özeti

| Dosya | Repo Yolu | Sunucu Yolu |
|-------|-----------|-------------|
| Nginx Config | `nginx/kamelya.conf` | `/etc/nginx/sites-available/kamelya.conf` |
| Deploy Script | `deploy.php` | Yerel (CI/CD runner) |
| .env Template | `backend/.env.production` | `/var/www/kamelya/shared/.env` |
| DB Backup | `scripts/backup-db.sh` | `/var/www/kamelya/current/scripts/backup-db.sh` |
| DB Restore | `scripts/restore-db.sh` | `/var/www/kamelya/current/scripts/restore-db.sh` |
| GSC Sync | `scripts/gsc-senkronize.php` | `/var/www/kamelya/current/scripts/gsc-senkronize.php` |
| Bildirim | `scripts/bildirim-gonder.php` | `/var/www/kamelya/current/scripts/bildirim-gonder.php` |