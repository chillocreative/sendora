<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappNumberController extends Controller
{
    private $waServerUrl;

    public function __construct()
    {
        $this->waServerUrl = \App\Models\Setting::where('key', 'wa_server_url')->value('value') 
                             ?? env('WA_SERVER_URL', 'http://localhost:3000');
        
        // Ensure no trailing slash
        $this->waServerUrl = rtrim($this->waServerUrl, '/');
    }

    public function index()
    {
        $user = auth()->user();
        $deviceLimit = $user->current_plan->limits['whatsapp_nos'] ?? 1;

        return \Inertia\Inertia::render('Whatsapp/Index', [
            'numbers' => $user->whatsappNumbers()->get(),
            'deviceLimit' => $deviceLimit,
        ]);
    }

    public function create()
    {
        $user = auth()->user();
        $limit = $user->current_plan->limits['whatsapp_nos'] ?? 1;

        if ($user->whatsappNumbers()->count() >= $limit) {
            return back()->with('error', 'You have reached your device limit.');
        }

        $number = $user->whatsappNumbers()->create([
            'status' => 'disconnected',
        ]);

        return redirect()->route('whatsapp.show', $number->id);
    }

    public function show($id)
    {
        $number = auth()->user()->whatsappNumbers()->findOrFail($id);
        
        // If disconnected, initiate connection
        if ($number->status === 'disconnected') {
            try {
                $url = "{$this->waServerUrl}/connect";
                Log::info("WhatsApp calling connect URL: $url");
                $response = Http::withoutVerifying()->timeout(15)->post($url, [
                    'user_id' => auth()->id(),
                    'phone_number' => $number->id,
                ]);
                
                if ($response->successful()) {
                    $data = $response->json();
                    $number->update([
                        'status' => $data['status'] ?? 'connecting',
                        'qr_code' => $data['qr'] ?? null,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('WhatsApp connection error: ' . $e->getMessage());
            }
        } else {
            // Fetch current status for this specific user's connection
            try {
                $response = Http::withoutVerifying()->timeout(10)->get("{$this->waServerUrl}/status/" . auth()->id() . "/{$number->id}");
                
                if ($response->successful()) {
                    $data = $response->json();
                    Log::info("WhatsApp status response for device {$number->id}: ", $data);
                    
                    $updateData = [
                        'status' => $data['status'] ?? 'disconnected',
                    ];
                    
                    if (!empty($data['qr'])) {
                        Log::info("QR Code received from Node server.");
                        $updateData['qr_code'] = $data['qr'];
                    }
                    
                    if ($data['connected'] && !empty($data['phone_info'])) {
                        $updateData['phone_info'] = $data['phone_info'];
                        $updateData['phone_number'] = $data['phone_info']['id'] ?? null;
                        $updateData['qr_code'] = null; // Clear QR when connected
                    }
                    
                    $number->update($updateData);
                }
            } catch (\Exception $e) {
                Log::error('WhatsApp status fetch error: ' . $e->getMessage());
            }
        }
        
        return \Inertia\Inertia::render('Whatsapp/Show', [
            'number' => $number->fresh(),
        ]);
    }

    public function refreshQr($id)
    {
        $number = auth()->user()->whatsappNumbers()->findOrFail($id);
        
        try {
            Log::info("Manual QR refresh requested for device {$number->id}");
            
            // First check status
            $response = Http::withoutVerifying()->timeout(5)->get("{$this->waServerUrl}/status/" . auth()->id() . "/{$number->id}");
            $data = $response->successful() ? $response->json() : ['status' => 'disconnected', 'connected' => false];
            
            // If disconnected, force a connect call
            if ($data['status'] === 'disconnected' || !$data['connected']) {
                Log::info("Node server reports disconnected. Triggering /connect...");
                $response = Http::withoutVerifying()->timeout(15)->post("{$this->waServerUrl}/connect", [
                    'user_id' => auth()->id(),
                    'phone_number' => $number->id,
                ]);
                if ($response->successful()) {
                    $data = $response->json();
                }
            }
            
            $updateData = [
                'status' => $data['status'] ?? 'disconnected',
            ];
            
            if (!empty($data['qr'])) {
                $updateData['qr_code'] = $data['qr'];
            }
            
            if ($data['connected'] && !empty($data['phone_info'])) {
                $updateData['phone_info'] = $data['phone_info'];
                $updateData['phone_number'] = $data['phone_info']['id'] ?? null;
                $updateData['qr_code'] = null;
            }
            
            $number->update($updateData);
        } catch (\Exception $e) {
            Log::error('QR refresh error: ' . $e->getMessage());
        }

        return response()->json($number->fresh());
    }

    public function destroy($id)
    {
        $number = auth()->user()->whatsappNumbers()->findOrFail($id);
        
        // Disconnect from Baileys server
        try {
            Http::post("{$this->waServerUrl}/disconnect", [
                'user_id' => auth()->id(),
                'phone_number' => $number->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Disconnect error: ' . $e->getMessage());
        }

        $number->delete();

        return back()->with('success', 'Device removed successfully.');
    }
}
