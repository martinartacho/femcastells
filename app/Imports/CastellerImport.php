<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\Casteller;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithUpserts;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class CastellerImport implements ToModel, WithHeadingRow, WithUpserts
{
    public function __construct(private int $collaId) {}

    public function model(array $row): ?Casteller
    {
        if (empty($row['alias'])) {
            return null;
        }

        $birthdate = null;
        if (!empty($row['birthdate'])) {
            if (is_numeric($row['birthdate'])) {
                $birthdate = ExcelDate::excelToDateTimeObject((float) $row['birthdate'])->format('Y-m-d');
            } elseif (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', (string) $row['birthdate'])) {
                $birthdate = Carbon::createFromFormat('d/m/Y', $row['birthdate'])->format('Y-m-d');
            }
        }

        $phone = !empty($row['mobile_phone']) ? $row['mobile_phone'] : ($row['phone'] ?? null);

        return new Casteller([
            'colla_id'        => $this->collaId,
            'alias'           => trim((string) $row['alias']),
            'email'           => $row['email'] ?? null,
            'phone'           => $phone ?: null,
            'birthdate'       => $birthdate,
            'height'          => is_numeric($row['height'] ?? null) ? (float) $row['height'] : null,
            'weight'          => is_numeric($row['weight'] ?? null) ? (float) $row['weight'] : null,
            'shoulder_height' => is_numeric($row['shoulder_height'] ?? null) ? (float) $row['shoulder_height'] : null,
            'status'          => in_array((int) ($row['status'] ?? 0), [1, 2, 3, 4]) ? (int) $row['status'] : 2,
        ]);
    }

    public function uniqueBy(): string|array
    {
        return ['colla_id', 'alias'];
    }
}
