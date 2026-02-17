<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Dashboard redirect
Route::get('/dashboard', function () {
    return redirect()->route('admin.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Profile routes
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin routes
Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {
    // Dashboard
    Route::get('/', function () {
        return view('admin.dashboard');
    })->name('dashboard');
    
    // Branches
    Route::resource('branches', \App\Http\Controllers\Admin\BranchController::class);
    
    // Contacts
    Route::resource('contacts', \App\Http\Controllers\Admin\ContactController::class);
    Route::post('contacts/check-duplicate', [\App\Http\Controllers\Admin\ContactController::class, 'checkDuplicate'])->name('contacts.check-duplicate');
    Route::get('contacts/export/excel', [\App\Http\Controllers\Admin\ContactController::class, 'exportExcel'])->name('contacts.export');
    
    // Activity Logs
    Route::get('activity-logs', [\App\Http\Controllers\Admin\ActivityLogController::class, 'index'])->name('activity-logs');
    
    // Settings
    Route::get('settings', [\App\Http\Controllers\Admin\SettingsController::class, 'index'])->name('settings.index');
    Route::post('settings', [\App\Http\Controllers\Admin\SettingsController::class, 'update'])->name('settings.update');
});

// Public contacts directory (no auth required)
Route::get('/contacts', [\App\Http\Controllers\PublicContactController::class, 'index'])->name('public.contacts');

require __DIR__.'/auth.php';
