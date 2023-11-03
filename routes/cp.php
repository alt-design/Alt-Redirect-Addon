<?php
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['statamic.cp.authenticated'], 'namespace' => 'AltDesign\AltRedirect\Http\Controllers'], function() {
    // Settings
    Route::get('/alt-design/alt-redirect/', 'AltRedirectController@index')->name('alt-redirect.index');
    Route::post('/alt-design/alt-redirect/', 'AltRedirectController@create')->name('alt-redirect.create');

    Route::post('/alt-design/alt-redirect/delete', 'AltRedirectController@delete')->name('alt-redirect.delete');

});
