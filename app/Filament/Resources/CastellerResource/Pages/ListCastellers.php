<?php

declare(strict_types=1);

namespace App\Filament\Resources\CastellerResource\Pages;

use App\Filament\Resources\CastellerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCastellers extends ListRecords
{
    protected static string $resource = CastellerResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
