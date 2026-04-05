<?php
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\MarketController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StatController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\VisitController;
use App\Http\Controllers\DetailsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

    


Route::post('/login', [AuthController::class, 'login'])->name('/login');;

// --- Protected Routes (Any Authenticated User) ---
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', fn(Request $request) => $request->user());

    // Markets
    Route::post('/markets', [MarketController::class, 'store']);
    Route::get('/markets/info', [UsersController::class, 'group_markets2']);
    Route::get('/markets/{id}', [MarketController::class, 'show']);
    Route::get('/allmarkets', [MarketController::class, 'all_markets']);
    Route::get('/markets/products/{id}', [MarketController::class, 'products']);
    
    // Groups & Users (General)
    Route::get('/groups', [GroupController::class, 'index']);
    Route::get('/users/markets', [UsersController::class, 'group_markets']);

    // Visits & Products
    Route::get('/visits-filters', [VisitController::class, 'getFilterData']);
    Route::get('/visits/edit/{id}', [VisitController::class, 'edit']);
    Route::get('/visits', [VisitController::class, 'index']);
    Route::put('/visits/update/{id}', [VisitController::class, 'update']);
    Route::post('/visits', [VisitController::class, 'store']);
    Route::get('/visits/{id}', [VisitController::class, 'show'])->where('id', '[0-9]+');;
    Route::delete('/visits/{id}', [VisitController::class, 'destroy'])->where('id', '[0-9]+');;
    


    Route::get('/products/missing/{marketId}', [ProductController::class, 'getMissingProducts']);
    Route::post('/product-stocks/initial', [ProductController::class, 'storeInitial']);

    // Route for geting and changing stocks
    Route::get('/stocks/{id}', [DetailsController::class, 'getStocks']);
    Route::post('/stocks/{id}', [DetailsController::class, 'updateStocks']);


    // --- Admin Only Routes ---
    Route::middleware('role:admin')->group(function () {
        // Analytics
        Route::get('/dashboard', [MarketController::class, 'dashboard']);
        Route::get('/statistics', [MarketController::class, 'statistics']);
        Route::get('/agentstats', [StatController::class, 'agentsStatistics']);
        Route::get('/marketstats', [StatController::class, 'marketsStatistics']);

        
        


        Route::get('/details/{id}', [DetailsController::class, 'details']);

        // Note: store/show are handled in the general group above
        // Route::apiResource('markets', MarketController::class)->except(['store', 'show', 'index']); 
        Route::apiResource('products', ProductController::class);
        Route::apiResource('users', UsersController::class);
        
        Route::put('/groups/update/{id}', [GroupController::class, 'updateGroup']);
        Route::get('/groups/markets', [GroupController::class, 'getGroups']);
        Route::post('/groups', [GroupController::class, 'store']);

        Route::get('/users/{id}/markets', [UsersController::class, 'markets']);
        Route::post('/users/{id}/sync-markets', [UsersController::class, 'syncMarkets']);
    });
});

// Commands for optimizing server after completing project:
// php artisan config:cache
// php artisan route:cache
// php artisan view:cache





