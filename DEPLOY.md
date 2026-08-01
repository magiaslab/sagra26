# Deploy Sagra26 sul server Ubuntu

Questo documento è **specifico del progetto**. Per la parte sistema operativo
(dual boot, IP fisso, firewall, stampanti) segui la guida di installazione generale
già predisposta (fasi relative a rete/systemd/firewall).

## 1. Copia della cartella

Sul mini-PC Ubuntu, copia l'intera cartella del progetto in:

```text
~/cassa
```

(es. `rsync -a sagra26/ utente@server:~/cassa/`)

## 2. Dipendenze e ambiente

```bash
cd ~/cassa
composer install --no-dev --optimize-autoloader
cp .env.example .env   # se non esiste già
php artisan key:generate

# Imposta in .env:
#   APP_ENV=production
#   APP_DEBUG=false
#   APP_URL=http://<host-o-nome-lan>:8000
#   DB_CONNECTION=sqlite
#   DB_DATABASE=/home/<utente>/cassa/database/database.sqlite
#   PIN_MASTER_RESET=<codice-segreto-non-default>

touch database/database.sqlite
php artisan migrate --seed --force
```

## 3. Permessi

```bash
chmod -R ug+rwx storage bootstrap/cache
chmod 664 database/database.sqlite
chmod +x deploy/backup.sh
```

Assicurati che l'utente del servizio systemd possa scrivere su `storage/`,
`bootstrap/cache/` e sul file SQLite.

## 4. Systemd

Adatta `deploy/cassa.service` (`User`, `WorkingDirectory`) e installalo:

```bash
sudo cp deploy/cassa.service /etc/systemd/system/cassa.service
sudo systemctl daemon-reload
sudo systemctl enable --now cassa.service
```

L'`ExecStart` usa `php artisan serve --host=0.0.0.0 --port=8000` come da guida.

## 5. Backup

Cron ogni 5 minuti:

```cron
*/5 * * * * /home/<utente>/cassa/deploy/backup.sh
```

I file finiscono in `storage/backups/`.

## 6. Postazioni cassa

Sui notebook Windows apri Chrome su `http://<server>:8000/cassa`, seleziona la
postazione (Cassa A / Cassa B) e usa la stampa su fogli A4 orizzontali.

## 7. PIN gestione e recupero

Al primo deploy, imposta in `.env` un valore reale per `PIN_MASTER_RESET`
(diverso dal placeholder), e **annotalo qui sotto o in un posto sicuro non digitale**:

    Codice master: ____________________

Se il PIN operativo (quello a 4 cifre usato quotidianamente) viene dimenticato:
apri `/gestione/pin`, tocca "PIN dimenticato?", inserisci il codice master
sopra e imposta un nuovo PIN direttamente dal browser — nessun terminale necessario.

### Se anche il codice master è perso (ultima risorsa, da terminale)

    sqlite3 ~/cassa/database/database.sqlite "SELECT pin_gestione FROM impostazioni;"
    sqlite3 ~/cassa/database/database.sqlite "UPDATE impostazioni SET pin_gestione='1234' WHERE id=1;"

Il PIN è salvato in chiaro (non hashato) di proposito: serve solo a tenere fuori
un cassiere per sbaglio dall'area gestionale, non è una barriera di sicurezza vera.
