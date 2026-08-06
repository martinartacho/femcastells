<?php

declare(strict_types=1);

namespace App\Filament\Resources\HelpItemResource\Pages;

use App\Filament\Resources\HelpItemResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHelpItem extends CreateRecord
{
    protected static string $resource = HelpItemResource::class;
}
