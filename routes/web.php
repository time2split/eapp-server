<?php

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

Route::get( '@word/{word}', 'Word@get' );
Route::get( '@word/{word}/childs', 'Word@getChilds' );
Route::get( '@word/{word}/parents', 'Word@getParents' );
Route::get( '@word/{word}/autocomplete', 'Word@autocomplete' );
Route::get( '@word/{relation}/rel_autocomplete', 'Word@rel_autocomplete' );

Route::get( '@get/relationTypes', 'Word@relationTypes' );
Route::get( '@get/words', 'Word@getWords' );


Route::get( '@jdmpattern/{worda}/{relation}/{wordb}', 'JDMPatternEngine' );

Route::get( '/@app:{direction}/{service}/{args?}', 'Service@app' )->where('args','.+');
//Route::get( '/{word?}/{relation?}', 'Word@app' );