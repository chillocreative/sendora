<?php

use Illuminate\Support\Facades\Route;
use App\Models\WhatsappNumber;

Route::get('/debug/whatsapp', function() {
    $number = WhatsappNumber::first();
    
    if (!$number) {
        return response()->json(['error' => 'No WhatsApp number found']);
    }
    
    return response()->json([
        'id' => $number->id,
        'user_id' => $number->user_id,
        'status' => $number->status,
        'has_qr_code' => !empty($number->qr_code),
        'qr_code_length' => $number->qr_code ? strlen($number->qr_code) : 0,
        'qr_code_preview' => $number->qr_code ? substr($number->qr_code, 0, 100) . '...' : null,
    ]);
});
