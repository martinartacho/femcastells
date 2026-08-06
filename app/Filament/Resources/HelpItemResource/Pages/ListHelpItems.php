<?php

declare(strict_types=1);

namespace App\Filament\Resources\HelpItemResource\Pages;

use App\Filament\Resources\HelpItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHelpItems extends ListRecords
{
    protected static string $resource = HelpItemResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
