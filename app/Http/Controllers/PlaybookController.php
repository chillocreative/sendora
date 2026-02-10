<?php

namespace App\Http\Controllers;

use App\Models\Playbook;
use App\Models\PlaybookVersion;
use App\Services\PlaybookSanitizer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PlaybookController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        return Inertia::render('Playbooks/Index', [
            'playbooks' => Playbook::where('user_id', $user->id)
                ->withCount(['whatsappNumbers', 'versions'])
                ->latest()
                ->paginate(10),
        ]);
    }

    public function create()
    {
        return Inertia::render('Playbooks/Create', [
            'defaultTemplate' => $this->getDefaultTemplate(),
            'whatsappNumbers' => auth()->user()->whatsappNumbers()
                ->select('id', 'phone_number', 'status', 'playbook_id', 'ai_reply_enabled')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string|min:50',
            'model' => 'nullable|string|in:gpt-4o,gpt-4o-mini,gpt-3.5-turbo',
            'temperature' => 'nullable|numeric|min:0|max:2',
            'max_tokens' => 'nullable|integer|min:100|max:2000',
        ]);

        $content = PlaybookSanitizer::sanitize($request->content);

        if (strlen($content) < 50) {
            return back()->withErrors(['content' => 'Content must be at least 50 characters after processing.']);
        }

        $playbook = Playbook::create([
            'user_id' => auth()->id(),
            'name' => $request->name,
            'content' => $content,
            'model' => $request->model ?? 'gpt-4o',
            'temperature' => $request->temperature ?? 0.7,
            'max_tokens' => $request->max_tokens ?? 500,
            'is_active' => true,
        ]);

        PlaybookVersion::create([
            'playbook_id' => $playbook->id,
            'version_number' => 1,
            'content' => $content,
            'change_summary' => 'Initial version',
            'source' => 'manual',
            'created_at' => now(),
        ]);

        return redirect()->route('playbooks.index')->with('success', 'Playbook created.');
    }

    public function edit($id)
    {
        $playbook = Playbook::where('user_id', auth()->id())->findOrFail($id);

        return Inertia::render('Playbooks/Edit', [
            'playbook' => $playbook,
            'versionCount' => $playbook->versions()->count(),
            'whatsappNumbers' => auth()->user()->whatsappNumbers()
                ->select('id', 'phone_number', 'status', 'playbook_id', 'ai_reply_enabled')
                ->get(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $playbook = Playbook::where('user_id', auth()->id())->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string|min:50',
            'model' => 'nullable|string|in:gpt-4o,gpt-4o-mini,gpt-3.5-turbo',
            'temperature' => 'nullable|numeric|min:0|max:2',
            'max_tokens' => 'nullable|integer|min:100|max:2000',
            'is_active' => 'boolean',
        ]);

        $content = PlaybookSanitizer::sanitize($request->content);

        if (strlen($content) < 50) {
            return back()->withErrors(['content' => 'Content must be at least 50 characters after processing.']);
        }

        $contentChanged = $playbook->content !== $content;

        $playbook->update([
            'name' => $request->name,
            'content' => $content,
            'model' => $request->model ?? $playbook->model,
            'temperature' => $request->temperature ?? $playbook->temperature,
            'max_tokens' => $request->max_tokens ?? $playbook->max_tokens,
            'is_active' => $request->is_active ?? $playbook->is_active,
        ]);

        if ($contentChanged) {
            $latestVersion = $playbook->versions()->max('version_number') ?? 0;
            PlaybookVersion::create([
                'playbook_id' => $playbook->id,
                'version_number' => $latestVersion + 1,
                'content' => $content,
                'change_summary' => 'Updated via editor',
                'source' => 'manual',
                'created_at' => now(),
            ]);
        }

        return back()->with('success', 'Playbook updated.');
    }

    public function destroy($id)
    {
        $playbook = Playbook::where('user_id', auth()->id())->findOrFail($id);

        $playbook->whatsappNumbers()->update(['playbook_id' => null, 'ai_reply_enabled' => false]);
        $playbook->delete();

        return back()->with('success', 'Playbook deleted.');
    }

    public function assignToNumber(Request $request)
    {
        $request->validate([
            'whatsapp_number_id' => 'required|exists:whatsapp_numbers,id',
            'playbook_id' => 'nullable|exists:playbooks,id',
            'ai_reply_enabled' => 'required|boolean',
        ]);

        $number = auth()->user()->whatsappNumbers()->findOrFail($request->whatsapp_number_id);

        if ($request->playbook_id) {
            Playbook::where('user_id', auth()->id())->findOrFail($request->playbook_id);
        }

        $number->update([
            'playbook_id' => $request->playbook_id,
            'ai_reply_enabled' => $request->ai_reply_enabled,
        ]);

        return back()->with('success', 'AI reply settings updated.');
    }

    public function versions($id)
    {
        $playbook = Playbook::where('user_id', auth()->id())->findOrFail($id);

        return response()->json([
            'versions' => $playbook->versions()
                ->select('id', 'version_number', 'change_summary', 'source', 'created_at')
                ->get(),
        ]);
    }

    public function showVersion($id, $versionId)
    {
        $playbook = Playbook::where('user_id', auth()->id())->findOrFail($id);
        $version = $playbook->versions()->findOrFail($versionId);

        return response()->json([
            'version' => $version,
        ]);
    }

    public function restoreVersion($id, $versionId)
    {
        $playbook = Playbook::where('user_id', auth()->id())->findOrFail($id);
        $version = $playbook->versions()->findOrFail($versionId);

        $playbook->update(['content' => $version->content]);

        $latestVersion = $playbook->versions()->max('version_number') ?? 0;
        PlaybookVersion::create([
            'playbook_id' => $playbook->id,
            'version_number' => $latestVersion + 1,
            'content' => $version->content,
            'change_summary' => "Restored from version {$version->version_number}",
            'source' => 'restore',
            'created_at' => now(),
        ]);

        return back()->with('success', "Restored to version {$version->version_number}.");
    }

    protected function getDefaultTemplate(): string
    {
        return <<<'MD'
# Persona
You are [Name], a friendly customer service agent for [Company Name].

# Tone & Style
- Warm, professional, and concise
- Use short WhatsApp-style messages (1-3 sentences max)
- Match the customer's language when possible

# Knowledge Base
## Products/Services
- [List your products and services here]
- [Include pricing if applicable]

## Business Hours
- Monday to Friday: 9am - 6pm
- Saturday: 9am - 1pm
- Sunday: Closed

## FAQs
- Q: [Common question 1]
  A: [Answer]
- Q: [Common question 2]
  A: [Answer]

# Goals
1. Answer customer inquiries accurately
2. Guide customers toward making a purchase
3. Collect customer contact details when appropriate

# Escalation Rules
Escalate to a human when:
- Customer requests to speak to a human
- Complaint involves a refund or legal matter
- Technical issue you cannot resolve
- Customer appears angry after 3 exchanges

# Forbidden Actions
- Never promise discounts not listed above
- Never share internal company information
- Never make up product features or specifications
- Never discuss competitors
MD;
    }
}
