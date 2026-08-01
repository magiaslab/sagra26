# Sagra26 — gestionale cassa sagra

App Laravel 12 + Livewire 3 + Alpine.js per comande, cassa e riepiloghi della
**Sagra del Cacciucchetto**. Pensata per girare **offline su LAN** (mini-PC Ubuntu
come server, notebook Windows come postazioni cassa, stampanti laser monocromatiche
USB locali).

Sviluppo/test tipicamente su Mac; deploy su Ubuntu. **Il codice è identico**: cambia
solo il file `.env`.

## Requisiti

- PHP 8.3+
- Composer 2
- Estensioni: sqlite3, mbstring, xml, curl, zip, bcmath

## Setup sviluppo locale

```bash
cd sagra26
composer install
cp .env.example .env
php artisan key:generate

# SQLite: crea il file se non esiste
touch database/database.sqlite

# In .env assicurati di avere:
#   DB_CONNECTION=sqlite
#   # DB_DATABASE lasciato vuoto → usa database/database.sqlite

php artisan migrate --seed
npm install && npm run build
php artisan serve
```

Apri `http://localhost:8000`.

- **Cassa** `/cassa` — inserimento tastiera (nessun round-trip per ogni tasto)
- **Riepilogo** `/riepilogo` — live poll
- **Gestione** `/gestione` — PIN di default `1234` (modificabile in Impostazioni)

## Test

```bash
php artisan test
# oppure: ./vendor/bin/pest
```

## Deploy

Vedi [DEPLOY.md](DEPLOY.md) per la copia sul server Ubuntu. Unit systemd e script
di backup sono in `deploy/`.

## Note offline

Nessuna dipendenza da CDN/API esterne a runtime: UI con Tailwind via Vite
(`npm run build` → `public/build`), stampa mm in `public/css/print.css`.
Alpine.js arriva con Livewire. La stampa comanda è pensata per Chrome kiosk-printing
e layout B/N (bordi/testo, mai solo `background-color`).
