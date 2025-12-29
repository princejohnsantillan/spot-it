<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/solo');
//    return view('welcome');
});

Route::view('/solo', 'solo')->name('solo');
