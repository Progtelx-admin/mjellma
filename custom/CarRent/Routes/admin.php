<?php

use \Illuminate\Support\Facades\Route;

Route::get('/reservations', 'CarRentReservationController@index')->name('carrent.admin.reservations.index');
Route::get('/reservations/{id}', 'CarRentReservationController@show')->name('carrent.admin.reservations.show');
Route::get('/reservations/{id}/invoice/download', 'CarRentReservationController@downloadInvoicePdf')
    ->name('carrent.admin.reservations.invoice.download');

