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
