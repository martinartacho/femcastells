<?php

declare(strict_types=1);

namespace App\Filament\Resources\CastellerResource\Pages;

use App\Filament\Resources\CastellerResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCasteller extends CreateRecord
{
    protected static string $resource = CastellerResource::class;
}
