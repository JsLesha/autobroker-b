<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\FinanceController;
use App\Http\Controllers\Api\V1\IntegrationController;
use App\Http\Controllers\Api\V1\LogisticsController;
use App\Http\Controllers\Api\V1\LotController;
use App\Http\Controllers\Api\V1\PrebidController;
use App\Http\Controllers\Api\V1\RateController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\WalletController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::post('auth/forgot', [AuthController::class, 'forgot'])->middleware('throttle:5,1');
    Route::get('public/invoice/{token}', [FinanceController::class, 'preview']);
    Route::post('public/invoice/{token}', [FinanceController::class, 'decidePreview'])->middleware('throttle:30,1');
    Route::post('calculator/quote', [RateController::class, 'quote']);
    Route::get('countries', [CatalogController::class, 'countries']);
    Route::get('cities', [CatalogController::class, 'cities']);
    Route::get('ports', [CatalogController::class, 'ports']);
    Route::get('auctions', [CatalogController::class, 'auctions']);
    Route::get('brands', [CatalogController::class, 'brands']);
    Route::get('prebid/listings', [PrebidController::class, 'listings']);
    Route::post('integrations/vin-check/callback', [IntegrationController::class, 'vinCallback']);
    Route::post('integrations/telegram/webhook', [IntegrationController::class, 'telegramWebhook']);

    Route::middleware(['auth:sanctum', 'active'])->group(function (): void {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/offer/accept', [AuthController::class, 'acceptOffer']);
        Route::post('auth/impersonate/{user}', [AuthController::class, 'impersonate']);

        Route::get('users', [UserController::class, 'index']);
        Route::get('users/{user}', [UserController::class, 'show']);

        Route::get('lots', [LotController::class, 'index']);
        Route::post('lots', [LotController::class, 'store']);
        Route::get('lots/{lot}', [LotController::class, 'show']);
        Route::patch('lots/{lot}', [LotController::class, 'update']);
        Route::post('lots/{lot}/images', [LotController::class, 'storeImage']);
        Route::post('lots/{lot}/messages', [LotController::class, 'storeMessage']);

        Route::get('shipping', [LogisticsController::class, 'shipping']);
        Route::patch('shipping/{shipping}', [LogisticsController::class, 'updateShipping']);
        Route::get('containers', [LogisticsController::class, 'containers']);
        Route::post('containers', [LogisticsController::class, 'storeContainer']);
        Route::get('local-hauls', [LogisticsController::class, 'hauls']);

        Route::get('lots/{lot}/finance-lines', [FinanceController::class, 'lines']);
        Route::post('lots/{lot}/finance-lines', [FinanceController::class, 'upsertLine']);
        Route::get('lots/{lot}/invoices', [FinanceController::class, 'invoices']);
        Route::post('lots/{lot}/invoices', [FinanceController::class, 'storeInvoice']);
        Route::post('lots/{lot}/payments', [FinanceController::class, 'storePayment']);

        Route::get('wallets', [WalletController::class, 'accounts']);
        Route::post('wallets/transfer', [WalletController::class, 'transfer']);
        Route::get('wallets/checksum', [WalletController::class, 'checksum']);
        Route::get('erip', [WalletController::class, 'erip']);
        Route::post('erip/{erip}/confirm', [WalletController::class, 'confirmErip']);

        Route::get('rates', [RateController::class, 'index']);

        Route::get('counterparties', [CatalogController::class, 'counterparties']);
        Route::post('counterparties', [CatalogController::class, 'storeCounterparty']);
        Route::get('credentials', [CatalogController::class, 'credentials']);
        Route::post('credentials', [CatalogController::class, 'storeCredential']);

        Route::post('prebid/listings/{listing}/bid', [PrebidController::class, 'bid']);
        Route::post('prebid/listings/{listing}/moderate', [PrebidController::class, 'moderate']);

        Route::get('integrations/status', [IntegrationController::class, 'status']);
    });
});
