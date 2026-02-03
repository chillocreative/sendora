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
        $this->waServerUrl = env('WA_SERVER_URL', 'http://localhost:3000');
    }

    public function index()
    {
        $whatsappNumber = auth()->user()->whatsappNumbers()
            ->where('status', 'connected')
            ->first();

        if (!$whatsappNumber) {
            $whatsappNumber = auth()->user()->whatsappNumbers()->first();
        }

        return \Inertia\Inertia::render('TestMessage', [
            'whatsappNumber' => $whatsappNumber,
        ]);
    }

    public function send(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'message' => 'nullable|string|max:4096',
            'media' => 'nullable|file|max:20480', // 20MB limit
        ]);

        // Get user's connected WhatsApp number
        $whatsappNumber = auth()->user()->whatsappNumbers()
            ->where('status', 'connected')
            ->first();

        if (!$whatsappNumber) {
            return back()->withErrors(['message' => 'No WhatsApp account connected. Please connect your WhatsApp first.']);
        }

        try {
            $payload = [
                'user_id' => auth()->id(),
                'phone_number' => $whatsappNumber->id,
                'to' => $request->phone,
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
            $response = Http::timeout(20)->post("{$this->waServerUrl}/send-message", $payload);

            if ($response->successful()) {
                return back()->with('success', 'Message sent successfully!');
            } else {
                $errorData = $response->json();
                $errorMessage = $errorData['error'] ?? 'Failed to send message';
                return back()->withErrors(['message' => $errorMessage]);
            }
        } catch (\Exception $e) {
            Log::error('Test message send error: ' . $e->getMessage());
            return back()->withErrors(['message' => 'Failed to send message. Please check your WhatsApp connection.']);
        }
    }
}
