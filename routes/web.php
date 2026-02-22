<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\ContactController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\ExtensionController;
use App\Http\Controllers\Admin\UcmServerController;
use App\Http\Controllers\PhonebookController;
use App\Http\Controllers\PublicContactController;
use App\Http\Controllers\PhoneRequestLogController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

// Welcome page
Route::get('/', function () {
    return view('welcome');
});

// XML for Phones
Route::get('/phonebook.xml', [PhonebookController::class, 'generate'])
    ->withoutMiddleware(['web'])
    ->name('phonebook.xml');

// Public Contact Directory
Route::get('/contacts', [PublicContactController::class, 'index'])
    ->name('public.contacts');

// Public Contact Print
Route::get('/contacts/print', [PublicContactController::class, 'print'])
    ->name('public.contacts.print');

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

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
    Route::get('contacts-export', [ContactController::class, 'export'])
        ->name('contacts.export');

    // Check Duplicate Email (AJAX)
    Route::post('contacts/check-duplicate', [ContactController::class, 'checkDuplicate'])
        ->name('contacts.check-duplicate');

    // Settings
    Route::get('settings', [SettingsController::class, 'index'])
        ->name('settings.index');
    Route::post('settings', [SettingsController::class, 'update'])
        ->name('settings.update');
    Route::delete('settings/logo', [SettingsController::class, 'deleteLogo'])
        ->name('settings.delete-logo');
    Route::get('phone-logs', [PhoneRequestLogController::class, 'index'])
        ->name('phone-logs.index');
    Route::post('phone-logs/sync', [PhoneRequestLogController::class, 'sync'])
        ->name('phone-logs.sync');
    Route::post('phone-logs/sync-unsynced', [PhoneRequestLogController::class, 'syncUnsynced'])
        ->name('phone-logs.sync-unsynced');

    // Activity Logs
    Route::get('activity-logs', [ActivityLogController::class, 'index'])
        ->name('activity-logs');

    // XML preview
    Route::get('xml-preview', [PhonebookController::class, 'preview'])
        ->name('xml.preview');

    // ─── Extensions (IPPBX) ───────────────────────────────────
    Route::get('extensions', [ExtensionController::class, 'index'])
        ->name('extensions.index');
    Route::post('extensions', [ExtensionController::class, 'store'])
        ->name('extensions.store');
    Route::put('extensions/{extension}', [ExtensionController::class, 'update'])
        ->name('extensions.update');
    Route::delete('extensions/{extension}', [ExtensionController::class, 'destroy'])
        ->name('extensions.destroy');

    // ─── UCM Servers (managed from Settings page) ─────────────
    Route::post('ucm-servers', [UcmServerController::class, 'store'])
        ->name('ucm-servers.store');
    Route::put('ucm-servers/{ucmServer}', [UcmServerController::class, 'update'])
        ->name('ucm-servers.update');
    Route::delete('ucm-servers/{ucmServer}', [UcmServerController::class, 'destroy'])
        ->name('ucm-servers.destroy');
    Route::patch('ucm-servers/{ucmServer}/toggle', [UcmServerController::class, 'toggleActive'])
        ->name('ucm-servers.toggle');
});

/*
|--------------------------------------------------------------------------
| Auth Routes (Laravel Breeze)
|--------------------------------------------------------------------------
*/

require __DIR__.'/auth.php';
