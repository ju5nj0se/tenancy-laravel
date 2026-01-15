<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TenantController;

Route::get('/', function () {
    return view('welcome');
});

// routes/web.php, api.php or any other central route files you have

foreach (config('tenancy.central_domains') as $domain) {
    Route::domain($domain)->group(function () {
        // your actual central routes
    });
}

Route::get('/tenant', [TenantController::class, 'index'])->name('tenant.index');
Route::post('/store', [TenantController::class, 'store'])->name('tenant.store');

