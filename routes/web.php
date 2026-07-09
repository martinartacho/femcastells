<?php

use App\Models\Noticia;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/noticies', function () {
    $noticies   = Noticia::published()->global()->public()->orderByDesc('published_at')->get();
    $categories = Noticia::LABELS;
    return view('noticies.index', compact('noticies', 'categories'));
})->name('noticies.index');

Route::redirect('/novetats', '/noticies?label=sistema', 301);

Route::get('/admin/{shortname}/events/{event}/export-answers', function (string $shortname, \App\Models\Event $event) {
    abort_if(! auth()->check(), 403);
    abort_if($event->colla->shortname !== $shortname, 403);

    $questions   = $event->questions()->orderBy('order')->get();
    $attendances = $event->attendance()->with(['casteller', 'answers'])->orderBy('created_at')->get();

    $headers = ['Casteller', 'Assistència', 'Acompanyants'];
    foreach ($questions as $q) {
        $headers[] = $q->text;
    }

    $rows = [$headers];
    foreach ($attendances as $att) {
        $row = [
            $att->casteller->alias,
            match ($att->status) { 'ok' => 'Sí', 'nok' => 'No', default => 'Pendent' },
            $att->companions,
        ];
        foreach ($questions as $q) {
            $answer = $att->answers->firstWhere('question_id', $q->id);
            $value  = '';
            if ($answer) {
                $value = is_array($answer->answer)
                    ? implode(', ', $answer->answer)
                    : (string) $answer->answer;
            }
            $row[] = $value;
        }
        $rows[] = $row;
    }

    $filename = 'respostes-event-' . $event->id . '.csv';
    $callback = function () use ($rows): void {
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM perquè Excel l'obri correctament
        foreach ($rows as $row) {
            fputcsv($out, $row, ';');
        }
        fclose($out);
    };

    return response()->stream($callback, 200, [
        'Content-Type'        => 'text/csv; charset=UTF-8',
        'Content-Disposition' => "attachment; filename=\"{$filename}\"",
    ]);
})->middleware(['web', 'auth']);
