<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Casteller;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CastellerExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private int $collaId) {}

    public function collection(): Collection
    {
        return Casteller::where('colla_id', $this->collaId)
            ->orderBy('alias')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Sobrenom', 'Nom', 'Cognoms', 'Email', 'Telèfon',
            'Data naixement', 'Estat', 'Alçada', 'Pes', 'Alçada espatlles',
        ];
    }

    public function map(mixed $casteller): array
    {
        return [
            $casteller->alias,
            $casteller->name,
            $casteller->last_name,
            $casteller->email,
            $casteller->phone,
            $casteller->birthdate?->format('d/m/Y'),
            Casteller::statusLabel($casteller->status),
            $casteller->height,
            $casteller->weight,
            $casteller->shoulder_height,
        ];
    }
}
