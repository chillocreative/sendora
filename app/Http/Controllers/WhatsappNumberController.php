<?php

namespace App\Http\Controllers;

use App\Services\WhatsappCloudService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappNumberController extends Controller
{
    private ?string $waServerUrl = null;

    // Resolved lazily so Cloud-only requests never touch the settings table.
    private function waServerUrl(): string
    {
        if ($this->waServerUrl === null) {
            $url = \App\Models\Setting::where('key', 'wa_server_url')->value('value')
                   ?? env('WA_SERVER_URL', 'http://127.0.0.1:3005');
            $this->waServerUrl = rtrim($url, '/');
        }

        return $this->waServerUrl;
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

    public function storeCloud(Request $request, WhatsappCloudService $cloud)
    {
        $user = auth()->user();
        $limit = $user->current_plan->limits['whatsapp_nos'] ?? 1;

        if ($user->whatsappNumbers()->count() >= $limit) {
            return back()->with('error', 'You have reached your device limit.');
        }

        $data = $request->validate([
            'waba_id' => ['required', 'string', 'regex:/^\d+$/'],
            'phone_number_id' => ['required', 'string', 'regex:/^\d+$/', 'unique:whatsapp_numbers,phone_number_id'],
            'access_token' => ['required', 'string', 'min:20'],
            'token_expires_at' => ['nullable', 'date'],
        ]);

        $info = $cloud->fetchPhoneNumber($data['phone_number_id'], $data['access_token']);

        if ($info === null) {
            return back()->withErrors([
                'access_token' => 'Meta rejected these credentials. Check the Phone Number ID and access token, and that the token has whatsapp_business_management and whatsapp_business_messaging permissions.',
            ]);
        }

        $display = $info['display_phone_number'] ?? null;

        $number = $user->whatsappNumbers()->create([
            'provider' => 'cloud',
            'status' => 'connected',
            'waba_id' => $data['waba_id'],
            'phone_number_id' => $data['phone_number_id'],
            'access_token' => $data['access_token'],
            'token_expires_at' => $data['token_expires_at'] ?? null,
            'display_phone_number' => $display,
            'phone_number' => $display ? preg_replace('/\D/', '', $display) : null,
            'quality_rating' => $info['quality_rating'] ?? null,
            'messaging_limit' => $info['messaging_limit_tier'] ?? null,
            'qr_code' => null,
        ]);

        return redirect()->route('whatsapp.show', $number->id);
    }

    public function show($id)
    {
        $number = auth()->user()->whatsappNumbers()->findOrFail($id);

        // Cloud numbers have no Baileys session: render details only, no Node calls.
        if ($number->isCloud()) {
            return \Inertia\Inertia::render('Whatsapp/Show', ['number' => $number]);
        }

        // If disconnected, initiate connection
        if ($number->status === 'disconnected') {
            // LOCK: Set status to connecting immediately to prevent refresh-spamming the Node server
            $number->update(['status' => 'connecting']);
            
            try {
                $url = "{$this->waServerUrl()}/connect";
                Log::info("WhatsApp calling connect URL: $url for device {$number->id}");
                
                $response = Http::withoutVerifying()->timeout(15)->post($url, [
                    'user_id' => auth()->id(),
                    'phone_number' => $number->id,
                ]);
                
                if (!$response->successful()) {
                    Log::error("WhatsApp Node server returned error: " . $response->body());
                    $number->update(['status' => 'disconnected']); // Revert lock on failure
                }
            } catch (\Exception $e) {
                Log::error('WhatsApp connection error: ' . $e->getMessage());
                $number->update(['status' => 'disconnected']); // Revert lock on error
            }
        } else {
            // Fetch current status for this specific user's connection
            try {
                $response = Http::withoutVerifying()->timeout(10)->get("{$this->waServerUrl()}/status/" . auth()->id() . "/{$number->id}");
                
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

        if ($number->isCloud()) {
            return response()->json($number);
        }

        try {
            Log::info("Manual QR refresh requested for device {$number->id}");
            
            // First check status
            $response = Http::withoutVerifying()->timeout(5)->get("{$this->waServerUrl()}/status/" . auth()->id() . "/{$number->id}");
            $data = $response->successful() ? $response->json() : ['status' => 'disconnected', 'connected' => false];
            
            // If disconnected, force a connect call
            if ($data['status'] === 'disconnected' || !$data['connected']) {
                Log::info("Node server reports disconnected. Triggering /connect...");
                $response = Http::withoutVerifying()->timeout(15)->post("{$this->waServerUrl()}/connect", [
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

        // Disconnect from Baileys server (Cloud numbers have no session)
        if (! $number->isCloud()) {
            try {
                Http::post("{$this->waServerUrl()}/disconnect", [
                    'user_id' => auth()->id(),
                    'phone_number' => $number->id,
                ]);
            } catch (\Exception $e) {
                Log::error('Disconnect error: ' . $e->getMessage());
            }
        }

        $number->delete();

        return back()->with('success', 'Device removed successfully.');
    }
}
