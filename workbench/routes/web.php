<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

// Serve the compiled Tailwind + Flux stylesheet (testbench does not expose
// workbench/public). Built via `npm run build:css`.
Route::get('/css/app.css', function () {
    return response()->file(dirname(__DIR__).'/public/css/app.css', [
        'Content-Type' => 'text/css',
    ]);
});
