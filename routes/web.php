<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\VoyagerOfferTypeController;
use App\Http\Controllers\VoyagerRulesPricesController;
use App\Http\Controllers\VoyagerProductsController;

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
    return redirect('/admin');
});
Route::post('/getCounties', [AddressController::class, 'getCountiesByCountry']);
Route::post('/getCities', [AddressController::class, 'getCitiesByState']);
Route::post('/removeAddress', [AddressController::class, 'removeAddress']);
Route::post('/getSubtypes', [VoyagerOfferTypeController::class, 'getSubtypes']);
Route::post('/getUserAddresses', [AddressController::class, 'getUserAddresses']);
Route::post('/saveNewAddress', [AddressController::class, 'saveNewAddress']);

Route::group(['prefix' => 'admin'], function () {
    Voyager::routes();
    Route::post('orderOffer', [VoyagerOfferTypeController::class,'order_item'])->middleware('admin.user');
    Route::get('offers/relation', [VoyagerOfferTypeController::class,'relation'])->name('voyager.offers.relation');
    Route::post('/saveOfferTypeProducts', [VoyagerOfferTypeController::class, 'saveOfferTypeProducts'])->middleware('admin.user');
    Route::post('/saveRulePrice', [VoyagerRulesPricesController::class, 'saveRulePrice'])->middleware('admin.user');
    Route::post('/getAttributesByCategory', [VoyagerProductsController::class, 'getAttributesByCategory'])->middleware('admin.user');
});
