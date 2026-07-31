#!/usr/bin/env bash
# Backup SQLite sagra26 — pensato per cron ogni 5 minuti
# Esempio crontab: */5 * * * * /home/utente/cassa/deploy/backup.sh

set -euo pipefail

# Directory del progetto (padre di deploy/)
ROOT="$(cd "$(dirname "$0")/.." && pwd)"

# Carica DB_DATABASE da .env se presente, altrimenti default
DB_FILE="${ROOT}/database/database.sqlite"
if [[ -f "${ROOT}/.env" ]]; then
  ENV_DB="$(grep -E '^DB_DATABASE=' "${ROOT}/.env" | head -1 | cut -d= -f2- | tr -d '"' | tr -d "'")"
  if [[ -n "${ENV_DB}" ]]; then
    DB_FILE="${ENV_DB}"
  fi
fi

BACKUP_DIR="${ROOT}/storage/backups"
mkdir -p "${BACKUP_DIR}"

TS="$(date +%Y%m%d-%H%M%S)"
DEST="${BACKUP_DIR}/database-${TS}.sqlite"

if [[ ! -f "${DB_FILE}" ]]; then
  echo "Database non trovato: ${DB_FILE}" >&2
  exit 1
fi

# Copia consistente (SQLite: meglio usare .backup via sqlite3 se disponibile)
if command -v sqlite3 >/dev/null 2>&1; then
  sqlite3 "${DB_FILE}" ".backup '${DEST}'"
else
  cp -a "${DB_FILE}" "${DEST}"
fi

# Mantieni solo gli ultimi 288 backup (~24h a 5 min)
ls -1t "${BACKUP_DIR}"/database-*.sqlite 2>/dev/null | tail -n +289 | xargs -r rm -f

echo "Backup OK: ${DEST}"
