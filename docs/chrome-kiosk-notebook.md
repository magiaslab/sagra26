# Collegamenti Chrome in kiosk — notebook cassa

Guida pratica per aprire la cassa Sagra26 a tutto schermo sui notebook Windows,
con stampa A4 orizzontale nella stessa finestra Chrome.

Sostituisci ovunque `IP` con l’indirizzo del mini-PC Ubuntu, ad esempio:

```text
http://192.168.178.191:8000/cassa
```

---

## Requisiti

- Google Chrome installato sul notebook
- Mini-PC Ubuntu con Sagra26 in ascolto su porta `8000` (servizio `cassa` / `php artisan serve`)
- Notebook e server sulla stessa LAN
- Stampante A4 orizzontale (landscape) configurata come predefinita (consigliato)

Percorso tipico di Chrome:

```text
C:\Program Files\Google\Chrome\Application\chrome.exe
```

Se non lo trovi, prova:

```text
C:\Program Files (x86)\Google\Chrome\Application\chrome.exe
```

---

## Opzione consigliata: file `.bat` sul Desktop

1. Sul Desktop del notebook, tasto destro → **Nuovo** → **Documento di testo**
2. Rinominalo in `Cassa.bat` (attenzione a togliere `.txt`)
3. Tasto destro → **Modifica** e incolla uno degli snippet sotto
4. Salva e chiudi
5. Doppio clic sul file per avviare

### A) Kiosk a tutto schermo (uso serata)

```bat
@echo off
start "" "C:\Program Files\Google\Chrome\Application\chrome.exe" ^
  --kiosk "http://IP:8000/cassa" ^
  --kiosk-printing ^
  --no-first-run ^
  --disable-session-crashed-bubble ^
  --disable-infobars ^
  --user-data-dir="%LOCALAPPDATA%\SagraCassaChrome"
```

- Apre Chrome a schermo intero sulla cassa
- Per uscire dal kiosk: di solito **Alt+F4** o **Alt+Tab** e chiudi la finestra  
  (su alcune versioni anche **Esc**)

### B) Finestra “app” (collaudi / meno invasivo)

```bat
@echo off
start "" "C:\Program Files\Google\Chrome\Application\chrome.exe" ^
  --app="http://IP:8000/cassa" ^
  --kiosk-printing ^
  --no-first-run ^
  --user-data-dir="%LOCALAPPDATA%\SagraCassaChrome"
```

- Finestra dedicata senza barre tipiche del browser
- La stampa comanda resta nella **stessa** finestra (importante per `--kiosk-printing`)

---

## Opzione alternativa: scorciatoia `.lnk`

1. Desktop → tasto destro → **Nuovo** → **Collegamento**
2. Come percorso inserisci (tutto su una riga):

```text
"C:\Program Files\Google\Chrome\Application\chrome.exe" --kiosk "http://IP:8000/cassa" --kiosk-printing --no-first-run --user-data-dir="%LOCALAPPDATA%\SagraCassaChrome"
```

3. Nome: `Cassa` (o `Cassa A` / `Cassa B`)
4. Se serve, tasto destro sul collegamento → **Proprietà**:
   - **Destinazione:** come sopra
   - **Da:** `C:\Program Files\Google\Chrome\Application`

---

## Due postazioni (Cassa A / Cassa B)

Usa un **profilo Chrome separato** per ogni postazione, così cookie/sessione non si mischiano.

### `Cassa A.bat`

```bat
@echo off
start "" "C:\Program Files\Google\Chrome\Application\chrome.exe" ^
  --kiosk "http://IP:8000/cassa" ^
  --kiosk-printing ^
  --no-first-run ^
  --disable-session-crashed-bubble ^
  --disable-infobars ^
  --user-data-dir="%LOCALAPPDATA%\SagraCassaA"
```

### `Cassa B.bat`

```bat
@echo off
start "" "C:\Program Files\Google\Chrome\Application\chrome.exe" ^
  --kiosk "http://IP:8000/cassa" ^
  --kiosk-printing ^
  --no-first-run ^
  --disable-session-crashed-bubble ^
  --disable-infobars ^
  --user-data-dir="%LOCALAPPDATA%\SagraCassaB"
```

All’interno dell’app seleziona la postazione corretta (**Cassa A** / **Cassa B**) dal menu in alto a sinistra.

---

## Flag Chrome spiegati

| Flag | A cosa serve |
|------|----------------|
| `--kiosk` | Schermo intero, modalità cassa |
| `--app=URL` | Finestra applicazione (alternativa a kiosk) |
| `--kiosk-printing` | Stampa più diretta; evita che si apra un’altra finestra “normale” |
| `--no-first-run` | Salta wizard primo avvio |
| `--disable-infobars` | Riduce barre informative |
| `--disable-session-crashed-bubble` | Evita il popup “Chrome non si è chiuso correttamente” |
| `--user-data-dir=...` | Profilo dedicato (impostazioni/sessione isolati) |

---

## Prima serata: checklist notebook

1. Verifica che dal notebook si apra nel browser normale:  
   `http://IP:8000/cassa`
2. Crea il `.bat` / collegamento con l’IP corretto
3. Avvia in kiosk e seleziona la postazione
4. Prova una comanda di test e la stampa A4 **orizzontale**
5. In Chrome (se serve fuori dal kiosk): stampante predefinita, margini minimi / nessuno, orizzontale
6. Controlla che dopo la stampa si torni alla cassa (comportamento previsto dall’app)

---

## Problemi frequenti

### “Chrome non trovato”
Controlla il path di `chrome.exe` (Program Files vs Program Files x86) e aggiorna il `.bat`.

### Si apre ma pagina non carica
- IP sbagliato o server spento
- Firewall sul mini-PC / rete Wi‑Fi diversa
- Prova ping: `ping IP`

### Stampa su due pagine / bordi strani
- Foglio A4 **orizzontale** (landscape)
- In Chrome: margini **Nessuno** (o Minimi), scala **100%** / predefinita (non “adatta al foglio”)
- L’app forza già 1 pagina nel CSS (`position: fixed` sul foglio)
- Sul server: `git pull` e riavvio servizio; CSS in `public/css/print.css` (niente npm per questo)

### Due notebook, stessa postazione “già in uso”
È il claim soft della postazione: conferma “Prendi comunque il controllo” oppure usa postazioni diverse (A/B).

### Vuoi tornare al desktop durante la serata
Alt+F4 sulla finestra Chrome, oppure Alt+Tab. Non spegnere il mini-PC.

---

## Esempio con IP reale (modello)

Se il server è `192.168.178.191`:

```bat
@echo off
start "" "C:\Program Files\Google\Chrome\Application\chrome.exe" ^
  --kiosk "http://192.168.178.191:8000/cassa" ^
  --kiosk-printing ^
  --no-first-run ^
  --disable-session-crashed-bubble ^
  --disable-infobars ^
  --user-data-dir="%LOCALAPPDATA%\SagraCassaChrome"
```

---

## Riferimenti progetto

- App cassa: `/cassa`
- Deploy server: `DEPLOY.md`
- Repo: cartella `~/cassa` sul mini-PC Ubuntu
