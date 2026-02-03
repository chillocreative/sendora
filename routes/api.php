<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WhatsappWebhookController;
use App\Http\Controllers\Api\ApiController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// WhatsApp Webhooks (no auth required - called by WhatsApp server)
Route::post('/whatsapp/qr-update', [WhatsappWebhookController::class, 'qrUpdate']);
Route::post('/whatsapp/status-update', [WhatsappWebhookController::class, 'statusUpdate']);
Route::post('/whatsapp/incoming-message', [WhatsappWebhookController::class, 'incomingMessage']);
Route::post('/whatsapp/message-receipt', [WhatsappWebhookController::class, 'messageReceipt']);

// Business Plan API Routes
Route::middleware(['auth:sanctum', 'api.access'])->prefix('v1')->group(function () {
    // Profile & Usage
    Route::get('/profile', [ApiController::class, 'profile']);
    Route::get('/usage', [ApiController::class, 'usage']);
    
    // Contacts
    Route::get('/contacts', [ApiController::class, 'contacts']);
    Route::post('/contacts', [ApiController::class, 'storeContact']);
    
    // Devices
    Route::get('/devices', [ApiController::class, 'devices']);
    
    // Messaging
    Route::post('/messages/send', [ApiController::class, 'sendMessage']);
    
    // Campaigns
    Route::get('/campaigns', [ApiController::class, 'campaigns']);
});
