<?php

declare(strict_types=1);

namespace App\Filament\Resources\HelpItemResource\Pages;

use App\Filament\Resources\HelpItemResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditHelpItem extends EditRecord
{
    protected static string $resource = HelpItemResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
