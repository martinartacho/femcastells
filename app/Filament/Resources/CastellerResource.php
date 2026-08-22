<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\CastellerResource\Pages;
use App\Models\Casteller;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Hash;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TagsColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CastellerResource extends Resource
{
    protected static ?string $model = Casteller::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $navigationLabel = 'Castellers';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return auth()->user()?->canDo('castellers') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->canDo('castellers', 'write') ?? false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()?->canDo('castellers', 'write') ?? false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()?->canDo('castellers', 'write') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identificació')->schema([
                TextInput::make('alias')
                    ->required()
                    ->maxLength(50),
                TextInput::make('name')
                    ->label('Nom')
                    ->required()
                    ->maxLength(100),
                TextInput::make('last_name')
                    ->label('Cognoms')
                    ->maxLength(100),
                Select::make('status')
                    ->label('Estat')
                    ->options([
                        1 => 'Actiu',
                        2 => 'Inactiu',
                        3 => 'Baixa',
                        4 => 'Candidat',
                    ])
                    ->default(1)
                    ->required(),
                TextInput::make('num_soci')
                    ->label('Núm. soci')
                    ->maxLength(20),
                DatePicker::make('subscription_date')
                    ->label('Data ingrés')
                    ->displayFormat('d/m/Y'),
            ])->columns(2),

            Section::make('Dades personals')->schema([
                Select::make('gender')
                    ->label('Gènere')
                    ->options([
                        'M' => 'Home',
                        'F' => 'Dona',
                        'X' => 'No binari / Altre',
                    ])
                    ->native(false),
                DatePicker::make('birthdate')
                    ->label('Data de naixement')
                    ->displayFormat('d/m/Y'),
                Select::make('national_id_type')
                    ->label('Tipus document')
                    ->options([
                        'DNI'      => 'DNI',
                        'NIE'      => 'NIE',
                        'Passaport' => 'Passaport',
                        'Altre'    => 'Altre',
                    ])
                    ->native(false),
                TextInput::make('national_id')
                    ->label('Número document')
                    ->maxLength(30),
                TextInput::make('nationality')
                    ->label('Nacionalitat')
                    ->maxLength(100),
            ])->columns(2),

            Section::make('Contacte')->schema([
                TextInput::make('email')
                    ->email()
                    ->maxLength(200),
                TextInput::make('email2')
                    ->label('Correu alternatiu')
                    ->email()
                    ->maxLength(200),
                TextInput::make('phone')
                    ->label('Telèfon')
                    ->tel()
                    ->maxLength(20),
                TextInput::make('mobile_phone')
                    ->label('Mòbil')
                    ->tel()
                    ->maxLength(20),
                TextInput::make('emergency_phone')
                    ->label('Telèfon emergència')
                    ->tel()
                    ->maxLength(20),
            ])->columns(2),

            Section::make('Adreça')->schema([
                TextInput::make('address')
                    ->label('Adreça')
                    ->maxLength(200)
                    ->columnSpanFull(),
                TextInput::make('postal_code')
                    ->label('Codi postal')
                    ->maxLength(10),
                TextInput::make('city')
                    ->label('Població')
                    ->maxLength(100),
                TextInput::make('province')
                    ->label('Província')
                    ->maxLength(100),
                TextInput::make('country')
                    ->label('País')
                    ->maxLength(100),
            ])->columns(2),

            Section::make('Accés al portal')->schema([
                TextInput::make('password')
                    ->label('Contrasenya')
                    ->password()
                    ->dehydrateStateUsing(fn (string $state) => Hash::make($state))
                    ->dehydrated(fn (?string $state) => filled($state))
                    ->hint('Deixa buit per no canviar')
                    ->maxLength(255),
            ]),

            Section::make('Dades físiques')->schema([
                TextInput::make('height')
                    ->label('Alçada (cm)')
                    ->numeric()
                    ->minValue(100)
                    ->maxValue(250),
                TextInput::make('weight')
                    ->label('Pes (kg)')
                    ->numeric()
                    ->minValue(30)
                    ->maxValue(200),
                TextInput::make('shoulder_height')
                    ->label('Alçada espatlles (cm)')
                    ->numeric(),
            ])->columns(3),

            Section::make('Etiquetes i posició')->schema([
                Select::make('tags')
                    ->label('Posició i etiquetes')
                    ->helperText('Selecciona la posició (Rengla, Laterals…) i les etiquetes (Habituals, Canalla…)')
                    ->relationship('tags', 'name', fn ($query) => $query
                        ->whereIn('type', ['POSITIONS', 'CASTELLERS'])
                        ->where('colla_id', \Filament\Facades\Filament::getTenant()?->id)
                        ->orderBy('type')
                        ->orderBy('name'))
                    ->multiple()
                    ->preload(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('alias')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->formatStateUsing(fn ($record) => $record->full_name),
                TextColumn::make('last_name')
                    ->label('Cognoms')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('email')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('phone')
                    ->label('Telèfon')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('mobile_phone')
                    ->label('Mòbil')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('num_soci')
                    ->label('Núm. soci')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->label('Estat')
                    ->badge()
                    ->formatStateUsing(fn (int $state) => Casteller::statusLabel($state))
                    ->color(fn (int $state) => match ($state) {
                        1 => 'success',
                        2 => 'warning',
                        3 => 'danger',
                        4 => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('posicio')
                    ->label('Posició')
                    ->state(fn (Casteller $record) => $record->tags
                        ->where('type', 'POSITIONS')
                        ->first()?->name ?? '—')
                    ->badge()
                    ->color('info')
                    ->toggleable(),
                TagsColumn::make('etiquetes')
                    ->label('Etiquetes')
                    ->state(fn (Casteller $record) => $record->tags
                        ->where('type', 'CASTELLERS')
                        ->pluck('name')
                        ->toArray())
                    ->toggleable(),
            ])
            ->modifyQueryUsing(fn ($query) => $query->with('tags'))
            ->filters([
                SelectFilter::make('status')
                    ->label('Estat')
                    ->options([
                        1 => 'Actiu',
                        2 => 'Inactiu',
                        3 => 'Baixa',
                        4 => 'Candidat',
                    ]),
            ])
            ->defaultSort('alias');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'    => Pages\ListCastellers::route('/'),
            'create'   => Pages\CreateCasteller::route('/create'),
            'edit'     => Pages\EditCasteller::route('/{record}/edit'),
            'import'   => Pages\ImportCastellers::route('/import'),
            'profiles' => Pages\ListCastellerProfiles::route('/profiles'),
        ];
    }
}
