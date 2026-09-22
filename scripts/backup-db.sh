#!/usr/bin/env bash

# =============================================================================
# BACKUP DATABASE — KAMELYA
# =============================================================================
# Kullanım: ./scripts/backup-db.sh
# Cron: 0 2 * * * /var/www/kamelya/current/scripts/backup-db.sh >> /var/log/kamelya-backup.log 2>&1
# =============================================================================

set -euo pipefail

# ---- AYARLAR ----
# .env dosyasından okunur
ENV_FILE="/var/www/kamelya/current/.env"
BACKUP_DIR="/var/www/kamelya/backups"
RETENTION_DAYS=30

# Hetzner Storage Box (opsiyonel - uzak yedek)
# STORAGE_BOX_USER="uXXXXXX"
# STORAGE_BOX_HOST="uXXXXXX.your-storagebox.de"
# STORAGE_BOX_PATH="/home/backups/kamelya"

# ---- RENKLİ ÇIKTI ----
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

log() { echo -e "${GREEN}[$(date '+%Y-%m-%d %H:%M:%S')]${NC} $*"; }
warn() { echo -e "${YELLOW}[$(date '+%Y-%m-%d %H:%M:%S')] UYARI:${NC} $*"; }
error() { echo -e "${RED}[$(date '+%Y-%m-%d %H:%M:%S')] HATA:${NC} $*"; }

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

# ---- Yedek dizini ----
mkdir -p "$BACKUP_DIR"

# ---- Dosya adı ----
TIMESTAMP=$(date '+%Y-%m-%d_%H-%M-%S')
BACKUP_FILE="${BACKUP_DIR}/db_backup_${TIMESTAMP}.sql.gz"

log "Veritabanı yedeği başlatılıyor: $BACKUP_FILE"

# ---- mysqldump ----
# --single-transaction: InnoDB için tutarlı yedek (kilitlenmez)
# --routines --triggers: Prosedürler ve tetikleyiciler
# --quick: Bellek kullanımını azaltır
mysqldump \
    --host="$DB_HOST" \
    --port="${DB_PORT:-3306}" \
    --user="$DB_KULLANICI" \
    --password="$DB_SIFRE" \
    --single-transaction \
    --routines \
    --triggers \
    --quick \
    --databases "$DB_ADI" \
    | gzip > "$BACKUP_FILE"

if [[ $? -ne 0 ]]; then
    error "mysqldump başarısız"
    rm -f "$BACKUP_FILE"
    exit 1
fi

FILE_SIZE=$(du -h "$BACKUP_FILE" | cut -f1)
log "Yedek tamamlandı: $BACKUP_FILE ($FILE_SIZE)"

# ---- Eski yedekleri temizle (RETENTION_DAYS'den eski) ----
find "$BACKUP_DIR" -name 'db_backup_*.sql.gz' -mtime +$RETENTION_DAYS -delete
log "Eski yedekler temizlendi (>${RETENTION_DAYS} gün)"

# ---- Opsiyonel: Storage Box'a kopyala (rsync over SSH) ----
if [[ -n "${STORAGE_BOX_USER:-}" && -n "${STORAGE_BOX_HOST:-}" ]]; then
    log "Storage Box'a kopyalanıyor..."
    rsync -avz -e "ssh -p 23" "$BACKUP_DIR/" "${STORAGE_BOX_USER}@${STORAGE_BOX_HOST}:${STORAGE_BOX_PATH}/" \
        && log "Storage Box yedeği tamamlandı" \
        || warn "Storage Box kopyalama başarısız (yerel yedek duruyor)"
fi

log "✓ Yedek işlemi başarıyla tamamlandı"