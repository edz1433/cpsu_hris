<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\NoCacheMiddleware;
use App\Http\Controllers\LoginAuthController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\MasterController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\TirednessController;
use App\Http\Controllers\OfficeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\MyAccountController;
use App\Http\Controllers\DocumentFolderController;
use App\Http\Controllers\DriveAccountController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DtrController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\DpipopController;
use App\Http\Controllers\PdsController;
use App\Http\Controllers\FamilybgController;
use App\Http\Controllers\EducBgController;
use App\Http\Controllers\EligibilityController;
use App\Http\Controllers\WorkExperienceController;
use App\Http\Controllers\VoluntaryWorkController;
use App\Http\Controllers\LearningDevController;
use App\Http\Controllers\OtherInfoController;
use App\Http\Controllers\InfoQuestionController;
use App\Http\Controllers\PdsReferencesController;
use App\Http\Controllers\GovIdController;
use App\Http\Controllers\LeaveCreditController;
use App\Http\Controllers\LeaveApplicationController;
use App\Http\Controllers\NotificationController;
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
// Route::get('/update-pass', [EmployeeController::class, 'updateEmployeePasswords']);

Route::get('/auth/google', [GoogleAuthController::class, 'redirectToGoogle'])->name('google.login');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback']);
Route::get('/verify', [GoogleAuthController::class, 'verifyForm'])->name('verify');
Route::post('/verify', [GoogleAuthController::class, 'verify'])->name('verify.code');

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

        //performance rating
        Route::get('/pr/{empid?}/{folderId}', [DocumentController::class, 'perRating'])->name('per-rating');
    });
    
    // Drive Account
    Route::get('/account', [DriveAccountController::class, 'driveAccount'])->name('drive-account');

    // DTR
    Route::prefix('dtr')->group(function() {
        Route::get('/', [DtrController::class, 'dtrRead'])->name('dtr-read');
        Route::post('/', [DtrController::class, 'dtrSearch'])->name('dtrSearch');
        Route::get('/dtr-logs', [DtrController::class, 'dtrLogs'])->name('dtrLogs');
        Route::post('/dtr-logs', [DtrController::class, 'dtrLogs'])->name('dtrLogspost');
        Route::get('/dtr-log-pdf/{employeeId}/{dateFrom}/{dateTo}', [DtrController::class, 'logDtrView'])->name('logDtrView');
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
        // Route::get('/', [MyAccountController::class, 'myAccount']) ->name('myAccount');
        // Route::post('/update-account', [MyAccountController::class, 'updateAccount']) ->name('updateAccount');
    });
    
    // Employee
    Route::prefix('employees')->group(function() {
        Route::get('/', [EmployeeController::class, 'emp_list'])->name('emp_list');
        Route::get('/add', [EmployeeController::class, 'empAdd'])->name('empAdd');
        Route::get('/generate', [EmployeeController::class, 'genEmp'])->name('genEmp');

        Route::post('/create', [EmployeeController::class, 'empCreate'])->name('empCreate');
        Route::post('/update-profile/{id}', [EmployeeController::class, 'updateProfilePicture'])->name('updateProfilePicture');
        Route::post('/update', [EmployeeController::class, 'empUpdate'])->name('empUpdate');
        Route::post('/employee-update', [EmployeeController::class, 'employeeUpdate'])->name('employeeUpdate');
        Route::post('/toggle-acct-stat', [EmployeeController::class, 'toggleAcctStat'])->name('toggleAcctStat');

        Route::get('/delete/{id}', [EmployeeController::class, 'empDelete'])->name('empDelete');
    });
    
    Route::prefix('tirdeness')->group(function(){
        Route::get('/', [TirednessController::class, 'readTiredness'])->name('readTiredness');
        Route::post('/', [TirednessController::class, 'readTiredness'])->name('tirednessSearch');
        Route::get('/pdf/{employeeId}/{month}', [TirednessController::class, 'pdfTirednes'])->name('pdfTirednes');
    });
    
    //pds
    Route::prefix('pds')->group(function() {
        Route::get('/', [PdsController::class, 'empPDS'])->name('empPDS');  
        Route::get('/generatepds/{id?}', [PdsController::class, 'generatepds'])->name('generatepds');
        
        //personal Info
        Route::get('personal-info/{id}', [EmployeeController::class, 'PDS'])->name('PDS');   

        //family background
        Route::get('/family-bg/{id?}', [FamilybgController::class, 'familybg'])->name('familybg');
        Route::post('/update-child', [FamilyBgController::class, 'updateChild'])->name('update-child');
        Route::post('/familybg-update', [FamilyBgController::class, 'familyBgUpdate'])->name('familyBgUpdate');
        Route::post('/familybg-update-array', [FamilyBgController::class, 'familyBgUpdateArray'])->name('familyBgUpdateArray');
        
        //Educational Background
        Route::get('/educ-bg/{id?}', [EducBgController::class, 'educbg'])->name('educbg');
        Route::post('/educbg-update', [EducBgController::class, 'educBgUpdate'])->name('educBgUpdate');

        //Eligibility
        Route::get('/eligibility/{id?}', [EligibilityController::class, 'eligibility'])->name('eligibility');
        Route::post('/eligibility-create', [EligibilityController::class, 'eligibilityCreate'])->name('eligibilityCreate');
        Route::get('/eligibility-edit/{id?}/{eid}', [EligibilityController::class, 'eligibilityEdit'])->name('eligibilityEdit');
        Route::post('/eligibility-update/{id}', [EligibilityController::class, 'eligibilityUpdate'])->name('eligibilityUpdate');
        Route::post('/eligibility-delete/{id}', [EligibilityController::class, 'eliDelete'])->name('eliDelete');
        Route::post('/eligibility-approve/{id}', [EligibilityController::class, 'eliApprove'])->name('eliApprove');

        //Work-experience
        Route::get('/work-experience/{id?}', [WorkExperienceController::class, 'workexperience'])->name('work-experience');
        Route::post('/work-experience-create', [WorkExperienceController::class, 'workexperienceCreate'])->name('workexperienceCreate');
        Route::get('/work-experience-edit/{id?}/{eid}', [WorkExperienceController::class, 'workexperienceEdit'])->name('workexperienceEdit');
        Route::post('/work-experience-update/{id}', [WorkExperienceController::class, 'workexperienceUpdate'])->name('workexperienceUpdate');
        Route::post('/work-experience-delete/{id}', [WorkExperienceController::class, 'workDelete'])->name('workDelete');
        Route::post('/work-experience-approve/{id}', [WorkExperienceController::class, 'expApprove'])->name('expApprove');

        //Voluntary-works
        Route::get('/voluntary-work/{id?}', [VoluntaryWorkController::class, 'voluntaryworks'])->name('voluntary-work');
        Route::post('/voluntary-work-create', [VoluntaryWorkController::class, 'voluntaryworksCreate'])->name('voluntaryworksCreate');
        Route::get('/voluntary-work-edit/{id?}/{eid}', [VoluntaryWorkController::class, 'voluntaryworksEdit'])->name('voluntaryworksEdit');
        Route::post('/voluntary-work-update/{id}', [VoluntaryWorkController::class, 'voluntaryworksUpdate'])->name('voluntaryworksUpdate');
        Route::post('/voluntary-work-delete/{id}', [VoluntaryWorkController::class, 'voluntaryworkDelete'])->name('voluntaryworkDelete');
        Route::post('/voluntary-work-approve/{id}', [VoluntaryWorkController::class, 'voluntaryworksApprove'])->name('voluntaryworksApprove');
        
        //Learning-development
        Route::get('/learning-dev/{id?}', [LearningDevController::class, 'learningdev'])->name('learning-dev');
        Route::post('/learning-dev-create', [LearningDevController::class, 'learningdevCreate'])->name('learningdevCreate');
        Route::get('/learning-dev-edit/{id?}/{eid}', [LearningDevController::class, 'learningdevEdit'])->name('learningdevEdit');
        Route::post('/learning-dev-update/{id}', [LearningDevController::class, 'learningdevUpdate'])->name('learningdevUpdate');
        Route::post('/learning-dev-delete/{id}', [LearningDevController::class, 'learningdevDelete'])->name('learningdevDelete');
        Route::post('/learning-dev-approve/{id}', [LearningDevController::class, 'learningdevApprove'])->name('learningdevApprove');

        //Other Information
        Route::get('/other-info/{id?}', [OtherInfoController::class, 'otherInfo'])->name('otherInfo');
        Route::post('/update-child-oi', [OtherInfoController::class, 'updateChild'])->name('update-child-oi');
        Route::post('/otherinfo-update', [OtherInfoController::class, 'otherInfoUpdate'])->name('otherInfoUpdate');
        Route::post('/otherInfo-update-array', [OtherInfoController::class, 'otherInfoUpdateArray'])->name('otherInfoUpdateArray');

        //Other Information Question
        Route::get('/info-question/{id?}', [InfoQuestionController::class, 'infoQuestion'])->name('infoQuestion');
        Route::post('/update-info-question', [InfoQuestionController::class, 'update'])->name('update.info.question');
        
        //References
        Route::get('/references/{id?}', [PdsReferencesController::class, 'references'])->name('references');
        Route::post('/update-references', [PdsReferencesController::class, 'update'])->name('update.references');

        //Government ID
        Route::get('/government-id/{id?}', [GovIdController::class, 'govids'])->name('govids');
        Route::post('/update-govids', [GovIdController::class, 'update'])->name('update.govids');
        
        //Signature
        Route::get('/signature/{id?}', [PdsController::class, 'signature'])->name('signature');
        Route::post('/upload-signature/{id?}', [PdsController::class, 'uploadSignature'])->name('uploadSignature');
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
    
    //Leave-Credits
    Route::prefix('leaves')->group(function() {
        Route::get('/{id?}', [LeaveCreditController::class, 'leavesRead'])->name('leavesRead');
        Route::post('/leaves-create', [LeaveCreditController::class, 'leavesCreate'])->name('leavesCreate');
        Route::post('/leaves-deduct', [LeaveCreditController::class, 'leavescreditDeduct'])->name('leavescreditDeduct');
        Route::post('/leaves-deduct-update', [LeaveCreditController::class, 'leavescreditDeductUpdate'])->name('leavescreditDeductUpdate');
        Route::post('/leaves-edit/{id}', [LeaveCreditController::class, 'leavesEdit'])->name('leavesEdit');
        Route::post('/leaves-update', [LeaveCreditController::class, 'leavesUpdate'])->name('leavesUpdate');
        Route::post('/delete/{id}/{empid}', [LeaveCreditController::class, 'leavesDelete'])->name('leavesDelete');  
    });

    //Notification
    Route::prefix('notification')->group(function() {
        Route::get('/load/{page}', [NotificationController::class, 'loadMore'])->name('notificationload');
        Route::get('/update-notif/{menid}/{lappid}/{menu}', [NotificationController::class, 'updateNotif'])->name('updateNotif');
    });

    // Logout
    Route::prefix('leave')->group(function() {
        Route::get('/', [LeaveCreditController::class, 'leavesReadEmp'])->name('leavesReadEmp');
        Route::post('/create', [LeaveApplicationController::class, 'LeaveAppCreate'])->name('LeaveAppCreate');
        
        Route::get('/status/{id?}', [LeaveApplicationController::class, 'leaveStatus'])->name('leaveStatus');
        Route::post('/leave-wpay', [LeaveApplicationController::class, 'leaveWpay'])->name('leaveWpay');
        Route::post('/approve', [LeaveApplicationController::class, 'leaveApprove'])->name('leaveApprove');
        Route::post('/dis-approve', [LeaveApplicationController::class, 'leaveDisapprove'])->name('leaveDisapprove');
        Route::get('/preview-leave/{id}', [LeaveApplicationController::class, 'previewLeave'])->name('previewLeave');   
        Route::post('/leave-live/{id?}', [LeaveApplicationController::class, 'leaveLive'])->name('leaveLive');
        Route::get('/history/{id?}', [LeaveApplicationController::class, 'historyRead'])->name('historyRead');
        Route::post('/return/{id?}', [LeaveApplicationController::class, 'leaveReturn'])->name('leaveReturn');
        
        Route::post('/get-pdf-path', [LeaveApplicationController::class, 'getPdfPath'])->name('getPdfPath');
    });
    
    Route::get('/leave/disapprove', [LeaveApplicationController::class, 'leaveDisapprove']);
    Route::get('/logout', [MasterController::class, 'logout'])->name('logout');
});



