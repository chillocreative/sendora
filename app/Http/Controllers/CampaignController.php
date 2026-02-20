<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Campaign;
use App\Models\CampaignMessage;
use App\Models\Contact;
use App\Models\ContactBook;
use Illuminate\Support\Facades\DB;
use App\Jobs\ProcessCampaignJob;

class CampaignController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $planName = $user->current_plan?->name;
        // All plans including Starter can create campaigns to test the system.
        $canCreate = in_array($planName, ['Starter', 'Basic', 'Pro', 'Business']);

        $campaigns = Campaign::where('user_id', $user->id)
            ->withCount([
                'messages as total_count',
                'messages as success_count' => function ($query) {
                    $query->whereIn('status', ['sent', 'delivered', 'read']);
                },
                'messages as failure_count' => function ($query) {
                    $query->where('status', 'failed');
                }
            ])
            ->latest()
            ->paginate(10);

        return Inertia::render('Campaigns/Index', [
            'campaigns' => $campaigns,
            'canCreate' => $canCreate,
        ]);
    }

    public function create()
    {
        $user = auth()->user();
        $contacts = Contact::where('user_id', $user->id)->get();
        $whatsappNumbers = $user->whatsappNumbers()->where('status', 'connected')->get();
        $contactBooks = ContactBook::where('user_id', $user->id)->withCount('contacts')->get();

        return Inertia::render('Campaigns/Create', [
            'contacts' => $contacts,
            'whatsappNumbers' => $whatsappNumbers,
            'contactBooks' => $contactBooks,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'whatsapp_number_id' => 'required|exists:whatsapp_numbers,id',
            'body' => 'nullable|string',
            'media' => 'nullable|file|max:20480', // 20MB
            'scheduled_at' => 'nullable|date|after:now',
            'contact_ids' => 'nullable|array',
            'contact_ids.*' => 'exists:contacts,id',
            'contact_book_ids' => 'nullable|array',
            'contact_book_ids.*' => 'exists:contact_books,id',
            'is_drip' => 'boolean',
            'drip_delay_minutes' => 'nullable|integer|min:1|max:1440',
        ]);

        $user = auth()->user();

        // Require at least one of contact_ids or contact_book_ids
        $contactIds = collect($request->contact_ids ?? []);
        $bookIds = collect($request->contact_book_ids ?? []);

        if ($contactIds->isEmpty() && $bookIds->isEmpty()) {
            return back()->withErrors(['contact_ids' => 'Please select at least one contact or contact book.']);
        }

        // Transaction to ensure campaign + messages created atomically
        $campaign = DB::transaction(function () use ($request, $user, $contactIds, $bookIds) {
            $mediaPath = null;
            $type = 'text';

            if ($request->hasFile('media')) {
                $file = $request->file('media');
                // Sanitize filename: replace spaces and special chars with underscores
                // so the resulting URL never has characters that break HTTP downloads.
                $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
                $filename = time() . '_' . $safeName;
                $mediaPath = $file->storeAs('campaigns', $filename, 'public');

                $mime = $file->getMimeType();
                if (str_starts_with($mime, 'image/')) $type = 'image';
                elseif (str_starts_with($mime, 'video/')) $type = 'video';
                elseif (str_starts_with($mime, 'audio/')) $type = 'audio';
                else $type = 'document';
            }

            $campaign = Campaign::create([
                'user_id' => $user->id,
                'whatsapp_number_id' => $request->whatsapp_number_id,
                'name' => $request->name,
                'message_type' => $type,
                'body' => $request->body,
                'media_path' => $mediaPath,
                'scheduled_at' => $request->scheduled_at,
                'status' => $request->scheduled_at ? 'scheduled' : 'pending',
                'is_drip' => $request->is_drip ?? false,
                'drip_delay_minutes' => $request->is_drip ? ($request->drip_delay_minutes ?? 1) : null,
            ]);

            // Resolve contacts from books
            $bookContactIds = collect();
            if ($bookIds->isNotEmpty()) {
                $bookContactIds = ContactBook::where('user_id', $user->id)
                    ->whereIn('id', $bookIds)
                    ->with('contacts')
                    ->get()
                    ->pluck('contacts')
                    ->flatten()
                    ->pluck('id');
            }

            // Merge and deduplicate contact IDs
            $allContactIds = $contactIds->merge($bookContactIds)->unique()->values();

            // Send to selected contacts
            $contacts = Contact::whereIn('id', $allContactIds)
                ->where('user_id', $user->id)
                ->get();

            $messages = [];
            foreach ($contacts as $contact) {
                $messages[] = [
                    'campaign_id' => $campaign->id,
                    'contact_id' => $contact->id,
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (!empty($messages)) {
                CampaignMessage::insert($messages);
            }

            return $campaign;
        });

        // Dispatch after transaction commits to ensure campaign data is available
        if (!$request->scheduled_at) {
            ProcessCampaignJob::dispatch($campaign);
        }

        return redirect()->route('campaigns.index')->with('success', 'Campaign created successfully.');
    }
    
    public function edit($id)
    {
        $user = auth()->user();
        $campaign = Campaign::where('user_id', $user->id)->findOrFail($id);
        $contacts = Contact::where('user_id', $user->id)->get();
        $whatsappNumbers = $user->whatsappNumbers()->where('status', 'connected')->get();
        $selectedContactIds = CampaignMessage::where('campaign_id', $id)->pluck('contact_id');
        $contactBooks = ContactBook::where('user_id', $user->id)->withCount('contacts')->get();

        return Inertia::render('Campaigns/Create', [
            'campaign' => $campaign,
            'contacts' => $contacts,
            'whatsappNumbers' => $whatsappNumbers,
            'selectedContactIds' => $selectedContactIds,
            'contactBooks' => $contactBooks,
            'isEditing' => true,
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = auth()->user();
        $campaign = Campaign::where('user_id', $user->id)->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'whatsapp_number_id' => 'required|exists:whatsapp_numbers,id',
            'body' => 'nullable|string',
            'media' => 'nullable|file|max:20480',
            'scheduled_at' => 'nullable|date|after:now',
            'contact_ids' => 'nullable|array',
            'contact_ids.*' => 'exists:contacts,id',
            'contact_book_ids' => 'nullable|array',
            'contact_book_ids.*' => 'exists:contact_books,id',
        ]);

        $contactIds = collect($request->contact_ids ?? []);
        $bookIds = collect($request->contact_book_ids ?? []);

        if ($contactIds->isEmpty() && $bookIds->isEmpty()) {
            return back()->withErrors(['contact_ids' => 'Please select at least one contact or contact book.']);
        }

        DB::transaction(function () use ($request, $campaign, $user, $contactIds, $bookIds) {
            $mediaPath = $campaign->media_path;
            $type = $campaign->message_type;

            if ($request->hasFile('media')) {
                $file = $request->file('media');
                $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
                $filename = time() . '_' . $safeName;
                $mediaPath = $file->storeAs('campaigns', $filename, 'public');

                $mime = $file->getMimeType();
                if (str_starts_with($mime, 'image/')) $type = 'image';
                elseif (str_starts_with($mime, 'video/')) $type = 'video';
                elseif (str_starts_with($mime, 'audio/')) $type = 'audio';
                else $type = 'document';
            }

            $campaign->update([
                'name' => $request->name,
                'whatsapp_number_id' => $request->whatsapp_number_id,
                'message_type' => $type,
                'body' => $request->body,
                'media_path' => $mediaPath,
                'scheduled_at' => $request->scheduled_at,
            ]);

            // For simplicity, we recreate messages only if campaign is still pending/scheduled
            if (in_array($campaign->status, ['pending', 'scheduled'])) {
                CampaignMessage::where('campaign_id', $campaign->id)->delete();

                // Resolve contacts from books
                $bookContactIds = collect();
                if ($bookIds->isNotEmpty()) {
                    $bookContactIds = ContactBook::where('user_id', $user->id)
                        ->whereIn('id', $bookIds)
                        ->with('contacts')
                        ->get()
                        ->pluck('contacts')
                        ->flatten()
                        ->pluck('id');
                }

                $allContactIds = $contactIds->merge($bookContactIds)->unique()->values();

                $contacts = Contact::whereIn('id', $allContactIds)
                    ->where('user_id', $user->id)
                    ->get();

                $messages = [];
                foreach ($contacts as $contact) {
                    $messages[] = [
                        'campaign_id' => $campaign->id,
                        'contact_id' => $contact->id,
                        'status' => 'pending',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if (!empty($messages)) {
                    CampaignMessage::insert($messages);
                }
            }
        });

        return redirect()->route('campaigns.index')->with('success', 'Campaign updated successfully.');
    }

    public function stop($id)
    {
        $user = auth()->user();
        $campaign = Campaign::where('user_id', $user->id)->findOrFail($id);
        
        $campaign->update(['status' => 'cancelled']);
        
        // Also cancel pending messages
        CampaignMessage::where('campaign_id', $id)
            ->where('status', 'pending')
            ->update(['status' => 'cancelled']);

        return back()->with('success', 'Campaign stopped.');
    }

    public function destroy($id)
    {
        $user = auth()->user();
        Campaign::where('user_id', $user->id)->findOrFail($id)->delete();
        return back()->with('success', 'Campaign deleted.');
    }
}
