<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\Attendance;
use App\Models\Casteller;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AttendanceImport implements ToModel, WithHeadingRow
{
    public function __construct(
        private int $eventId,
        private int $collaId,
    ) {}

    public function model(array $row): ?Attendance
    {
        $alias = trim((string) ($row['sobrenom'] ?? ''));
        if (empty($alias)) {
            return null;
        }

        $casteller = Casteller::where('alias', $alias)
            ->where('colla_id', $this->collaId)
            ->first();

        if ($casteller === null) {
            return null;
        }

        $prevista = strtoupper(trim((string) ($row['assistencia_prevista'] ?? '')));
        $status = match ($prevista) {
            'YES' => 'ok',
            'NO'  => 'nok',
            default => 'unknown',
        };

        $companions = (int) ($row['acompanyants'] ?? 0);

        Attendance::updateOrCreate(
            ['event_id' => $this->eventId, 'casteller_id' => $casteller->id],
            ['status' => $status, 'companions' => $companions]
        );

        return null;
    }

    public function headingRow(): int
    {
        return 1;
    }
}
