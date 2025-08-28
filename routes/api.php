<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DtrController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\TimeEntryController;
use App\Http\Controllers\Api\JobHiringController;

Route::post('/dtrs', [DtrController::class, 'syncDtr'])->name('api.syncDtr');
Route::post('/dtrs-batch', [DtrController::class, 'syncDtrBatch'])->name('api.syncDtrBatch');
Route::get('/event-list/{passcode}', [EventController::class, 'eventList'])->name('api.eventList');
Route::get('/event-login/{passcode}/{eventid}/{empid}', [EventController::class, 'eventLogin'])->name('api.eventLogin');
Route::get('/event-logs/{passcode}/{eventid}', [EventController::class, 'eventLogs'])->name('api.eventLogs');

Route::get('/job-list', [JobHiringController::class, 'jobList'])->name('api.jobList');

Route::prefix('app')->group(function() {
    Route::get('/dtrlogs', [DtrController::class, 'appdtrLogs'])->name('appdtrLogs');
    Route::get('/authcheck', [DtrController::class, 'appdtrauthCheck'])->name('appdtrauthcheck');
    Route::get('/authlogin', [DtrController::class, 'appdtrauthLogin'])->name('appdtrauthLogin');
    Route::get('/check-coordinates', [DtrController::class, 'checkCoordinates'])->name('checkCoordinates');

    // Time Entry
    Route::post('/health', function (Request $request) { return response()->noContent(); });
    
    Route::post('/face-verify', [TimeEntryController::class, 'faceVerify']);
    Route::post('/log-attendance', [TimeEntryController::class, 'logAttendance']);
    
    Route::post('/fetch-logzones', [TimeEntryController::class, 'fetchLogzones']);
   

    Route::post('/fetch-employees', [TimeEntryController::class, 'fetchEmployees']);
    Route::post('/face-register', [TimeEntryController::class, 'faceRegister']);

    Route::post('/fetch-recent-logs', [TimeEntryController::class, 'fetchRecentLogs']);
    Route::post('/admin-face-verify', [TimeEntryController::class, 'adminFaceVerify']);
});