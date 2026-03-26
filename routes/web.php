<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landing');
});

Route::get('/dashboard/{any?}', function () {
    return view('dashboard');
})->where('any', '.*')->name('dashboard');
