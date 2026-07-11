<?php

declare(strict_types=1);

namespace App\Filament\Resources\CastellerResource\Pages;

use App\Filament\Resources\CastellerResource;
use App\Imports\CastellerImport;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Maatwebsite\Excel\Facades\Excel;

class ImportCastellers extends Page
{
    protected static string $resource = CastellerResource::class;

    protected static ?string $title = 'Importar castellers';

    protected string $view = 'filament.pages.import-castellers';

    public array $data = [];

    /** @var array<int, array{row: int, errors: string[]}> */
    public array $importErrors = [];

    /** @var array{num_soci: int, alias: int, new: int, total: int}|null */
    public ?array $importStats = null;

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()?->canDo('castellers', 'write') ?? false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Selecciona el fitxer')->schema([
                    FileUpload::make('file')
                        ->label('Fitxer CSV o Excel (.csv, .xlsx)')
                        ->disk('local')
                        ->directory('imports')
                        ->acceptedFileTypes([
                            'text/csv',
                            'text/plain',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                        ])
                        ->required(),
                ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('import')
                ->footer([
                    Actions::make($this->getFormActions()),
                ]),
        ]);
    }

    public function import(): void
    {
        $state = $this->form->getState();
        $filePath = $state['file'] ?? null;

        if (! $filePath) {
            Notification::make()->title('Cap fitxer seleccionat.')->danger()->send();
            return;
        }

        $collaId  = filament()->getTenant()->id;
        $importer = new CastellerImport($collaId);

        Excel::import($importer, $filePath, 'local');

        $this->importStats  = $importer->getStats();
        $this->importErrors = [];

        foreach ($importer->getFailures() as $failure) {
            $this->importErrors[] = [
                'row'    => $failure->row(),
                'errors' => $failure->errors(),
            ];
        }

        $total      = $this->importStats['total'];
        $errorCount = count($this->importErrors);

        if ($errorCount === 0) {
            Notification::make()
                ->title("Importació completada: {$total} castellers processats.")
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title("{$total} processats, {$errorCount} files amb errors.")
                ->warning()
                ->send();
        }

        $this->data = [];
        $this->form->fill([]);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('import')
                ->label('Importar')
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->submit('import'),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download_template')
                ->label('Descarregar plantilla CSV')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->url(route('castellers.import-template', [
                    'tenant' => filament()->getTenant()->shortname,
                ])),
        ];
    }
}
