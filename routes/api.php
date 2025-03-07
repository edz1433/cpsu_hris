<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DtrController;

Route::post('/dtrs', [DtrController::class, 'syncDtr'])->name('api.syncDtr');
