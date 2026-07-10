<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\Casteller;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class CastellerImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure, SkipsEmptyRows
{
    /** @var Failure[] */
    private array $failures = [];

    private int $matchedByNumSoci = 0;
    private int $matchedByAlias   = 0;
    private int $newInserts       = 0;

    public function __construct(private int $collaId) {}

    public function model(array $row): ?Casteller
    {
        $numSoci = is_numeric($row['num_soci'] ?? null) ? (int) $row['num_soci'] : null;
        $alias   = trim((string) $row['alias']);

        $data = [
            'alias'            => $alias,
            'name'             => trim((string) ($row['name'] ?? '')),
            'last_name'        => $row['last_name'] ?? null,
            'email'            => $row['email'] ?? null,
            'email2'           => $row['email2'] ?? null,
            'phone'            => $row['phone'] ?? null,
            'mobile_phone'     => $row['mobile_phone'] ?? null,
            'emergency_phone'  => $row['emergency_phone'] ?? null,
            'birthdate'        => $this->parseDate($row['birthdate'] ?? null),
            'gender'           => in_array($row['gender'] ?? '', ['M', 'F', 'X']) ? $row['gender'] : null,
            'status'           => in_array((int) ($row['status'] ?? 1), [1, 2, 3, 4]) ? (int) $row['status'] : 1,
            'num_soci'         => $numSoci,
            'subscription_date' => $this->parseDate($row['subscription_date'] ?? null),
            'national_id_type' => $row['national_id_type'] ?? null,
            'national_id'      => $row['national_id'] ?? null,
            'nationality'      => $row['nationality'] ?? null,
            'height'           => is_numeric($row['height'] ?? null) ? (int) $row['height'] : null,
            'weight'           => is_numeric($row['weight'] ?? null) ? (int) $row['weight'] : null,
            'shoulder_height'  => is_numeric($row['shoulder_height'] ?? null) ? (int) $row['shoulder_height'] : null,
            'address'          => $row['address'] ?? null,
            'postal_code'      => $row['postal_code'] ?? null,
            'city'             => $row['city'] ?? null,
            'province'         => $row['province'] ?? null,
            'country'          => $row['country'] ?? null,
        ];

        // Filament afegeix un global scope de tenant que interfereix — el desactivem
        // 1. Cerca per num_soci (si ve informat)
        if ($numSoci !== null) {
            $byNumSoci = Casteller::withoutGlobalScopes()
                ->where('colla_id', $this->collaId)
                ->where('num_soci', $numSoci)
                ->first();
            if ($byNumSoci) {
                $this->matchedByNumSoci++;
                $byNumSoci->fill($data)->save();
                return null;
            }
        }

        // 2. updateOrCreate per àlies sense scopes de tenant
        $casteller = Casteller::withoutGlobalScopes()->updateOrCreate(
            ['colla_id' => $this->collaId, 'alias' => $alias],
            $data
        );

        if ($casteller->wasRecentlyCreated) {
            $this->newInserts++;
        } else {
            $this->matchedByAlias++;
        }

        return null;
    }

    public function rules(): array
    {
        return [
            'alias' => ['required', 'string', 'max:50'],
            'name'  => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:200'],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'alias.required' => 'El camp alias és obligatori.',
            'name.required'  => 'El camp nom és obligatori.',
            'email.email'    => 'El format del correu no és vàlid.',
        ];
    }

    public function onFailure(Failure ...$failures): void
    {
        foreach ($failures as $failure) {
            $this->failures[] = $failure;
        }
    }

    /** @return Failure[] */
    public function getFailures(): array
    {
        return $this->failures;
    }

    /** @return array{num_soci: int, alias: int, new: int, total: int} */
    public function getStats(): array
    {
        $total = $this->matchedByNumSoci + $this->matchedByAlias + $this->newInserts;
        return [
            'num_soci' => $this->matchedByNumSoci,
            'alias'    => $this->matchedByAlias,
            'new'      => $this->newInserts,
            'total'    => $total,
        ];
    }

    private function parseDate(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }
        if (is_numeric($value)) {
            return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
        }
        if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', (string) $value)) {
            return Carbon::createFromFormat('d/m/Y', $value)->format('Y-m-d');
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $value)) {
            return $value;
        }
        return null;
    }
}
