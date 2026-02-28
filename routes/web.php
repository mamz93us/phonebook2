<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\ContactController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\ExtensionController;
use App\Http\Controllers\Admin\TrunkController;
use App\Http\Controllers\Admin\UcmServerController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\PermissionsController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\GdmsController;
use App\Http\Controllers\Admin\NetworkController;
use App\Http\Controllers\Auth\MicrosoftController;
use App\Http\Controllers\PhonebookController;
use App\Http\Controllers\PublicContactController;
use App\Http\Controllers\PhoneRequestLogController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/phonebook.xml', [PhonebookController::class, 'generate'])
    ->withoutMiddleware(['web'])
    ->name('phonebook.xml');

Route::get('/contacts', [PublicContactController::class, 'index'])
    ->name('public.contacts');

Route::get('/contacts/print', [PublicContactController::class, 'print'])
    ->name('public.contacts.print');

/*
|--------------------------------------------------------------------------
| Microsoft SSO
|--------------------------------------------------------------------------
*/

Route::get('/auth/microsoft', [MicrosoftController::class, 'redirect'])
    ->name('auth.microsoft');
Route::get('/auth/microsoft/callback', [MicrosoftController::class, 'callback']);

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::get('/dashboard', function () {
    return redirect()->route('admin.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Profile (change password modal)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::put('/admin/profile/password', [ProfileController::class, 'updatePassword'])
        ->name('admin.profile.password');
});

/*
|--------------------------------------------------------------------------
| Admin Routes (Protected by auth + per-route permissions)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {

    // Dashboard (all authenticated users)
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // XML preview (all authenticated)
    Route::get('xml-preview', [PhonebookController::class, 'preview'])
        ->name('xml.preview');

    // ─── Branches ─────────────────────────────────────────────
    Route::middleware('permission:view-branches')->group(function () {
        Route::get('branches', [BranchController::class, 'index'])->name('branches.index');
        Route::get('branches/create', [BranchController::class, 'create'])->name('branches.create');
        Route::get('branches/{branch}/edit', [BranchController::class, 'edit'])->name('branches.edit');
    });
    Route::middleware('permission:manage-branches')->group(function () {
        Route::post('branches', [BranchController::class, 'store'])->name('branches.store');
        Route::put('branches/{branch}', [BranchController::class, 'update'])->name('branches.update');
        Route::patch('branches/{branch}', [BranchController::class, 'update']);
        Route::delete('branches/{branch}', [BranchController::class, 'destroy'])->name('branches.destroy');
    });

    // ─── Contacts ─────────────────────────────────────────────
    Route::middleware('permission:view-contacts')->group(function () {
        Route::get('contacts', [ContactController::class, 'index'])->name('contacts.index');
        Route::get('contacts/create', [ContactController::class, 'create'])->name('contacts.create');
        Route::get('contacts/{contact}/edit', [ContactController::class, 'edit'])->name('contacts.edit');
        Route::post('contacts/check-duplicate', [ContactController::class, 'checkDuplicate'])
            ->name('contacts.check-duplicate');
    });
    Route::middleware('permission:manage-contacts')->group(function () {
        Route::post('contacts', [ContactController::class, 'store'])->name('contacts.store');
        Route::put('contacts/{contact}', [ContactController::class, 'update'])->name('contacts.update');
        Route::patch('contacts/{contact}', [ContactController::class, 'update']);
        Route::delete('contacts/{contact}', [ContactController::class, 'destroy'])->name('contacts.destroy');
    });
    Route::middleware('permission:export-contacts')->group(function () {
        Route::get('contacts-export', [ContactController::class, 'export'])->name('contacts.export');
    });

    // ─── Activity Logs ────────────────────────────────────────
    Route::middleware('permission:view-activity-logs')->group(function () {
        Route::get('activity-logs', [ActivityLogController::class, 'index'])
            ->name('activity-logs');
    });

    // ─── Phone XML Logs ───────────────────────────────────────
    Route::middleware('permission:view-phone-logs')->group(function () {
        Route::get('phone-logs', [PhoneRequestLogController::class, 'index'])
            ->name('phone-logs.index');
    });
    Route::middleware('permission:sync-phone-logs')->group(function () {
        Route::post('phone-logs/sync', [PhoneRequestLogController::class, 'sync'])
            ->name('phone-logs.sync');
        Route::post('phone-logs/sync-unsynced', [PhoneRequestLogController::class, 'syncUnsynced'])
            ->name('phone-logs.sync-unsynced');
    });

    // ─── Extensions (IPPBX) ───────────────────────────────────
    Route::middleware('permission:view-extensions')->group(function () {
        Route::get('extensions', [ExtensionController::class, 'index'])
            ->name('extensions.index');
        Route::get('extensions/{extension}/details', [ExtensionController::class, 'details'])
            ->name('extensions.details');
        Route::get('extensions/{extension}/wave', [ExtensionController::class, 'wave'])
            ->name('extensions.wave');
    });
    Route::middleware('permission:manage-extensions')->group(function () {
        Route::post('extensions', [ExtensionController::class, 'store'])
            ->name('extensions.store');
        Route::put('extensions/{extension}', [ExtensionController::class, 'update'])
            ->name('extensions.update');
        Route::delete('extensions/{extension}', [ExtensionController::class, 'destroy'])
            ->name('extensions.destroy');
    });

    // ─── VoIP Trunks ──────────────────────────────────────────
    Route::middleware('permission:view-trunks')->group(function () {
        Route::get('trunks', [TrunkController::class, 'index'])
            ->name('trunks.index');
    });

    // ─── Settings ─────────────────────────────────────────────
    Route::middleware('permission:manage-settings')->group(function () {
        Route::get('settings', [SettingsController::class, 'index'])
            ->name('settings.index');
        Route::post('settings', [SettingsController::class, 'update'])
            ->name('settings.update');
        Route::delete('settings/logo', [SettingsController::class, 'deleteLogo'])
            ->name('settings.delete-logo');
        Route::post('settings/sso', [SettingsController::class, 'updateSso'])
            ->name('settings.sso');
        Route::post('settings/meraki', [SettingsController::class, 'updateMeraki'])
            ->name('settings.meraki');
    });

    // ─── UCM Servers (managed from Settings page) ─────────────
    Route::middleware('permission:manage-settings')->group(function () {
        Route::post('ucm-servers', [UcmServerController::class, 'store'])
            ->name('ucm-servers.store');
        Route::put('ucm-servers/{ucmServer}', [UcmServerController::class, 'update'])
            ->name('ucm-servers.update');
        Route::delete('ucm-servers/{ucmServer}', [UcmServerController::class, 'destroy'])
            ->name('ucm-servers.destroy');
        Route::patch('ucm-servers/{ucmServer}/toggle', [UcmServerController::class, 'toggleActive'])
            ->name('ucm-servers.toggle');
    });

    // ─── User Management ──────────────────────────────────────
    Route::middleware('permission:manage-users')->group(function () {
        Route::get('users', [UserController::class, 'index'])
            ->name('users.index');
        Route::post('users', [UserController::class, 'store'])
            ->name('users.store');
        Route::put('users/{user}', [UserController::class, 'update'])
            ->name('users.update');
        Route::delete('users/{user}', [UserController::class, 'destroy'])
            ->name('users.destroy');
    });

    // ─── GDMS UCM Status ──────────────────────────────────────
    Route::middleware('permission:view-extensions')->group(function () {
        Route::get('gdms/ucm', [GdmsController::class, 'ucmIndex'])
            ->name('gdms.ucm');
    });

    // ─── Role Permissions ─────────────────────────────────────
    Route::middleware('permission:manage-permissions')->group(function () {
        Route::get('permissions', [PermissionsController::class, 'index'])
            ->name('permissions.index');
        Route::put('permissions', [PermissionsController::class, 'update'])
            ->name('permissions.update');
    });

    // ─── Network (Meraki) ─────────────────────────────────────
    Route::middleware('permission:view-network')->prefix('network')->name('network.')->group(function () {
        Route::get('/',              [NetworkController::class, 'overview'])    ->name('overview');
        Route::get('/switches',      [NetworkController::class, 'switches'])    ->name('switches');
        Route::get('/switches/{serial}', [NetworkController::class, 'switchDetail'])->name('switch-detail');
        Route::get('/clients',       [NetworkController::class, 'clients'])     ->name('clients');
    });

    Route::middleware('permission:view-network-events')->prefix('network')->name('network.')->group(function () {
        Route::get('/events',        [NetworkController::class, 'events'])      ->name('events');
    });

    Route::middleware('permission:manage-network-settings')->prefix('network')->name('network.')->group(function () {
        Route::post('/sync',         [NetworkController::class, 'sync'])        ->name('sync');
        Route::post('/test-connection', [NetworkController::class, 'testConnection'])->name('test-connection');

        // ── Location management ─────────────────────────────────
        Route::get('/locations',                          [NetworkController::class, 'locations'])    ->name('locations');

        Route::post('/floors',                            [NetworkController::class, 'storeFloor'])   ->name('floors.store');
        Route::put('/floors/{floor}',                     [NetworkController::class, 'updateFloor'])  ->name('floors.update');
        Route::delete('/floors/{floor}',                  [NetworkController::class, 'destroyFloor']) ->name('floors.destroy');

        Route::post('/racks',                             [NetworkController::class, 'storeRack'])    ->name('racks.store');
        Route::put('/racks/{rack}',                       [NetworkController::class, 'updateRack'])   ->name('racks.update');
        Route::delete('/racks/{rack}',                    [NetworkController::class, 'destroyRack'])  ->name('racks.destroy');

        Route::post('/switches/{serial}/assign-location', [NetworkController::class, 'assignLocation'])->name('switches.assign-location');
    });
});

/*
|--------------------------------------------------------------------------
| Auth Routes (Laravel Breeze)
|--------------------------------------------------------------------------
*/

require __DIR__.'/auth.php';
