<?php

use Illuminate\Support\Facades\Route;
use Modules\Offers\Controllers\OfferPublicController;

Route::get('/offers/ping', fn() => 'offers-web-ok');
Route::get('/offers', [OfferPublicController::class, 'index'])->name('offers.public.index');
Route::get('/offers/section/{slug}', [OfferPublicController::class, 'section'])->name('offers.public.section');
