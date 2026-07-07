<?php

declare(strict_types=1);

namespace App\Filament\Resources\CollaResource\Pages;

use App\Filament\Resources\CollaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditColla extends EditRecord
{
    protected static string $resource = CollaResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
