<?php

use Illuminate\Support\Facades\Route;

/**
 * Sections (list/create/edit)
 */
Route::get('/',               'OfferSectionController@index')->name('offers.admin.sections.index');
Route::get('/create',         'OfferSectionController@create')->name('offers.admin.sections.create');
Route::get('/edit/{id}',      'OfferSectionController@edit')->name('offers.admin.sections.edit');
Route::post('/store/{id?}',   'OfferSectionController@store')->name('offers.admin.sections.store');
Route::post('/bulkEdit',      'OfferSectionController@bulkEdit')->name('offers.admin.sections.bulkEdit');
Route::delete('/delete/{id}', 'OfferSectionController@destroy')->name('offers.admin.sections.destroy');

/**
 * Cards (query-param style)  -> /admin/module/offers/cards?section_id=1
 */
Route::prefix('cards')->group(function () {
    Route::get('/',                'OfferCardController@index')->name('offers.admin.cards.index');        // ?section_id=
    Route::get('/create',          'OfferCardController@create')->name('offers.admin.cards.create');      // ?section_id=
    Route::get('/edit/{card}',     'OfferCardController@edit')->name('offers.admin.cards.edit');
    Route::post('/store/{id?}',    'OfferCardController@store')->name('offers.admin.cards.store');
    Route::delete('/delete/{card}','OfferCardController@destroy')->name('offers.admin.cards.destroy');
});

/**
 * Cards (path-param style) -> /admin/module/offers/card/{section_id}/index
 */
Route::prefix('card/{section_id}')->group(function () {
    Route::get('/index',  'OfferCardController@index')->name('offers.admin.cards.index.by_section');
    Route::get('/create', 'OfferCardController@create')->name('offers.admin.cards.create.by_section');
});
