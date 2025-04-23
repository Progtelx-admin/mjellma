<?php
use \Illuminate\Support\Facades\Route;

// Route::group(['prefix'=>config('hotel.hotel_route_prefix')],function(){
//     Route::get('/','HotelController@index')->name('hotel.search'); // Search
//     Route::get('/{slug}','HotelController@detail')->name('hotel.detail');// Detail
// });
Route::post('/hotel/booking/handle', 'HotelHController@handleBookingSubmission')->name('hotel.booking.handle');
Route::get('/pcb-return', 'HotelHController@handlePcbReturn')->name('pcb.booking.return');
Route::get('/hotel/payment/confirm', 'HotelHController@confirmAfterPcb')->name('hotel.payment.confirm');

Route::get('/hotels', 'HotelHController@showHotels')->name('hotel.show');
Route::get('/hotels/search', 'HotelHController@searchHotels')->name('hotel.search');
Route::get('/hotel/{id}', 'HotelHController@hotelInfo')->name('hotel.info');
Route::get('/hotel-suggestions', 'HotelHController@getHotelSuggestions')->name('hotel.suggestions');
Route::post('/hotel/prebook', 'HotelHController@prebookRoom')->name('hotel.prebook');
Route::get('/hotel/prebook/result', 'HotelHController@prebookResult')->name('hotel.prebook.result');
Route::post('/hotel/book', 'HotelHController@bookRoom')->name('hotel.book');
Route::get('/hotel/booking/confirmation/{book_hash}', 'HotelHController@bookingConfirmation')->name('hotel.booking.confirmation');
Route::post('/hotel/payment', 'HotelHController@processPayment')->name('hotel.payment');
Route::post('/hotel/booking/finish', 'HotelHController@finishBooking')->name('hotel.booking.finish');
Route::get('/hotel/payment/success', function () {
    return view('Hotel::frontend.payment-success');
})->name('hotel.payment.success');

Route::get('/booking', 'HotelHController@index')->name('hotel.admin.booking.index');

Route::get('/bookings/{orderId}/details', 'HotelHController@showBookingDetails')->name('booking.admin.details');
Route::post('/bookings/{partnerOrderId}/cancel', 'HotelHController@cancelBooking')->name('booking.admin.cancel');


Route::group(['prefix' => 'user/' . config('hotel.hotel_route_prefix'), 'middleware' => ['auth', 'verified']], function () {
    Route::get('/', 'VendorController@index')->name('hotel.vendor.index');
    Route::get('/create', 'VendorController@create')->name('hotel.vendor.create');
    Route::get('/recovery', 'VendorController@recovery')->name('hotel.vendor.recovery');
    Route::get('/restore/{id}', 'VendorController@restore')->name('hotel.vendor.restore')->middleware(['signed']);
    Route::get('/edit/{id}', 'VendorController@edit')->name('hotel.vendor.edit');
    Route::get('/del/{id}', 'VendorController@delete')->name('hotel.vendor.delete')->middleware(['signed']);
    Route::post('/store/{id}', 'VendorController@store')->name('hotel.vendor.store');
    Route::get('bulkEdit/{id}', 'VendorController@bulkEdithotel')->name("hotel.vendor.bulk_edit")->middleware(['signed']);
    Route::get('/booking-report/bulkEdit/{id}', 'VendorController@bookingReportBulkEdit')->name("hotel.vendor.booking_report.bulk_edit");
    Route::group(['prefix' => 'availability'], function () {
        Route::get('/', 'AvailabilityController@index')->name('hotel.vendor.availability.index');
        Route::get('/loadDates', 'AvailabilityController@loadDates')->name('hotel.vendor.availability.loadDates');
        Route::post('/store', 'AvailabilityController@store')->name('hotel.vendor.availability.store');
    });
    Route::group(['prefix' => 'room'], function () {
        Route::get('{hotel_id}/index', 'VendorRoomController@index')->name('hotel.vendor.room.index');
        Route::get('{hotel_id}/create', 'VendorRoomController@create')->name('hotel.vendor.room.create');
        Route::get('{hotel_id}/edit/{id}', 'VendorRoomController@edit')->name('hotel.vendor.room.edit');
        Route::post('{hotel_id}/store/{id}', 'VendorRoomController@store')->name('hotel.vendor.room.store');
        Route::get('{hotel_id}/del/{id}', 'VendorRoomController@delete')->name('hotel.vendor.room.delete');
        Route::get('{hotel_id}/bulkEdit/{id}', 'VendorRoomController@bulkEdit')->name('hotel.vendor.room.bulk_edit');
    });
});

Route::group(['prefix' => 'user/' . config('hotel.hotel_route_prefix')], function () {
    Route::group(['prefix' => '{hotel_id}/availability'], function () {
        Route::get('/', 'AvailabilityController@index')->name('hotel.vendor.room.availability.index');
        Route::get('/loadDates', 'AvailabilityController@loadDates')->name('hotel.vendor.room.availability.loadDates');
        Route::post('/store', 'AvailabilityController@store')->name('hotel.vendor.room.availability.store');
    });
});

Route::post(config('hotel.hotel_route_prefix') . '/checkAvailability', 'HotelController@checkAvailability')->name('hotel.checkAvailability');
