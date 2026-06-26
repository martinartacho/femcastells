<?php

declare(strict_types=1);

namespace App\Filament\Resources\CastellerResource\Pages;

use App\Filament\Resources\CastellerResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCasteller extends EditRecord
{
    protected static string $resource = CastellerResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
