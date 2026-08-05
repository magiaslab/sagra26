# Guida operativa — Cassa Sagra

Guida per chi lavora alle **postazioni cassa** e per chi apre/chiude la serata.  
Linguaggio semplice, passi numerati. Non serve conoscere i computer.

---

## Indice

1. [Cosa fa questo sistema](#1-cosa-fa-questo-sistema)
2. [Accendere tutto all’inizio](#2-accendere-tutto-allinizio)
3. [Aprire la serata (obbligatorio)](#3-aprire-la-serata-obbligatorio)
4. [Usare la cassa — comanda normale](#4-usare-la-cassa--comanda-normale)
5. [Pagamento (contante, POS, misto)](#5-pagamento-contante-pos-misto)
6. [Correggere o richiamare una comanda](#6-correggere-o-richiamare-una-comanda)
7. [Ristampare un pezzo della comanda](#7-ristampare-un-pezzo-della-comanda)
8. [Annullare una comanda](#8-annullare-una-comanda)
9. [Tavolo, note e messaggi stock](#9-tavolo-note-e-messaggi-stock)
10. [Durante la serata](#10-durante-la-serata)
11. [Chiudere la serata](#11-chiudere-la-serata)
12. [Report e stampe di fine serata](#12-report-e-stampe-di-fine-serata)
13. [Menù e impostazioni (chi gestisce)](#13-menù-e-impostazioni-chi-gestisce)
14. [Tasti utili in cassa](#14-tasti-utili-in-cassa)
15. [Problemi frequenti](#15-problemi-frequenti)
16. [Schema della serata in sintesi](#16-schema-della-serata-in-sintesi)

---

## 1. Cosa fa questo sistema

Il sistema serve a:

- prendere le **comande** alle casse
- **stampare** lo scontrino cliente e i fogli per cucina / griglia / cameriere
- tenere il **magazzino** (quanti piatti restano)
- fare **chiusura cassa** e **report** a fine serata

Ci sono due tipi di computer:

| Cosa | Dove | A cosa serve |
|------|------|--------------|
| **Server** | Mini-PC (di solito Ubuntu) | Tiene i dati; deve essere acceso |
| **Postazione cassa** | Notebook Windows | Schermo dove si inseriscono le comande |

Tutti i notebook devono essere collegati alla **stessa rete Wi‑Fi / cavo** del server.

---

## 2. Accendere tutto all’inizio

### 2.1 Accendere il server

1. Accendi il **mini-PC server** e attendi che finisca l’avvio.
2. Su quel computer, apri il terminale (o fai fare a chi conosce il server) e controlla che il servizio sia attivo:

```bash
sudo systemctl status cassa
```

3. Se non è attivo:

```bash
sudo systemctl start cassa
```

4. Controlla di nuovo: deve risultare **active (running)**.

> Indirizzo tipico della cassa (sostituisci l’IP con quello del vostro server):  
> `http://192.168.x.x:8000/cassa`

### 2.2 Accendere una postazione cassa (notebook)

1. Accendi il notebook.
2. Controlla di essere sulla **stessa rete** del server.
3. Avvia la cassa con il collegamento sul Desktop (di solito **Cassa.bat** o icona Chrome kiosk).
4. Si apre lo schermo intero della cassa.
5. In alto a sinistra, scegli la **Postazione** corretta (es. *Cassa A* o *Cassa B*).

### 2.3 Se compare «Postazione già in uso»

Significa che quella cassa risulta ancora aperta su un altro PC (o è rimasta “appesa”).

1. Leggi il messaggio.
2. Se sei sicuro di dover prendere tu quella cassa → **Prendi comunque il controllo**.
3. Se hai sbagliato postazione → **Annulla** e scegline un’altra.

### 2.4 Spegnere a fine giornata (dopo la chiusura)

1. Sui notebook: chiudi Chrome (di solito **Alt+F4**).
2. Sul server, se serve spegnerlo:

```bash
sudo systemctl stop cassa
```

Poi spegni il mini-PC in modo normale.

---

## 3. Aprire la serata (obbligatorio)

**Senza serata aperta non si possono fare comande.**

### Passi

1. Dalla cassa (o dalla Home) vai su **Gestione**.
2. Inserisci il **PIN** (quello deciso dall’organizzazione) e tocca **Sblocca**.
3. Apri **Serate**.
4. Compila:
   - **Data** (di solito quella di oggi)
   - **Note** (facoltative)
   - **Stock limitati**: quante porzioni mettete a disposizione all’inizio per i piatti a quantità limitata
   - **Fondo iniziale** per ogni cassetto / punto cassa (obbligatorio)
5. Tocca **Apri serata**.
6. Deve comparire la conferma «Serata aperta».

### Cosa non fare

- Non aprire una seconda serata se ce n’è già una aperta.
- Non lasciare vuoti i fondi iniziali.

---

## 4. Usare la cassa — comanda normale

### 4.1 Controlli prima di iniziare

1. In alto vedi la **postazione** corretta.
2. Non deve esserci il messaggio rosso **Nessuna serata aperta**.
3. La stampante è accesa e con carta A4 (stampa in **orizzontale**).

### 4.2 Inserire la comanda

1. Scorri le **categorie** (o usa le frecce ← →).
2. Seleziona il **piatto** (frecce ↑ ↓ oppure tocco).
3. Imposta la **quantità**:
   - tasti **+** e **-**, oppure
   - digita il numero sulla tastiera.
4. Ripeti per tutti i piatti.
5. Controlla a destra (o in alto) il **Totale** e i **Coperti**.
6. Se serve, compila **Tavolo** e **Note** (vedi sezione 9).
7. Tocca **Conferma e stampa** (oppure tasto **F9**).

### 4.3 Cosa viene stampato

Un foglio A4 diviso in tre parti:

| Parte | A chi serve |
|-------|-------------|
| **Cliente** | Ricevuta / totale / pagamento |
| **Produzione** | Cucina 1, Cucina 2, Griglia |
| **Cameriere** | Riepilogo per il servizio |

---

## 5. Pagamento (contante, POS, misto)

Dopo **Conferma e stampa** appare la domanda: **Come paga il cliente?**

### Solo contante

1. Tocca **Contante** (oppure tasto **C**).
2. Controlla l’anteprima.
3. Premi **Invio** (o **Conferma e stampa**).

### Solo POS

1. Tocca **POS** (oppure tasto **P**).
2. Controlla l’anteprima.
3. Premi **Invio**.

### Misto (parte contante + parte POS)

1. Tocca **Misto (contante + POS)**.
2. Scrivi quanto paga in **Contante €**.
3. Il **POS €** si calcola da solo (deve sommare esattamente al totale).
4. Tocca **Conferma misto**.
5. Controlla l’anteprima e premi **Invio**.

> Se i due importi non sommano al totale, il sistema non salva la comanda.

### Annullare prima di stampare

- Premi **Esc** per chiudere e tornare alla comanda senza stampare.

---

## 6. Correggere o richiamare una comanda

Serve se hai sbagliato un piatto, una quantità, o il cliente cambia idea **dopo** la stampa.

### Passi

1. Tocca **Richiama** (oppure **F2**).
2. Digita il **numero progressivo** della comanda (es. 42) e **Carica**,  
   **oppure** tocca la riga nelle **Ultime comande** → **Correggi →**.
3. La comanda si ricarica sullo schermo (badge *mod.* / *corr.*).
4. Modifica le quantità come in una comanda normale.
5. Se vuoi, scrivi un **Motivo correzione**.
6. Tocca **Conferma e stampa** (**F9**).
7. Scegli Contante o POS:
   - se il totale **aumenta** → **Incassa contante** o **Incassa POS** (solo la differenza)
   - se il totale **diminuisce** → **Restituisci contante** o **Restituisci POS**
   - se il totale **non cambia** → conferma o **correggi il metodo** (es. era POS e doveva essere contante, o il POS non funziona)
8. Controlla l’anteprima (c’è scritto **CORREZIONE**) e conferma con **Invio**.

### Attenzione

- Si correggono solo le comande della **serata aperta**.
- Se un’altra cassa ha già corretto la stessa comanda, compare un avviso: **ricarica** e riprova.
- Le comande **annullate** non si possono più richiamare.

---

## 7. Ristampare un pezzo della comanda

Se la stampante ha inceppato, o serve solo il pezzo cucina / cliente / cameriere.

### Dal richiamo (consigliato)

1. Tocca **Richiama** (**F2**).
2. Nella lista, sulla comanda giusta, tocca **Ristampa**.
3. Scegli cosa stampare:
   - **Tutto**
   - **Cliente**
   - **Produzione**
   - **Cameriere**
4. Parte la stampa.

### Se hai già caricato la comanda

1. Accanto a **Richiama** appare il pulsante **Ristampa**.
2. Scegli la sezione e conferma.

---

## 8. Annullare una comanda

Usalo **solo** se l’ordine non va più fatto (errore grave, cliente se ne va, doppia digitazione).  
**Non si può tornare indietro.**

### Passi

1. **Richiama** (**F2**).
2. Sulla riga della comanda tocca **Annulla**.
3. Scrivi un **motivo** (almeno 2 caratteri), es. «sbagliata» o «cliente rinuncia».
4. Tocca **Conferma annullamento**.

Le quantità tornano disponibili nello stock.

---

## 9. Tavolo, note e messaggi stock

### Tavolo e note

- **Tavolo**: utile se gestite i tavoli (es. T12). Compare sulla stampa.
- **Note**: messaggi brevi (es. «senza cipolla»). Compare sulla stampa.

Si compilano **prima** di confermare una comanda nuova.  
In correzione, tavolo e note restano quelli già salvati (modificabili se ricarichi e ristampi).

### Messaggi sullo stock (piatti limitati)

| Messaggio | Significato | Cosa fare |
|-----------|-------------|-----------|
| **rimasti N** | Disponibili | Procedi normalmente |
| **QUASI ESAURITO** | Pochi pezzi | Avvisa cucina / chi gestisce |
| **ESAURITO** | Zero pezzi | Non puoi venderlo; chiedi un **rifornimento** (sezione 10) |
| **stock non impostato** | Manca lo stock della serata | Chiedi a chi gestisce di verificare **Serate** |

---

## 10. Durante la serata

### 10.1 Rifornire lo stock (piatti finiti o quasi)

1. Vai in **Gestione** → PIN → **Serate**.
2. Nella tabella **Stock in serata** trova il piatto.
3. Scrivi quanti pezzi **aggiungere**.
4. Tocca **Rifornisci**.

Non serve chiudere e riaprire la serata.

### 10.2 Guardare come va la serata (Riepilogo)

1. Dalla Home (o dalla barra in alto) apri **Riepilogo**.
2. Vedi: coperti, incasso, contante/POS, piatti venduti, comande per cassa.
3. Si aggiorna da solo ogni pochi secondi.

### 10.3 Controllare che il sistema stia bene

1. **Gestione** → **Stato**.
2. Tocca **Aggiorna**.
3. Ideale: **Tutto funzionante**.  
   Se vedi **Problemi critici**, avvisa subito chi mantiene il server.

---

## 11. Chiudere la serata

### 11.1 Chiusura di ogni cassetto (punto cassa)

1. **Gestione** → **Chiusura**.
2. Scegli la **serata** e il **punto cassa** (cassetto).
3. Compila:
   - **Fondo iniziale** (quello messo all’apertura)
   - conteggio pezzi (banconote e monete)
   - **Fondo trattenuto** (se ne lasciate in cassa)
   - **Totale POS** (dal terminale)
   - **Totale Z** (se usato)
   - **Note** (facoltative)
4. Tocca **Salva chiusura**.
5. Controlla il riquadro di **riconciliazione** (atteso vs contato).
6. Ripeti per **tutti** i punti cassa.

### 11.2 Chiudere la serata

1. Vai in **Serate**.
2. Tocca **Chiudi serata**.
3. Se manca qualche chiusura cassa, il sistema avvisa:
   - puoi andare a completare la chiusura, **oppure**
   - **Chiudi comunque** (solo se siete consapevoli che i totali resteranno incompleti).

### 11.3 Riaprire una serata chiusa (solo se serve)

Serve per correzioni dopo la chiusura.

1. In **Serate**, nello storico, tocca **Riapri**.
2. Funziona solo se **non** c’è già un’altra serata aperta.

---

## 12. Report e stampe di fine serata

Percorso: **Gestione** → **Report**.

1. Scegli il **tipo** di report.
2. Scegli la **serata**.
3. Se serve, spunta **Completo (tutta la sagra)**.
4. Tocca **Stampa / PDF** oppure **Export CSV**.

### Tipi di report (in parole semplici)

| Nome | A cosa serve |
|------|----------------|
| **Cumulativo produzione** | Tutti i piatti di cucina e griglia insieme, suddivisi per zona |
| **Dettaglio Cucina 1** | Solo Cucina 1 |
| **Dettaglio Cucina 2** | Solo Cucina 2 |
| **Dettaglio Griglia** | Solo griglia |
| **Bevande** | Bevande e bar |
| **Statistiche** | Coperti, orari, piatti più venduti |
| **Economico** | Contante / POS per serata |
| **Consegna incassi** | Foglio per firmare e consegnare i soldi |
| **Confronto serate** | Confrontare due sere |
| **Export CSV** | File da aprire in Excel |

> Alcuni report stampano in **orizzontale**, altri in **verticale**: il sistema lo imposta da solo.  
> Nella stampante, se il foglio esce storto, controlla che sia impostata la carta A4 corretta.

---

## 13. Menù e impostazioni (chi gestisce)

Queste funzioni di solito le usa **una persona di riferimento**, non tutti i cassieri.

### 13.1 Entrare in Gestione

1. Tocca **Gestione**.
2. Inserisci il **PIN**.
3. Se hai dimenticato il PIN: **PIN dimenticato?** e usa il **codice master** (solo responsabili).

### 13.2 Menù

- Cambiare prezzi, nomi, attivare/disattivare piatti.
- Impostare se un piatto va a **Cucina 1**, **Cucina 2**, **Griglia** o **Cliente**.
- Impostare lo **stock di default** (quantità tipica all’apertura).
- Scrivere una **comunicazione** che compare sulle comande.
- Stampare il **facsimile** menù (foglio da compilare a mano se serve).

### 13.3 Impostazioni

- Nome / anno / sottotitolo della sagra (in testa alle stampe).
- PIN gestione.
- **Soglia alert stock** (quando in cassa compare «quasi esaurito»).
- Elenco **postazioni** e **punti cassa**.
- **Collegamento** postazione → cassetto (importante: senza questo non si chiude bene la cassa).

---

## 14. Tasti utili in cassa

| Tasto | Cosa fa |
|-------|---------|
| **↑ ↓** | Cambia piatto |
| **← →** | Cambia categoria |
| **+ −** | Aumenta / diminuisce quantità |
| **0–9** | Digita la quantità |
| **Canc** / **Backspace** | Azzera la riga |
| **F9** | Conferma e stampa (va al pagamento) |
| **F2** | Richiama comanda |
| **Esc** | Chiude le finestre / azzera la comanda |
| **C** | Contante (nella scelta pagamento) |
| **P** | POS (nella scelta pagamento) |
| **Invio** | Conferma l’anteprima e stampa |

---

## 15. Problemi frequenti

### «Nessuna serata aperta»

1. Qualcuno deve aprire la serata da **Gestione → Serate** (sezione 3).
2. Poi torna in cassa e aggiorna la pagina se serve.

### Non stampa

1. Controlla che la stampante sia accesa e selezionata come predefinita.
2. Controlla carta e inceppamenti.
3. La stampa della comanda deve restare nella **stessa** finestra Chrome (non aprire un secondo browser).
4. Se serve, ristampa da **Richiama → Ristampa**.

### «Stock insufficiente» / ESAURITO

1. Non forzare: il piatto è finito nei conti.
2. Chiedi rifornimento da **Serate → Rifornisci** (sezione 10).
3. Solo dopo il rifornimento si può vendere di nuovo.

### «Postazione già in uso»

Vedi sezione 2.3. Se sei tu a dover lavorare lì → **Prendi comunque il controllo**.

### La comanda è stata modificata da un’altra cassa

1. Ricarica la comanda con **Richiama**.
2. Controlla lo stato aggiornato.
3. Ripeti la correzione.

### PIN gestione dimenticato

1. Su **Gestione → PIN** usa **PIN dimenticato?** con il codice master.
2. Se anche quello è perso, chi mantiene il server ha una procedura di recupero (non farla in cassa durante il servizio).

### Il notebook non trova il server

1. Stessa rete Wi‑Fi / cavo?
2. Il server è acceso e `cassa` è **active**?
3. Prova ad aprire nel browser: `http://IP-DEL-SERVER:8000`  
   Se non apre, avvisa il responsabile tecnico.

---

## 16. Schema della serata in sintesi

```text
1. Accendi SERVER → controlla servizio cassa
2. Accendi NOTEBOOK → apri Cassa.bat → scegli postazione
3. Gestione → PIN → Serate → Apri serata (stock + fondi)
4. Tutta la sera: comande (F9) → pagamento → stampa
   - errori: Richiama (F2) → correggi / ristampa / annulla
   - stock finito: Serate → Rifornisci
5. Fine sera: Chiusura di ogni cassetto → Salva
6. Serate → Chiudi serata
7. Report / CSV se servono
8. Spegnimento notebook e (se previsto) server
```

---

## Dove trovare le altre guide tecniche

- **In Gestione (dashboard):** sezione **Documenti e aiuto** → Guida operativa e Liberatoria minori (PDF)
- Installazione server: `DEPLOY.md`
- Collegamenti Chrome kiosk sui notebook: `docs/chrome-kiosk-notebook.md`

Questa guida è pensata per **l’uso quotidiano in sagra**. Per modifiche al computer o alla rete, coinvolgi il responsabile tecnico.
