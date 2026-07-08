<?php

declare(strict_types=1);

namespace App\Filament\Member\Pages;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EditProfile extends \Filament\Auth\Pages\EditProfile
{
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identificació')->schema([
                TextInput::make('alias')
                    ->label('Àlies')
                    ->disabled(),
                TextInput::make('name')
                    ->label('Nom')
                    ->disabled(),
                TextInput::make('last_name')
                    ->label('Cognoms')
                    ->disabled(),
            ])->columns(3),

            Section::make('Contacte')->schema([
                TextInput::make('email')
                    ->label('Correu electrònic')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('phone')
                    ->label('Telèfon')
                    ->tel(),
            ])->columns(2),

            Section::make('Canviar contrasenya')->schema([
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
            ])->columns(2),
        ]);
    }
}
