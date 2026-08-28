<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\DealerCalcController;
use App\Http\Controllers\Api\V1\FinanceController;
use App\Http\Controllers\Api\V1\IntegrationController;
use App\Http\Controllers\Api\V1\LogisticsController;
use App\Http\Controllers\Api\V1\LotController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\PrebidController;
use App\Http\Controllers\Api\V1\RateController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\WalletController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::post('auth/forgot', [AuthController::class, 'forgot'])->middleware('throttle:5,1');
    Route::get('public/invoice/{token}/file', [FinanceController::class, 'file']);
    Route::get('public/invoice/{token}', [FinanceController::class, 'preview']);
    Route::post('public/invoice/{token}', [FinanceController::class, 'decidePreview'])->middleware('throttle:30,1');
    Route::post('calculator/quote', [RateController::class, 'quote']);
    Route::get('countries', [CatalogController::class, 'countries']);
    Route::get('cities', [CatalogController::class, 'cities']);
    Route::get('ports', [CatalogController::class, 'ports']);
    Route::get('auctions', [CatalogController::class, 'auctions']);
    Route::get('brands', [CatalogController::class, 'brands']);
    Route::get('status-orders', [CatalogController::class, 'statuses']);
        Route::get('prebid/listings', [PrebidController::class, 'listings']);
        Route::get('prebid/listings/{listing}', [PrebidController::class, 'show']);
    Route::post('integrations/vin-check/callback', [IntegrationController::class, 'vinCallback']);
    Route::post('integrations/telegram/webhook', [IntegrationController::class, 'telegramWebhook']);

    Route::middleware(['auth:sanctum', 'active'])->group(function (): void {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/offer/accept', [AuthController::class, 'acceptOffer']);
        Route::post('auth/impersonate/{user}', [AuthController::class, 'impersonate']);
        Route::get('dealer-calc/sso', [DealerCalcController::class, 'sso']);

        Route::get('users', [UserController::class, 'index']);
        Route::post('users', [UserController::class, 'store']);
        Route::get('users/{user}', [UserController::class, 'show']);
        Route::patch('users/{user}', [UserController::class, 'update']);
        Route::get('roles', [RoleController::class, 'index']);
        Route::get('permissions', [RoleController::class, 'permissions']);
        Route::put('roles/{role}/permissions', [RoleController::class, 'syncPermissions']);

        Route::get('lots', [LotController::class, 'index']);
        Route::get('lots/dictionaries', [LotController::class, 'dictionaries']);
        Route::post('lots', [LotController::class, 'store']);
        Route::get('lots/{lot}', [LotController::class, 'show']);
        Route::patch('lots/{lot}', [LotController::class, 'update']);
        Route::post('lots/{lot}/images', [LotController::class, 'storeImage']);
        Route::get('lots/{lot}/images/{image}/file', [LotController::class, 'imageFile']);
        Route::post('lots/{lot}/messages', [LotController::class, 'storeMessage']);
        Route::get('lots/{lot}/messages', [LotController::class, 'messages']);
        Route::get('lots/{lot}/notes', [LotController::class, 'notes']);
        Route::post('lots/{lot}/notes', [LotController::class, 'storeNote']);
        Route::post('lots/{lot}/drops', [LotController::class, 'storeDrop']);
        Route::get('lots/{lot}/notifications', [LotController::class, 'notifications']);
        Route::post('lots/{lot}/notifications', [LotController::class, 'storeNotification']);
        Route::get('lots/{lot}/export', [LotController::class, 'export']);

        Route::get('shipping', [LogisticsController::class, 'shipping']);
        Route::patch('shipping/{shipping}', [LogisticsController::class, 'updateShipping']);
        Route::get('containers', [LogisticsController::class, 'containers']);
        Route::post('containers', [LogisticsController::class, 'storeContainer']);
        Route::get('containers/{container}', [LogisticsController::class, 'showContainer']);
        Route::patch('containers/{container}', [LogisticsController::class, 'updateContainer']);
        Route::get('local-hauls', [LogisticsController::class, 'hauls']);
        Route::post('local-hauls', [LogisticsController::class, 'storeHaul']);
        Route::patch('local-hauls/{haul}', [LogisticsController::class, 'updateHaul']);

        Route::get('lots/{lot}/finance-lines', [FinanceController::class, 'lines']);
        Route::post('lots/{lot}/finance-lines', [FinanceController::class, 'upsertLine']);
        Route::get('lots/{lot}/invoices', [FinanceController::class, 'invoices']);
        Route::post('lots/{lot}/invoices', [FinanceController::class, 'storeInvoice']);
        Route::post('lots/{lot}/invoices/{invoice}/preview', [FinanceController::class, 'issuePreview']);
        Route::post('lots/{lot}/invoices/{invoice}/document', [FinanceController::class, 'generateDocument']);
        Route::get('lots/{lot}/invoices/{invoice}/file', [FinanceController::class, 'download']);
        Route::post('lots/{lot}/payments', [FinanceController::class, 'storePayment']);

        Route::get('wallets', [WalletController::class, 'accounts']);
        Route::get('wallets/entries', [WalletController::class, 'entries']);
        Route::post('wallets/transfer', [WalletController::class, 'transfer']);
        Route::get('wallets/checksum', [WalletController::class, 'checksum']);
        Route::get('erip', [WalletController::class, 'erip']);
        Route::post('erip', [WalletController::class, 'storeErip']);
        Route::post('erip/{erip}/confirm', [WalletController::class, 'confirmErip']);

        Route::get('doc-fees', [CatalogController::class, 'docFees']);
        Route::get('delivery-types', [CatalogController::class, 'deliveryTypes']);
        Route::get('locations', [CatalogController::class, 'locations']);
        Route::get('transportation-agents', [CatalogController::class, 'agents']);
        Route::get('status-shippings', [CatalogController::class, 'statusShippings']);
        Route::get('rates', [RateController::class, 'index']);

        Route::get('counterparties', [CatalogController::class, 'counterparties']);
        Route::post('counterparties', [CatalogController::class, 'storeCounterparty']);
        Route::patch('counterparties/{counterparty}', [CatalogController::class, 'updateCounterparty']);
        Route::get('credentials', [CatalogController::class, 'credentials']);
        Route::post('credentials', [CatalogController::class, 'storeCredential']);
        Route::post('countries', [CatalogController::class, 'storeCountry']);
        Route::post('cities', [CatalogController::class, 'storeCity']);
        Route::post('auctions', [CatalogController::class, 'storeAuction']);
        Route::post('brands', [CatalogController::class, 'storeBrand']);
        Route::post('brands/{brand}/models', [CatalogController::class, 'storeModel']);

        Route::post('prebid/listings', [PrebidController::class, 'store']);
        Route::post('prebid/listings/{listing}/bid', [PrebidController::class, 'bid']);
        Route::post('prebid/listings/{listing}/moderate', [PrebidController::class, 'moderate']);

        Route::get('notifications', [NotificationController::class, 'index']);
        Route::patch('notifications/{notification}/read', [NotificationController::class, 'markRead']);
        Route::get('integrations/status', [IntegrationController::class, 'status']);
        Route::get('integrations/logs', [IntegrationController::class, 'logs']);
        Route::post('integrations/vin-check', [IntegrationController::class, 'vinCheck']);
        Route::get('integrations/vin-reports', [IntegrationController::class, 'vinReports']);
        Route::post('integrations/aec/lookup', [IntegrationController::class, 'aecLookup']);
        Route::post('integrations/copart/lookup', [IntegrationController::class, 'copartLookup']);
        Route::post('integrations/bitrix/lead', [IntegrationController::class, 'bitrixLead']);
        Route::post('integrations/telegram/send', [IntegrationController::class, 'telegramSend']);
        Route::post('integrations/auction/session', [IntegrationController::class, 'auctionSession']);
        Route::post('integrations/auction/bid', [IntegrationController::class, 'auctionBid']);
    });
});

Route::prefix('v2')->group(function (): void {
    Route::get('listings', [PrebidController::class, 'listings']);
    Route::middleware(['auth:sanctum', 'active'])->group(function (): void {
        Route::post('listings/{listing}/bid', [PrebidController::class, 'bid']);
        Route::post('listings/{listing}/moderate', [PrebidController::class, 'moderate']);
    });
});
