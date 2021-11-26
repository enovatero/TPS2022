<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\VoyagerOfferTypeController;
use App\Http\Controllers\VoyagerRulesPricesController;
use App\Http\Controllers\VoyagerProductsController;
use App\Http\Controllers\VoyagerOfferController;
use App\Http\Controllers\VoyagerController;
use App\Http\Controllers\FanCourierController;
use App\Http\Controllers\ColorsController;

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
//     Route::get('/', [VoyagerController::class,'index'])->middleware('admin.user');
    Route::post('orderOffer', [VoyagerOfferTypeController::class,'order_item'])->middleware('admin.user');
    Route::get('offers/relation', [VoyagerOfferTypeController::class,'relation'])->name('voyager.offers.relation');
    Route::post('/saveOfferTypeProducts', [VoyagerOfferTypeController::class, 'saveOfferTypeProducts'])->middleware('admin.user');
    Route::post('/saveRulePrice', [VoyagerRulesPricesController::class, 'saveRulePrice'])->middleware('admin.user');
    Route::post('/getAttributesByParent', [VoyagerProductsController::class, 'getAttributesByParent'])->middleware('admin.user');
    Route::post('/getPricesByProductAndCategory', [VoyagerOfferController::class, 'getPricesByProductAndCategory'])->middleware('admin.user');
    Route::put('/ajaxSaveUpdateOffer', [VoyagerOfferController::class, 'ajaxSaveUpdateOffer'])->middleware('admin.user');
    Route::get('/generatePDF/{offer_id}', [VoyagerOfferController::class, 'generatePDF'])->middleware('admin.user');
    Route::get('/generatePDFFisa/{offer_id}', [VoyagerOfferController::class, 'generatePDFFisa'])->middleware('admin.user');
    Route::post('/retrieveOffersPerYearMonth', [VoyagerOfferController::class, 'retrieveOffersPerYearMonth'])->middleware('admin.user');
    Route::get('/forceFetchProductsWinMentor', [VoyagerProductsController::class, 'forceFetchProductsWinMentor'])->middleware('admin.user');
    Route::get('/forceFetchProductsWinMentor', [VoyagerProductsController::class, 'forceFetchProductsWinMentor'])->middleware('admin.user');
    Route::get('/products-complete', [VoyagerProductsController::class, 'productsComplete'])->middleware('admin.user');
    Route::get('/products-incomplete', [VoyagerProductsController::class, 'productsIncomplete'])->middleware('admin.user');
    // Fancourier
    Route::post('generateAwb', [FanCourierController::class, 'generateAwb'])->middleware('admin.user');
    Route::get('printAwb/{awb}/{client_id}', [FanCourierController::class, 'printAwb'])->middleware('admin.user');
    Route::post('changeStatus', [VoyagerOfferController::class, 'changeStatus'])->middleware('admin.user');
    Route::post('launchOrder', [VoyagerOfferController::class, 'launchOrder'])->middleware('admin.user');
    Route::get('uploadColors', [ColorsController::class, 'uploadColors'])->middleware('admin.user');
});

Route::get('counties', [FanCourierController::class, 'getCounties']);
Route::post('cities', [FanCourierController::class, 'getCities']);
Route::post('citiesWithId', [FanCourierController::class, 'getCitiesWithId']);
Route::post('cityAgentie', [FanCourierController::class, 'getCitiesAgency']);
Route::get('km-exteriori/{id_localitate}', [FanCourierController::class, 'getKmExteriori']);
Route::get('getSedii/{localitate}', [FanCourierController::class, 'getSedii']);
