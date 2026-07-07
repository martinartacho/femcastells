<?php

declare(strict_types=1);

namespace App\Filament\Resources\CollaResource\Pages;

use App\Filament\Resources\CollaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListColles extends ListRecords
{
    protected static string $resource = CollaResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
