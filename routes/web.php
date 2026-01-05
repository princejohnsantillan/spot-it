<?php

use App\Livewire\MultiplayerGameUi;
use App\Livewire\MultiplayerLobby;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/solo');
});

Route::view('/solo', 'solo')->name('solo');

// Multiplayer routes
Route::get('/multiplayer', MultiplayerLobby::class)->name('multiplayer.lobby');
Route::get('/multiplayer/{code}', MultiplayerGameUi::class)->name('multiplayer.room');
