<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Seratas\SerataResource;
use App\Models\MenuItem;
use App\Models\PuntoCassa;
use App\Models\Serata;
use App\Services\RiconciliazioneService;
use App\Services\SerataService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ApriSerata extends Page
{
    protected string $view = 'filament.pages.apri-serata';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPlay;

    protected static string|UnitEnum|null $navigationGroup = 'Operativo';

    protected static ?string $navigationLabel = 'Apri serata';

    protected static ?string $title = 'Apri serata';

    protected static ?int $navigationSort = 1;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function shouldRegisterNavigation(): bool
    {
        return Serata::corrente() === null;
    }

    public function mount(RiconciliazioneService $ric): void
    {
        if (Serata::corrente()) {
            Notification::make()->title('Esiste già una serata aperta.')->warning()->send();
            $this->redirect(SerataResource::getUrl('index'));

            return;
        }

        $fondi = [];
        $stock = [];
        foreach (PuntoCassa::query()->where('attivo', true)->orderBy('id')->get() as $punto) {
            $dettaglio = $ric->fondoPrecedenteDettaglio($punto);
            $fondi[(string) $punto->id] = $dettaglio !== null ? (string) $dettaglio['importo'] : '';
        }
        foreach (MenuItem::query()->whereNotNull('stock_default')->where('attivo', true)->orderBy('ordinamento')->get() as $item) {
            $stock[(string) $item->id] = (int) $item->stock_default;
        }

        $this->form->fill([
            'data_serata' => now()->toDateString(),
            'note' => '',
            'fondi' => $fondi,
            'stock' => $stock,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $fondiFields = [];
        foreach (PuntoCassa::query()->where('attivo', true)->orderBy('id')->get() as $punto) {
            $fondiFields[] = TextInput::make('fondi.'.$punto->id)
                ->label('Fondo iniziale — '.$punto->nome)
                ->numeric()
                ->required()
                ->prefix('€');
        }

        $stockFields = [];
        foreach (MenuItem::query()->whereNotNull('stock_default')->where('attivo', true)->orderBy('ordinamento')->get() as $item) {
            $stockFields[] = TextInput::make('stock.'.$item->id)
                ->label($item->nome)
                ->numeric()
                ->required()
                ->minValue(0);
        }

        return $schema
            ->components([
                Section::make('Serata')->schema([
                    DatePicker::make('data_serata')->label('Data')->required(),
                    Textarea::make('note')->label('Note')->rows(2),
                ]),
                Section::make('Fondi iniziali punti cassa')
                    ->description('Precompilati dalla chiusura precedente quando disponibile.')
                    ->schema($fondiFields)
                    ->visible(fn () => $fondiFields !== []),
                Section::make('Stock limitati')
                    ->schema($stockFields)
                    ->visible(fn () => $stockFields !== []),
            ])
            ->statePath('data');
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('apri')
                ->label('Apri serata')
                ->color('primary')
                ->action('apri'),
        ];
    }

    public function apri(SerataService $service): void
    {
        $state = $this->form->getState();
        $fondi = [];
        foreach (($state['fondi'] ?? []) as $id => $val) {
            if ($val === '' || $val === null) {
                Notification::make()->title('Inserisci il fondo iniziale per tutti i punti cassa.')->danger()->send();

                return;
            }
            $fondi[(int) $id] = (float) $val;
        }
        $stock = [];
        foreach (($state['stock'] ?? []) as $id => $val) {
            $stock[(int) $id] = (int) $val;
        }

        try {
            $service->apri(
                (string) $state['data_serata'],
                filled($state['note'] ?? null) ? (string) $state['note'] : null,
                $stock,
                $fondi,
            );
            Notification::make()->title('Serata aperta.')->success()->send();
            $this->redirect(SerataResource::getUrl('index'));
        } catch (\Throwable $e) {
            Notification::make()->title('Errore')->body($e->getMessage())->danger()->send();
        }
    }
}
