<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Admin\ShooterController;
use App\Http\Controllers\Admin\TargetController;
use App\Http\Controllers\Admin\MappingController;

/*
|--------------------------------------------------------------------------
| Public Route (Redirect to login)
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return redirect()->route('login');
});


/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

// Dashboard — visible to ALL logged-in users
Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ADMIN + SUPER ADMIN
    Route::middleware('role:super_admin,admin')->group(function () {
        Route::resource('mappings', MappingController::class);
        Route::get('/targets', [TargetController::class, 'index'])->name('targets.index');
    });
    
    // ONLY SUPER ADMIN
    Route::middleware('role:super_admin')->group(function () {
        Route::resource('users', UserController::class);
        
        Route::get('/shooters', [ShooterController::class, 'index'])->name('shooters.index');
        Route::get('/targets', [TargetController::class, 'index'])->name('targets.index');
        Route::get('/mappings', [MappingController::class, 'index'])->name('mappings.index');

    });
});


// temp and test routes 
Route::middleware('auth')->get('/test', fn()=> 'OK');
Route::get('/logs', function () {
    return 'Logs will come later';
})->name('logs.index')->middleware('auth');


/*
|--------------------------------------------------------------------------
| Auth Routes (Breeze)
|--------------------------------------------------------------------------
*/
require __DIR__.'/auth.php';
