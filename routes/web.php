<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// routes/web.php, api.php or any other central route files you have



foreach (config('tenancy.central_domains') as $domain) {
    Route::domain($domain)->group(function () {
        // your actual routes
    });

    Route::get('/', function () {
    dd(\App\Models\User::all());
    return 'This is your multi-tenant application. The id of the current tenant is ' . tenant('id');
});

}
