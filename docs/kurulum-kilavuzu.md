# Kurulum Kılavuzu — Kamelya (yerel geliştirme)

## Gereksinimler

- PHP ≥ 8.1 (`pdo_mysql`, `json`, `mbstring`, `curl`, `openssl`)
- Composer 2, MySQL 8+, Node 18+ (SEO denetimi için)

## Adımlar

```bash
# 1. MySQL servisi
brew services start mysql
mysql -uroot -e "CREATE DATABASE kamelya CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -uroot -e "CREATE USER 'kamelya'@'localhost' IDENTIFIED BY 'YEREL_SIFRE'; GRANT ALL ON kamelya.* TO 'kamelya'@'localhost';"

# 2. Backend
cd backend
cp .env.example .env   # DB bilgilerini yazın
composer dump-autoload
php scripts/migrate.php up
ADMIN_SIFRE='en-az-12-karakter' php scripts/seed.php
php -S 127.0.0.1:8000 -t public

# 3. Frontend (:8080) ve Admin (:8001)
php -S 127.0.0.1:8080 -t ../frontend ../frontend/router.php
php -S 127.0.0.1:8001 -t ../admin
```

## Doğrulama

- `GET :8000/api/v1/health` → `{"success":true,...,"db":"up"}`
- `GET :8080/` → ana sayfa (200)
- `GET :8001/index.php` → admin login (200)

## Test kullanıcıları (yerel)

SQL ile oluşturun, işiniz bitince silin (şifre min 12 karakter).
