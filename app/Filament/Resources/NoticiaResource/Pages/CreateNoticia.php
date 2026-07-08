<?php

declare(strict_types=1);

namespace App\Filament\Resources\NoticiaResource\Pages;

use App\Filament\Resources\NoticiaResource;
use Filament\Resources\Pages\CreateRecord;

class CreateNoticia extends CreateRecord
{
    protected static string $resource = NoticiaResource::class;
}
