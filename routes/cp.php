<?php
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['statamic.cp.authenticated'], 'namespace' => 'AltDesign\AltRedirect\Http\Controllers'], function() {
    // Settings
    Route::get('/alt-design/alt-redirect/', 'AltRedirectController@index')->name('alt-redirect.index');
    Route::post('/alt-design/alt-redirect/', 'AltRedirectController@create')->name('alt-redirect.create');
    Route::post('/alt-design/alt-redirect/delete', 'AltRedirectController@delete')->name('alt-redirect.delete');

    // Settings CSV Import / Export
    Route::get('/alt-design/alt-redirect/export', 'AltRedirectController@export')->name('alt-redirect.export');
    Route::post('/alt-design/alt-redirect/import', 'AltRedirectController@import')->name('alt-redirect.import');

    // Query Strings
    Route::get('/alt-design/alt-redirect/query-strings/', 'AltRedirectController@index')->name('alt-redirect.query-strings.index');
    Route::post('/alt-design/alt-redirect/query-strings/', 'AltRedirectController@create')->name('alt-redirect.query-strings.create');
    Route::post('/alt-design/alt-redirect/query-strings/delete', 'AltRedirectController@delete')->name('alt-redirect.query-strings.delete');
    Route::post('/alt-design/alt-redirect/query-strings/toggle', 'AltRedirectController@toggle')->name('alt-redirect.query-strings.toggle');
});
