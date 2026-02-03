<?php

namespace App\Http\Controllers;

use App\Models\CampaignMessage;
use Illuminate\Http\Request;

class LinkController extends Controller
{
    public function track($messageId, Request $request)
    {
        $url = base64_decode($request->query('u'));
        
        if (!$url) {
            return redirect('/');
        }

        // Track click
        $message = CampaignMessage::find($messageId);
        if ($message && !$message->clicked_at) {
            $message->update([
                'status' => 'clicked',
                'clicked_at' => now(),
            ]);
        }

        return redirect($url);
    }
}
