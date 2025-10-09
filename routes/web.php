<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

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
Route::get('/intro', 'LandingpageController@index');
// Route::get('/', 'HomeController@index'); // Disabled - using hotels as homepage
Route::get('/home', 'HomeController@index')->name('home');
Route::post('/install/check-db', 'HomeController@checkConnectDatabase');

// Social Login
Route::get('social-login/{provider}', 'Auth\LoginController@socialLogin');
Route::get('social-callback/{provider}', 'Auth\LoginController@socialCallBack');

// Logs
Route::get(config('admin.admin_route_prefix') . '/logs', '\Rap2hpoutre\LaravelLogViewer\LogViewerController@index')->middleware(['auth', 'dashboard', 'system_log_view'])->name('admin.logs');

Route::get('/install', 'InstallerController@redirectToRequirement')->name('LaravelInstaller::welcome');
Route::get('/install/environment', 'InstallerController@redirectToWizard')->name('LaravelInstaller::environment');
Route::fallback([\Modules\Core\Controllers\FallbackController::class, 'FallBack']);

// Hide page update default
Route::get('/update', 'InstallerController@redirectToHome');
Route::get('/update/overview', 'InstallerController@redirectToHome');
Route::get('/update/database', 'InstallerController@redirectToHome');

// PCB Bank Test Routes
Route::get('/pcb-test', function () {
    $service = new \App\Services\PcbBankService();

    $data = [
        'configured' => $service->isConfigured(),
        'merchant_id' => config('pcb_bank.merchant_id'),
        'api_url' => config('pcb_bank.api_url'),
        'cert_path' => config('pcb_bank.certificates.cert_path'),
        'key_path' => config('pcb_bank.certificates.key_path'),
        'ca_path' => config('pcb_bank.certificates.ca_path'),
        'cert_exists' => file_exists(config('pcb_bank.certificates.cert_path')),
        'key_exists' => file_exists(config('pcb_bank.certificates.key_path')),
        'ca_exists' => file_exists(config('pcb_bank.certificates.ca_path')),
    ];

    return response()->json($data, 200, [], JSON_PRETTY_PRINT);
})->name('pcb.test');

Route::get('/pcb-test-order', function () {
    $service = new \App\Services\PcbBankService();

    if (!$service->isConfigured()) {
        return response()->json(['error' => 'Certificates not configured'], 400);
    }

    $order = $service->createOrder(10.00, 'Test Order', url('/pcb-test-redirect'));

    return response()->json($order, 200, [], JSON_PRETTY_PRINT);
})->name('pcb.test.order');

Route::get('/pcb-test-redirect', function () {
    return '<h1>PCB Bank Test Redirect</h1><p>This is where users would be redirected after payment.</p>';
})->name('pcb.test.redirect');
