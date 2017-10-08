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

Route::get('/', function () {
    return view('welcome');
});

Route::get('word/{word}','Word@get');
Route::get('word/{word}/childs','Word@getChilds');
Route::get('word/{word}/parents','Word@getParents');
Route::get('word/{word}/autocomplete','Word@autocomplete');