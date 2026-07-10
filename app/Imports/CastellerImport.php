<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\Casteller;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithUpserts;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class CastellerImport implements ToModel, WithHeadingRow, WithUpserts, WithValidation, SkipsOnFailure, SkipsEmptyRows
{
    /** @var Failure[] */
    private array $failures = [];

    private int $imported = 0;

    public function __construct(private int $collaId) {}

    public function model(array $row): ?Casteller
    {
        $this->imported++;

        return new Casteller([
            'colla_id'         => $this->collaId,
            'alias'            => trim((string) $row['alias']),
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
            'num_soci'         => $row['num_soci'] ?? null,
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
        ]);
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

    public function uniqueBy(): string|array
    {
        return ['colla_id', 'alias'];
    }

    /** @return Failure[] */
    public function getFailures(): array
    {
        return $this->failures;
    }

    public function getImportedCount(): int
    {
        return $this->imported;
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
