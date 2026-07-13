<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Casteller;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileEventsController extends Controller
{
    private const TYPE_MAP = [1 => 'ASSAIG', 2 => 'ACTUACIO', 3 => 'ACTIVITAT'];

    private const STATUS_TO_STRING = [
        1  => 'accepted',
        -1 => 'declined',
        0  => 'undefined',
    ];

    private const STATUS_FROM_STRING = [
        'accepted'  => 1,
        'declined'  => -1,
        'undefined' => 0,
        'warning'   => 0,
        'unknown'   => 0,
    ];

    public function index(Request $request): JsonResponse
    {
        /** @var Casteller $casteller */
        $casteller = $request->user();
        $colla     = $casteller->colla;

        $query = Event::where('colla_id', $colla->id)
            ->where('visibility', true)
            ->orderBy('start_date');

        if ($request->filled('startDate')) {
            $date  = \Carbon\Carbon::parse($request->startDate);
            $query->whereDate('start_date', $date->toDateString());
        }

        if ($request->filled('eventTypeFilters')) {
            $typeStrings = (array) $request->eventTypeFilters;
            $typeInts    = array_keys(array_filter(
                self::TYPE_MAP,
                fn (string $label) => in_array($label, $typeStrings, true)
            ));
            if ($typeInts) {
                $query->whereIn('type', $typeInts);
            }
        }

        $events = $query->get();

        $attendanceMap = Attendance::where('casteller_id', $casteller->id)
            ->whereIn('event_id', $events->pluck('id'))
            ->get()
            ->keyBy('event_id');

        $showAnswered  = filter_var($request->input('showAnswered', true), FILTER_VALIDATE_BOOLEAN);
        $showUndefined = filter_var($request->input('showUndefined', true), FILTER_VALIDATE_BOOLEAN);

        $result = $events
            ->map(function (Event $event) use ($attendanceMap, $casteller): array {
                $att    = $attendanceMap->get($event->id);
                $status = $att ? (self::STATUS_TO_STRING[$att->status] ?? 'undefined') : 'undefined';

                return [
                    'id'          => $event->id,
                    'title'       => $event->name,
                    'type'        => self::TYPE_MAP[$event->type] ?? 'ACTIVITAT',
                    'status'      => $status,
                    'startDate'   => $event->start_date?->toIso8601String(),
                    'endDate'     => $event->start_date?->addMinutes((int) ($event->duration ?? 60))->toIso8601String(),
                    'dateHour'    => $event->start_date?->format('H:i'),
                    'address'     => $event->address,
                    'description' => $event->comments,
                    'companions'  => $att?->companions ?? 0,
                    'comment'     => null,
                    'tags'        => [],
                ];
            })
            ->filter(function (array $event) use ($showAnswered, $showUndefined): bool {
                if ($event['status'] === 'undefined') {
                    return $showUndefined;
                }

                return $showAnswered;
            })
            ->values();

        return response()->json($result);
    }

    public function updateAttendance(Request $request, int $id): JsonResponse
    {
        /** @var Casteller $casteller */
        $casteller = $request->user();

        $event = Event::where('id', $id)
            ->where('colla_id', $casteller->colla_id)
            ->firstOrFail();

        $statusString = $request->input('status', 'undefined');
        $statusInt    = self::STATUS_FROM_STRING[$statusString] ?? 0;

        Attendance::updateOrCreate(
            ['casteller_id' => $casteller->id, 'event_id' => $event->id],
            ['status' => $statusInt, 'companions' => (int) $request->input('companions', 0)]
        );

        return response()->json(['message' => 'Assistència actualitzada']);
    }
}
