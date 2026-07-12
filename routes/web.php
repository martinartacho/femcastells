<?php

use App\Http\Controllers\DisplayPinyaController;
use App\Http\Controllers\ProjectorDisplayController;
use App\Models\Noticia;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Projector permanent per colla (token de la colla, URL per TV/Raspberry)
Route::get('/display/{shortname}/{token}', ProjectorDisplayController::class)->name('display.projector');

// Compartir un snapshot específic (token del snapshot)
Route::get('/share/{shortname}/{token}', DisplayPinyaController::class)->name('display.pinya');

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

Route::get('/admin/{tenant}/castellers/import-template', function () {
    $headers = [
        'alias', 'name', 'last_name', 'email', 'email2',
        'phone', 'mobile_phone', 'emergency_phone',
        'birthdate', 'gender', 'status', 'num_soci', 'subscription_date',
        'national_id_type', 'national_id', 'nationality',
        'height', 'weight', 'shoulder_height',
        'address', 'postal_code', 'city', 'province', 'country',
    ];
    $example = [
        'alba', 'Alba', 'Garcia Puig', 'alba@example.cat', '',
        '600000001', '', '',
        '15/03/1990', 'F', '1', '42', '01/09/2020',
        'DNI', '12345678A', 'Espanyola',
        '165', '60', '145',
        'Carrer Major 1', '08001', 'Barcelona', 'Barcelonès', 'ES',
    ];

    $callback = function () use ($headers, $example): void {
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, $headers, ';');
        fputcsv($out, $example, ';');
        fclose($out);
    };

    return response()->stream($callback, 200, [
        'Content-Type'        => 'text/csv; charset=UTF-8',
        'Content-Disposition' => 'attachment; filename="plantilla-castellers.csv"',
    ]);
})->middleware(['web', 'auth'])->name('castellers.import-template');
