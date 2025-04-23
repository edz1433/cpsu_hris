<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DtrController;
use App\Http\Controllers\Api\EventController;

Route::post('/dtrs', [DtrController::class, 'syncDtr'])->name('api.syncDtr');
Route::post('/dtrs-batch', [DtrController::class, 'syncDtrBatch'])->name('api.syncDtrBatch');
Route::get('/event-list/{passcode}', [EventController::class, 'eventList'])->name('api.eventList');
Route::get('/event-login/{passcode}/{eventid}/{empid}', [EventController::class, 'eventLogin'])->name('api.eventLogin');
Route::get('/event-logs/{passcode}/{eventid}', [EventController::class, 'eventLogs'])->name('api.eventLogs');

Route::prefix('app')->group(function() {
    Route::get('/dtrlogs', [DtrController::class, 'appdtrLogs'])->name('appdtrLogs');
    Route::get('/authcheck', [DtrController::class, 'appdtrauthCheck'])->name('appdtrauthcheck');
    Route::get('/authlogin', [DtrController::class, 'appdtrauthLogin'])->name('appdtrauthLogin');
    Route::get('/check-coordinates', [DtrController::class, 'checkCoordinates'])->name('checkCoordinates');
});