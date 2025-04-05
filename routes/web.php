<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome'); // ou redirect, auth, etc.
});

require __DIR__.'/auth.php';
