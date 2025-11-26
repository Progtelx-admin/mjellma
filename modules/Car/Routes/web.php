<?php
use \Illuminate\Support\Facades\Route;

Route::group(['prefix' => config('car.car_route_prefix')], function () {
    Route::get('/', 'CarController@index')->name('car.search'); // Search page
    Route::get('/search', 'CarController@search')->name('car.do_search'); // Perform search
    Route::post('/checkout', 'CarController@checkout')->name('car.checkout'); // Checkout Step 1 - Get reservation details
    Route::get('/checkout/form', 'CarController@checkoutForm')->name('car.checkout.form'); // Checkout Step 2 - Customer form
    Route::post('/checkout/store', 'CarController@storeCheckout')->name('car.checkout.store'); // Checkout Step 3 - Submit booking
    Route::get('/checkout/confirm', 'CarController@checkoutConfirm')->name('car.checkout.confirm'); // Checkout confirmation
    Route::get('/pcb/return', 'CarController@handlePcbReturn')->name('car.pcb.return'); // PCB Bank return URL
    Route::get('/{slug}', 'CarController@detail')->name('car.detail');// Detail
    Route::get('/api/locations', 'CarController@apiGetLocations')->name('car.api.locations'); // Get locations API
    Route::get('/api/reservations', 'CarController@apiGetReservations')->name('car.api.reservations'); // Get reservations API
    Route::get('/api/refresh-locations', 'CarController@refreshLocations')->name('car.api.refresh_locations'); // Refresh locations cache
    Route::get('/api/debug', 'CarController@debugApi')->name('car.api.debug'); // Debug API connection
    Route::get('/api/manufacturers', 'CarController@apiGetManufacturers')->name('car.api.manufacturers'); // Get manufacturers API
    Route::get('/api/categories', 'CarController@apiGetCategories')->name('car.api.categories'); // Get categories API
    Route::get('/api/transmissions', 'CarController@apiGetTransmissions')->name('car.api.transmissions'); // Get transmissions API
    Route::get('/api/load-more', 'CarController@loadMore')->name('car.api.load_more'); // Load more cars API
});

Route::group(['prefix' => 'user/' . config('car.car_route_prefix'), 'middleware' => ['auth', 'verified']], function () {
    Route::get('/', 'ManageCarController@manageCar')->name('car.vendor.index');
    Route::get('/create', 'ManageCarController@createCar')->name('car.vendor.create');
    Route::get('/edit/{id}', 'ManageCarController@editCar')->name('car.vendor.edit');
    Route::get('/del/{id}', 'ManageCarController@deleteCar')->name('car.vendor.delete');
    Route::post('/store/{id}', 'ManageCarController@store')->name('car.vendor.store');
    Route::get('bulkEdit/{id}', 'ManageCarController@bulkEditCar')->name("car.vendor.bulk_edit");
    Route::get('/booking-report/bulkEdit/{id}', 'ManageCarController@bookingReportBulkEdit')->name("car.vendor.booking_report.bulk_edit");
    Route::get('/recovery', 'ManageCarController@recovery')->name('car.vendor.recovery');
    Route::get('/restore/{id}', 'ManageCarController@restore')->name('car.vendor.restore');
});

Route::group(['prefix' => 'user/' . config('car.car_route_prefix')], function () {
    Route::group(['prefix' => 'availability'], function () {
        Route::get('/', 'AvailabilityController@index')->name('car.vendor.availability.index');
        Route::get('/loadDates', 'AvailabilityController@loadDates')->name('car.vendor.availability.loadDates');
        Route::post('/store', 'AvailabilityController@store')->name('car.vendor.availability.store');
    });
});
