<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\Profile;
use App\Models\User;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static ?string $navigationLabel = 'Usuaris';

    protected static \UnitEnum|string|null $navigationGroup = null;

    protected static ?int $navigationSort = 4;

    public static function canAccess(): bool
    {
        return auth()->user()?->canDo('config') ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (auth()->user()?->hasRole('super-admin')) {
            return $query;
        }

        return $query->where('colla_id', filament()->getTenant()?->id);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identificació')->schema([
                TextInput::make('name')
                    ->label('Nom')
                    ->required()
                    ->maxLength(100),
                TextInput::make('email')
                    ->label('Correu')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(150),
                TextInput::make('password')
                    ->label('Contrasenya')
                    ->password()
                    ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->required(fn (string $operation): bool => $operation === 'create'),
            ])->columns(2),

            Section::make('Accés')->schema([
                Select::make('roles')
                    ->label('Rol')
                    ->relationship('roles', 'name')
                    ->options(fn () => \Spatie\Permission\Models\Role::whereNotIn('name', ['super-admin'])->pluck('name', 'id'))
                    ->preload()
                    ->native(false),
                Select::make('profile_id')
                    ->label('Perfil de permisos')
                    ->options(fn () => Profile::where('colla_id', filament()->getTenant()?->id)->pluck('name', 'id'))
                    ->nullable()
                    ->native(false)
                    ->helperText('Sense perfil = sense accés a seccions restringides'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Correu')
                    ->searchable(),
                TextColumn::make('roles.name')
                    ->label('Rol')
                    ->badge(),
                TextColumn::make('profile.name')
                    ->label('Perfil')
                    ->default('—'),
                TextColumn::make('created_at')
                    ->label('Alta')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
