<?php
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\MarketController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StatController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\VisitController;
use App\Models\Market;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);
Route::middleware('auth:sanctum')->get('/me', function(Request $request){ return $request->user();});

Route::post('/markets', [MarketController::class, 'store'])->middleware('auth:sanctum');


Route::middleware('auth:sanctum')->group(function () {

    Route::post('/markets', [MarketController::class, 'store']);
    Route::get('/markets/{id}', [MarketController::class, 'show']);
    Route::get('/allmarkets', [MarketController::class, 'all_markets']);
    Route::get('/markets/products/{id}', [MarketController::class, 'products']);
    Route::get('/groups' , [GroupController::class, 'index']);

    Route::get('/users/markets', [UsersController::class, 'group_markets']);

    // Route::post('/visits', [VisitController::class, 'store']);
    Route::get('/visits', [VisitController::class, 'index']);
    Route::get('/products/missing/{marketId}', [ProductController::class, 'getMissingProducts']);
    Route::post('/product-stocks/initial', [ProductController::class, 'storeInitial']);
    Route::post('/visits', [VisitController::class, 'store']);
    Route::get('/visits/{id}', [VisitController::class, 'show']);
});

Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    
    
    Route::get('/dashboard', [MarketController::class, 'dashboard']);
    Route::get('/statistics', [MarketController::class, 'statistics']);
    Route::get('/agentstats', [StatController::class, 'agentsStatistics']);
    Route::get('/marketstats', [StatController::class, 'marketsStatistics']);

    Route::apiResource('markets', MarketController::class)->except(['store', 'show','all_markets']);
    Route::apiResource('products', ProductController::class);
    // Route::get('/groups' , [GroupController::class, 'index']);
    Route::post('/groups' , [GroupController::class, 'store']);

    Route::post('/users/{id}/sync-markets', [UsersController::class, 'syncMarkets']);
    // Route::get('/users/markets', [UsersController::class, 'group_markets']);
    Route::get('/users/{id}/markets', [UsersController::class, 'markets']);
    Route::apiResource('users', UsersController::class);
    
    Route::get('/visits/filters', [VisitController::class, 'getFilterData']);
    // Route::get('/visits', [VisitController::class, 'index']);
    
    

    
    


    // Route::get('/users', function (Request $request) {
    //     return response()->json(User::all());
    // });

});







