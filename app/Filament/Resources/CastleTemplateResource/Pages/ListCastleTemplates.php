<?php

declare(strict_types=1);

namespace App\Filament\Resources\CastleTemplateResource\Pages;

use App\Filament\Resources\CastleTemplateResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListCastleTemplates extends ListRecords
{
    protected static string $resource = CastleTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('Nova plantilla')
                ->icon('heroicon-o-plus')
                ->url(fn () => route('filament.admin.pages.wizard-plantilla', [
                    'tenant' => filament()->getTenant()->shortname,
                ])),
        ];
    }
}
