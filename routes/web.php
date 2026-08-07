<?php

use App\Http\Controllers\CassaController;
use App\Http\Controllers\DocumentiController;
use App\Http\Controllers\FacsimileController;
use App\Http\Middleware\PinGestione;
use App\Livewire\Gestione\ChiusuraForm;
use App\Livewire\Gestione\Dashboard;
use App\Livewire\Gestione\EdizionePage;
use App\Livewire\Gestione\ImpostazioniPage;
use App\Livewire\Gestione\MenuCrud;
use App\Livewire\Gestione\Pin;
use App\Livewire\Gestione\Serate;
use App\Livewire\Gestione\Omaggi;
use App\Livewire\Gestione\Sospesi;
use App\Livewire\Gestione\StatoSistema;
use App\Livewire\Report\ReportHub;
use App\Livewire\RiepilogoLive;
use App\Models\Impostazione;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home', ['impostazioni' => Impostazione::corrente()]);
})->name('home');

Route::get('/cassa', [CassaController::class, 'index'])->name('cassa');
Route::post('/cassa/postazione', [CassaController::class, 'setPostazione'])->name('cassa.postazione');
Route::get('/cassa/stock', [CassaController::class, 'stock'])->name('cassa.stock');
Route::post('/cassa/conferma', [CassaController::class, 'conferma'])->name('cassa.conferma');
Route::get('/cassa/storico', [CassaController::class, 'storico'])->name('cassa.storico');
Route::get('/cassa/richiamo/{numero}', [CassaController::class, 'richiamo'])->name('cassa.richiamo');
Route::get('/cassa/stampa/{comanda}', [CassaController::class, 'stampa'])->name('cassa.stampa');
Route::post('/cassa/annulla/{comanda}', [CassaController::class, 'annulla'])->name('cassa.annulla');

Route::get('/riepilogo', RiepilogoLive::class)->name('riepilogo');

Route::get('/gestione/pin', Pin::class)->name('gestione.pin');

Route::middleware(PinGestione::class)->prefix('gestione')->name('gestione.')->group(function () {
    Route::get('/', Dashboard::class)->name('dashboard');
    Route::get('/edizione', EdizionePage::class)->name('edizione');
    Route::get('/serate', Serate::class)->name('serate');
    Route::get('/menu', MenuCrud::class)->name('menu');
    Route::get('/menu/facsimile', [FacsimileController::class, 'index'])->name('menu.facsimile');
    Route::get('/chiusura', ChiusuraForm::class)->name('chiusura');
    Route::get('/sospesi', Sospesi::class)->name('sospesi');
    Route::get('/omaggi', Omaggi::class)->name('omaggi');
    Route::get('/report', ReportHub::class)->name('report');
    Route::get('/impostazioni', ImpostazioniPage::class)->name('impostazioni');
    Route::get('/stato', StatoSistema::class)->name('stato');
    Route::get('/guida', [DocumentiController::class, 'guida'])->name('guida');
    Route::get('/documenti/guida.md', [DocumentiController::class, 'downloadGuida'])->name('documenti.guida.download');
    Route::get('/documenti/liberatoria-volontari-minori.pdf', [DocumentiController::class, 'downloadLiberatoria'])->name('documenti.liberatoria');
});
