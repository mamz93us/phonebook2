<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\ContactController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\PhonebookController;
use App\Http\Controllers\PublicContactController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

// XML for Phones
Route::get('/phonebook.xml', [PhonebookController::class, 'generate'])
    ->withoutMiddleware(['web'])
    ->name('phonebook.xml');

// Public Contact Directory
Route::get('/contacts', [PublicContactController::class, 'index'])
    ->name('public.contacts');

// Dashboard redirect
Route::get('/dashboard', function () {
    return redirect()->route('admin.dashboard');
})->middleware(['auth'])->name('dashboard');

// Profile routes
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| Admin Routes (Protected by auth)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {

    // Dashboard
    Route::get('/', function () {
        return view('admin.dashboard');
    })->name('dashboard');

    // Branches
    Route::resource('branches', BranchController::class)
        ->except(['show']);

    // Contacts
    Route::resource('contacts', ContactController::class)
        ->except(['show']);

    // Contact Export
    Route::get('/contacts-export', [ContactController::class, 'export'])
        ->name('contacts.export');

    // Check Duplicate Email (AJAX)
    Route::post('/contacts/check-duplicate', [ContactController::class, 'checkDuplicate'])
        ->name('contacts.check-duplicate');

    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])
        ->name('settings.index');
    Route::post('/settings', [SettingsController::class, 'update'])
        ->name('settings.update');
    Route::delete('/settings/logo', [SettingsController::class, 'deleteLogo'])
        ->name('settings.delete-logo');

    // Activity Logs
    Route::get('/activity-logs', [ActivityLogController::class, 'index'])
        ->name('activity-logs');

    // XML preview
    Route::get('/xml-preview', [PhonebookController::class, 'preview'])
        ->name('xml.preview');
});

/*
|--------------------------------------------------------------------------
| Auth Routes (Laravel Breeze)
|--------------------------------------------------------------------------
*/

// Disable registration
Route::any('/register', function () {
    abort(404);
});

require __DIR__.'/auth.php';
