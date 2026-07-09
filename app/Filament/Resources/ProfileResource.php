<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ProfileResource\Pages;
use App\Models\Profile;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProfileResource extends Resource
{
    protected static ?string $model = Profile::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static ?string $navigationLabel = 'Perfils';

    protected static \UnitEnum|string|null $navigationGroup = null;

    protected static ?int $navigationSort = 5;

    public static function canAccess(): bool
    {
        return auth()->user()?->canDo('config') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        $areas = [
            'castellers'    => 'Castellers',
            'esdeveniments'  => 'Esdeveniments',
            'pissarra'      => 'Pissarra',
            'plantilles'    => 'Plantilles',
            'noticies'      => 'Notícies',
            'tags'          => 'Etiquetes',
            'config'        => 'Configuració',
            'generador'     => 'Generador',
        ];

        $permissionSections = [];
        foreach ($areas as $key => $label) {
            $permissionSections[] = Section::make($label)->schema([
                Grid::make(2)->schema([
                    Toggle::make("permissions.{$key}.read")
                        ->label('Lectura')
                        ->default(false),
                    Toggle::make("permissions.{$key}.write")
                        ->label('Escriptura')
                        ->default(false),
                ]),
            ])->columns(1);
        }

        return $schema->components([
            Section::make('Identificació')->schema([
                TextInput::make('name')
                    ->label('Nom del perfil')
                    ->required()
                    ->maxLength(100),
            ]),
            Section::make('Permisos')->schema($permissionSections)->columns(4),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Perfil')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('users_count')
                    ->label('Usuaris')
                    ->counts('users')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Creat')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProfiles::route('/'),
            'create' => Pages\CreateProfile::route('/create'),
            'edit'   => Pages\EditProfile::route('/{record}/edit'),
        ];
    }
}
