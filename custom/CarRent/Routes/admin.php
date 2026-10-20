<?php

use \Illuminate\Support\Facades\Route;

Route::get('/reservations', 'CarRentReservationController@index')->name('carrent.admin.reservations.index');
Route::get('/local-orders/{id}', 'CarRentReservationController@showLocal')->name('carrent.admin.orders.show');
Route::post('/local-orders/{id}/comments', 'CarRentReservationController@commentLocal')->name('carrent.admin.orders.comments');
Route::get('/reservations/{id}', 'CarRentReservationController@show')->name('carrent.admin.reservations.show');
Route::get('/reservations/{id}/invoice/download', 'CarRentReservationController@downloadInvoicePdf')
    ->name('carrent.admin.reservations.invoice.download');

