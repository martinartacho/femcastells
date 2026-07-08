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

Route::get('/novetats', function () {
    $releases = Noticia::published()->global()->public()
        ->whereJsonContains('labels', 'sistema')
        ->orderByDesc('published_at')
        ->get();
    return view('noticies.novetats', compact('releases'));
})->name('noticies.novetats');
