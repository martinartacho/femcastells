<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Casteller;
use App\Models\Event;
use App\Models\EventAnswer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileEventsController extends Controller
{
    private const TYPE_MAP = [1 => 'ASSAIG', 2 => 'ACTUACIO', 3 => 'ACTIVITAT'];

    // DB usa enum 'ok'/'nok'/'unknown'; l'app Flutter usa 'accepted'/'declined'/'undefined'
    private const STATUS_TO_STRING = [
        'ok'      => 'accepted',
        'nok'     => 'declined',
        'unknown' => 'undefined',
    ];

    private const STATUS_FROM_STRING = [
        'accepted'  => 'ok',
        'declined'  => 'nok',
        'undefined' => 'unknown',
        'warning'   => 'unknown',
        'unknown'   => 'unknown',
    ];

    public function index(Request $request): JsonResponse
    {
        /** @var Casteller $casteller */
        $casteller = $request->user();
        $colla     = $casteller->colla;

        $weeksAhead = (int) ($colla->assaigs_weeks_visible ?? 2);
        $assaigLimit = now()->startOfDay()->addWeeks($weeksAhead);

        $query = Event::where('colla_id', $colla->id)
            ->where('visibility', true)
            ->where('start_date', '>=', now()->startOfDay())
            ->where(function ($q) use ($assaigLimit) {
                $q->where('type', '!=', 1)
                  ->orWhere('start_date', '<=', $assaigLimit);
            })
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
                    'id'              => $event->id,
                    'title'           => $event->name,
                    'type'            => self::TYPE_MAP[$event->type] ?? 'ACTIVITAT',
                    'status'          => $status,
                    'startDate'       => $event->start_date?->toIso8601String(),
                    'endDate'         => $event->start_date?->addMinutes((int) ($event->duration ?? 60))->toIso8601String(),
                    'closeDate'       => $event->close_date?->toIso8601String(),
                    'dateHour'        => $event->start_date?->format('H:i'),
                    'address'         => $event->address,
                    'description'     => $event->comments,
                    'allowCompanions' => (bool) $event->companions,
                    'companions'      => $att?->companions ?? 0,
                    'comment'         => null,
                    'tags'            => [],
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

    public function show(Request $request, int $id): JsonResponse
    {
        /** @var Casteller $casteller */
        $casteller = $request->user();

        $event = Event::where('id', $id)
            ->where('colla_id', $casteller->colla_id)
            ->where('visibility', true)
            ->with(['questions' => fn ($q) => $q->orderByPivot('order')])
            ->firstOrFail();

        $att    = Attendance::where('casteller_id', $casteller->id)
            ->where('event_id', $event->id)
            ->first();
        $status = $att ? (self::STATUS_TO_STRING[$att->status] ?? 'undefined') : 'undefined';

        $answersMap = $att
            ? EventAnswer::where('attendance_id', $att->id)
                ->get()
                ->keyBy('question_id')
            : collect();

        $questions = $event->questions->map(function ($q) use ($answersMap): array {
            $raw     = $q->options ?? [];
            $options = collect($raw)->map(
                fn ($o) => is_array($o) ? ($o['value'] ?? '') : $o
            )->values()->all();

            return [
                'id'       => $q->id,
                'text'     => $q->text,
                'type'     => $q->type,
                'required' => (bool) $q->pivot->required,
                'order'    => (int) $q->pivot->order,
                'options'  => $options,
                'answer'   => $answersMap->get($q->id)?->answer,
            ];
        });

        return response()->json([
            'id'              => $event->id,
            'title'           => $event->name,
            'type'            => self::TYPE_MAP[$event->type] ?? 'ACTIVITAT',
            'status'          => $status,
            'startDate'       => $event->start_date?->toIso8601String(),
            'endDate'         => $event->start_date?->addMinutes((int) ($event->duration ?? 60))->toIso8601String(),
            'closeDate'       => $event->close_date?->toIso8601String(),
            'dateHour'        => $event->start_date?->format('H:i'),
            'address'         => $event->address,
            'description'     => $event->comments,
            'allowCompanions' => (bool) $event->companions,
            'companions'      => $att?->companions ?? 0,
            'comment'         => null,
            'tags'            => [],
            'questions'       => $questions,
        ]);
    }

    public function saveAnswers(Request $request, int $id): JsonResponse
    {
        /** @var Casteller $casteller */
        $casteller = $request->user();

        $event = Event::where('id', $id)
            ->where('colla_id', $casteller->colla_id)
            ->firstOrFail();

        $att = Attendance::firstOrCreate(
            ['casteller_id' => $casteller->id, 'event_id' => $event->id],
            ['status' => 'unknown', 'companions' => 0]
        );

        foreach ((array) $request->input('answers', []) as $item) {
            $questionId = (int) ($item['question_id'] ?? 0);
            $answer     = $item['answer'] ?? null;
            if (! $questionId) continue;

            EventAnswer::updateOrCreate(
                ['attendance_id' => $att->id, 'question_id' => $questionId],
                ['answer' => $answer]
            );
        }

        return response()->json(['message' => 'Respostes guardades']);
    }

    public function updateAttendance(Request $request, int $id): JsonResponse
    {
        /** @var Casteller $casteller */
        $casteller = $request->user();

        $event = Event::where('id', $id)
            ->where('colla_id', $casteller->colla_id)
            ->firstOrFail();

        $statusEnum = self::STATUS_FROM_STRING[$request->input('status', 'undefined')] ?? 'unknown';

        Attendance::updateOrCreate(
            ['casteller_id' => $casteller->id, 'event_id' => $event->id],
            ['status' => $statusEnum, 'companions' => (int) $request->input('companions', 0)]
        );

        return response()->json(['message' => 'Assistència actualitzada']);
    }
}
