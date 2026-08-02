<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Attendance;
use App\Services\IdcClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class IssueIdcCredentialJob implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly int $attendanceId) {}

    public function handle(IdcClient $idc): void
    {
        $attendance = Attendance::with(['casteller', 'event'])->find($this->attendanceId);

        if (! $attendance || ! $attendance->checked_in || $attendance->event->type !== 2) {
            return;
        }

        $uuid = $idc->findOrCreateCastellerUuid($attendance->casteller);

        if ($uuid) {
            $idc->issueCredential($uuid, 'participacio-diada');
        }
    }
}
