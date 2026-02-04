<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TestMessageController extends Controller
{
    private $waServerUrl;

    public function __construct()
    {
        $this->waServerUrl = \App\Models\Setting::where('key', 'wa_server_url')->value('value') 
                             ?? env('WA_SERVER_URL', 'http://localhost:3000');
        $this->waServerUrl = rtrim($this->waServerUrl, '/');
        
        // Ensure asset() uses the correct production URL
        $appUrl = \App\Models\Setting::where('key', 'app_url')->value('value') ?? env('APP_URL');
        config(['app.url' => $appUrl]);
    }

    public function index()
    {
        $user = auth()->user();
        $whatsappNumbers = $user->whatsappNumbers()
            ->where('status', 'connected')
            ->get();

        return \Inertia\Inertia::render('TestMessage', [
            'whatsappNumbers' => $whatsappNumbers,
        ]);
    }

    public function send(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'whatsapp_number_id' => 'required|exists:whatsapp_numbers,id',
            'message' => 'nullable|string|max:4096',
            'media' => 'nullable|file|max:20480', // 20MB limit
        ]);

        // Get user's selected WhatsApp number
        $whatsappNumber = auth()->user()->whatsappNumbers()
            ->where('id', $request->whatsapp_number_id)
            ->where('status', 'connected')
            ->first();

        if (!$whatsappNumber) {
            return back()->withErrors(['message' => 'No WhatsApp account connected. Please connect your WhatsApp first.']);
        }

        // Create clean phone number for WhatsApp
        $phone = preg_replace('/[^0-9]/', '', $request->phone);
        if (str_starts_with($phone, '0')) {
            $phone = '6' . $phone; // Default to Malaysia country code if starting with 0
        }

        try {
            $payload = [
                'user_id' => auth()->id(),
                'phone_number' => $whatsappNumber->id,
                'to' => $phone,
                'message' => $request->message ?? '',
            ];

            if ($request->hasFile('media')) {
                $file = $request->file('media');
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('temp', $filename, 'public');
                $url = asset('storage/' . $path);
                
                $type = 'document';
                $mime = $file->getMimeType();
                if (str_starts_with($mime, 'image/')) $type = 'image';
                elseif (str_starts_with($mime, 'video/')) $type = 'video';
                elseif (str_starts_with($mime, 'audio/')) $type = 'audio';

                $payload['media_url'] = $url;
                $payload['media_type'] = $type === 'document' ? $mime : $type;
                $payload['filename'] = $file->getClientOriginalName();
            }

            // Send message via WhatsApp server
            Log::info("Attempting to send test message", ['url' => "{$this->waServerUrl}/send-message", 'payload' => $payload]);
            $response = Http::timeout(30)->post("{$this->waServerUrl}/send-message", $payload);

            if ($response->successful()) {
                return back()->with('success', 'Message sent successfully!');
            } else {
                Log::error("Test message server error", [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                $errorData = $response->json();
                $errorMessage = $errorData['error'] ?? 'Failed to send message: ' . $response->status();
                return back()->withErrors(['message' => $errorMessage]);
            }
        } catch (\Exception $e) {
            Log::error('Test message exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withErrors(['message' => 'Failed to send message: ' . $e->getMessage()]);
        }
    }
}
