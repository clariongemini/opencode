#!/usr/bin/env bash

# =============================================================================
# RESTORE DATABASE — KAMELYA
# =============================================================================
# Kullanım: ./scripts/restore-db.sh [backup_dosyasi]
# Örnek: ./scripts/restore-db.sh /var/www/kamelya/backups/db_backup_2026-09-22_02-00-00.sql.gz
# =============================================================================

set -euo pipefail

# ---- AYARLAR ----
ENV_FILE="/var/www/kamelya/current/.env"
BACKUP_DIR="/var/www/kamelya/backups"

# ---- RENKLİ ÇIKTI ----
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log() { echo -e "${GREEN}[$(date '+%Y-%m-%d %H:%M:%S')]${NC} $*"; }
warn() { echo -e "${YELLOW}[$(date '+%Y-%m-%d %H:%M:%S')] UYARI:${NC} $*"; }
error() { echo -e "${RED}[$(date '+%Y-%m-%d %H:%M:%S')] HATA:${NC} $*"; }
info() { echo -e "${BLUE}[$(date '+%Y-%m-%d %H:%M:%S')] BİLGİ:${NC} $*"; }

# ---- .env kontrol ----
if [[ ! -f "$ENV_FILE" ]]; then
    error ".env dosyası bulunamadı: $ENV_FILE"
    exit 1
fi

# .env'den veritabanı bilgilerini oku
DB_HOST=$(grep '^DB_HOST=' "$ENV_FILE" | cut -d'=' -f2- | tr -d '"')
DB_PORT=$(grep '^DB_PORT=' "$ENV_FILE" | cut -d'=' -f2- | tr -d '"')
DB_ADI=$(grep '^DB_ADI=' "$ENV_FILE" | cut -d'=' -f2- | tr -d '"')
DB_KULLANICI=$(grep '^DB_KULLANICI=' "$ENV_FILE" | cut -d'=' -f2- | tr -d '"')
DB_SIFRE=$(grep '^DB_SIFRE=' "$ENV_FILE" | cut -d'=' -f2- | tr -d '"')

if [[ -z "$DB_HOST" || -z "$DB_ADI" || -z "$DB_KULLANICI" || -z "$DB_SIFRE" ]]; then
    error ".env dosyasında veritabanı bilgileri eksik"
    exit 1
fi

# ---- Argüman: Backup dosyası ----
if [[ $# -eq 1 ]]; then
    BACKUP_FILE="$1"
else
    # En son yedeği listele ve seç
    echo "Mevcut yedekler:"
    mapfile -t BACKUPS < <(ls -1t "$BACKUP_DIR"/db_backup_*.sql.gz 2>/dev/null)
    if [[ ${#BACKUPS[@]} -eq 0 ]]; then
        error "Yedek dosyası bulunamadı: $BACKUP_DIR"
        exit 1
    fi

    for i in "${!BACKUPS[@]}"; do
        SIZE=$(du -h "${BACKUPS[$i]}" | cut -f1)
        DATE=$(basename "${BACKUPS[$i]}" | sed 's/db_backup_\(.*\)\.sql\.gz/\1/')
        echo "  [$i] $DATE ($SIZE)"
    done

    read -p "Hangi yedek? (0-${#BACKUPS[@]}-1, Enter=son): " CHOICE
    CHOICE=${CHOICE:-0}
    BACKUP_FILE="${BACKUPS[$CHOICE]}"
fi

if [[ ! -f "$BACKUP_FILE" ]]; then
    error "Dosya bulunamadı: $BACKUP_FILE"
    exit 1
fi

info "Seçilen yedek: $BACKUP_FILE"
info "Boyut: $(du -h "$BACKUP_FILE" | cut -f1)"
info "Hedef veritabanı: $DB_ADI @ $DB_HOST"

# ---- ONAY ----
warn "BU İŞLEM MEVCUT VERİTABANININ ÜZERİNE YAZAR!"
read -p "Devam etmek istiyor musunuz? (y/N): " CONFIRM
if [[ "${CONFIRM,,}" != "y" ]]; then
    log "İptal edildi"
    exit 0
fi

# ---- Veritabanını sıfırla ve geri yükle ----
log "Veritabanı geri yükleniyor..."

# 1. Veritabanını drop & create (temiz başlangıç)
mysql \
    --host="$DB_HOST" \
    --port="${DB_PORT:-3306}" \
    --user="$DB_KULLANICI" \
    --password="$DB_SIFRE" \
    --execute="DROP DATABASE IF EXISTS \`$DB_ADI\`; CREATE DATABASE \`$DB_ADI\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

if [[ $? -ne 0 ]]; then
    error "Veritabanı sıfırlama başarısız"
    exit 1
fi

# 2. Yedeği geri yükle
gunzip -c "$BACKUP_FILE" | mysql \
    --host="$DB_HOST" \
    --port="${DB_PORT:-3306}" \
    --user="$DB_KULLANICI" \
    --password="$DB_SIFRE" \
    "$DB_ADI"

if [[ $? -ne 0 ]]; then
    error "Geri yükleme başarısız"
    exit 1
fi

log "✓ Veritabanı başarıyla geri yüklendi: $BACKUP_FILE"

# ---- Migration durumu kontrol (opsiyonel) ----
if [[ -f "/var/www/kamelya/current/scripts/migrate.php" ]]; then
    log "Migration durumu kontrol ediliyor..."
    cd /var/www/kamelya/current && php scripts/migrate.php durum
fi