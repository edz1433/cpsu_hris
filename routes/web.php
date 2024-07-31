<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginAuthController;
use App\Http\Controllers\MasterController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\OfficeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\MyAccountController;
use App\Http\Controllers\DocumentFolderController;
use App\Http\Controllers\DriveAccountController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DtrController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\PdsController;
use App\Http\Controllers\DpipopController;
use App\Http\Middleware\NoCacheMiddleware;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    if (Auth::guard('web')->check()) {
        return redirect()->route('dashboard');
    }elseif(Auth::guard('employee')->check()){
        return redirect()->route('drive');
    }
    return view('login');
});

//login
Route::get('/login',[LoginAuthController::class,'getLogin'])->name('getLogin')->middleware([NoCacheMiddleware::class]);
Route::post('/login',[LoginAuthController::class,'postLogin'])->name('postLogin');
Route::get('/update-pass', [EmployeeController::class, 'updateEmployeePasswords']);
Route::group(['middleware' => ['login_auth', NoCacheMiddleware::class]], function() {
    // Dashboard
    Route::get('/dashboard', [MasterController::class, 'dashboard'])->name('dashboard');

    // Drive
    Route::prefix('spms')->group(function() {
        Route::get('/', [MasterController::class, 'drive'])->name('drive');
        Route::get('/{id}', [DocumentFolderController::class, 'subFolder'])->name('sub-folder');
        Route::post('/create', [DocumentFolderController::class, 'createFolder'])->name('create-folder');
        Route::post('/update', [DocumentFolderController::class, 'updateFolder'])->name('update-folder');
        Route::post('/create-sub/{id}', [DocumentFolderController::class, 'createSubFolder'])->name('create-subfolder');
        Route::get('/delete/{id}', [DocumentFolderController::class, 'deleteFolder'])->name('delete-folder');

        // Upload File
        Route::post('/upload/{id}', [DocumentController::class, 'storeFile'])->name('document-store');
        Route::post('/update-file', [DocumentController::class, 'updateFile'])->name('document-update');
        Route::get('/delete-file/{id}', [DocumentController::class, 'deleteFile'])->name('delete-file');
    });

    // Drive Account
    Route::get('/account', [DriveAccountController::class, 'driveAccount'])->name('drive-account');

    // DTR
    Route::prefix('dtr')->group(function() {
        Route::get('/', [DtrController::class, 'dtrRead'])->name('dtr-read');
        Route::post('/', [DtrController::class, 'dtrSearch'])->name('dtrSearch');
        Route::get('/dtr-logs', [DtrController::class, 'dtrLogs'])->name('dtrLogs');
        Route::post('/dtr-logs', [DtrController::class, 'dtrLogs'])->name('dtrLogspost');
        Route::get('/pdf', [DtrController::class, 'dtrPdf'])->name('dtr-pdf');
    });

    //DPIPOP
    
    Route::prefix('pr-form')->group(function() {
        Route::post('/', [DpipopController::class, 'createpr'])->name('createpr');
        Route::post('/get-formdata', [DpipopController::class, 'getFormData'])->name('getFormData');
    });

    // User
    Route::prefix('user')->group(function() {
        Route::get('/', [UserController::class, 'ulist'])->name('ulist');
        Route::post('/create', [UserController::class, 'uCreate'])->name('uCreate');
        Route::get('/edit/{id}', [UserController::class, 'uEdit'])->name('uEdit');
        Route::post('/update', [UserController::class, 'uUpdate'])->name('uUpdate');
        Route::get('/delete/{id}', [UserController::class, 'uDelete'])->name('uDelete');

        Route::get('/myaccount', [UserController::class, 'myAccount'])->name('myAccount');
    });

    // My Account
    Route::prefix('/myaccount')->group(function(){
        Route::get('/', [MyAccountController::class, 'myAccount']) ->name('myAccount');
        Route::post('/update-account', [MyAccountController::class, 'updateAccount']) ->name('updateAccount');
    });

    // Employee
    Route::prefix('employees')->group(function() {
        Route::get('/', [EmployeeController::class, 'emp_list'])->name('emp_list');
        Route::get('/add', [EmployeeController::class, 'empAdd'])->name('empAdd');

        Route::post('/create', [EmployeeController::class, 'empCreate'])->name('empCreate');
        Route::get('/pds/{id}', [EmployeeController::class, 'PDS'])->name('PDS');
        Route::post('/update-profile/{id}', [EmployeeController::class, 'updateProfilePicture'])->name('updateProfilePicture');
        Route::post('/update', [EmployeeController::class, 'empUpdate'])->name('empUpdate');
        Route::post('/employee-update', [EmployeeController::class, 'employeeUpdate'])->name('employeeUpdate');
        Route::post('/toggle-accnt-stat', [EmployeeController::class, 'toggleAcctStat'])->name('toggleAcctStat');
        Route::get('/delete/{id}', [EmployeeController::class, 'empDelete'])->name('empDelete');
    });

    Route::prefix('pds')->group(function() {
        Route::get('/', [PdsController::class, 'empPDS'])->name('empPDS');
    });
    // Modify
    Route::prefix('modify')->group(function() {
        Route::post('/show', [ModifyController::class, 'modifyShow'])->name('modifyShow');
        Route::post('/update', [ModifyController::class, 'modifyUpdate'])->name('modifyUpdate');
    });

    // Office
    Route::prefix('office')->group(function() {
        Route::get('/', [OfficeController::class, 'officeList'])->name('officeList');
        Route::post('/create', [OfficeController::class, 'officeCreate'])->name('officeCreate');
        Route::get('/edit/{id}', [OfficeController::class, 'officeEdit'])->name('officeEdit');
        Route::post('/update', [OfficeController::class, 'officeUpdate'])->name('officeUpdate');
        Route::get('/delete/{id}', [OfficeController::class, 'officeDelete'])->name('officeDelete');
    });

    //Address
    Route::prefix('/address')->group(function() {
        Route::get('/provinces/{regionId}', [AddressController::class, 'getProvinces'])->name('getProvinces');
        Route::get('/cities/{provinceId}', [AddressController::class, 'getCities'])->name('getCities');
        Route::get('/barangays/{cityId}', [AddressController::class, 'getBarangays'])->name('getBarangays');
    }); 

    // Calendar
    Route::prefix('events')->group(function() {
        Route::get('/', [CalendarController::class, 'eventRead'])->name('eventRead');
        Route::get('/show', [CalendarController::class, 'eventShow'])->name('eventShow');
        Route::post('/create', [CalendarController::class, 'eventCreate'])->name('eventCreate');
        Route::get('/edit/{id}', [CalendarController::class, 'eventEdit'])->name('eventEdit');
        Route::post('/update', [CalendarController::class, 'eventUpdate'])->name('eventUpdate');
        Route::get('/delete/{id}', [CalendarController::class, 'eventDelete'])->name('eventDelete');
    });
    
    // Logout
    Route::get('/logout', [MasterController::class, 'logout'])->name('logout');
});



