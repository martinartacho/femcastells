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
            'alias', 'name', 'last_name', 'email', 'email2',
            'phone', 'mobile_phone', 'emergency_phone',
            'birthdate', 'gender', 'status', 'num_soci', 'subscription_date',
            'national_id_type', 'national_id', 'nationality',
            'height', 'weight', 'shoulder_height',
            'address', 'postal_code', 'city', 'province', 'country',
        ];
    }

    public function map(mixed $c): array
    {
        return [
            $c->alias,
            $c->name,
            $c->last_name,
            $c->email,
            $c->email2,
            $c->phone,
            $c->mobile_phone,
            $c->emergency_phone,
            $c->birthdate?->format('d/m/Y'),
            $c->gender,
            $c->status,
            $c->num_soci,
            $c->subscription_date?->format('d/m/Y'),
            $c->national_id_type,
            $c->national_id,
            $c->nationality,
            $c->height,
            $c->weight,
            $c->shoulder_height,
            $c->address,
            $c->postal_code,
            $c->city,
            $c->province,
            $c->country,
        ];
    }
}
